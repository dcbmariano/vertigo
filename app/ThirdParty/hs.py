"""
hs.py | Structure Search - Match ModelAngelo chain fragments against reference
Protein/RNA FASTA files.

Reads a 3D structure (PDB or mmCIF) produced by ModelAngelo (or a similar tool),
extracts the sequence of every chain fragment, builds a single-sequence HMM for
each fragment with pyhmmer, and searches it against the reference protein and RNA
FASTA files. Replaces the previous workflow based on thousands of individual .hmm
profile files.

Author: Rafael Eduardo Oliveira Rocha / Diego Mariano
Created: 2026-05-18
Last Modified: 2026-07-22
Version: 3.0
License: MIT
"""

__author__ = "Rafael Eduardo Oliveira Rocha / Diego Mariano"
__version__ = "3.0"
__status__ = "Development"

import argparse
import os
import sys

import gemmi
import pyhmmer

# Fragments shorter than this are ignored (too short to align meaningfully).
MIN_FRAGMENT_LENGTH = 10


def parse_arguments():
    """Parse and return command-line arguments."""
    parser = argparse.ArgumentParser(
        description="Search structure chain fragments against target FASTA files."
    )
    parser.add_argument(
        "-o", "--output",
        required=True,
        help="Working directory/output directory"
    )
    parser.add_argument(
        "-s", "--structure",
        required=True,
        help="Input structure file (PDB or mmCIF) with the chain fragments"
    )
    parser.add_argument(
        "-p", "--prot",
        nargs="+",
        required=True,
        help="Reference protein FASTA files (one or more)"
    )
    parser.add_argument(
        "-r", "--rna",
        nargs="*",
        default=[],
        help="Reference RNA FASTA files (optional, one or more)"
    )
    parser.add_argument(
        "-q", "--quiet",
        action="store_true",
        help="Run the software without displaying log messages."
    )
    parser.add_argument(
        "--high-sensitivity",
        action="store_true",
        help=(
            "Relax the search so weak/short fragments also report their best "
            "match. Recovers more sequences at the cost of possible false "
            "positives (filter them using the Identity/Coverage columns)."
        )
    )
    return parser.parse_args()


# pyhmmer pipeline settings. High-sensitivity loosens the MSV/Viterbi/Forward
# filters and E-value cutoffs so a fragment's best match is reported even when
# it is weak (single-sequence HMMs score low for short fragments). Only the top
# hit per fragment is used, so the extra low-scoring hits add no noise beyond
# that best match.
HIGH_SENSITIVITY_PIPELINE = dict(
    E=100, incE=100, bias_filter=False, F1=0.1, F2=0.05, F3=0.01
)


def load_structure_chains(structure_path):
    """
    Read a PDB/mmCIF structure and extract the one-letter sequence of every chain.

    The polymer type of each chain is inferred from its residues (amino acids vs.
    nucleic acids), so the function works on raw ModelAngelo output that only
    contains atom records and no entity/annotation metadata.

    Args:
        structure_path (str): Path to a PDB or mmCIF file.

    Returns:
        list: A list of dicts with keys 'name', 'seq' and 'type' ('protein'/'rna').
    """
    structure = gemmi.read_structure(structure_path)
    if len(structure) == 0:
        return []

    model = structure[0]
    chains = []

    for chain in model:
        residues = []
        amino_count = 0
        nucleic_count = 0

        for residue in chain:
            info = gemmi.find_tabulated_residue(residue.name)
            if info is not None and info.is_amino_acid():
                amino_count += 1
            elif info is not None and info.is_nucleic_acid():
                nucleic_count += 1
            residues.append(info)

        if amino_count == 0 and nucleic_count == 0:
            continue

        chain_type = 'rna' if nucleic_count > amino_count else 'protein'
        unknown = 'N' if chain_type == 'rna' else 'X'

        letters = []
        for info in residues:
            code = info.one_letter_code.upper() if info is not None else ''
            letters.append(code if code and code.isalpha() else unknown)

        chains.append({
            'name': chain.name,
            'seq': ''.join(letters),
            'type': chain_type,
        })

    return chains


def load_sequence_blocks(file_paths, alphabet):
    """
    Load multiple FASTA files into digital sequence blocks.

    Args:
        file_paths (list): List of paths to FASTA files.
        alphabet (pyhmmer.easel.Alphabet): The biological alphabet (amino or rna).

    Returns:
        list: A list containing pyhmmer.easel.DigitalSequenceBlock objects.
    """
    blocks = []
    for path in file_paths:
        with pyhmmer.easel.SequenceFile(path, digital=True, alphabet=alphabet) as seq_file:
            blocks.append(seq_file.read_block())
    return blocks


def build_fragment_hmm(name, sequence, alphabet):
    """
    Build a single-sequence HMM from a chain fragment sequence.

    Args:
        name (str): Fragment/chain name (used as the HMM name).
        sequence (str): One-letter sequence of the fragment.
        alphabet (pyhmmer.easel.Alphabet): The biological alphabet (amino or rna).

    Returns:
        pyhmmer.plan7.HMM: The built HMM, or None if it could not be built.
    """
    try:
        text_seq = pyhmmer.easel.TextSequence(
            name=name.encode(), sequence=sequence
        )
        digital_seq = text_seq.digitize(alphabet)
        builder = pyhmmer.plan7.Builder(alphabet)
        background = pyhmmer.plan7.Background(alphabet)
        hmm, _, _ = builder.build(digital_seq, background)
        return hmm
    except Exception:
        return None


def get_target_seq_by_name(seq_name, block_list):
    """
    Recursively search for a sequence by its name across a list of sequence blocks.

    Args:
        seq_name (bytes/str): The name of the target sequence.
        block_list (list): List of DigitalSequenceBlock objects.

    Returns:
        pyhmmer.easel.DigitalSequence: The matching sequence object, or None if not found.
    """
    for block in block_list:
        for seq in block:
            if seq.name == seq_name:
                return seq
    return None


def compute_alignment_metrics(alignment, fragment_length):
    """
    Compute identity, positives and coverage (as percentages) for a single
    hit alignment, matching what is displayed in each alignment block.

    Args:
        alignment: pyhmmer alignment object (hit.domains[0].alignment).
        fragment_length (int): Total length of the model fragment (query chain).

    Returns:
        tuple: (identity, positives, coverage) rounded to one decimal place.
    """
    # The identity/match line uses letters for identical positions, '+' for
    # conservative (positive) substitutions and spaces for mismatches.
    ident = alignment.identity_sequence.replace('^', '')
    hmm_seq = alignment.hmm_sequence.replace('^', '')

    columns = len(ident) or 1
    identical = sum(1 for c in ident if c.isalpha())
    positive = identical + ident.count('+')

    # Coverage: how much of the model fragment took part in the alignment.
    aligned_residues = sum(1 for c in hmm_seq if c.isalpha())
    fragment_length = fragment_length or 1

    identity = round(100 * identical / columns, 1)
    positives = round(100 * positive / columns, 1)
    coverage = round(100 * aligned_residues / fragment_length, 1)

    return identity, positives, coverage


def generate_sequence_numbering(end_position):
    """
    Create a formatted index header string for alignment reporting (e.g., 1, 10, 20...).

    Args:
        end_position (int): Total length of the sequence.

    Returns:
        str: Formatted numbering string.
    """
    num = 10
    num_str = "{:<9}".format("1")
    while num <= end_position:
        num_str += "{:<10}".format(str(num))
        num += 10
    return num_str


def run_sequence_search(hmm, sequence_blocks, pipeline_kwargs=None):
    """
    Align a fragment (represented as a single-sequence model) against the
    reference sequence blocks and return the best-matching block's hits.

    Args:
        hmm (pyhmmer.plan7.HMM): Model built from the fragment's own sequence.
        sequence_blocks (list): List of DigitalSequenceBlock targets.
        pipeline_kwargs (dict): Extra pyhmmer Pipeline options (e.g. relaxed
            thresholds for high-sensitivity mode).

    Returns:
        tuple: (file_index, hits) if hits are found, otherwise (None, None).
    """
    pipeline = pyhmmer.plan7.Pipeline(hmm.alphabet, **(pipeline_kwargs or {}))
    for idx, block in enumerate(sequence_blocks, start=1):
        try:
            hits = pipeline.search_hmm(hmm, block)
            if len(hits) > 0:
                return idx, hits
        except Exception:
            continue
    return None, None


def validate_input_paths(structure_path, protein_files, rna_files):
    """
    Verify if all provided files exist on the system.
    Terminates the program if any input path is invalid.

    Args:
        structure_path (str): Path to the input structure file.
        protein_files (list): List of paths to protein FASTA files.
        rna_files (list): List of paths to RNA FASTA files.
    """
    # 1. Check structure file
    if not os.path.isfile(structure_path):
        sys.exit(f"Error: Structure file '{structure_path}' does not exist.")

    # 2. Check all protein files
    for path in protein_files:
        if not os.path.isfile(path):
            sys.exit(f"Error: Protein FASTA file '{path}' does not exist.")

    # 3. Check all rna files
    for path in rna_files:
        if not os.path.isfile(path):
            sys.exit(f"Error: RNA FASTA file '{path}' does not exist.")


def write_fragments_fasta(chains, output_folder):
    """
    Write every fragment sequence to a FASTA file (consumed by the alignment editor
    so the user can re-insert fragment sequences manually). Kept as 'hmm.fasta' for
    backward compatibility with the front-end.
    """
    fasta_path = os.path.join(output_folder, 'hmm.fasta')
    with open(fasta_path, 'w') as fasta:
        for chain in chains:
            if not chain['seq']:
                continue
            fasta.write(f">{chain['name']}\n")
            seq = chain['seq']
            for i in range(0, len(seq), 80):
                fasta.write(seq[i:i + 80] + "\n")


def main():
    args = parse_arguments()
    quiet = args.quiet

    # Relaxed pipeline settings when high sensitivity is requested.
    pipeline_kwargs = HIGH_SENSITIVITY_PIPELINE if args.high_sensitivity else {}

    if not quiet:
        mode = "high-sensitivity" if args.high_sensitivity else "default"
        print(f"Running structure search ({mode})...")

    # Validate all input paths before doing any heavy processing or creating folders
    validate_input_paths(args.structure, args.prot, args.rna)

    # Normalize paths using standard library tools
    output_folder = os.path.join(args.output, "")

    # Ensure output directories exist
    alignments_dir = os.path.join(output_folder, "alignments")
    os.makedirs(alignments_dir, exist_ok=True)

    # Load sequence data dynamically
    amino_alpha = pyhmmer.easel.Alphabet.amino()
    rna_alpha = pyhmmer.easel.Alphabet.rna()

    protein_blocks = load_sequence_blocks(args.prot, amino_alpha)
    rna_blocks = load_sequence_blocks(args.rna, rna_alpha)

    # Extract every chain fragment from the input structure
    chains = load_structure_chains(args.structure)
    if not quiet:
        print(f"Extracted {len(chains)} chain fragments from the structure.")

    # Persist the fragment sequences for the alignment editor
    write_fragments_fasta(chains, output_folder)

    align_hits = {}

    # Process each chain fragment
    for chain in chains:
        chain_name = chain['name']
        chain_seq = chain['seq']
        chain_type = chain['type']

        if len(chain_seq) < MIN_FRAGMENT_LENGTH:
            continue

        if chain_type == 'protein':
            hmm = build_fragment_hmm(chain_name, chain_seq, amino_alpha)
            if hmm is None:
                continue
            idx, hits = run_sequence_search(hmm, protein_blocks, pipeline_kwargs)
            if hits:
                align_hits[chain_name] = {'type': 'protein', 'file_index': idx, 'hits': hits}
            else:
                align_hits[chain_name] = "NoHits"

        elif chain_type == 'rna':
            hmm = build_fragment_hmm(chain_name, chain_seq, rna_alpha)
            if hmm is None:
                continue
            idx, hits = run_sequence_search(hmm, rna_blocks, pipeline_kwargs)
            if hits:
                if not quiet:
                    print(hits[0].domains[0].alignment)
                align_hits[chain_name] = {'type': 'rna', 'file_index': idx, 'hits': hits}
            else:
                align_hits[chain_name] = "NoHits"

    # Length of every fragment (used to compute coverage)
    chain_len = {c['name']: len(c['seq']) for c in chains}

    # Write summary results to CSV
    csv_path = os.path.join(output_folder, 'alignmentHits.csv')
    with open(csv_path, 'w') as out_file:
        out_file.write('modelChain;SequenceHit;AlignmentScore;Identity;Positives;Coverage;fastaType\n')
        for key, data in align_hits.items():
            chain_prefix = key.split("_")[0]
            if data == "NoHits":
                out_file.write(f"{chain_prefix};-;-;-;-;-;-\n")
            else:
                hit = data['hits'][0]
                identity, positives, coverage = compute_alignment_metrics(
                    hit.domains[0].alignment, chain_len.get(key, 0)
                )
                name = hit.name.decode() if isinstance(hit.name, bytes) else hit.name
                out_file.write(
                    f"{chain_prefix};{name};{hit.score};"
                    f"{identity};{positives};{coverage};{data['type']}\n"
                )

    # Group hits by target sequence name for alignment generation
    target_hits_map = {}
    for key, data in align_hits.items():
        if data == "NoHits":
            continue

        target_name = data['hits'][0].name
        if target_name not in target_hits_map:
            # Fix: Properly maps target sequences checking either the protein or rna block lists
            blocks = protein_blocks if data['type'] == 'protein' else rna_blocks
            target_seq_obj = get_target_seq_by_name(target_name, blocks)

            if target_seq_obj is not None:
                target_hits_map[target_name] = {'seq': target_seq_obj.textize().sequence, 'hits': {}}
            else:
                continue

        ali = data['hits'][0].domains[0].alignment
        target_hits_map[target_name]['hits'][key] = {
            'hmm_from': ali.hmm_from,
            'hmm_seq': ali.hmm_sequence,
            'hmm_to': ali.hmm_to,
            'ident': ali.identity_sequence,
            'target_from': ali.target_from,
            'target_to': ali.target_to
        }

    # Write visual alignments file
    txt_path = os.path.join(output_folder, 'alignments.txt')
    with open(txt_path, 'w') as out_file_txt:
        for t_name, t_data in target_hits_map.items():
            decoded_name = t_name.decode() if isinstance(t_name, bytes) else t_name
            out_file_txt.write(f">{decoded_name}\n")
            out_file_txt.write(generate_sequence_numbering(len(t_data['seq'])) + '\n')
            out_file_txt.write(t_data['seq'] + '\n')

            # Sort fragments chronologically by their start position
            sorted_hits = sorted(t_data['hits'].items(), key=lambda x: x[1]['target_from'])
            fragment_names = []

            for h_key, h_data in sorted_hits:
                chain_prefix = h_key.split("_")[0]
                target_start = h_data['target_from'] - 1

                # Write individual alignment text files
                indiv_path = os.path.join(alignments_dir, f"{h_key}.txt")
                with open(indiv_path, 'w') as out_indiv:
                    out_indiv.write(f"{h_data['hmm_seq']}\n{h_data['ident']}\n")

                # Clean up insertion gap anchors '^' safely
                clean_ident = h_data['ident'].replace('^', '')
                clean_hmm_seq = h_data['hmm_seq'].replace('^', '')

                # Construct visual block details
                block_size = len(clean_hmm_seq)
                left_tag, right_tag = f"{chain_prefix}<-", f"->{chain_prefix}"
                num_spaces = max(0, block_size - len(left_tag) - len(right_tag))
                frag_block = f"{left_tag}{' ' * num_spaces}{right_tag}"

                num_num_spaces = max(1, block_size - len(str(h_data['hmm_from'])) - len(str(h_data['hmm_to'])))
                numbering_block = f"{h_data['hmm_from']}{' ' * num_num_spaces}{h_data['hmm_to']}"

                # Append aligned tracks padding with gaps
                out_file_txt.write(("-" * target_start + clean_ident).ljust(len(t_data['seq']), '-') + '\n')
                out_file_txt.write(("-" * target_start + clean_hmm_seq).ljust(len(t_data['seq']), '-') + '\n')
                out_file_txt.write(" " * target_start + numbering_block + '\n')
                out_file_txt.write(" " * target_start + frag_block + '\n')

                fragment_names.append(chain_prefix)

            out_file_txt.write(f"fragments chains: {','.join(fragment_names)}")
            out_file_txt.write(f"\n{'#' * len(t_data['seq'])}\n\n")

    if not quiet:
        print(f"Successfully finished. The results are available in the folder: {args.output}")


if __name__ == "__main__":
    main()

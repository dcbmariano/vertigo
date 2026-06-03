"""
hm.py | hmmSearch - Run HMM searches against target Protein and RNA FASTA files.

This script automates the process of searching biological sequences against HMM 
profiles using the pyhmmer library, processing multiple input files dynamically.

Author: Rafael Eduardo Oliveira Rocha / Diego Mariano
Created: 2026-05-18
Last Modified: 2026-05-18
Version: 2.1
License: MIT
"""

__author__ = "Rafael Eduardo Oliveira Rocha / Diego Mariano"
__version__ = "2.1"
__status__ = "Development"

import argparse
import os
import sys
import pyhmmer

# TEST ENVIRONMENT ***********************
# sys.argv = [
#     "python",
#     "-o", "output",
#     "-d", "hmm_profiles",
#     "-p", "protein.fasta", 
#     "-r", "rna.fasta", 
#     "--quiet"
# ]
# ****************************************

def parse_arguments():
    """Parse and return command-line arguments."""
    parser = argparse.ArgumentParser(
        description="Run HMM searches against target FASTA files."
    )
    parser.add_argument(
        "-o", "--output",
        required=True,
        help="Working directory/output directory"
    )
    parser.add_argument(
        "-d", "--hmm",
        required=True,
        help="Directory containing HMM files"
    )
    parser.add_argument(
        "-p", "--prot",
        nargs="+",
        required=True,
        help="Input protein FASTA files (one or more)"
    )
    parser.add_argument(
        "-r", "--rna",
        nargs="+",
        required=True,
        help="Target RNA FASTA files (one or more)"
    )
    parser.add_argument(
        "-q", "--quiet",
        action="store_true",
        help="Run the software without displaying log messages."
    )
    return parser.parse_args()


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


def run_hmm_search(hmm, sequence_blocks):
    """
    Execute HMM search pipeline against a list of sequence blocks.
    
    Args:
        hmm (pyhmmer.plan7.HMM): The HMM profile to search with.
        sequence_blocks (list): List of DigitalSequenceBlock targets.
        
    Returns:
        tuple: (file_index, hits) if hits are found, otherwise (None, None).
    """
    pipeline = pyhmmer.plan7.Pipeline(hmm.alphabet)
    for idx, block in enumerate(sequence_blocks, start=1):
        try:
            hits = pipeline.search_hmm(hmm, block)
            if len(hits) > 0:
                return idx, hits
        except Exception:
            continue
    return None, None


def validate_input_paths(hmm_dir, protein_files, rna_files):
    """
    Verify if all provided files and directories exist on the system.
    Terminates the program if any input path is invalid.
    
    Args:
        hmm_dir (str): Path to the HMM profiles directory.
        protein_files (list): List of paths to protein FASTA files.
        rna_files (list): List of paths to RNA FASTA files.
    """
    # 1. Check HMM directory
    if not os.path.isdir(hmm_dir):
        sys.exit(f"Error: HMM directory '{hmm_dir}' does not exist.")

    # 2. Check all protein files
    for path in protein_files:
        if not os.path.isfile(path):
            sys.exit(f"Error: Protein FASTA file '{path}' does not exist.")

    # 3. Check all rna files
    for path in rna_files:
        if not os.path.isfile(path):
            sys.exit(f"Error: RNA FASTA file '{path}' does not exist.")


def main():
    args = parse_arguments()
    quiet = args.quiet

    if not quiet: 
        print("Running HMM Search...")

    # Validate all input paths before doing any heavy processing or creating folders
    validate_input_paths(args.hmm, args.prot, args.rna)

    # Normalize paths using standard library tools
    output_folder = os.path.join(args.output, "")
    hmm_dir = args.hmm

    # Ensure output directories exist
    alignments_dir = os.path.join(output_folder, "alignments")
    os.makedirs(alignments_dir, exist_ok=True)

    # Load sequence data dynamically
    amino_alpha = pyhmmer.easel.Alphabet.amino()
    rna_alpha = pyhmmer.easel.Alphabet.rna()
    
    protein_blocks = load_sequence_blocks(args.prot, amino_alpha)
    rna_blocks = load_sequence_blocks(args.rna, rna_alpha)

    align_hits = {}
    hmm_files = [f for f in os.listdir(hmm_dir) if f.endswith(".hmm")]

    # Process each HMM profile
    for hmm_file_name in hmm_files:
        hmm_path = os.path.join(hmm_dir, hmm_file_name)
        hmm_key = os.path.splitext(hmm_file_name)[0]

        with pyhmmer.plan7.HMMFile(hmm_path) as hmm_file:
            hmm = hmm_file.read()
            
            if hmm.alphabet.is_amino and len(hmm.consensus) >= 10:
                idx, hits = run_hmm_search(hmm, protein_blocks)
                if hits:
                    align_hits[hmm_key] = {'type': 'protein', 'file_index': idx, 'hits': hits}
                else:
                    align_hits[hmm_key] = "NoHits"
                            
            elif hmm.alphabet.is_rna:
                idx, hits = run_hmm_search(hmm, rna_blocks)
                if hits:
                    if not quiet: 
                        print(hits[0].domains[0].alignment)
                    align_hits[hmm_key] = {'type': 'rna', 'file_index': idx, 'hits': hits}
                else:
                    align_hits[hmm_key] = "NoHits"

    # Write summary results to CSV
    csv_path = os.path.join(output_folder, 'alignmentHits.csv')
    with open(csv_path, 'w') as out_file:
        out_file.write('modelChain;NumberOfHits;SequenceHit;AlignmentScore;targetFastaNumber;fastaType\n')
        for key, data in align_hits.items():
            chain_prefix = key.split("_")[0]
            if data == "NoHits":
                out_file.write(f"{chain_prefix};-;-;-;-;-\n")
            else:
                hit = data['hits'][0]
                out_file.write(f"{chain_prefix};{len(data['hits'])};{hit.name.decode() if isinstance(hit.name, bytes) else hit.name};{hit.score};{data['file_index']};{data['type']}\n")

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
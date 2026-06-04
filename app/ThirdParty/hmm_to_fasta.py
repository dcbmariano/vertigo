#!/usr/bin/env python3

import argparse
from pathlib import Path


def get_model_name(hmm_file):
    """
    Obtém o valor do campo NAME do arquivo HMM.
    """
    with open(hmm_file, "r", encoding="utf-8", errors="ignore") as f:
        for line in f:
            if line.startswith("NAME"):
                parts = line.split()
                if len(parts) > 1:
                    return parts[1]

    return Path(hmm_file).stem


def extract_consensus_sequence(hmm_file):
    """
    Extrai a sequência consenso de um arquivo HMMER3.
    """
    consensus = []
    model_started = False

    with open(hmm_file, "r", encoding="utf-8", errors="ignore") as f:
        for line in f:
            if line.startswith("HMM"):
                model_started = True
                continue

            if not model_started:
                continue

            if line.startswith("//"):
                break

            fields = line.strip().split()
            #
            # Exemplo de linha:
            #
            # 1  ...  1 s - - -
            #
            # O aminoácido consenso está em fields[-4]
            #
            if len(fields) >= 26 and fields[0].isdigit():
                residue = fields[-4]
                if len(residue) == 1:
                    consensus.append(residue.upper())

    return "".join(consensus)


def write_fasta_entry(handle, header, sequence):
    handle.write(f">{header}\n")
    for i in range(0, len(sequence), 80):
        handle.write(sequence[i:i + 80] + "\n")


def process_hmms(input_dir, output_dir):
    hmm_files = sorted(input_dir.glob("*.hmm"))
    if not hmm_files:
        print("Nenhum arquivo .hmm encontrado.")
        return

    fasta_file = output_dir / "hmm.fasta"
    total = 0

    with open(fasta_file, "w") as fasta:
        for hmm_file in hmm_files:
            try:
                model_name = get_model_name(hmm_file)
                sequence = extract_consensus_sequence(hmm_file)
                if not sequence:
                    print(
                        f"[SKIP] {hmm_file.name}: "
                        f"sequência vazia"
                    )
                    continue

                write_fasta_entry(
                    fasta,
                    model_name,
                    sequence
                )
                total += 1
                print(
                    f"[OK] {hmm_file.name} "
                    f"({len(sequence)} aa)"
                )

            except Exception as e:
                print(
                    f"[ERROR] {hmm_file.name}: "
                    f"{str(e)}"
                )

    print("")
    print(f"Total of sequences: {total}")
    print(f"FASTA file: {fasta_file}")


def main():

    parser = argparse.ArgumentParser(
        description="Convert HMMER files into a single FASTA file."
    )

    parser.add_argument(
        "-d",
        "--directory",
        required=True,
        help="Directory containing the .hmm files"
    )

    parser.add_argument(
        "-o",
        "--output",
        required=True,
        help="Output directory"
    )

    args = parser.parse_args()

    input_dir = Path(args.directory)

    if not input_dir.exists():

        print(
            f"Erro: Input directory "
            f"not found: {input_dir}"
        )

        return

    output_dir = Path(args.output)

    output_dir.mkdir(
        parents=True,
        exist_ok=True
    )

    print(f"Input : {input_dir}")
    print(f"Output   : {output_dir}")
    print("")

    process_hmms(
        input_dir,
        output_dir
    )


if __name__ == "__main__":
    main()
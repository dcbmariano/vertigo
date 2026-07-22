<?= $this->extend('template') ?>

<?= $this->section('scripts') ?>
<?= $this->endSection() ?>

<?= $this->section('conteudo') ?>

<div class="container col-xxl-10 px-3">

    <div class="row mt-4">
        <div class="col-12">
            <span class="badge rounded-pill text-bg-light border mb-2">Cryo-EM • Protein–RNA modeling</span>
            <h1 class="mb-3">Documentation</h1>
            <p class="lead text-secondary" style="max-width: 60rem;">
                Vertigo is a workbench for finishing atomic models built from cryo-electron
                microscopy density maps. It takes a <strong>draft 3D structure</strong> and the
                <strong>sequences of the organism</strong>, works out which reference protein or RNA each
                modelled fragment belongs to, and gives you an interactive editor to correct that
                assignment and the order of the fragments before refining the model in ChimeraX.
            </p>
        </div>
    </div>

    <div class="row mt-4">

        <!-- ============ TABLE OF CONTENTS ============ -->
        <div class="col-lg-3 d-none d-lg-block">
            <nav class="doc-toc">
                <p class="text-uppercase text-muted small fw-bold mb-2" style="letter-spacing:.06em;">On this page</p>
                <a href="#challenge">1. The challenge</a>
                <a href="#idea">2. The idea</a>
                <a href="#algorithm">3. How the algorithm works</a>
                <a href="#input">4. Input</a>
                <a href="#running">5. Running a project</a>
                <a href="#output">6. Output</a>
                <a href="#features">7. Features</a>
                <a href="#chimerax">8. Finishing in ChimeraX</a>
                <hr>
                <a href="<?= base_url('/project-example') ?>">↗ Open the live example</a>
                <a href="<?= base_url('/#run') ?>">↗ Run a project</a>
            </nav>
        </div>

        <!-- ============ CONTENT ============ -->
        <div class="col-lg-9">

            <!-- ---------- 1. CHALLENGE ---------- -->
            <section id="challenge" class="doc-section mb-5">
                <h2 class="h3 mb-3">1. The challenge: building models from cryo-EM maps</h2>
                <p class="text-secondary">
                    Cryo-EM does not give you a structure directly &mdash; it gives you a
                    <strong>density map</strong>. Turning that map into an atomic model means threading
                    polypeptide and nucleotide chains through the density, residue by residue. In
                    well-resolved regions this is straightforward; in weaker regions the density becomes
                    ambiguous and the chain cannot be traced with confidence.
                </p>
                <p class="text-secondary">
                    The difficulty scales badly with the size of the complex. A
                    <strong>ribosome</strong>, for example, contains dozens of different proteins wrapped
                    around several long ribosomal RNAs. The model builder has to decide, for every piece of
                    density, not only <em>which residues</em> are there but also <em>which of the many
                    chains</em> that piece belongs to.
                </p>
                <p class="text-secondary">
                    AI tools such as <strong>ModelAngelo</strong> have made this dramatically better: they
                    read the map and propose an atomic model automatically. But the result is rarely a
                    finished structure. Because the network only builds where it is confident, the output is
                    <strong>fragmented</strong>: instead of one continuous chain per protein, you get many
                    short chain fragments separated by gaps, each with its own arbitrary chain identifier
                    and no indication of which real protein or RNA it came from.
                </p>
                <div class="doc-warn mb-3">
                    <strong>The remaining problem.</strong> You end up with hundreds of anonymous fragments.
                    To finish the model you must answer two questions for each of them:
                    <em>which chain does this fragment belong to?</em> and
                    <em>where along that chain does it sit?</em> Only then can the gaps be bridged and the
                    chains completed.
                </div>
                <p class="text-secondary mb-0">
                    Vertigo exists to answer exactly those two questions, and to let you correct the answer
                    by hand when the automatic assignment is not right.
                </p>
            </section>

            <!-- ---------- 2. IDEA ---------- -->
            <section id="idea" class="doc-section mb-5">
                <h2 class="h3 mb-3">2. The idea: align the fragments against known sequences</h2>
                <p class="text-secondary">
                    Every fragment in the draft model carries a sequence &mdash; the residues the model builder
                    read out of the density. That readout is imperfect, but it is informative enough to be
                    <strong>matched against the real sequences</strong> of the organism, which are usually
                    already known from sequencing.
                </p>
                <p class="text-secondary">
                    This turns a hard 3D problem into a familiar 1D one. For each fragment, Vertigo asks:
                    <em>which reference sequence does this fragment align to, and at which position?</em>
                </p>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100">
                            <div class="fw-semibold mb-1">The best match gives the identity</div>
                            <p class="text-secondary small mb-0">
                                The reference sequence a fragment aligns to best is the protein or RNA that
                                fragment belongs to.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100">
                            <div class="fw-semibold mb-1">The alignment position gives the order</div>
                            <p class="text-secondary small mb-0">
                                Where a fragment lands on the reference tells you its position along the
                                chain &mdash; so several fragments matching the same reference fall into their
                                correct order automatically.
                            </p>
                        </div>
                    </div>
                </div>
                <p class="text-secondary mb-0">
                    All fragments assigned to the same reference are stacked into a single
                    <strong>alignment block</strong>, laid out along that reference. Reading the block from
                    left to right gives you the order of the fragments along the real chain, and the gaps
                    between them show exactly what is missing from the model.
                </p>
            </section>

            <!-- ---------- 3. ALGORITHM ---------- -->
            <section id="algorithm" class="doc-section mb-5">
                <h2 class="h3 mb-3">3. How the algorithm works</h2>
                <p class="text-secondary">
                    The search runs server-side in Python
                    (<code>gemmi</code> for structure parsing, <code>pyhmmer</code> for the alignments).
                    These are the steps:
                </p>

                <div class="d-flex gap-3 mb-3">
                    <span class="doc-step">1</span>
                    <div>
                        <div class="fw-semibold">Read the structure and extract one sequence per chain</div>
                        <p class="text-secondary small mb-0">
                            The PDB/mmCIF file is parsed and every chain is converted to a one-letter
                            sequence. Each chain is classified as <strong>protein</strong> or
                            <strong>RNA</strong> from its own residue composition, so raw model-builder
                            output works even without entity annotations.
                        </p>
                    </div>
                </div>

                <div class="d-flex gap-3 mb-3">
                    <span class="doc-step">2</span>
                    <div>
                        <div class="fw-semibold">Discard fragments that are too short</div>
                        <p class="text-secondary small mb-0">
                            Fragments shorter than <strong>10 residues</strong> are skipped &mdash; they carry too
                            little information to be assigned reliably.
                        </p>
                    </div>
                </div>

                <div class="d-flex gap-3 mb-3">
                    <span class="doc-step">3</span>
                    <div>
                        <div class="fw-semibold">Turn each fragment into a probabilistic profile</div>
                        <p class="text-secondary small mb-0">
                            Rather than comparing raw letters, each fragment sequence is converted into a
                            profile model. This tolerates the substitutions that are inevitable when
                            residues are read from noisy density, so a fragment still matches its true
                            protein even when several positions were misread.
                        </p>
                    </div>
                </div>

                <div class="d-flex gap-3 mb-3">
                    <span class="doc-step">4</span>
                    <div>
                        <div class="fw-semibold">Search it against the reference sequences</div>
                        <p class="text-secondary small mb-0">
                            Protein fragments are searched against the protein FASTA files and RNA fragments
                            against the RNA files. The <strong>best-scoring reference</strong> is kept as the
                            fragment's assignment.
                        </p>
                    </div>
                </div>

                <div class="d-flex gap-3 mb-3">
                    <span class="doc-step">5</span>
                    <div>
                        <div class="fw-semibold">Score the alignment</div>
                        <p class="text-secondary small mb-0">
                            For each assignment Vertigo computes <strong>identity</strong> and
                            <strong>positives</strong> (over the aligned columns) and
                            <strong>coverage</strong> (how much of the fragment took part in the alignment),
                            so you can judge how trustworthy it is.
                        </p>
                    </div>
                </div>

                <div class="d-flex gap-3 mb-4">
                    <span class="doc-step">6</span>
                    <div>
                        <div class="fw-semibold">Group fragments into alignment blocks</div>
                        <p class="text-secondary small mb-0">
                            Fragments hitting the same reference are placed along it, sorted by their
                            alignment position, producing the alignment blocks you edit in the browser.
                        </p>
                    </div>
                </div>

                <div class="doc-note">
                    <strong>Sensitivity.</strong> By default only confident matches are reported. Short or
                    weak fragments may find no significant match and are left unassigned. If you would
                    rather see every fragment's best candidate &mdash; and filter them yourself using the
                    Identity and Coverage columns &mdash; enable <strong>High sensitivity</strong> when you launch
                    the project.
                </div>
            </section>

            <!-- ---------- 4. INPUT ---------- -->
            <section id="input" class="doc-section mb-5">
                <h2 class="h3 mb-3">4. Input</h2>
                <p class="text-secondary">
                    Vertigo needs two kinds of information: the <strong>draft atomic model</strong> and the
                    <strong>sequences it should be matched against</strong>.
                </p>

                <h3 class="h5 mt-4">4.1 Draft 3D structure <span class="badge text-bg-secondary align-middle">required</span></h3>
                <p class="text-secondary">
                    A <strong>PDB</strong> or <strong>mmCIF</strong> file containing the fragmented model.
                    Vertigo was designed around <strong>ModelAngelo</strong> output, but it does not depend
                    on it: any tool that produces a PDB/CIF structure works, since the only thing Vertigo
                    reads is the chains and their residues.
                </p>
                <p class="text-secondary">
                    Raw model-builder output is usually a bare atom list &mdash; that is enough:
                </p>
                <pre class="doc-code mb-2">data_1
#
loop_
_atom_site.group_PDB
_atom_site.id
_atom_site.type_symbol
_atom_site.label_atom_id
_atom_site.label_comp_id
_atom_site.label_asym_id
...
_atom_site.auth_seq_id
_atom_site.auth_asym_id
_atom_site.pdbx_PDB_model_num
ATOM 1  N N   . TRP A ? 1 ? 251.694 294.971 207.331 1 76.489365 1 A 1
ATOM 2  C CA  . TRP A ? 1 ? 250.368 295.563 207.186 1 76.489365 1 A 1
ATOM 3  C C   . TRP A ? 1 ? 249.712 295.115 205.884 1 76.489365 1 A 1
...</pre>
                <p class="text-secondary small">
                    The example dataset used throughout this page is a <em>Leishmania tarentolae</em>
                    complex whose draft model contains <strong>454 chain fragments</strong>.
                </p>

                <h3 class="h5 mt-4">4.2 Protein sequences <span class="badge text-bg-secondary align-middle">required</span></h3>
                <p class="text-secondary">
                    One or more FASTA files. You do <strong>not</strong> need to know in advance which
                    proteins are in your complex &mdash; you can upload a <strong>whole proteome</strong> and let
                    Vertigo find the relevant entries. In the example dataset the search runs against
                    <strong>8,388</strong> protein sequences.
                </p>
                <pre class="doc-code mb-3">&gt;sp|Q8WQX6|TUT1_LEITA Terminal uridylyltransferase 1 OS=Leishmania tarentolae GN=KRET1
MSKYSLLFNQGTKDGTNASSGSEANSANITSSSAPASSTNTSSPTSSESAVVSPPASTSP
RRRLIHRRHGSAGAAEVAPLSLPKRPQQPNEEKHENFISDSVHHCSNRGASGSELKALTT
SGSETVMSASPDIAFEAPSPPTASASPPLESTSAVESDGDVVIDDMMRYQEGDSGGSRSA
&gt;tr|A0A640KDE4|A0A640KDE4_LEITA Serine hydroxymethyltransferase OS=Leishmania tarentolae
MSFFDSPLHSLDPEVFDLIAKEQERQCHGIELIASENFTSKAVMEAVGSCLTNKYSEGYP
...</pre>

                <h3 class="h5 mt-4">4.3 RNA sequences <span class="badge text-bg-light border align-middle">optional</span></h3>
                <p class="text-secondary">
                    FASTA files with the RNA sequences of the complex &mdash; typically derived from
                    <strong>RNA-seq</strong>. Only needed if your model contains nucleic acid chains
                    (ribosomes, spliceosomes, ribonucleoprotein complexes).
                </p>
                <pre class="doc-code mb-3">&gt;LSUA-LSU
UUAGUAUGAUCUCAAAGUACCAGAAGCCAUGUGAUCUAUAUAGUAUAACGGGACAUUAGACCGAACCUGC
GAUAGAUAAAUAUAUCUUGGAUGAUUGUAUAUUAGCGGCUAAAUGUCAAUCAAACAUGCGAAUUUUAGGU
&gt;RNA2-LSU
GGUUAAGCGACUAAGCGUACACGGUGGAUGCCCUGGCAGUCAGAGGCGAUGAAGGACGUGCUAAUCUGCG
...</pre>

                <div class="doc-note mb-4">
                    <strong>You do not need to curate the input.</strong> Vertigo scans the sequence sets and
                    selects, on its own, the entries that the modelled fragments actually match. A complete
                    proteome plus a full RNA-seq set is a perfectly valid input.
                </div>

                <figure class="doc-figure mb-2">
                    <img src="<?= base_url('/img/doc/input_form.png') ?>" alt="Vertigo upload form with protein, RNA and structure dropzones">
                    <figcaption>
                        The upload form: protein FASTA, RNA FASTA (optional) and the PDB/CIF structure.
                        The <em>High sensitivity</em> option is described in section 3.
                    </figcaption>
                </figure>
            </section>

            <!-- ---------- 5. RUNNING ---------- -->
            <section id="running" class="doc-section mb-5">
                <h2 class="h3 mb-3">5. Running a project</h2>
                <ol class="text-secondary">
                    <li class="mb-2">Go to <a href="<?= base_url('/#run') ?>">Run a Project</a> and drop in your
                        protein FASTA, your RNA FASTA (if you have one) and the structure file.</li>
                    <li class="mb-2">Optionally tick <strong>High sensitivity</strong> to also surface weak
                        candidate matches.</li>
                    <li class="mb-2">Press <strong>Launch Project</strong>. You are redirected to a project page
                        with a unique ID &mdash; <strong>save that link</strong>, it is how you return to your results.</li>
                    <li class="mb-2">The search runs in the background and the page refreshes automatically.
                        Large proteomes with hundreds of fragments take a few minutes.</li>
                    <li>When it finishes, the results page appears: the hits table, the 3D viewer and the
                        alignment editor.</li>
                </ol>
            </section>

            <!-- ---------- 6. OUTPUT ---------- -->
            <section id="output" class="doc-section mb-5">
                <h2 class="h3 mb-3">6. Output</h2>
                <p class="text-secondary">
                    Everything below is live on the
                    <a href="<?= base_url('/project-example') ?>"><strong>example page</strong></a> &mdash; open it
                    in another tab and follow along.
                </p>

                <h3 class="h5 mt-4">6.1 The hits table and the 3D viewer</h3>
                <figure class="doc-figure mb-3">
                    <img src="<?= base_url('/img/doc/results_table.png') ?>" alt="Vertigo results: hits table on the left and 3D structure viewer on the right">
                    <figcaption>Results page: assignments on the left, the draft model on the right.</figcaption>
                </figure>

                <p class="text-secondary">Each row is one modelled fragment that received an assignment:</p>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered small align-middle">
                        <thead class="table-light">
                            <tr><th style="width:130px;">Column</th><th>Meaning</th></tr>
                        </thead>
                        <tbody>
                            <tr><td><strong>#</strong></td><td>Alignment block this fragment belongs to. Fragments sharing a number belong to the same reference chain.</td></tr>
                            <tr><td><strong>Chain</strong></td><td>The fragment's chain identifier in the structure file. Click it to see its sequence.</td></tr>
                            <tr><td><strong>Reference</strong></td><td>The protein/RNA it was assigned to. Click to jump to its alignment block.</td></tr>
                            <tr><td><strong>Score</strong></td><td>Alignment bit score &mdash; the higher, the more confident the assignment.</td></tr>
                            <tr><td><strong>Identity</strong></td><td>Percentage of aligned positions that are identical.</td></tr>
                            <tr><td><strong>Positives</strong></td><td>Identical plus chemically similar positions.</td></tr>
                            <tr><td><strong>Coverage</strong></td><td>How much of the fragment took part in the alignment.</td></tr>
                            <tr><td><strong>Type</strong></td><td><code>protein</code> or <code>rna</code>.</td></tr>
                            <tr><td><strong>View</strong></td><td>Highlights this chain in the 3D viewer.</td></tr>
                        </tbody>
                    </table>
                </div>

                <p class="text-secondary">
                    The viewer shows the draft model. Clicking the <i class="bi bi-eye"></i> button on a row
                    highlights that fragment in green, labels it and zooms to it; the neighbouring chains are
                    labelled discreetly in grey. Clicking any atom reveals its residue as
                    <code>chain: residue+number</code> (for example <code>A: F1</code>).
                </p>

                <h3 class="h5 mt-4">6.2 The neighbour graph</h3>
                <p class="text-secondary">
                    Knowing which chains sit next to a fragment's <em>ends</em> is a strong hint about what
                    comes before and after it. The <strong>Graph</strong> button replaces the 3D view with a
                    map of the chains within 5 &Aring; of the fragment's first and last residue:
                </p>
                <div class="border rounded p-2 mb-2" style="background: var(--bg);">
                    <div style="height:340px;">
                        <svg viewBox="0 0 400 400" width="100%" height="100%" preserveAspectRatio="xMidYMid meet" style="font-family:Inter,sans-serif;"><line x1="200" y1="200" x2="200" y2="36" stroke="#cbd5e1" stroke-width="2"></line><line x1="200" y1="200" x2="328.2" y2="97.7" stroke="#cbd5e1" stroke-width="2"></line><line x1="200" y1="200" x2="359.9" y2="236.5" stroke="#cbd5e1" stroke-width="2"></line><line x1="200" y1="200" x2="271.2" y2="347.8" stroke="#cbd5e1" stroke-width="2"></line><line x1="200" y1="200" x2="128.8" y2="347.8" stroke="#cbd5e1" stroke-width="2"></line><line x1="200" y1="200" x2="40.1" y2="236.5" stroke="#cbd5e1" stroke-width="2"></line><line x1="200" y1="200" x2="71.8" y2="97.7" stroke="#cbd5e1" stroke-width="2"></line><g><circle cx="200" cy="36" r="22" fill="#6b7280" stroke="#94a3b8" stroke-width="1"></circle><text x="200" y="36" text-anchor="middle" dominant-baseline="central" font-size="13" font-weight="600" fill="#ffffff">A0</text></g><g><circle cx="328.2" cy="97.7" r="22" fill="#d1d5db" stroke="#94a3b8" stroke-width="1"></circle><text x="328.2" y="97.7" text-anchor="middle" dominant-baseline="central" font-size="13" font-weight="600" fill="#0f172a">Es</text></g><g><circle cx="359.9" cy="236.5" r="22" fill="#6b7280" stroke="#94a3b8" stroke-width="1"></circle><text x="359.9" y="236.5" text-anchor="middle" dominant-baseline="central" font-size="13" font-weight="600" fill="#ffffff">Ev</text></g><g><circle cx="271.2" cy="347.8" r="22" fill="#d1d5db" stroke="#94a3b8" stroke-width="1"></circle><text x="271.2" y="347.8" text-anchor="middle" dominant-baseline="central" font-size="13" font-weight="600" fill="#0f172a">F9</text></g><g><circle cx="128.8" cy="347.8" r="22" fill="#d1d5db" stroke="#94a3b8" stroke-width="1"></circle><text x="128.8" y="347.8" text-anchor="middle" dominant-baseline="central" font-size="13" font-weight="600" fill="#0f172a">FH</text></g><g><circle cx="40.1" cy="236.5" r="22" fill="#d1d5db" stroke="#94a3b8" stroke-width="1"></circle><text x="40.1" y="236.5" text-anchor="middle" dominant-baseline="central" font-size="13" font-weight="600" fill="#0f172a">FL</text></g><g><circle cx="71.8" cy="97.7" r="22" fill="#6b7280" stroke="#94a3b8" stroke-width="1"></circle><text x="71.8" y="97.7" text-anchor="middle" dominant-baseline="central" font-size="13" font-weight="600" fill="#ffffff">FM</text></g><g><circle cx="200" cy="200" r="26" fill="#00bc9e" stroke="#94a3b8" stroke-width="1"></circle><text x="200" y="200" text-anchor="middle" dominant-baseline="central" font-size="13" font-weight="600" fill="#ffffff">O</text></g></svg>
                    </div>
                    <div class="d-flex flex-wrap justify-content-center gap-2 gap-md-3 small text-muted px-2 pb-1" style="font-size:11px;">
                        <span class="d-inline-flex align-items-center gap-1"><span style="display:inline-block;width:11px;height:11px;border-radius:50%;background:#00bc9e;border:1px solid #00bc9e;"></span>Selected chain</span>
                        <span class="d-inline-flex align-items-center gap-1"><span style="display:inline-block;width:11px;height:11px;border-radius:50%;background:#d1d5db;border:1px solid #94a3b8;"></span>Near start (res. 1)</span>
                        <span class="d-inline-flex align-items-center gap-1"><span style="display:inline-block;width:11px;height:11px;border-radius:50%;background:#6b7280;border:1px solid #94a3b8;"></span>Near end (res. 15)</span>
                        <span class="d-inline-flex align-items-center gap-1"><span style="display:inline-block;width:11px;height:11px;border-radius:50%;background:#9ca3af;border:1px solid #94a3b8;"></span>Near both ends</span>
                    </div>
                </div>
                <p class="text-secondary small">
                    Chain <code>O</code> from the example: <strong>light grey</strong> chains touch its first
                    residue, <strong>dark grey</strong> chains touch its last one. Clicking any node opens
                    that chain's sequence.
                </p>

                <h3 class="h5 mt-4">6.3 The alignment blocks</h3>
                <p class="text-secondary">
                    Below the table, each reference sequence gets a block showing the fragments aligned to it:
                </p>
                <figure class="doc-figure mb-3">
                    <img src="<?= base_url('/img/doc/alignment_block.png') ?>" alt="An alignment block with the reference sequence and an aligned fragment">
                    <figcaption>
                        An alignment block. The numbered badge matches the <strong>#</strong> column of the table.
                    </figcaption>
                </figure>
                <p class="text-secondary">Reading a block, from top to bottom:</p>
                <ul class="text-secondary">
                    <li>the <strong>reference sequence</strong> with a position ruler;</li>
                    <li>for each fragment: the <strong>match line</strong>, the <strong>fragment residues</strong>,
                        its <strong>residue numbering</strong>, and a marker such as
                        <code>O&lt;- ... -&gt;O</code> naming the chain;</li>
                    <li>a <strong>fragments chains:</strong> line summarising the chains in the block.</li>
                </ul>
                <p class="text-secondary">
                    Residues are coloured by agreement with the reference &mdash; identical, chemically similar and
                    mismatched &mdash; and the block header shows live <strong>Coverage</strong>,
                    <strong>Identity</strong> and <strong>Positives</strong> bars that update as you edit.
                </p>

                <h3 class="h5 mt-4">6.4 Downloadable files</h3>
                <p class="text-secondary">The <strong>Download</strong> menu gives you:</p>
                <p class="text-secondary mb-1"><code>alignmentHits.csv</code> &mdash; one row per assigned fragment:</p>
                <pre class="doc-code mb-3">modelChain;SequenceHit;AlignmentScore;Identity;Positives;Coverage;fastaType
O;tr|A0A640KVN9|A0A640KVN9_LEITA;8.48;57.1;85.7;93.3;protein
P;tr|A0A640KH68|A0A640KH68_LEITA;4.98;62.5;87.5;80.0;protein
S;tr|A0A640KNR8|A0A640KNR8_LEITA;5.55;53.8;84.6;86.7;protein</pre>
                <p class="text-secondary mb-1">
                    <code>alignments.txt</code> (original) and <code>alignments_edited.txt</code> (after your
                    edits) &mdash; the alignment blocks as plain text:
                </p>
                <pre class="doc-code">&gt;tr|A0A640KH68|A0A640KH68_LEITA
1        10        20        30        40        50 ...
MLSSQISAFALWSACAIVALVGAFSAAFLCLTVQQLCGHVNVMQHSAVAIT ...
-------------------------------------------------- ... acf+ +a
-------------------------------------------------- ... acfiqla
                                                        2
                                                        P&lt;-  -&gt;P
fragments chains: P
####################################################</pre>
            </section>

            <!-- ---------- 7. FEATURES ---------- -->
            <section id="features" class="doc-section mb-5">
                <h2 class="h3 mb-3">7. Features</h2>

                <h3 class="h6 text-uppercase text-muted mt-4" style="letter-spacing:.05em;">Inspecting</h3>
                <ul class="text-secondary">
                    <li><strong>Sortable, searchable table</strong> &mdash; order by block, score, identity or coverage;
                        search for a chain or a reference.</li>
                    <li><strong>3D highlight</strong> &mdash; the <i class="bi bi-eye"></i> button colours the chain,
                        labels it and zooms to it.</li>
                    <li><strong>Residue identification</strong> &mdash; click any atom to label it
                        <code>chain: residue+number</code>.</li>
                    <li><strong>Neighbour graph</strong> &mdash; chains within 5 &Aring; of the fragment's start and end,
                        shaded by which end they touch.</li>
                    <li><strong>Go to alignment</strong> &mdash; jumps from the 3D view straight to the fragment's
                        alignment block.</li>
                    <li><strong>Sequence popovers</strong> &mdash; click a chain name anywhere to see its full sequence.</li>
                </ul>

                <h3 class="h6 text-uppercase text-muted mt-4" style="letter-spacing:.05em;">Editing the alignment</h3>
                <ul class="text-secondary">
                    <li><strong>Select and nudge</strong> &mdash; select part of a sequence and use the
                        <code>&larr;</code> / <code>&rarr;</code> buttons to shift it one column at a time.</li>
                    <li><strong>Drag</strong> &mdash; grab a selection and slide it as many columns as needed within
                        its line; it moves only over gaps.</li>
                    <li><strong>Edit, Insert, Delete, Delete gap</strong> &mdash; fine-grained fixes on the selected
                        residues.</li>
                    <li><strong>Expand Sequences</strong> &mdash; lays out every fragment's complete sequence, from
                        its first residue to its last, revealing what the model builder left out.</li>
                    <li><strong>Add chain / Remove chain</strong> &mdash; put another fragment into a block, or take
                        one out, when the automatic assignment was wrong.</li>
                    <li><strong>Add / Delete alignment block</strong> &mdash; create a block for a reference that was
                        missed, or drop one you do not need.</li>
                    <li><strong>Live metrics</strong> &mdash; Identity, Positives and Coverage recompute in the table
                        and in the block headers after every edit.</li>
                    <li><strong>Save alignment</strong> &mdash; stores your edited alignment, downloadable as
                        <code>alignments_edited.txt</code>.</li>
                </ul>

                <div class="doc-note">
                    <strong>Why manual editing matters.</strong> An automatic assignment based on a sequence
                    read from noisy density is a hypothesis, not a fact. Short fragments in particular can
                    match the wrong protein by chance. Vertigo shows you the evidence &mdash; score, identity,
                    coverage, spatial neighbours &mdash; and lets you overrule it.
                </div>
            </section>

            <!-- ---------- 8. CHIMERAX ---------- -->
            <section id="chimerax" class="doc-section mb-5">
                <h2 class="h3 mb-3">8. Finishing the model in ChimeraX</h2>
                <p class="text-secondary">
                    Vertigo does not rebuild the atomic model &mdash; it produces the
                    <strong>information needed to rebuild it</strong>. Once you are happy with the alignment
                    you know, for every fragment: which chain it belongs to, where it sits along that chain,
                    what order the fragments follow, and which residues are missing between them.
                </p>
                <p class="text-secondary">
                    Exported as <code>alignments_edited.txt</code>, that assignment drives the final step in
                    a structural modelling program such as <strong>ChimeraX</strong> &mdash; for example with the
                    <strong>Kurwenal</strong> bundle &mdash; where the fragments are merged into continuous chains,
                    the gaps are bridged and the model is refined against the density.
                </p>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <a href="<?= base_url('/project-example') ?>" class="btn btn-primary">
                        <i class="bi bi-eye"></i> Explore the live example
                    </a>
                    <a href="<?= base_url('/#run') ?>" class="btn btn-outline-primary">
                        <i class="bi bi-play-circle"></i> Run your own project
                    </a>
                </div>
            </section>

        </div>
    </div>
</div>

<div class="py-5"></div>

<?= $this->endSection() ?>

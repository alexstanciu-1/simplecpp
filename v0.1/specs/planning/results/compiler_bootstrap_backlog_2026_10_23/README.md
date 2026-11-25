# First accepted-backlog bootstrap checkpoint
Doc Status: planning

HEAD 394e6edc08e184c4b74ab7df54335c809e1e5619 plus the typed empty-default slice.
Run `python3 compiler/my-try/tools/bootstrap_probe.py --results FRESH`.
Exact snapshot hashes, tracked overlay, converted input and command logs are in
`/tmp/compiler-bootstrap-backlog-01`. The harness and declaration-default changes
were uncommitted at measurement. This evidence retains measured process/heap
results; it does not claim the base revision alone reproduces the overlay.

Conversion: 4.67 s, 189.2 MiB peak RSS; 269 assembled PHS files, 1,958,956 bytes.
Bootstrap: 193.79 s, 431.5 MiB peak RSS, 422.5 MiB peak PHP heap under a 4 GiB limit.
Parsing/sync completed at 4.081 s. Preparation failed first on AST-01 nonvirtual
inherited redeclaration, with other already inventoried diagnostics aggregated.
No fatal/OOM, C++ emission or native compiler build. These are partial-run costs,
not a forecast of completed self-compilation. No concurrent build/test load ran.

# Output binding bootstrap checkpoint
Doc Status: planning

Starting revision 607891d0f plus eleven filesystem output-leaf registrations.
Full snapshot, source hashes and tracked diff: `/tmp/compiler-bootstrap-output-bindings-01`.
Run `python3 compiler/my-try/tools/bootstrap_probe.py --results FRESH`.

Conversion: 4.65 s / 194,992 KiB peak RSS (190.4 MiB).
PHP-hosted bootstrap: 191.17 s / 441,568 KiB RSS (431.2 MiB), 422.5 MiB PHP heap.
Initialization completed at 0.300 s and sync/parse at 4.012 s. Preparation still
fails first on AST-01. All eleven unresolved output-leaf diagnostic texts disappear;
the result_payload capability error is added. No fatal/OOM, emission or native
compiler rebuild. No concurrent build/test workload ran during measurement.

`/tmp/output-bindings-02` executes all eleven leaves on legacy and ten on ordinary/
prelowered v0.2. Read extraction from result<nullable<string>> remains blocked by
nullable's missing result_payload capability, explicitly reproduced in both modes.
The exact return type remains registered. Those blocker tests must become positive
execution with NULL-02; BIND-01 is not complete.

`/tmp/output-adapter-reuse-01` passes the existing PHP output adapter fixture under
an exclusive lock, including binary bytes, digest boundaries, edited-file protection
and Output_Stage alias/close behavior. This is not full converted facade admission.

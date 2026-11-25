# JSON reader factory checkpoint
Doc Status: planning

Starting revision 30c6640d9 plus the shared reader factory, fourteen method bindings
and facade allocation update. Full source snapshot, hashes and tracked diff:
`/tmp/compiler-bootstrap-json-reader-01`. Run
`python3 compiler/my-try/tools/bootstrap_probe.py --results FRESH`.

Conversion: 4.73 s / 194,804 KiB RSS (190.2 MiB), 269 PHS files / 1,956,350 bytes.
PHP-hosted bootstrap: 200.67 s / 455,508 KiB RSS (444.8 MiB), 434.5 MiB PHP heap.
Initialization: 0.302 s; sync/parse: 4.081 s. Preparation still fails first on AST-01.
The unresolved json_pull_reader type diagnostic disappears; no new diagnostic text
is added. No fatal/OOM, emission or native compiler rebuild. Measurement ran without
concurrent builds/tests. This measures preparation to a blocker, not a complete
self-bootstrap or a statistically established performance change.

`/tmp/json-reader-bindings-02` executes the complete protocol fixture on legacy and
ordinary v0.2. Concrete prelowering reproduces the existing shared runtime member
argument receiver-check sequencing blocker; this is not successful execution.
`/tmp/json-reader-facade-01` passes the PHP/native facade consumer and native reader/
document regressions. See fixture_summary.json and the execution ledger for scope.

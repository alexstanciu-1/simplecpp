# Shared reader prelowering checkpoint
Doc Status: planning

Starting revision b439b13f9 plus the opaque shared receiver check bridge and
prepared argument forwarding. Full source snapshot, hashes and tracked diff:
`/tmp/compiler-bootstrap-reader-prelower-01`. Run
`python3 compiler/my-try/tools/bootstrap_probe.py --results FRESH`.

Conversion: 4.73 s; exact RSS is in convert.time. Converted input: 269 PHS files,
1,956,705 bytes. PHP-hosted bootstrap: 199.64 s / 455,704 KiB (445.0 MiB) peak RSS,
434.5 MiB PHP heap. Initialization: 0.305 s; sync/parse: 4.074 s. No concurrent
builds/tests ran during measurement. The same diagnostic texts remain; AST-01 is
first. No fatal/OOM, emission or native compiler rebuild. Backend execution is
proved by the focused fixtures, not this preparation-blocked bootstrap.

`/tmp/json-reader-prelower-01` executes the complete reader fixture on legacy,
ordinary v0.2 and concrete prelowering. `/tmp/runtime-member-prelower-02` executes
six unfamiliar ticket-provider cases on both v0.2 modes: early null failure,
argument-side receiver replacement, generic scalar and observed shared arguments.
`/tmp/runtime-handles-receiver-01` passes the existing four preparation suites and
seven native cases. See fixture_summary.json. Reference results, variadics and
borrowed-this argument transport retain their existing exclusions.

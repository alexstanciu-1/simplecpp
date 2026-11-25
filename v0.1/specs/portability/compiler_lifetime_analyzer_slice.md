# Lifetime stage coordinator
Doc Status: supporting

Lifetime_Analyzer composes request selection, dependency-ready ownership execution,
lifetime task selection, per-body workers and the final join. Accepted ownership
inputs precede lifetime reuse decisions. No result is published until the complete
current batch is accepted. Previous lifetime and ownership sets remain unchanged.

The active rewrite uses a one-shot analyze()/Lifetime_Update boundary, matching the
existing body/template update pattern instead of copying the preserved generic
init/run/finalize Step adapter. Session scheduling and a common phase protocol remain
separate integration work. This changes orchestration representation, not analysis
order or permission to consume partial results.

Lifetime_Update contains exactly one complete set or attributed source diagnostic.
Both ownership-body failures and ordinary lifetime-worker failures preserve the
worker diagnostic and expose no result. Unsupported recursive ownership dependencies
remain explicit RuntimeException failures because they have no single source-body
attribution; the coordinator does not invent one.

The first six PHP scenarios pass: scalar and borrowed-aggregate bodies, owned record
returns, balanced raw resource flow, direct leak failure and aggregate destruction
failure. Each includes a preceding successful peer body; clean, incremental and full
runs check membership and exact reuse, while failures expose no partial set. The
expanded proof adds a recursive ownership dependency. First PHP readiness is
`/tmp/lifetime-analyzer-php-02`; expansion is `/tmp/lifetime-analyzer-php-03`. One test
expectation correction recorded the earlier aggregate destruction precondition failure
rather than the later raw-local leak message. No production correction was needed.
Native validation is not yet launched.

Run `python3 compiler/tests/lifetime_analyzer/run.py --results FRESH --target-checkout TARGET`.
Source export verification, debug serialization and whole-compiler session integration
remain separate obligations. src-runtime-preparation remains unchanged PHP.

Commit review checkpoint, 2026-10-19: the existing runner passed 7 PHP
outcomes, portability checking, conversion and unchanged-conversion reuse in
`/tmp/develop-lifetime-analyzer-20261019`. No target checkout was supplied, so this
checkpoint does not establish native execution or whole-compiler integration.

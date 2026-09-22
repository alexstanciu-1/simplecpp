# Fixed tool-service request contracts
Doc Status: supporting

This resumed portability slice prepares the existing
`compiler/src/05_generate_code/prepare_backend/data/tools.php` file:
`object_compilation` and `link_configuration`. It depends only on the already-ready
`backend_configuration` record and strings. No process launch, file reservation,
link invocation or new compiler functionality is introduced.

## Adaptation and ownership

Only the uniform managed imports are added. Removing that block reproduces the
pre-slice source byte-for-byte: names, constructors, readonly fields, comments and
empty constructor bodies remain unchanged. Bootstrap already loads the file.
The source stays in its prototype-derived owner and is added after configuration
in `compiler/portability.json`. The ready set is now twelve production files.

The existing promoted-constructor and named-field conversion paths suffice. No
converter or runtime change was needed. PHP readonly enforcement remains stronger
than the selected native target's initialization-only usage contract; this slice
does not claim native immutability enforcement.

## Behavioral evidence

The cumulative component harness now checks:

- IR bytes and an output path containing a space and UTF-8 text survive construction.
- An alias identifies the same request; separately constructed equal requests are
  distinct objects.
- The exact supplied backend configuration is shared. A separate equal-valued
  configuration retains its distinct identity.
- Empty string inputs remain empty; these transfer records add no validation or
  defaults beyond the existing owner contract.
- Link executable spelling and distinct link revision keys are preserved.

The shared validation workflow runs these expectations in PHP and strict native
v0.1.76, alongside the existing component proof, tool regressions and sixteen
retained compiler fixtures. A separate PHP check confirms request/output and link/key
readonly writes fail. Native readonly enforcement is explicitly not inferred from it.

[Recorded evidence](../planning/compiler_migration/results/tool-contracts-01/summary.json)
includes validation logs, cumulative native results and source provenance.

This is readiness of the fixed request records, not the LLVM toolchain or native
builder. Their process/lock dependencies remain separate, including issue #231.

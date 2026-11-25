# First working slice and progress gates
Doc Status: supporting

Status: the first source-to-native sample and its body-edit increment are proved.
Track subsequent capabilities in the [foundations tracker](../planning/compiler_foundations.md). The active
[PHP prototype](../../README.md) now reads, tokenizes, parses and collects
project declarations, resolves call names and produces a [native executable](native_executable.md)
for the current subset when `--output` is supplied. The retained PHP++
[skeleton](source_skeleton.md) remains the older reference.
[Declared return-type resolution](return_type_resolution.md) now follows the frontend stages.
[Callable body checking](body_checking.md) includes the
[manifest-selected entry](program_entry.md) through the common path, followed by
[scalar lifetime analysis](lifetime_analysis.md), verified
[backend preparation](backend_preparation.md), [instruction lowering](lowering.md),
LLVM emission and native publication.

## Small project, complete flow

Use three source files: one main file and two files containing one small
function each. Use explicit integer return types, literal returns, and
parameterless cross-file calls. Main calls a function in one file, which calls
one in the other. The [sample](../../examples/three_files/project.json) includes
a nested directory to exercise recursive discovery. Fixture names and
expected answers must never select compiler behavior.

Proceed in small steps: manifest and rebuild decision; file discovery and
snapshots; tokenization and parsing; project resolution; common body/lowering
and native output; then one real body-edit increment. Each step must report
what works and where the next unimplemented boundary stops it.

Every parsed file contains a list of defined entities and one implicit entry
callable's body. Top-level executable statements and any required runtime
declaration-initialization actions form its ordered list. Files with neither
have an empty body, including the function-only declaration files in this slice. Named
functions use the same block/statement representation and body processing.
The current startup policy selects the manifest entry alone, with a catalog-defined
language `int` result. Supporting files must have empty entry bodies; initialization
ordering is deferred. The native process adapter remains a separate target-owned
contract; process exit and a source return are not interchangeable assumptions.
The [initialization model](../code_organization.md#compact-storage-and-identity)
separates symbol definitions from their initialization actions; constant
declarations and their initialization are not implemented by this first sample.

Every applicable pipeline responsibility participates. A stage may do no work
when its contract requires none for the input. An unimplemented requirement
reports a blocker rather than returning fabricated success. Start with full
rebuilds through the common stage path; introduce body-edit reuse afterward.
The compiler must retain its session between updates in the resident process.

Optimization, actual threading, richer language features, rename detection,
and general incremental propagation are outside this slice. Additions of code,
functions, properties, and classes gain incremental rules individually later.

After this sample compiles, runs and demonstrates its first executable increment,
consolidate before adding language features. Make a structure/correctness review
pass, then a measured memory/performance pass if needed. Do not expand the sample
or start those optimization passes while its end-to-end compilation is unfinished.

## One path through bodies and lowering

Walk ordered statements even when a body contains only one. Recursively obtain
expression operands through the [shared value/operation path](../type_model.md#value-and-operation-lowering).
Lower into explicit values, places, effects, blocks, instructions, and terminators.
LLVM emission loops over functions, blocks, instructions, and terminators; it
must not reinterpret source shapes to recover execution decisions.

`return` is a distinct terminator. Reuse scope-exit cleanup with other transfers
only where the actual rules coincide; do not collapse return, break, and other
control flow into a generic jump with missing semantics. There is no need to
implement every transfer or ownership mode to compile the initial example.

Per-file work, the declaration join, dependency-ready resolution, and per-body
lowering use explicit unchanged inputs and separate outputs. Run these units
serially now; future workers must run the same units and joins. A passing
serial test does not prove multithreaded safety.

## Gates as behavior is introduced

| Gate | Required evidence |
|---|---|
| Source to native result | The three-file project executes through the common stages; manifest and entry policy are real contracts. |
| Project symbols | Cross-file lookup is independent of file enumeration order; duplicate and missing symbols produce diagnostics. |
| Shared execution model | A new operation/context composes with an existing one through the same body/value/lowering path; unsupported behavior stops precisely. |
| Resident updates | Successive requests retain one session; failure followed by repair never advertises a stale artifact as current. |
| First incremental category | A body edit under an unchanged callable contract changes execution through an unchanged caller; unaffected bodies are reused; results match a fresh build. |
| Fallback and removal | Signature changes/renames select the same full-rebuild loops; obsolete declarations, bindings, diagnostics, and outputs disappear; a later supported update can be selective again. |

The [incremental rules](incremental_refresh_rules.md) own eligibility and the
full-rebuild decision. These gates are introduced with their behavior; they do
not require every future backend, lifetime, or incremental category upfront.
Inspect who produces a result, what it reads/writes, and what invalidates it.
One passing example proves neither arbitrary expression support nor cleanup.

Select a relevant composition when adding behavior, rather than an exhaustive
feature/type matrix. The [old lowering investigation](resolved_to_llvm_development_slowdown.md)
explains why complete executable-body contracts matter. Reuse a verified build
for related checks and separate compiler build cost from target build cost.

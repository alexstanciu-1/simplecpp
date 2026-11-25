# Compiler authoring-shape audit
Doc Status: planning

Date: 2026-09-21. Audit only; the migration goal remains paused. No compiler,
converter or runtime behavior changes are authorized by this document.

Subsequent decision: the user approved restricted direct same-namespace traits
and a declaration index, replacing this audit's initial trait-refactor recommendation.
See the [implemented checkpoint](../../portability/traits_and_incremental_index.md).
Traits cannot use traits or adaptations; multiple direct consumers are permitted.
The original audit observations below remain the discussion record.

Subsequent array/copying decision: prefer defined structures as much as possible;
ordinary PHP arrays are for non-hot setup followed by read-only use. Preserve the
behavior the algorithm relies on through visible usage and unit tests. This is
authoring discipline, entirely outside the converter's decisions or enforcement.
See the [agreed policy](../../portability/README.md#agreed-authoring-discipline-structures-and-arrays).

Subsequent reference decision: park by-reference usage as **be careful**. The
compiler will gradually move toward owned record storage accessed by index as
owners are adapted. No blanket reference ban, immediate storage rewrite, converter
enforcement or general target reference feature is requested. See the
[recorded direction](../../portability/README.md#references-be-careful-migrate-gradually-toward-indexed-records).

## Decision boundary

Portability-ready PHP is a deliberately authored subset. The writer adapts the
prototype before conversion. Preserving algorithms, behavior, identities and
ownership does not require preserving PHP idioms. A rejected prototype expression
is not automatically a request to extend the converter.

Disposition vocabulary:

- **Rewrite**: express the same behavior with explicit portable source shapes.
- **Contract first**: choose a representation/library contract, then adapt its users.
- **Syntax gap**: locally expressible syntax missing from today's parser; not proof
  that the algorithm or data model needs rewriting.
- **Target defect/limitation**: existing selected-target evidence requires a separate
  decision; do not hide it as permanent PHP compatibility support.
- **Target enhancement candidate**: a focused Simple C++ capability can be a better
  owner than repeated source workarounds. This is an allowed option for discussion,
  not approval to implement every feature found in the prototype.

Recommendations below are proposals, not newly implemented syntax. Where a change
would cross ownership areas, agree on the design before implementation. No symbol
resolution, general PHP compatibility, native escape-hatch implementation, new
compiler functionality or whole-program rewrite is part of this audit.

## Coverage and evidence

[Inventory](results/authoring-audit-01/inventory.json) and its
[read-only scanner](results/authoring-audit-01/scan.php) cover 301 PHP files under
`compiler/src`, `compiler/src-runtime-preparation`, and `compiler/tool_process`.
Preparation test directories are excluded. External bootstrap/composition files,
test harnesses, historical code, generated artifacts and non-PHP assets are not
part of the census. Bootstrap composition is reviewed separately below.

The scanner uses PHP tokens, excluding comment/string false positives for the
counted token kinds. It fingerprints every input and converter policy, and invokes
the converter in memory without publishing output. Eleven files accept syntax;
290 fail at their first current diagnostic, often an import or declaration. This
is neither a native success rate nor a count of intrinsically nonconvertible files.
It does not enumerate every rejection within each file. Manual inspection of
representative owners supplies the dispositions below; there is no exhaustive
semantic classification of all expressions or builtin calls.

| Token shape | Files | Occurrences | Interpretation |
| --- | ---: | ---: | --- |
| Traits | 41 | 41 | Source composition needs replacement |
| Arrow functions | 43 | 69 | Prefer explicit iteration for collection callbacks |
| Clone | 8 | 12 | Review copy/alias contracts individually |
| Match | 46 | 63 | Local syntax gap; preserve or simplify intentionally |
| Finally | 14 | 21 | Cleanup behavior must survive adaptation |
| Instanceof | 55 | 164 | Polymorphic representation needs proof, not blanket removal |
| Nullsafe access | 109 | 373 | Common local syntax gap; explicit guards are an alternative |
| Ellipsis | 43 | 97 | Mixed category: unpacking and first-class callable syntax |
| Readonly | 144 | 1,236 | Selected target does not enforce PHP immutability |

Counts overlap and are not effort estimates. They supersede the earlier textual
candidate counts only for this precisely stated scope/current source snapshot.

## Shapes to adapt or resolve before authoring

All example paths below are relative to `compiler/`.

| Shape and evidence | Disposition and recommended source direction | Behavior that must be preserved |
| --- | --- | --- |
| Trait handlers sharing private `$this` state: `src/03_parse/parse_file.php:25`, `src/03_parse/handlers/expressions.php:17`; also analysis, lowering and emission handlers | **Rewrite / design decision.** Prefer explicit worker state plus named handler operations receiving that state; preserve existing process/handler ownership. Do not make the converter load and flatten trait files. First inspect one process's call graph and private invariants; the final decomposition is not decided here. | Cursor updates, recursive handler calls, append order, visibility/invariants. This is the largest recurring structural change; a blanket cross-stage refactor needs agreement. |
| PHP arrays serving as lists, maps, sets and records: `src/01_prepare_inputs/read_sources/data/store.php:25`, `src/04_analyze/analyze_lifetimes/data/ownership.php:28` | **Contract first, then annotate/rewrite.** Declare element/key types at storage and call boundaries. Keep dense lists distinct from sparse ID indexes. Replace fixed heterogeneous tuples/records with named records where useful; retain genuine external dynamic data at a checked boundary. `@var array<...>` is evidence of intent, not automatically an accepted target annotation. | Missing versus null, numeric-string key coercion, insertion order, index stability, nested mutation and equality. Do not indiscriminately replace every array with an object. |
| Snapshot `clone` with array copy-on-write and shared contained objects: `src/01_prepare_inputs/read_sources/data/store.php:100`, `src/04_analyze/analyze_lifetimes/allocation_flow.php:148`, `src/compile/compile.php:156` | **Rewrite after explicit copy contract.** Owner-specific snapshot/copy operations state which containers become independent and which rows/buffers retain identity. Generic deep-copy would be incorrect; generic shallow-copy is not an established target contract either. | Old snapshots unchanged, changed rows independent, unchanged rows shared, fixed-point iterations isolated. Test mutation and identity, not just serialized equality. |
| `array_map`, `array_filter`, callbacks and callable capture: `src/06_build_output/build_native/main_build_native.php:123`, `src/05_generate_code/prepare_backend/export_verification.php:29` | **Rewrite by default.** Typed output plus explicit iteration/direct calls; keep callback abstractions only where they express a real recurring interface worth a separate contract. No automatic discovery of captured types. | Filtering key behavior, reindexing, order, captures, evaluation count and error timing. |
| By-reference parameters and nested reference-sensitive mutations: `src/03_parse/parse_file.php:81`, `src/03_parse/handlers/expressions.php:102`, `src-runtime-preparation/requests.php:49` | **Contract first.** Simple typed local out-parameters may be supportable; do not ban them merely because today's parser rejects them. Prefer explicit cursor/state owners or returned results when callers pass nested slots or mutation becomes ambiguous. | Which caller storage changes, aliasing between inputs/outputs, failure-state mutation. Never silently replace reference updates with by-value calls. |
| Byte strings accessed through `$text[$offset]`, `substr`, `strspn`, `strcspn`, `strpos`: `src/02_tokenize/tokenize.php:63` onward | **Rewrite/library contract.** Use explicit byte operations and wrapper-aware search results. Current `string_byte_at` returns an integer, so comparisons must change intentionally, not by textual replacement. Byte slice/at exist; scan/search helpers still need bounded contracts or explicit loops. | Byte offsets, embedded NUL/invalid UTF-8 from input, EOF, malformed strings, exact diagnostics. Literal encoding restrictions remain separate from runtime binary data. |
| Named arguments omitting intervening defaults: `src/04_analyze/check_bodies/utilities/conversions.php:47` | **Rewrite by default.** Positional arguments or an explicit named factory with a clear typed signature. A converter cannot fill omitted arguments by inspecting a remote constructor under the no-resolution agreement. | Defaults, selected fields and evaluation order. Do not invent a callee-signature lookup. |
| Dynamic property selection `$this->$name`: `tool_process/process.php:117` | **Rewrite.** Explicit fixed-member cleanup calls, or a typed resource owner with named operations. The field set is already known to the author. | All resources closed once; partial construction and repeated close remain safe. |
| Implicit object serialization and JSON roundtrip normalization: `src-runtime-preparation/project/module.php:83`, `src/02_tokenize/store.php:34` | **Rewrite/library contract.** Explicit schema serialization, checked parsing and semantic comparison where intended. Shared JSON facade must define flags/errors/order; a name map alone is insufficient. | Receipt/cache formats, field presence, null/false, numeric values, ordering when observable. Keep external schema stable during migration. |
| Enum `->name` / `->value`: `src/02_tokenize/store.php:34`, `src/04_analyze/resolve_types/preparation_queue.php:50` | **Contract first.** Explicit vocabulary/name or key operations if target access is not proved. Do not resolve a receiver's enum type in the converter. Declaration-local generation could be considered separately. | Stable diagnostic labels and queue keys; emitted integer representation alone does not preserve PHP enum reflection behavior. |
| Heterogeneous class union, interface variants and `instanceof`: `src/04_analyze/collect_symbols/data/structures.php:46`, `src/04_analyze/type_model/data/type_references.php` | **Representation proof first.** Keep meaningful shared interfaces where supported. If union storage/narrowing cannot be proved, consider explicit tagged records or a common interface with typed operations. Do not flatten the semantic type model merely to satisfy today's parser. | Variant identity, invalid-state exclusion, subtype behavior and null absence. Potentially broad model change: discuss before rewriting. |
| Resource-valued `mixed`, process handles, locks and destructor cleanup: `tool_process/process.php`, `src-runtime-preparation/reservation.php` | **Framework/platform contract, then adaptation.** Explicit process/stream/lock owners with PHP implementations and target counterparts. No blanket mapping of `proc_*`, POSIX, `flock` or destructor timing. | Timeout, process-group cancellation/reaping, stderr, exit status, lock exclusivity/transfer and cleanup after partial failure. Real process/locking behavior needs native proofs; sequential MT mimicry does not prove it. |
| Custom diagnostics and cleanup paths: `src/diagnostics/diagnostics.php`, `src-runtime-preparation/prepare.php` | **Contract/target decision.** Preserve diagnostic fields and guaranteed release. Today's framework covers a fixed handled-exception family, not custom exception inheritance, engine errors or finally. Do not replace cleanup with success-path-only calls. | Location/message/code, handler selection, rethrow identity, release on every exit, uncaught CLI diagnostics. |

## What does not automatically need a structural rewrite

Ordinary typed methods, visibility, typed object parameters/returns, loops,
constants and explicit member calls are mostly parser coverage work. Match,
nullsafe access and unpacking need individual decisions: some can be written as
simple branches/loops; they are not inherently proof that the compiler architecture
is nonportable. Lexical imports can be replaced with fully qualified names by the
writer, or eventually normalized from explicit per-file aliases without symbol
resolution. Current managed-import rejection is not a semantic impossibility.

PHP bootstrap `require_once` wiring belongs to the PHP composition root; native
project membership replaces that loading mechanism. The converter should not grow
an include resolver. PHP reflection-based tests can stay host-side with explicit
native behavioral witnesses; no assertion should disappear because its harness
cannot be converted.

## Review of accommodations already implemented

Do not remove existing behavior during this audit. Preserve its tests and evidence
until a replacement is agreed and demonstrated.

| Accommodation | Assessment under the authoring-first boundary |
| --- | --- |
| Local scalar/vector annotations and uniform function bindings | Keep: explicit intent and direct local transformation are the converter's job. |
| Byte helpers and `strlen` byte mapping | Keep the byte contract. Mapping compensates for selected-target behavior; it must remain explicit and tested. |
| Promoted constructor expansion | Optional convenience, not essential compatibility. Recommend explicit properties/assignments for new adaptation work once ordinary constructors are admitted. Today the converter accepts promotion but not general constructors, so author guidance and parser support must be aligned before enforcing that preference. |
| Nullable enum spelling / explicitly typed empty vectors | Bounded local type-intent emission, with selected-target limitations recorded. Do not infer types from distant assignments. |
| Readonly fields emitted without effective native enforcement | Limitation, not equivalent behavior. Existing usage discipline is evidence only; do not promise enforced immutability. |
| Fixed native exception hierarchy, category catch dispatch, `same_exception` | Highest-risk accommodation: much of it compensates for target behavior. Existing proofs are useful, but do not expand it automatically to arbitrary PHP inheritance/exceptions. Decide target fixes versus a deliberately small diagnostics contract before porting custom errors. |
| NUL/invalid-UTF-8 literal rejection | Keep a clear rejection until a deliberate literal representation is available. Do not silently alter source bytes or confuse this with binary runtime input support. |

The recorded target evidence is in the [role-view](../../portability/compiler_role_views_slice.md),
[byte-string](../../portability/compiler_string_bytes_slice.md), and
[exception](../../portability/compiler_exception_slice.md) checkpoints. This audit
does not rerun native builds or assume the workspace's newer semantics match v0.1.76.

## Recommended decisions before resuming

Target development is an option alongside source adaptation and framework support.
Prioritize reusable semantics over convenience compatibility:

| Candidate | Review recommendation |
| --- | --- |
| Correct typed catch dispatch, exception identity and constructor metadata | Strong candidates for target fixes: current workarounds compensate for observed defects. |
| Enforced readonly, reliable nullable/container declarations | Assess target work where it removes repeated limitations and supports the intended authoring contract. |
| Typed copy/snapshot operations and dependable container mutation | First define required semantics and inspect existing target support. Add only a demonstrated missing capability; do not propose emulating PHP copy-on-write wholesale. |
| Trait-like source composition | Compare explicit handler/state refactoring with a narrowly defined target composition feature. Full PHP traits are not automatically the right feature. Either choice needs a design review before cross-stage changes. |
| Process, stream and lock facilities | Potential target library additions, with the existing service's lifecycle contract and native proofs as requirements. |
| Named-argument convenience, automatic object-to-JSON conversion, dynamic properties | Prefer straightforward authored alternatives first; these do not currently justify enlarging the target or converter by themselves. |

The selected v0.1.76 remains the recorded proof target. If target changes are chosen,
select and record a new tested revision explicitly and rerun affected proofs before
retiring workarounds. Adding Simple C++ capabilities does not authorize new behavior
in the adopted compiler during its migration.

1. Agree that ordinary source rewrites come before convenience syntax additions.
2. Specify typed containers and owner-specific copying, using source snapshots and
   token storage as the first examples. Prove mutation/identity before broader use.
3. Choose a replacement for traits using one parser worker as the design sample;
   preserve handler organization without converter-side file merging.
4. Decide the custom diagnostic/cleanup boundary and policy for target defects.
5. Turn the agreed spellings into concise writer guidance plus local rejection
   examples. Mark proposed versus implemented forms explicitly.

Then use complete tokenization (tokens and diagnostics) as a meaningful vertical
proof. This sequence is a discussion proposal; the goal has not been resumed and
none of the listed rewrites has started.

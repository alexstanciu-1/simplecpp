# Collection capability completion: vector, hash, mixed and dynamic

Doc Status: planning
Updated: 2026-11-18
Scope: C0–C7 goal complete; decisions D1–D3 approved on 2026-11-18.

## Objective and authority

Bring the four families to a coherent, useful common collection surface, using existing
metadata, concrete recipes, call binding, indexed targets and traversal protocols.
Similar capabilities do not imply identical ownership, keys, failure rules or operators.
This backlog supports bootstrap groups 1.3/1.4 and later lookup work; it does not claim
that completing collections also completes readonly initialization or native bootstrap.

The [implementation plan](v03_bootstrap_implementation_plan.md) supplies milestone order.
Normative collection, boxed storage and expression specs remain semantic authority.
The older [typed collection notes](typed_collections.md) include historical proposals
(e.g. separate clear helpers) that have been superseded by receiver methods.

Current checkpoint includes the tested, uncommitted boxed count/empty/has/remove slice.
Preserve it when beginning the goal. Preserve unrelated `tests/yield_benchmark/` work.
Do not silently commit, pull or push subsequent slices merely because this is a goal.

## Current coverage

P = runtime-published vector/hash specializations; A = application-defined concrete types.
A missing source binding is distinct from a missing native implementation.

| Capability | vector P / A | hash P / A | mixed | dynamic |
| --- | --- | --- | --- | --- |
| Typed empty local | Both | Both | Yes | Yes |
| Copy and assignment | Independent containers | Independent containers | Value semantics/COW | Shared container identity |
| Function/method value parameters and returns | Both | Both | Yes | Yes |
| Exact-type named writable parameters | Both | Both | Yes | Yes |
| Instance fields | Both, empty default | Both, empty default | Null default | Null default |
| Keyed read/write | Both, checked index | Both, checked key domain | Yes, normalized int/string | Yes, normalized int/string |
| Append | Both | Integer-key P / A | Yes; null bootstraps | Yes; container required |
| count/empty | Both | Both | Implemented in current slice | Implemented in current slice |
| has/remove/clear receiver methods | P / A | P / A | All three | All three |
| Indexed compounds/updates | Existing element rules | Existing value rules | Generated scalar rules, existing key | Generated scalar rules, existing key |
| Value/key foreach | Both, copied elements | Both, copied elements | Same-family copied value | Same-family value preserving identity |
| Foreach inside yield | Both | Both | Yes | Yes |
| Nested collection types / indexed writes | Separate | Separate | Nested storage exists; source path deferred | Nested storage exists; source path deferred |
| Populated literals | Separate | Separate | Separate | Separate |
| Nullable find | Native published cases exist; not a uniform source API | Source gap | Missing read already returns null | Missing read already returns null |

Vector/hash operations on a struct element are not automatically arithmetic operations:
indexed updates apply only if the element type itself supports the operation. Likewise,
string-key hashes do not need append, and boxed values need not accept arbitrary objects.
Constructor reference parameters, property/element references, reference returns, globals
and static collection fields remain independent boundaries.

Evidence: compiler docs for application_vectors, application_hashes, collection_references,
collection_methods, indexed_compounds, foreach and boxed_collection_coverage; generated
registration in tools/api_generator/php/render_compiler.php; types/boxed.php, vector.php
and hash.php. Boxed binary dispatch now includes the agreed compound/update groups, projected from
validated scalar plans; this does not claim every scalar operator or new payload family.

## Ordered backlog

| Order | Slice | Work and completion proof |
| --- | --- | --- |
| C0 | Freeze scope and semantic choices | Settle decision gates below; record goal objective and exclusions; retain current passing baseline. |
| C1 | Application vector/hash method publication | Expose has/remove/clear for current concrete types using the same method contract as published specializations. Verify expression receivers, required element constructors, shared fields, deletion holes and provider/incremental edits. Do not add three handwritten method cases per family. |
| C2 | Boxed container-preserving clear | One runtime operation with family-specific ownership behavior, generated source binding. Repeated clear/reuse, aliased dynamic state, mixed isolation, non-container failure, mutation during traversal, destruction/leak probes. Never substitute clean(), which resets a binding to null. |
| C3 | Append where meaningful | Boxed append and integer-key hash append for published/application values via appendable capability and existing checked conversions. Verify next-key policy, holes/removal/clear, exhaustion and failure preservation. String-key hash append remains rejected. |
| C4 | Boxed instance fields | Generated field admission/default contract; reuse existing initialization, copy and mutation paths. Cover inline/nested/shared receiver structs, by-value copies, field replacement and retained receiver lifetime. No special AST field node. |
| C5a | Boxed operator prerequisites | If approved, extend runtime kind dispatch from existing scalar metadata for the compound/update groups; preserve boxed result-family rules and reject unsupported payload kinds. Verify these handlers independently before indexing. |
| C5b | Indexed boxed mutation | Reuse indexed target/read-modify-write ownership. Stage old value, invoke permitted generated boxed operator rule, validate existing key, then write back. Implement only the agreed operator scope; do not pretend scalar operators are already all generated for boxes. Test RHS mutation/removal/replacement and failure. |
| C6 | Close the parity proof | One compact cross-family fixture matrix exercising common capabilities and explicit differences; source-to-native execution, independent providers, repeated incremental errors/recovery and fresh equivalence. Record Clang stages and PHP timings; update coverage and remaining debt. |
| C7 | Bootstrap collection-use proof | Construct/pass/mutate/traverse an application Token-like record vector and a string-key symbol index. This proves collection usage; actual readonly token/source-snapshot translation remains gated by milestone 1.5. |

Proposed first goal: C0–C7 for currently admitted value families and one-level collection
operations. Goal completion means all agreed rows are implemented and verified, with the
excluded items still explicitly visible below. It must not be described as full PHP array
support or completion of every bootstrap requirement.

## Approved decisions

### D1. Goal breadth and operator boundary

Approved: current published values and current application record/class values;
one-level operations, existing keys, no new template language or object boxing.
Approved for useful indexed-update parity: include generated boxed runtime dispatch
for the compound/update operator groups, derived from existing scalar plans and preserving
the agreed boxed result-family rules. This is C5a, a real extra workstream, not an
indexing-only change. No duplicate scalar matrices. Unresolved semantics still require discussion, not invention.

### D2. Clear and field defaults

Approved clear: requires a container, leaves an empty container, mixed copies isolate,
dynamic aliases observe the cleared table; null/scalars throw as with count/empty.
Do not promise capacity retention for boxed/hash storage. Vector's existing contract stays.

Approved omitted mixed/dynamic field value: null, matching existing native default
and their scalar-or-container identity. Users explicitly assign `[]` in a constructor
when an empty container is wanted. Explicit bracket field initializers are a separate
syntax/admission question; do not accidentally implement them only for boxes.

### D3. Append and missing-key update rules

Approved: preserve existing runtime contracts. Boxed mixed null can bootstrap on
append, dynamic null rejects; other scalars reject. Boxed integer keys remain bounded
by INT32_MAX, without uint64 activation. Confirm native append policy against current
spec before source exposure; integer hash append uses its existing next-key contract.
Do not silently equate different families' integer key domains or change key reuse.

Approved indexed compounds: require an existing entry, matching vector/hash's
read-modify-write contract; no auto-insertion/autovivification. A present null is different
from a missing key and still must pass operator admission at runtime. Ordinary boxed
reads may continue returning null on absence; mutation needs presence validation.

## Later backlog, explicitly outside the proposed first goal

- Boxed scalar operators outside the agreed compound/update and existing ordering groups.
- Nested collection specialization and chained indexed mutation: ownership-aware write-back
  for mixed COW, stable dynamic receivers, recursive concrete publication and cycle diagnosis.
- Populated literals and inference, preserving evaluation/failure contracts.
- Nonthrowing lookup/find: distinguish absent key from present null, and generate appropriate
  carrier specializations for application values instead of using null as an ambiguous sentinel.
- Additional element families: enums, carriers, nested containers, optional scalar widths,
  explicit shared/weak values and boxed elements where allowed. Validate each type's storage,
  copy/move/destruction contract through the common type interface rather than assuming all T.
- Application-defined keys: hashing/equality contract, not just another concrete representation.
- Boxed application objects: new kind/lifetime/copy/dispatch design; no automatic widening.
- Static/global collection storage and explicit empty/populated field initializers.
- Reference/destructuring foreach and property/element reference arguments; preserve rejection
  until lifetime rules are agreed. Cross-thread mutation/ownership and cycle collection remain deferred.

## Risks and mandatory stop points

1. App collection method publication currently consumes concrete runtime specialization rows.
   A canonical method capability must bridge application types without reading metadata during
   preparation or retaining per-call method tables. A local owner refactor is expected; a wide
   registration/type-system redesign needs discussion before proceeding.
2. Dynamic clear must mutate the shared table, not replace one alias. Mixed clear must detach
   safely. Self/cyclic contents and active traversals need destruction/memory-safety probes;
   this is not a promise of automatic cycle collection.
3. Nested mixed updates require write-back through every copied parent. Do not implement them
   as repeated copied reads or claim parity just because dynamic happens to share nested state.
4. No slot pointer may survive RHS execution when that execution can replace/reallocate storage.
   Reuse current receiver retention and checked write-back, without imposing deferred LTR ordering.
5. Removal and append have different effects on vector positions, hash holes and boxed key reuse.
   Preserve documented traversal safety, not snapshot/atomicity guarantees we never promised.
6. Wider generic values may introduce constructor/exception/publication cycles. Stop at the
   common type-interface boundary rather than widening admitted types opportunistically.

## Execution and verification discipline

Each slice: inspect owning specs and current capabilities; implement metadata → generated
runtime/compiler → preparation/lowering → native proof; consolidate docs/tests before moving on.
Keep user updated; stop for unresolved language choices or cross-owner redesign. Do not
implement locks, scheduler changes, reference transports or unrelated language features.

Test PHP/C++/JS spellings, correct type/result/admission, errors before unsafe mutation,
copy/identity differences, expression receivers, independent provider publication and light
includes. For ownership changes, use targeted sanitizer probes. Compare affecting edits,
deletions, repeated failures and recovery with fresh compilation; no-op prepares/rebuilds
nothing. Measure PHP, native wall, Clang frontend/LLVM and linker with disclosed flags,
PCH/cache/runtime reuse. Preserve existing native join gates and cleanup boundaries.

Record line additions/removals, structure changes, focused compiler snippets and exact
remaining debt. Do not expand this goal to optional capabilities merely to make every
column identical. Its endpoint is useful shared mechanisms plus explicit family contracts.

## Execution log

- C0 complete: goal active; D1 includes boxed operator prerequisites, D2/D3 accepted.
- C1 implemented: generated canonical types cache has/remove/clear signatures; published registry delegates to them. Native tests pass all flavors, including required constructors, expression receivers, provider edits and recovery.
- C2 implemented: container-preserving boxed clear; source recovery tests pass all flavors. Targeted ASan/UBSan/LSan proof passes, including self/parent cycles cleared explicitly.
- C3 implemented: boxed append and integer-key hash append use generated capabilities and shared concrete fragments. All-flavor recovery and exhaustion guards pass.
- C4 implemented: generated null-default field admission; static admission stays separate. All-flavor inline/nested/shared copy/mutation, expression receivers and constructor-edit recovery tests pass. Direct `field = []` syntax is not added: constructors assign a typed empty local.
- C5 implemented: generated arithmetic/bitwise/shift/concatenation and update dispatch; existing-key indexed mutations use copied payloads and checked write-back. All-flavor edits/retries/recovery and runtime guards pass, including RHS key removal.
- C6 complete: all-flavor boxed coverage, existing typed mutation, boxed scalar native regressions, independent provider publication and style checks pass. Legacy generator artifacts/target layouts are unchanged; only metadata-input fingerprints were refreshed.
- C7 complete: Token-like records in vector and string-key symbol hash; copy independence, traversal, clear, cross-unit edits, deletion and recovery pass all flavors. This is a collection-use proof, not milestone 1.5 readonly completion.

## Verification checkpoint, 2026-11-18

C0–C7 implemented and verified. No new AST nodes, occurrence fact slots or preparation
traversals. Structures changed are canonical capabilities/signatures: generated per-type
method caches, overload selector position/receiver flag, static versus instance field
admission, checked indexed-mutation read, and a flavor-aware compound lowering hook.
Runtime boxed layout is unchanged. The concrete recipe engine gained optional fragments.

Proofs retained in test tools: application collection methods; boxed clear; common append;
boxed fields; indexed boxed mutations; Token-like vector and symbol-hash workflow. Each
runs PHP/C++/JS and compares incremental artifacts and diagnostics against fresh runs,
including deletion, retry without edits, recovery and no-op reuse. Native guards cover
scalar/null receivers, key exhaustion, missing keys, unsupported payloads, divide/shift/
power failures, PHP string-add rejection and RHS key removal. ASan/UBSan/LSan storage
proof passes, including explicit dynamic cycle breaking and retained copied values.

Independent mixed-only and dynamic-only publication passes. Its standalone link proof
now includes the existing arithmetic implementation because generated boxed arithmetic
calls those checked helpers. This was a test link-input correction, not a new runtime
implementation. Generator frozen artifacts and native layouts remain identical; the 54
metadata input hashes changed. Concrete conditional-fragment validation and native storage
copy/growth/failure probes pass. Existing boxed scalar native, indexed compound/update,
collection method and coverage regressions pass.

Token-like proof initial measurements, milliseconds (single observations):

| Flavor | PHP compiler | Native wall | Clang frontend | LLVM | Link |
| --- | ---: | ---: | ---: | ---: | ---: |
| php | 7.05 | 337.38 | 116.37 | 164.19 | 59.00 |
| cpp | 7.47 | 311.68 | 89.03 | 156.87 | 54.00 |
| js | 7.22 | 309.82 | 86.84 | 153.52 | 55.00 |

O2/g0, Ninja8, mold, no PCH/LTO, prebuilt libc++ runtime. Direct Ninja qualification
is included in native wall; PHP is separate. Frontend/LLVM are summed work and can overlap.
These are correctness-fixture observations, not controlled before/after speed comparisons.
Raw artifacts: `/tmp/collection-goal-c1-proof`, `-c2-proof`, `-c3-final`, `-c4-proof`,
`-c4-final`, `-c5-guards`, `-c7-proof`; logs also retain failed test iterations and corrections.
The first clear/append native runs included a qualification refresh (~1 second); do not
attribute that to generated collection code. Final boxed-mutation native observations
were 448–499 ms, PHP 7.5–7.8 ms, frontend 49–50 ms, LLVM 255–306 ms, link 55–59 ms.

No compiler sources were committed or pushed during this goal. Broader features remain
in the explicit later backlog above; no readonly/native-bootstrap completion is claimed.

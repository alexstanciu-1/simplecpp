# Native scalar runtime migration inventory

Doc Status: planning
Date: 2026-10-31

This records the dependency audit after the compiler's native-int64 slice. It is
not authorization for a cross-family runtime API rewrite or a change in language
semantics. The [generated lowering direction](../../compiler/docs/generated_lowering.md)
records the agreed compiler/generator boundary.

## Actual state

The compiler emits native int64 locals/literals/references. Echo and entry APIs
already accept native integer values. The application and panel PCH no longer
include the integer wrapper header. The scalar classes and their converting
constructors/operators remain. The string byte-count wrapper signature has now
been retired as described below. Compiler publication is separate from runtime
publication; adding a compiler trait does not suppress runtime generation.

The current local default `runtime/generated/prepared.json` contains two integer
storage types (byte/int64), three values (bool_t/float64/null_t), 34 conversions,
66 operations, two vectors and 16 carriers. Of these, 14 conversions have int64
as source or target and 37 operations have an int64 operand. These are publication
observations, not universal counts or measurements of executable size. Optional
numeric, hash, boxed, JSON and datetime publications add other dependencies.

## Owners and disposition

| Area / source owner | Wrapper dependency | Intended disposition / behavior to retain |
| --- | --- | --- |
| Scalar classes and conversions: `tools/api_generator/php/render.php`, `renderValues()` | Integer/value classes, converting constructors, native accessors, deleted constructors | Preparation admits conversions; generated PHP lowers selected conversions. Retain representability checks, parsing/formatting algorithms, and distinct language identities. Remove constructors/fences when the corresponding public wrapper contract is retired. |
| Scalar operators: `render.php` operation loop | C++ overloads wrap operands/results and call private numeric helpers | Generated compiler handlers select direct operations or runtime calls. Preserve promotion, exact comparisons, shifts, division and compound-writeback contracts. Do not delete helper algorithms just because overloads disappear. |
| Native bridge and short names: `render.php` | Optional native/operator adapters, deleted overloads, convenience aliases | Compiler does not need these for native int64. Bridge is already disabled by default. Remove or explicitly scope alongside retired native consumer APIs; aliases alone do not fix signatures. |
| Strings: `render_sequences.php`, `string_*` templates | Count constructor/size use int64; empty/comparisons use bool_t; numeric constructors use wrappers | Native count/results and explicit selected conversion calls. Retain byte lengths, negative-length rejection, checked size conversion, ownership and actual string algorithms. |
| Vectors: `render_sequences.php`, `vector_*` templates | int64 count/index and element storage; bool_t empty; native index/value adapter cross-products and fences | Native scalar storage/signatures, compiler-selected conversions. Keep bounds checks, conversions between vector elements, lifetime, copy/move and failure behavior. |
| Carriers: `render_sequences.php`, carrier templates | Payloads use scalar classes; state queries use bool_t | Keep carrier storage/state/ownership semantics; replace scalar payload/boolean representation deliberately. Native representation must not conflate absent, false and errors. |
| Hashes: `render_modules.php`, `runtime/src/hash/typed_hash.hpp` | int64 keys/counts; integer_hash_key and packed integer paths call native_value; string-key length unwraps size | Adapt policies to native keys/counts, retain hashing, ordering, packed-key behavior and ownership. Private templates also require migration, not just public templates. |
| Boxed families: `render_boxed.php`, boxed templates | Scalar factories/accessors, integer keys/counts and string lengths use wrappers | Native typed boundaries; retain dynamic discrimination, checked extraction and independent mixed/dynamic ownership semantics. |
| JSON: `render_json.php` | Scalar unwrapping, carrier boolean tests, string byte counts and error construction | Native adapters; retain JSON parser/serializer, errors and carrier behavior. |
| Datetime: `render_modules.php` | int64/uint64 timestamps/durations, count construction, wrapped integer result payloads | Native module boundaries, preserving range/unit/error semantics. Module internals already use native values in several places. |
| Entry and echo: `runtime/src/entry`, `runtime/src/output` | Already native integer public parameters | Keep current APIs. Checked process exit and IO are actual runtime work, not wrapper compensation. |

Paths in the table are relative to `v0.2/`. Generated files are evidence only; edits
belong to the listed generator/template/private implementation owners.

## Dependencies prevent a blind deletion

Replacing `class int64` with a native alias is not a sufficient migration:
constructors and native_value calls become invalid, some overloads collapse onto
identical native signatures, and primitive-only overloaded operators are invalid
C++. Template policies also explicitly expect wrapper members. Removing deleted
C++ overloads before their admission role has moved changes what native consumers
can pass. Existing consumer tests record those contracts and must be consciously
migrated, rather than discarded or mechanically refreshing every fixture.

Even a string count migration has outward edges: JSON, boxed factories, datetime,
string hash policies and native tests consumed `size().native_value()` before this
slice migrated them. Accordingly,
this is a cross-owner API change, not a one-template cleanup.

## First removal slice — implemented 2026-10-31

Migrated **string byte counts**: the bytes/count constructor and size result use
native int64. Direct callers now use native counts, keeping
string ownership, bool_t predicates and numeric/string conversions out of scope.
This is a real removal from an existing boundary, without introducing a second
parallel string API or claiming general scalar-wrapper retirement.

This slice touches sequence rendering, JSON/boxed/datetime adapters, private string
hash policy, relevant native examples/tests, and contract documentation. This cross-owner
scope was approved and implemented. It enables the compiler to use a
native byte-count boundary when general strings are implemented, but does not add
string language support now and does not remove class int64 itself.

An alternative is a coordinated int64 retirement across all families. That removes
more machinery immediately but requires migrating scalar operations/conversions,
container payloads, optional modules and the native consumer contract together.
It is a substantially larger validation and API-change task. Do not quietly begin
that rewrite under the current small-slice authorization.

## Verification for the first removal slice

- Native string bytes/count and size tests: empty, embedded NUL, exact byte lengths,
  negative count rejection, copy/move/lifetime and size narrowing checks.
- Compile all affected direct consumers, including string-key hash, boxed, JSON and
  datetime publications, and exercise their existing behavior tests.
- Generator freshness/obsolete cleanup, isolated public headers and deterministic
  publication. Review fixture differences as intentional signature/adapter changes.
- Existing compiler integer/echo/exit and panel tests must remain green; no new
  source syntax or preparation work is part of this API migration.
- Confirm the retired wrapper signature is absent. Compare header closure and
  relevant native compile timing only after correctness; do not assume an archive
  or executable size reduction from fewer wrapper calls.

## Follow-up order

After count boundaries, migrate vector indexes/elements and carrier scalar payloads,
then hash/boxed/module scalar boundaries as coordinated slices. Replace scalar
conversion/operation wrappers with generated compiler dispatch and selected runtime
helpers as those source operations become supported. Record each retired API; do
not indefinitely generate both paths without an explicit consumer requirement.

## Validation of the string count migration

The default build, 64 runtime/compiler checks, and all three generator/scalar
contract suites passed. Separate all-flavor native entry/output and localhost
panel checks passed. The reduced string publication compiles with no published
integer wrapper; the full consumer explicitly rejects the old wrapped count
signature. Runtime publication is version 2. Snapshot review limited behavioral
artifact changes to string headers, prepared string facts and runtime definitions;
scalar-only snapshots changed only their inventory version.

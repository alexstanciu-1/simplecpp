# Runtime types, conversions and operators

Doc Status: planning
Date: 2026-10-28
Status: active goal; implementation proceeds in bounded slices below.

## Outcome and boundaries

Implement metadata-driven operators, checked explicit casts and requested overloads
for the fixed-width integer family, float64, boolean, null, string and generated
vectors. `int` in Simple C++ maps to the configured signed integer (default int64); source `bool` and `float` map to concrete
runtime types. C++ keywords cannot be redeclared. Source-to-C++ name mappings belong
in metadata, not macros. Our string and vector types do not use std names as their
public identities. A source vector<T> request generates a concrete class, initially
vector_string and vector_int64, without public templates or std container aliases.

The goal includes the runtime, metadata, generator and physical pre-lowering plans.
End-to-end my-try-cpp import/integration is explicitly a later goal. No source-symbol
resolution, source sequencing or type inference is implemented in the API generator.
No carrier, mixed/dynamic, asynchronous, module-loader or broad legacy import is
part of this goal. Non-overloadable source syntax stays with the compiler.

The compiler should resolve typed expressions and request explicit adaptations into
small runtime entrypoints. A complete source operator matrix does not require a
complete C++ overload matrix. Generate concrete convenience overloads only when
requested by a C++ consumer; preserve a single metadata policy across both paths.
Standard C++ algorithms/storage are appropriate private implementations. The runtime
adds representation, lifecycle, checked value boundaries and useful concrete APIs.
It must not recreate dispatch machinery needed only by the type-blind v0.1 S2S path.

## Decisions already made

- Explicit constructors/static_cast; no duplicate public named numeric cast functions.
- Same-type integer arithmetic preserves its type. Mixed arithmetic uses domain
  maximum; signed/uint64 combinations require explicit casts. No floating fallback.
- Native-int arithmetic is an optional bridge. Large/suffixed native values require
  explicit conversion; C++ cannot enforce literal-only usage of native int.
- Public headers and their include closure remain template-free. Private templates
  and standard-library implementation types are allowed.
- Float64 permits rounding on explicit integer conversion. Explicit float-to-integer
  truncates toward zero and checks finite/range before conversion. Mixed float64
  arithmetic with int64/uint64 requires an explicit cast; narrower integer ranges
  are eligible for exact type-range conversion.
- Vector at(index) returns a value copy; set(index, value) owns mutation. No exposed
  interior-reference lifetime protocol in the initial public vector contract.
- Physical plans are tooling artifacts, not evidence that source admission passed.

## Ownership and migration rules

Metadata owns identities, source/runtime spellings, conversion/promotion policies,
operation binding and publication demands. Preparation validates and resolves those
facts once. Emitters consume typed prepared facts; they do not infer another policy.
Handwritten runtime primitives own algorithms and data-dependent checks. The compiler
owns contextual decisions and can avoid a check only through a proven, specified
lowering path. Plain C++ consumers retain the promised checked behavior.

Keep one generator executable and the existing prepare/emit pipeline. Extend the
current integer operation model locally into reusable resolved operation facts;
introduce family-specific storage/lifecycle emission only where representation needs
it. Do not introduce a parallel string/vector generator or an unbounded metadata
implementation language. Group coherent public families and private implementations.

Each slice records exact reviewed legacy sources and intentional departures. Import
only its behavior and necessary dependencies into v0.2. Old-tree paths never become
build inputs. Do not migrate whole modules for a small primitive. Keep generated
artifacts under runtime/generated and regenerate from metadata, never hand-patch them.
Generated code, inventories, prepared plans and compilation outputs must not be
tracked in Git. Keep runtime/generated locally for consumers; the normal build
recreates it from committed metadata and generator sources on a fresh checkout.

### Periodic file cleanup

Commit each coherent work slice after its focused validation and cleanup pass.
Describe what is actually implemented and tested; an intermediate checkpoint does
not imply that a whole family or the goal is complete. Keep unrelated work out of
the commit. This cadence was requested by the user during the string foundation.

At the end of each coherent implementation slice, and before consolidation or a
commit, review files for obsolete implementations, superseded metadata, unused
helpers, temporary artifacts and stale documentation. Verify remaining consumers
and build/generation references before deciding that a file is no longer needed.

Delete files with no remaining purpose. Move files with useful historical or
reference value to `v0.2/archive/`, recording why they were retired and their
replacement where applicable. Archived files are inactive reference material:
exclude them from builds, generation, tests and active contract discovery. Update
references and inventories so no active dependency points into the archive.

Keep cleanup local to the reviewed slice; do not remove another agent's in-progress
work or treat an untracked file as obsolete merely because it is untracked. Record
the cleanup outcome, including when no files need retirement. This rule does not
authorize bulk archival of the old repository tree.

## Implementation slices and acceptance

1. **Operation preparation and integer binary primitives — implemented.** Replace
   addition-only assumptions with resolved operation identity, symbol, implementation,
   execution and result facts. Add subtraction, multiplication, division, remainder
   and bitwise binary operations. Keep same-type default demands small; demonstrate
   explicit mixed lowering and opt-in native-int adapters. Review the scalar portions
   of legacy generated/operator_detail.hpp and checked casting, without importing its
   wrappers/dependencies. Prove domain selection, signed division/remainder, zero
   failures, unsigned behavior, no fallback admission and deterministic generation.

2. **Boolean/null and integer completion — implemented.** Generate concrete values with
   explicit boolean construction/condition conversion and null identity. Preserve
   built-in &&/|| short-circuiting; do not overload them as eager functions. Add
   comparisons returning our boolean type, unary operations, shifts, mutation,
   increment/decrement and their operation-specific plans. Shifts preserve the left
   type and check counts; compound assignment has checked write-back and must leave
   the target unchanged when conversion fails. Do not force arithmetic promotion
   onto comparison/shift/mutation plans. Prove all eight integer representations,
   both mutation results and conditions. Null is not automatically numeric zero.

3. **Floating and scalar casts — implemented.** Adapt the existing float value and checked
   floating/integer conversion primitives into the same plan. Generate float64
   arithmetic, comparisons and explicit scalar conversions, using the agreed wide
   integer boundary. Record NaN/infinity, zero division and boolean conversion
   contracts before implementation; preserve reviewed behavior unless a deliberate
   change is agreed. Prove exact boundary checks at powers of two and nonfinite
   inputs, permitted/rejected mixed combinations and bool/numeric separation.

4. **Owned strings — implemented.** Review existing string representation, byte/length,
   pointer construction and scalar formatting/parsing responsibilities. Use private
   standard storage when it satisfies the contract. Generate lifecycle, explicit
   construction, concatenation, equality/ordering and a small byte-length API.
   Preserve embedded NUL and reject invalid pointer/length inputs. Distinguish
   numeric parsing from casts of numeric values; adopt strict parsing/format rules
   explicitly rather than importing dynamic PHP coercion. Prove copy/move, empty,
   embedded NUL, self-append, ordering and failure behavior.

5. **Generated vectors — implemented.** Use one specialization preparation path for
   vector_string and vector_int64, with public constructors, lifecycle, size/empty,
   append, at, set, clear and equality. Private std::vector is permitted. Resolve
   element operation dependencies from metadata and reject unsupported element
   contracts before emission. Provide narrowly scoped literal conveniences such as
   append("Ada") and at(0), not broad implicit conversion. Check negative/beyond-end
   indices before native unsigned conversion. Prove independent copies, moves,
   alias-safe append/set, failure invariants and both generated specializations.
   Cross-element vector casts require explicit element conversion requests; do not
   invent implicit container conversion or vector arithmetic.

6. **Composition and cleanup — pending.** Produce a plain C++ example using all
   families and optional short names/bridge. Verify declarations together under
   Clang, valid and invalid overloads, core-only closure, no public template leaks,
   standalone generation/build without old-tree paths, freshness and determinism.
   Exercise a non-default demand set to prove generation is not hardcoded to the
   sample. Remove superseded emitter assumptions and stale docs. Document exact
   implemented operators/casts, unsupported combinations and compiler integration
   obligations. Run applicable runtime tests and sanitizers with known environment
   limitations recorded honestly.

## Semantic checkpoints, not guessed defaults

The later slices must first inspect the current contracts for comparison across
signed/unsigned extremes; integer overflow/shift behavior; floating division and
NaN ordering; scalar boolean conversion; null conversion; string parsing/formatting;
and element/lifecycle operations. Ask the user before consequential changes or a
refactor spanning ownership areas. This does not block independent settled slices.
Keep unsupported operations explicitly absent rather than silently coercing them.

## Completion evidence

Completion requires every family above to have adopted metadata/contracts, generated
concrete public API, private behavior where needed, prepared conversion/operation
facts and compiled consumer proofs. It also requires all requested operator groups,
not merely addition. Record supported and rejected casts in a reviewable table.
Validate generation and behavior, overload composition, standalone dependency closure
and unchanged explicit construction. Compiler integration must be clearly identified
as excluded, not implied by a physical plan. This document alone is not completion.

## Checkpoint: integer binary operations

The first slice is implemented. Prepared operations include binding/symbol and use
operation-plus-operand identity for duplicate detection and bridge dependencies.
The default has 18 core/32 optional bridge signatures; a separate test publication
compiles 64 same-type signatures across all eight integer widths. Division/modulo
zero failures retain checks; signed overflow remains outside the defined domain.
Nine release tests pass. Remaining slices above are not claimed complete.

This binary-operation checkpoint also passes all nine tests in a fresh standalone
Clang/libc++ UBSan build with recovery disabled. The build starts without generated
artifacts; compiler dependency records contain no original-repository paths.

## Checkpoint: values and integer operation forms

The generator now owns a runtime plan rather than an integer-only top level.
`runtime/metadata/runtime.json` consolidates the previous integer facts with simple
value records and source mappings. Its owner files are `runtime.hpp`, `runtime.cpp`
and `emission.cpp`; the CMake consumer target is `scpp_runtime`. Boolean/null values,
exact integer comparisons, unary, shifts and mutations are implemented. The scope
and reviewed adaptations are in [the value contract](../values_slice.md). Scalar cross-family casts remain slice 3 work.

Eleven release tests pass, including the default 117 integer signatures (69 core,
48 optional native-int adapters) and a 602-signature full integer proof publication.
No source compiler or legacy runtime dependencies were introduced. A smaller runtime
still supplies data-dependent count/range checks and value semantics to C++ callers.

The value/form checkpoint passes all eleven tests in a fresh standalone Clang/libc++
UBSan build with recovery disabled. Generation starts from no derived files, and
compiler dependency records contain no original-repository paths.

## Naming refinement: configured default aliases

The public boolean class is `bool_t`, replacing `boolean`. Public `int_t` is a
concrete alias to the configured source int default, currently int64. The same
source-default metadata will drive `float_t` when floating support lands. No second
wrapper or duplicate operator set is introduced for an alias. The integer default
can select another registered signed integer; a publication exposes the alias only
when its target is present. `bool_t` remains the single concrete boolean identity.

Naming-slice cleanup: regenerated public declarations replace the old boolean name;
no compatibility alias or duplicate metadata record is retained. No files needed
retirement: the existing value/generator owners remain active. A separately generated
int32-default fixture proves that int_t follows configuration and shares overloads.

## Build setup

At the user's request the default setup is Ninja / 8 jobs / Clang / mold. CMake
presets record the generator, compiler, build parallelism and retained libc++ flags.
The project requires mold and uses it for executables and standalone consumer link
proofs. Build products live under ignored build/clang; derived API files remain in
runtime/generated. This applies to subsequent validation, including isolated copies.

## Checkpoint: floating/scalar conversion slice

Float64 operators and explicit numeric/boolean casts are implemented in the same
prepared model as integer operations. Losslessness and failure are distinct facts.
Wide integer operands require explicit float conversion. The default publication
has 151 numeric signatures; an independent 790-signature proof publication exercises
all integer widths and allowed narrow integer/float combinations. The exact contract,
reviewed reuse and cast matrix are in [floating_slice.md](../floating_slice.md).

The user chose to keep scpp::float_t namespaced by default because C math headers
provide a conflicting global typedef. Default short_aliases contains int_t only;
local function/namespace imports of scpp::float_t remain available.

Cleanup: renamed the private integer-only header to scalar_semantics.hpp and the
generated implementation to src/scalars.cpp. The retired names have no active build
consumers and no parallel files remain; no archive was needed. Generated artifacts
were republished from a fresh staging output through the existing generator.
The full goal remains incomplete: string/vector work and final composition follow.

Validation for the floating/build checkpoint: all thirteen tests pass in the default
release preset and a fresh standalone UBSan copy, both using Ninja, eight build jobs,
Clang/libc++ and mold. ELF .comment records confirm mold linked both the generator
and the independent consumer proof. The isolated compiler dependency inventory has
no original-repository includes. Generated freshness and alias composition pass.

## Checkpoint: string and sequence private boundaries

The user approved simpler explicit decimal/text casts, true/false boolean text and
no null-to-string cast. The reviewed reuse, exact parsing defaults and toolchain
fallback are recorded in [string_slice.md](../string_slice.md). Private pointer,
length, index and text-conversion primitives now have a focused proof. This is an
intermediate slice: string/vector metadata and public lifecycle/API emission remain
unfinished, as do public composition tests. No files needed retirement.

## Checkpoint: generated owned strings

String publication now resolves operator/cast dependencies and emits a concrete
public class with hidden standard storage. The default has six concrete types,
151 numeric and eight string operator signatures. String text casts are explicit
and selectively requested; null casts remain absent. Independent public headers,
reduced demand sets and composed scalar/string consumer behavior are tested.
See [string_slice.md](../string_slice.md) for ownership, borrowed data lifetime,
allocation tradeoffs and the exact metadata boundary. The generated implementation
is now src/runtime.cpp; the scalar-only filename has been retired. Vectors and final
composition/audit remain open.

The string checkpoint passes seventeen release tests and a fresh standalone UBSan
build with no original-tree dependencies. The generated consumer also passes
ASan+UBSan with libstdc++ (known environment LSan limitation retained). Full-family
text proofs cover extrema of all eight integer representations. See the string
contract for the exact evidence and storage tradeoff.

## Requested follow-on review: hash, mixed and dynamic

After generated vectors, the user requested review of porting `hash_t`, then
`mixed_t`, then `dynamic_t`. This follow-on review is now authorized; the earlier
exclusion of mixed/dynamic applies to the original scalar/string/vector delivery,
not to this subsequently requested review. Complete its remaining composition audit
as well; do not substitute follow-on work for unfinished original requirements.

Hard constraints from the user:

- Preserve hash_t's custom Swiss-table implementation and core algorithms. Do not
  replace it with std::unordered_map, another container, or a newly designed table.
- Adapt the public API and metadata/generator wiring where needed for v0.2.
- mixed_t and dynamic_t must use hash_t as their backing hash implementation.
- Continue bounded reviewed reuse into v0.2, with validated slice commits and no
  generated or compiled artifacts tracked in Git.

Start with runtime/specs/hash_t.md, runtime/include/scpp/hash_t.hpp and its
support/hash_t.hpp/.cpp implementation. Then inspect mixed_t.hpp and its support
files, followed by dynamic_t.hpp. Establish the dependency and ownership boundaries,
existing core invariants and behavior proofs before proposing a port. Preserve the
core behind an adapted public boundary; report a blocker if that cannot be done
without changing the protected algorithms. No bulk module import is authorized by
this queue entry. Record the bounded port slices and unresolved semantics after
the review rather than guessing them in advance.

## Checkpoint: generated vectors

One specialization path now publishes concrete vector_int64 and vector_string,
with value-copy reads, explicit set, checked indices, requested equality and optional
native-int/C-string member adapters. Explicit cross-element constructors require
published element conversions and discard partial targets on failure. Additional
bool/null/float/int32 specializations and an adapters-disabled publication are tested.
See [vector_slice.md](../vector_slice.md). No hash or mixed implementation was imported;
the requested follow-on review retains its Swiss-table preservation constraints.
Final composition, consolidated cast documentation and completion audit remain open.

Vector validation: twenty release tests and a fresh standalone UBSan build pass;
both default and cross-element vector consumers pass ASan+UBSan with the recorded
LSan environment limitation. Generated outputs remain ignored and untracked.

The requested initial hash/mixed/dynamic review is recorded in
[hash_mixed_dynamic_port_review.md](hash_mixed_dynamic_port_review.md). It identifies
the existing typed/packed cores and the current independent dynamic payload family;
no core algorithms or legacy modules were moved during that review.

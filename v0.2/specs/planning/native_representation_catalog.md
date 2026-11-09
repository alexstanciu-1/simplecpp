# Native representations and concrete vector publication

Doc Status: planning
Date: 2026-11-06
Scope: representation direction and vector investigation; implementation status is explicit below.

The [adopted alias ABI and named conversion contract](native_scalar_alias_abi.md)
supersedes the earlier choice between a parallel native API and migrating individual
vector signatures. The agreed destination replaces numeric/boolean wrappers with
scpp aliases, keeps vector filtering strict and leaves null unchanged. The [alias cutover](../native_scalar_aliases.md) is implemented, including native
vector element storage and bindings. The earlier separate vector prototype is superseded.

## Representation catalog

Source identity, physical representation and operation semantics are separate.
Use target-validated native scalar representations where they match exactly;
retain library representations for managed values. Native storage does not grant
C++ implicit conversions, promotions or unchecked source-language operations.

| Source identity | Proposed/current C++ representation | Direction |
| --- | --- | --- |
| int / int64 | Exact target-validated signed 64-bit integer | Native; currently active |
| byte / uint8 | Exact target-validated unsigned 8-bit integer | Native; currently active aliases |
| int8/16/32, uint16/32/64 | Exact-width native integer selected from target facts | Native when activated; not new compiler activation |
| bool | bool | Native; currently active |
| float / float64 | Validated binary64 double | Native; currently active |
| float32 | Validated binary32 float | Native when activated |
| null | scpp::null_t (alias for decltype(nullptr)) | Current native sentinel; nullable storage is separate |
| char and other character types | Depends on encoding and language contract | Deferred; native char is not assumed equivalent |
| Unusual/wider numeric formats | Target extension or library representation | Decide per format and target |
| string | scpp::string | Retain library storage/lifecycle |
| vector<T> | Published concrete vector class | Retain container; specialize element representation |
| hash<V,K> | Published concrete hash class | Retain container; specialize value/key representations |
| Nullable/result/ownership carriers | Concrete carrier or handle | Preserve required state and lifecycle machinery |

Fixed-width typedefs are aliases of native types, not wrapper classes. Native
long/long long are representation candidates, not portable width guarantees.
Removing scalar wrapper constructors, overload resolution and extraction is the
compile-cost motivation; no per-width speedup has been measured for this catalog.
Generated semantic handlers still own checked conversions, promotion, narrowing,
division and shift contracts. Public application include closures stay template-free;
private compiled runtime implementations may use templates.

## Baseline before the paused vector migration

- `tools/api_generator/php/compiler_plans.php::compilerTypePlan` selects native
  integer storage from the scalar registry, floating/bool/null spelling from
  compiler metadata, and string storage from its named binding. It also validates
  activation, literal policy and compiler classes; runtime publication should not
  depend on that entire compiler-specific plan.
- `tools/api_generator/php/families.php::prepareVectors` publishes bounded
  scalar/string specializations. Its element identity currently also supplies
  the C++ wrapped element spelling. Count/index and boolean results are wrapped.
- `tools/api_generator/php/render_sequences.php::renderVectors` emits concrete
  classes with opaque storage pointers. `vector_storage.cpp.tpl` privately stores
  `std::vector<Element>`; application headers do not instantiate that template.
- Existing methods include copy reads, explicit set, append, size, empty and clear.
  Equality and elementwise casts have separately requested publication capabilities.
- `prepareHashes` is currently bounded to int64/string keys and values; nullable
  lookup results are an additional dependency. Hash changes are a later slice.

## T's contract

[Runtime module metadata](../../../specs/runtime_module_metadata.md) separates
`value_storable` formation from argument-dependent `copyable_value` capabilities.
[Generic contracts](../../../specs/metaprogramming_contract.md) define copyability
through representation, copy construction/assignment and cleanup information.
The current vector implementation additionally asserts copy construction and
nothrow move construction/assignment. A directly stored native mutex fails these
requirements; a handle to an object containing a mutex is a different value.
There is no inferred blanket prohibition on mutex-containing objects, nor does
storage capability alone grant all collection operations.

## Vector integration requirements retained for the alias migration

1. Extract the shared representation selection from compiler-specific planning
   into an existing scalar/binding ownership area. Consume the same metadata and
   target facts from compiler and runtime publication; do not duplicate a type
   matrix or activate optional compiler types as a side effect.
2. Keep canonical element identity for specialization naming, capabilities and
   conversion decisions. Add a resolved element representation/binding to the
   temporary vector publication plan, including required public headers. Storage
   and method emission consume it rather than treating the identity as C++ text.
3. Use that representation consistently in private storage and public append,
   at and set signatures. Resolve count/index and boolean results as well; retaining
   wrapped size/empty/index boundaries would leave the integration incomplete.
4. Review native adapters and deleted overload fences against resolved signatures.
   Two different spellings may denote the same native type; blindly retaining
   adapters can introduce duplicate/deleted primary signatures. Preserve the chosen
   direct-C++ admission policy explicitly rather than silently broadening it.
5. Preserve checked bounds, value-copy reads, lifecycle and failure behavior.
   Vector casts currently use wrapper conversions via static_cast. Replacing these
   with native static_cast may lose checked narrowing. Reuse validated conversion
   bindings or keep this operation outside the first specialization migration.
6. Retain capability checks for equality independently of native operator availability.
   String elements remain scpp::string; runtime string comparison produces wrapped
   bool today, so internal result extraction still has a role.
7. Treat vector<bool> separately: std::vector<bool> uses packed/proxy storage.
   The current copy-read/explicit-set API need not expose proxies, but no future
   bool-reference/contiguous-storage contract may be assumed. Start with vector<int>.

The public ABI changes when wrapped scalar parameters/results become native.
Regenerate headers and implementations and rebuild consumers together. Existing
consumer tests and frozen publication fixtures intentionally encode the wrapped
API and must be reviewed, not blindly updated. No compatibility layer or version
validation is proposed here.

## Smallest coherent next slice and proof

The agreed scalar-alias migration now precedes source-level vector integration.
Do not introduce a parallel scpp::native hierarchy. Preserve and reconcile the
bounded vector work with the shared alias ABI and named checked conversions;
see the linked contract for proposed implementation slices. Runtime publication
proof comes before source-level vector<T> parsing/preparation.
Verify target representation identity, default/copy/move/append/read/set behavior,
negative/out-of-range indices, unaffected string publication, and signature collision
handling. Preserve or explicitly exclude checked vector casts in this slice. Check
transitive public headers and include Clang frontend/LLVM/linker timing when measuring
the resulting application build. Source-level integration later needs all-flavor and
incremental edit/error/recovery checks; runtime publication alone does not prove it.

Donor reference: `compiler/my-try/docs/architecture/cpp_template_prelowering.md`
separates source constraints, concrete bindings and specialization demand. Reuse the
ownership lesson, not the full donor framework without discussion.

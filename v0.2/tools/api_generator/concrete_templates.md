# Concrete template recipes
Doc Status: supporting

The API generator owns a reusable template-to-concrete path in `php/concrete.php`.
This is generator-time substitution of resolved native types, not a C++ template
parser or a second semantic compiler. Vector storage is its first production client;
application-defined element resolution remains owned by the compiler.

## Contract

`concreteTemplate(name, types, values)` loads a cached `.concrete.json` recipe.
The recipe declares named type parameters, their native requirements, and named
output sections composed from existing `.cpp.tpl` fragments. It returns rendered
sections to the caller. Publication still owns canonical specialization identity,
deduplication, provider headers and output assembly.

Type arguments implement `ConcreteType`; the engine never inspects metadata or ASTs.
The interface exposes canonical identity, C++ spelling in the emission namespace,
public declaration headers, and requirement support (`true`, `false`, or unknown).
An explicit refusal rejects instantiation; unknown facts require native verification.
Static assertions remain even for known facts, checking the native implementation.

`RuntimeConcreteType` adapts the existing validated publication plan. Aliases resolve
to canonical identities; symbols and headers come from exports/providers. Scalar
value semantics and the existing string lifecycle supply the currently known lifetime
requirements. Other requirements remain unknown, rather than inferred from a name.
The adapter retains only its small resolved facts, not the entire publication plan.
Header placement and specialization deduplication stay with publication; the interface
does not inject includes into fragments or create a second provider index.

For example, the vector recipe binds `e` to a metadata-backed type:

```php
$element = new RuntimeConcreteType($plan, $elementName);
$parts = concreteTemplate('collections/vector', ['e' => $element], $values, 'scpp');
```

Every declared type argument must be supplied exactly once. Type arguments cannot
shadow ordinary values. The engine generates aliases and native static assertions
for requested copy construction, nonthrowing move construction/assignment and
nonthrowing destruction. It has no vector-specific branch. A separate two-parameter
Pair recipe proves substitution independently of collections.

Native requirements are checked by Clang/GCC; this engine does not infer arbitrary
element capabilities. CMake tracks recipe JSON as well as PHP and fragment files.
New families can supply their own recipes and fragments without adding another
instantiation engine. This does not authorize source-level generic declarations.

There are two independent extension points: the recipe/bodies and the type arguments.
A future compiler adapter can implement this same type interface for application
types without changing the recipe engine. Compiler-produced templates from generic
source declarations are a separate, deferred feature. They would supply resolved
structure and explicit substitution points, not blind source-text replacement.
The compiler now supplies this interface for the bounded
[application-vector slice](../../compiler/docs/application_vectors.md). Compiler-generated
templates remain deferred. The independent test type provider also proves the interface
boundary without exposing a metadata plan or an AST.

## First client: vector

Metadata selects `concrete_buffer` storage. The public wrapper still contains one
private storage pointer, and its signatures and strict input filtering are retained.
Private generated storage has a byte-buffer pointer, live count and capacity. The
shared private `vector_buffer.hpp` helper owns checked allocation/growth and bounds;
concrete generated code owns element construction, movement and destruction.

Unused capacity contains no constructed elements. Growth allocates first, then
nonthrowingly moves live elements. Copying constructs one element at a time and
destroys the completed prefix if a copy throws. Copy assignment publishes only after
the complete replacement exists. Clearing destroys live elements and keeps capacity.
By-value append/set inputs preserve the existing alias boundary.

The vector implementation, conversion adapters and comparison loops emit concrete
C++ without template algorithms or `std::vector`. Other runtime families retain
their existing private implementations; this is not a template removal across the
entire runtime. Public and application C++ remain template-free.

## Verification and next boundary

`concrete_templates` runs on Clang and GCC. Its native-only element fixture is
over-aligned, has no default constructor, counts lifetimes, and can throw at every
copy prefix. A throwing-move variant must fail its generated static assertion.
Real generated runtime vectors are also tested with allocation failure injection,
including storage creation, growth and heap-backed string copies. `--sanitize`
instruments both generated runtime and proofs with Clang ASan/UBSan.

Provider exports, strict inputs, casts, copies and mutation/traversal regressions
remain independent proofs. Compiler AST, retained facts and preparation are unchanged.
Application vector values now reuse this recipe with existing record constructor
binding; capacity itself requires no default constructor. Traversal output initialization
still needs review before enabling foreach for application elements.

## Concrete hash runtime prerequisite

The `collections/hash` recipe now consumes key/value `ConcreteType` providers and
emits aligned entry storage with explicit live-slot lifetimes. It requires copying,
nonthrowing moves and destruction, but no default constructor. Erased physical slots
remain holes; neither growth nor copying constructs objects in them. Vector and hash
reuse `core/concrete_owner_lifecycle` for their pointer-sized public owners.

The existing control-byte/probing/index-width algorithm stays compiled once behind
`concrete_hash_index`. The generated storage performs concrete key equality and owns
entries; the index knows only hashes, physical positions and buckets. Its uint8/uint16/uint32 bucket buffers are type-independent; hashing helpers stay
compiled in the runtime library. Public hash declarations and strict input fences
are unchanged. The old private `typed_hash` template has been removed, and its storage
proof now instantiates the same production recipe with independent test type providers.

This is runtime/generator groundwork only. Application hash activation, shared compiler
publication, nullable application-value `find()`, and application collection fields are
still separate slices. Current runtime publication remains int64/string keys and values.

Validation (2026-11-18): existing hash consumer/boundary/freshness, provider, compiler
collection and traversal proofs pass. The recipe proof covers non-default-constructible,
over-aligned values, every throwing-copy prefix, holes, collision growth and cleanup under
Clang/GCC; Clang ASan/UBSan/leak checks pass. Allocation-failure and 66,000-entry churn
proofs now instantiate the production recipe, including 8/16/32-bit index transitions.

An alternating five-run O2/g0 libc++ churn/copy comparison against the prior committed
storage measured 25.68 ms median (25.62–26.80 ms), versus 21.25 ms (21.11–21.31 ms)
for concrete storage; allocation counts were 2,604 versus 2,580. This measures that workload
only, not general hash throughput. Fresh hash-operation native builds measured 200–209 ms
wall, frontend 26–27 ms, LLVM backend 17–19 ms, link 57–61 ms (Ninja8/mold, O2/g0,
no PCH/LTO, prebuilt runtime). PHP fixture generation including incremental proofs was
159 ms for the suite; this is not one compilation's frontend cost.

## Shared application collection publication

Recipes may declare an `application` assembly template, standard `includes` and
runtime-relative `support` source paths. `concreteApplication()` supplies the already
expanded fragments under `section_*` substitutions; `concreteSupport()` guards reused
helper source/declarations. Neither interprets C++ nor inspects compiler ASTs. Runtime
publication continues to select the required fragments directly.

Application hash/vector providers expose the same compiler interface and publication
path. Hash key policy arguments are planned once for both runtime and compiler generation;
application hash omits nullable lookup and iteration exposure for the bounded first slice.
The common method fragments prevent runtime/application wrapper behavior from diverging.

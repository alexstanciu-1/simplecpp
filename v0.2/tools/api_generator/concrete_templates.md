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

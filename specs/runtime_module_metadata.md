# Runtime module metadata — version 1
Doc Status: normative
Status: bounded v0.2 catalog format

Runtime-owned catalogs live in `runtime/metadata/modules/<module>.json`, outside any
compiler implementation. They describe type/callable/constant semantics independently of C++
bindings. The consumer describes concrete and inferred generic free-function contracts; it is not
a declaration of complete runtime coverage or a replacement of v0.1 metadata yet.

## Shape

The opt-in `source` catalog provides owning source-buffer, line-index and location
types/calls under the [compiler-support contract](compiler_support_runtime_contract.md).
Borrowed span results are outside the current metadata lifetime vocabulary and
must not be advertised as ordinary copyable owned values.

Every module object requires these fields:

- `version`: integer `1`.
- `module`: nonempty lowercase ASCII letters/digits/underscore name, matching its filename.
- `dependencies`: ordered array of module names, without duplicates or cycles.
- `functions`: ordered array of function contracts.
- `cpp`: array of separate C++ bindings, joined to functions by `id`.

An optional `constants` array contains module-owned scalar constants. Omission means
no constants. The initial supported form is:

```json
{"name":"STR_PAD_LEFT","type":"int","value":0}
```

Each entry requires `name`, `type`, and `value`, and may specify `owner`. `name` is the nonempty,
case-sensitive source spelling; `type` currently must be `int`. `value` is a JSON
integer spelling in the signed-64 range, not a string, fractional number or exponent.
The JSON reader checks the range without floating-point conversion; the settled
value retains normalized decimal text. Other scalar constant types are deferred.
Duplicate names across the selected modules or collisions with language constants
reject the entire configuration. Nearer source declarations may still shadow them
under ordinary lexical lookup. Constants use the existing settled constant model
and value lowering, not function contracts or C++ symbol bindings. Their values are
part of the trusted runtime contract and must agree with the provider implementation.

An optional `owner` selects a registered nominal or template runtime definition, for example
`{"owner":"ticket","name":"LIMIT","type":"int","value":37}`.
These public immutable constants use `ticket::LIMIT` and are not published as free
names. Constants and methods have separate name spaces. Duplicate constants on the
same owner reject configuration before publication; ownerless constants retain the
global collision rules above. Expression-qualified access evaluates its qualifier
once, including an empty shared handle, under the ordinary static-member contract.
All applications of a template owner share the same constant identity and exact
value: `parcel<int>::LIMIT` and `parcel<string>::LIMIT` use a single `parcel` owner
registration. Owner applications in metadata are rejected, not enumerated. Constant
values remain literal integers, not type-dependent expressions. No C++ binding is
needed for settled constant values.

A function requires `id`, `name`, `parameters`, and `returns`. Identities are globally
unique across selected modules. Source names may repeat for overloads with different
parameter types; return type or passing mode alone does not distinguish overloads.
Each parameter requires a unique `name`, a `type`, and `passing` (`value`, `reference`,
`readonly_reference`, or `observe`). These names are metadata, not named-argument support.
Reference-bearing signatures participate in exact-type overload selection. Ordinary
call admission still checks reference places, access and lifetime after selection.
Reference/value or readonly/writable mode alone cannot distinguish overloads.

### Scalar parameter defaults

A value parameter may add `default` with exactly one typed scalar member:
`{"int":7}`, `{"float":2.5}`, `{"bool":true}`, or `{"string":"text"}`.
The explicit tag preserves integer versus floating intent, including an integral
floating value. Integers use checked signed-64 JSON integer spellings; floating
spellings retain their decimal text, and strings preserve decoded bytes.
Null, enum, container and expression defaults are not enabled in this catalog slice.

The default must have the exact parameter type; metadata does not ask the source
preparation worker to infer conversions. Required inputs cannot follow optional
inputs. Reference and observation defaults are rejected. Defaults do not distinguish
otherwise identical overloads. Overload selection admits omitted optional inputs,
still requires one exact candidate and does not rank candidates by defaults used.

Generic defaults do not infer variables: explicit arguments or the owner application
must first bind every variable. The default is then checked against its substituted
parameter type, including when the caller explicitly supplies that parameter. This
keeps each selected signature valid independently of one invocation's omissions.
Backend generation uses the same default-value snapshots and ordered argument path
as source functions. Providers need no C++ default arguments; all omitted values
are materialized by the compiler. This applies to registered free and member calls.

### Typed variadics and reference inputs

A final value parameter may add `"variadic":true`. Its `type` describes each
argument, not the pack; the installed callable stores `vector<type>` using the
same ABI as source functions. Its provider receives one vector, never C varargs
or a C++ parameter pack. Core must be selected for its vector definition. Packs
may be empty; reference/observation packs and explicit pack defaults are rejected.
The element must be copyable under ordinary source capability rules. Optional
fixed scalar inputs may precede a variadic pack.

Generic packs infer one exact element type from their supplied arguments. Repeated
elements and occurrences in other inputs must agree. An empty pack is valid if
other inputs or the owning application already bind its variables; an entirely
uninferred variable is not filled from the return context or a guessed type.
Fixed and variadic overloads receive no preference ranking: multiple exact
candidates, including two empty-pack matches, are ambiguous.

Concrete and generic `reference`/`readonly_reference` inputs reuse ordinary call
admission after exact overload selection. They are non-owning, call-scoped inputs:
metadata does not authorize retaining a reference after the call. Generic recipes
do not clone registrations per application. References require admissible storage,
and writable inputs reject readonly storage and temporaries. Pre-prepared overload
arguments are still checked against the selected consumer: `move` is not permitted
for references, observation or copying packs. Runtime reference-return contracts
remain outside this metadata slice.

### Non-retaining observation

`observe` describes a runtime-only, read-only input boundary, not an ordinary source
reference parameter. The provider promises not to mutate the input, retain a borrow,
or return a value that borrows from it. Admission requires the exact storage type,
but does not require copyability or a stable reference place. Locals, readonly
inputs, temporary values and property expressions may be observed without copying
or consuming their storage. Evaluating the supplied expression may itself have
effects; it runs exactly once.

The current bounded consumer accepts one fixed required observation input and a
value return. Defaults, variadics, reference returns, static factories and source
observation declarations are not enabled. An overload set cannot mix observation
and consuming/reference input contracts. The trusted provider is responsible for
the non-retention promise; metadata cannot prove arbitrary C++ implementation bodies.

C++ lowering passes a `const T&` view in the call's full expression. Argument and
receiver temporaries therefore survive the call; a property of a temporary owner
must not be staged into a reference whose owner has already been destroyed.
Multi-argument observation is deferred until argument-induced invalidation rules
are established. This does not relax ordinary source reference lifetime rules.

A type is either a source-exposed registered name such as `"string"`, or an application:

```json
{"name": "result", "arguments": ["dynamic"]}
```

Arguments recursively use this same representation. Resolution delegates identity,
arity and formation checks to the type registry; JSON contains no canonical type IDs,
source ASTs, C++ type spellings or compiler callbacks. Void is not a parameter type.

### Structural callable types

A type may instead be a structural callable signature:

```json
{"callable": {
  "parameters": [{"name":"value", "type":"int", "passing":"value"}],
  "returns":"int"
}}
```

`callable` cannot be combined with `name`, `arguments` or `parameter`. Its body
requires `parameters` and `returns`; parameter names must be unique. Inputs reuse
the existing value/reference/readonly-reference and typed-variadic forms. Defaults
and observation are not callable-type contracts. Callback reference returns are
not enabled by this format. The result may be `void` or another supported type.

The signature is interned through the same callable type registry as source
`function<...>` types. These types can occur recursively in metadata; surrounding
function/owner type parameters remain available inside their input and return
types. One generic recipe can therefore describe a transformation taking
`function<R(T)>`, without enumerating argument/result combinations. Fully typed
lambda signatures and erased callable values supply structural inference facts.
Single fixed signatures supply normal contextual lambda typing; overloaded or
generic calls require enough explicit lambda typing (or a typed callable variable)
to establish the arguments independently before exact selection.

Admitting a closure uses the existing erased-callable conversion: exact signature,
copyable captures and no borrowed capture lifetimes. Overload matching reads settled
signatures but does not bypass that final admission. Providers receive the ordinary
`std::function` representation; no C function-pointer or untracked raw closure ABI
is introduced. A provider receiving a callable by value may retain an owning copy.
Reference inputs retain their call-scoped, non-retaining contract. Mutable
reference parameters *inside* a callback signature describe its invocation, not
permission for the closure to capture borrowed storage or retain those references.

A C++ binding requires `id`, `symbol`, and `header`. Each function has exactly one
binding in its declaring module, and no orphan bindings are allowed. Symbols are
qualified names, not C++ expressions; headers are include-path spellings, not source
snippets. These are trusted toolchain catalogs, not executable user extensions.
The backend validates and consumes bindings; semantic call preparation does not.

Unknown fields, wrong JSON kinds, duplicate decoded keys, unsupported versions,
unresolved types, invalid template applications and duplicate contracts are errors.
There is no silently ignored future-schema field.

## Ordinary runtime type declarations

Optional `types` and `cpp_types` arrays declare ordinary runtime definitions and
their separate backend bindings. Omission means no declarations/bindings. This is
plain JSON decoded directly into typed records, not an external schema language.
For example, the core vector definition is:

```json
{
  "types": [{
    "id": "core.vector",
    "name": "vector",
    "kind": "class",
    "parameters": [{
      "name": "Value",
      "requires": ["value_storable"],
      "capability_dependencies": ["copyable_value"]
    }],
    "capabilities": ["copyable_value", "value_storable", "result_payload"]
  }],
  "cpp_types": [{
    "id": "core.vector",
    "symbol": "scpp::vector_t",
    "header": "scpp/vector_t.hpp"
  }]
}
```

Each type requires `id`, `name`, `kind` and `capabilities`. `parameters` may be
omitted or empty for an ordinary nominal type; a nonempty list declares an ordinary
type template. No redundant template flag is needed. `kind` is `class` or `struct`,
using the existing nominal/result-kind classification. This does not confer source
record fields, constructors, inheritance or shared-pointer allocation: the backend
binding represents the existing runtime carrier. Copy/storage capabilities are
separate from kind, and the provider must truthfully describe its implementation.

Type IDs are unique across selected modules, independently of function IDs. Source
names and parameter names are ASCII identifiers (`[A-Za-z_][A-Za-z0-9_]*`), with
unique parameters per definition. Type names cannot collide with selected types,
language types/aliases or modifiers. Qualified declarations and aliases are deferred.
Provider identity is the declaring module (`simple_cpp.runtime.<module>`) plus the
type's `id`, separate from source and C++ spelling.

The closed capability vocabulary is `copyable_value`, `value_storable`, `hashable`,
`comparable`, `result_payload`; arrays must be unique. Each parameter requires
`name` and explicit `requires` (including `[]` when unconstrained). Optional
`capability_dependencies` defaults to `[]`. Formation requirements decide whether
an application is valid. Dependencies instead gate the owner's declared capability
on that argument's same capability, without prohibiting formation. A dependency
must name an owner-declared capability. `result_payload` is an unconditional Result
transport opt-in and cannot be an argument dependency. Copy operations still check
effective copyability. These are registry contracts, never JSON callbacks or rules.

An optional parameter `default` uses the ordinary type-reference shape above. It
must be closed (no `parameter` variables), satisfy that parameter's requirements,
and follow any required parameters. Forward references are supported across the
selected declarations; cycles and unresolved names reject configuration. Defaults
may be nested applications. Definitions are installed before callable signatures,
and ordinary registry interning completes omitted arguments. The core hash recipe
uses `Value` requiring storage, then `Key` requiring hashability/comparison and
defaulting to `string`; both arguments gate hash copyability.

Each type has exactly one `cpp_types` binding in its declaring module, using the
same `id`/`symbol`/`header` fields as a function binding. No orphan entries, source
fragments, baked-in template arguments or alternative parameter order are supported.
The backend appends canonical type arguments in declaration order. The compiler
does not verify C++ symbols exist; generated-program compilation proves that boundary.

The core vector/hash definitions and their C++ bindings now come solely from this
metadata. Source publication enumerates registered exposures rather than a second
name list. Language-interpreted scalars, mixed/dynamic, nullable, Result and ownership
families remain compiler-owned: ordinary JSON declarations cannot claim those roles.
Type declaration alone grants no initialization/indexing/iteration protocol, borrowed
storage, non-type parameters or handle-lifetime contract. Indexing and iteration
require the independent opt-ins below, as does initialization.
The existing compiler composition selects core for its foundational
container types; custom modules using them declare a core dependency.
Protocol installation does not assume named core containers exist. Configuration
resolves every declared protocol reference privately before retention; missing
referenced types fail there, not on a later reset.

### Initialization opt-in

A type record may contain an `initialization` array of recipes:

```json
"initialization": [
  {"value":{"parameter":"Value"}, "nested":{"parameter":"Value"}, "where":{"Key":"int"}},
  {"key":{"parameter":"Key"}, "value":{"parameter":"Value"}, "nested":{"parameter":"Value"}, "where":{"Key":"int"}},
  {"key":{"parameter":"Key"}, "value":{"parameter":"Value"}, "nested":{"parameter":"Value"}, "where":{"Key":"string"}}
]
```

`value` and `nested` are required protocol type references. Omitted `key` selects
list input; a present `key` selects typed entry input. Optional `where` maps owning
parameter names to exact closed types. It expresses protocol constraints, not
enumerated applications: the hash recipes above apply to every `Value`. Unknown
parameters, unresolved types and overlapping same-shape recipes reject startup.
Empty brackets retain the existing list-first/keyed-fallback rule; braces are keyed.
No recipes means no construction opt-in.

Each type with recipes requires exactly one entry in the module's separate
`cpp_initialization` array, keyed by the type's `id`:

```json
{"id":"core.hash", "symbol":"scpp::data_initializer", "header":"scpp/data_initializer.hpp"}
```

The backend forms `Adapter<SelectedTarget>::from_list` or `from_key_value`, using
the existing synchronous read-only `initializer_list<Value>` or
`initializer_list<initializer_entry<Value,Key>>` input. The adapter returns the exact
selected target. It may copy values, but must not retain the supplied sequence or
references into its temporary backing. The provider owns empty-input behavior,
key policy and duplicate-key rejection; no overwrite-on-duplicate policy is implied.
The compiler retains existing ordered evaluation, input lifetime, contextual nested
construction, copy checks and preparation diagnostics. These are initialization
protocol factories, not extra source-visible methods or permission for arbitrary
`new`. Missing/orphan adapter bindings reject before configuration publication.

Core vector/hash use these recipes and bindings. Boxed mixed/dynamic initialization
and internal input-sequence identities remain compiler-owned language contracts.

### Indexing and iteration opt-ins

A type record may contain `indexing` and/or `iteration`. Each declares one recipe
for its definition, not registrations for each concrete application. For example:

```json
"indexing": {
  "key": "int", "value": {"parameter":"Value"},
  "nested": {"parameter":"Value"}, "path": {"parameter":"Value"},
  "write": true, "append": "allowed", "probes": true
},
"iteration": {"key":"int", "value":{"parameter":"Value"}}
```

Indexing requires `key`, `value` and `nested` (the contextual nested literal type).
Optional `path` permits descent through an element; omission does not authorize it.
`read` is `checked` (default) or `null_on_miss`. `write`, `probes` and `removal` are
independent booleans defaulting to false. `append` is `unsupported` (default),
`allowed`, or `integer_key`; append requires write support. The integer-key rule
uses the selected key type, allowing integer-keyed hash append but not string-keyed
hash append. Iteration requires `key` and `value`; it grants value iteration only,
not reference iteration or mutation-during-iteration semantics.

Each protocol type reference is a closed registered type or an explicit owning
parameter (`{"parameter":"Value"}`). Unknown parameters and non-storable closed
types reject configuration. Nested open recipes are not yet supported by the
existing bounded protocol-reference model and reject instead of using placeholders.
The selected application substitutes the references through ordinary preparation.
Metadata supplies no per-application rows or provider-name processing branches.

The C++ type binding's header must expose the runtime helper overloads for every
promised operation: `scpp::checked_index_read`/`null_index_read`, `index_store`,
`index_append`, and the existing probe/removal/path helpers where enabled. Custom
write-key types also require `scpp::require_write_key`. Iteration supplies
`scpp::value_iteration`, whose entry view provides `key()` and `value_copy()`.
The provider owns checks, copy/storage behavior and helper implementations; metadata
does not verify C++ implementations or relax evaluation/lifetime rules. Generated
C++ compilation tests this ABI. No arbitrary executable snippets are accepted.

Core vector/hash indexing and iteration now use these metadata recipes. Mixed and
dynamic retain language-owned registrations. Initialization is a separate
opt-in; indexing or iteration never silently enables literal construction.

## Type-owned callable membership

Function records may add a `member` object:

```json
{
  "id": "example.ticket.open",
  "name": "open",
  "member": {"owner": "ticket", "dispatch": "static"},
  "parameters": [{"name": "value", "type": "int", "passing": "value"}],
  "returns": "ticket"
}
```

Omission declares a free function. Membership requires a registered runtime nominal or template
owner and explicit `static` or `instance` dispatch. These are public exported
contracts; no source AST, private provider implementation or inheritance is fabricated.
Names are indexed by owner separately from free-function lookup. A static factory
is an ordinary static callable returning its owner, not permission for arbitrary
source `new`. Special double-underscore method names are not admitted here.
The owner names a definition, never an enumerated concrete application. Template
owner parameters are available by their declared names in the method's type patterns.
The selected owner application binds them before argument inference; defaulted owner
arguments are already resolved by the type registry. Method `type_parameters` declare
additional input-inferred variables and must not shadow owner parameter names.
A zero-argument method may therefore return its owner's `T`. Repeated occurrences
must agree exactly; an argument cannot re-infer an already selected owner argument.

For example, `"member":{"owner":"parcel","dispatch":"instance"}` with no
parameters and `"returns":{"parameter":"T"}` describes `read()` once for every
`parcel<T>`. Both type-qualified `parcel<int>::make(7)` and expression-qualified
`$parcel::make(7)` use the selected application. The syntax qualifier reuses the
ordinary type AST/parser, independently of metadata.

For nominal owners, static bindings name their qualified C++ callable (including a free factory adapter).
For template owners, static bindings name an unqualified member identifier; the
backend qualifies it with the selected owner's C++ payload application. Arbitrary
template-expression bindings or free-factory template adapters are not implied.
Instance bindings name an unqualified C++ member identifier, not a source fragment.
All calls use ordinary argument boundaries and overload admission. Shared receivers
are evaluated/retained once and checked before argument evaluation; stable inline
receivers are borrowed under existing source-method restrictions. Instance methods
currently have the same mutable receiver admission as source methods. Expression-
qualified static calls evaluate the qualifier once without dereferencing it.
Non-retaining observed inputs stay alive for the call, including temporary inputs.
Member properties and constants are separate contracts, not callable metadata.

## Registered properties

An optional `properties` array declares public provider-owned storage:

```json
{"id":"ticket.value", "owner":"ticket", "name":"value", "type":"int",
 "static":false, "access":"writable"}
```

`id`, `owner`, `name` (without `$`) and `type` are required. `static` defaults to
false; `access` defaults to `writable` and also accepts `read_only`. Owners must be
registered nominal or template runtime definitions and property types must be value-storable. Names
are unique per owner within the property namespace, independently of methods and
constants; property IDs are unique across selected modules. Property type patterns
may reference owner parameters, for example `"type":{"parameter":"T"}` for owner
`parcel`. Nested type applications use the same patterns as callable signatures.
Preparation substitutes the selected owner's arguments and checks storage capability;
neither JSON nor backend bindings enumerate concrete applications.

Every property requires a separate `cpp_properties` binding with `id`, `symbol`
and `header`. Instance symbols are unqualified field identifiers; static symbols
name provider-owned static storage for nominal owners. For template owners, static
symbols are unqualified identifiers; the backend supplies the selected payload
application qualifier, preserving separate C++ static storage per application.
No getters, setters, source fragments or synthesized
provider definitions are implied. Providers own initialization and storage lifetime.

Reads and plain writes share source-property preparation and conversion boundaries.
`read_only` rejects mutation through that property path. Shared receiver interiors
cannot escape as native references; stable inline receiver projections retain existing
borrow rules. Static-property reference escape remains deferred. Wrong types, unknown
members and instance/static mismatches reject during preparation; null-handle checks
remain runtime behavior. Expression-qualified static access evaluates its qualifier
once without requiring a present handle. This grants no source construction or
inheritance semantics to the provider type.

## Registered storage policy

Ordinary type records may specify `"storage": "inline"` (the default) or
`"storage": "shared"`. The policy is registered per definition and applies to
all its template applications; no concrete argument combinations are enumerated.
Nominal kind alone does not select runtime storage. C++ bindings always name the
provider payload/carrier; shared storage adds the existing shared-handle wrapper.

Shared stored values copy handle identity, never the payload. Storage and copying
of the handle are available independently of payload capabilities; declarations
and conditional capability dependencies continue to describe the payload.
`result_payload` remains an explicit transport opt-in. Existing null checks,
reference-slot updates, value arguments and Result transport reuse ordinary paths.
Reference to a handle slot is distinct from mutation through a copied handle.

Opaque shared provider types currently reject explicit `value<T>` and ownership
rewrapping, including `shared<T>`, `unique<T>` and `weak<T>`. Those transformations
need a separate supported payload/ownership contract. Storage does not enable
source `new`, public members, data initialization, or inheritance. This bounded
restriction does not change source-declared class/struct ownership forms.

## Inferred generic runtime contracts

A function may add a nonempty `type_parameters` array of unique names. A type
variable is spelled `{"parameter":"T"}`, distinct from the registered name `"T"`.
It cannot also have `name` or `arguments`. Variables may occur recursively in input
and return types; every declared variable must occur in at least one input. Return
context does not infer variables. Unbound/unused variables and invalid application
arity reject during catalog installation. Omitted template arguments use the type
registry's defaults, not wildcards.

For example, the vector count contract is:

```json
{
  "id": "core.count.vector",
  "name": "count",
  "type_parameters": ["T"],
  "parameters": [{
    "name": "value",
    "type": {"name": "vector", "arguments": [{"parameter": "T"}]},
    "passing": "observe"
  }],
  "returns": "int"
}
```

Generic inputs support fixed value/reference parameters, typed variadics, scalar
defaults and the bounded unary observation contract. User-authored generics,
explicit source type arguments and explicit variable capability constraints remain
outside this slice. Variables impose no implicit copyability constraint: the formed
type and selected value boundary own storage/copy requirements.

Installation resolves named parts to registry identities once, retaining a type-pattern
recipe rather than a placeholder concrete signature. Preparation evaluates argument
types once, matches exact application identities and ordered arguments, and requires
repeated occurrences of a variable to agree exactly, including storage modifiers.
It does not rank conversions or prefer concrete/more-specific patterns: zero matches
or multiple matches are preparation errors. Equivalent positional recipes with renamed
variables are duplicate contracts. Other overlapping recipes are rejected when a call
matches more than one. Untyped data literals need an explicit target where inference
cannot determine their type independently of the candidate.

Only the selected recipe produces a concrete call-owned signature. Application
formation uses the ordinary registry and preparation capability/dependency queries.
No source-derived capabilities or per-call signatures are cached in the provider.
The C++ backend reads the selected signature and the original provider binding;
C++ template argument deduction handles its runtime implementation. Parsing and
collection contain no runtime-function-name-specific paths.

## Loading, ownership and publication

The composition root supplies a catalog directory and selected modules. Load each
selected/dependent module once, dependency-first, directly through the shared JSON
pull reader into typed metadata. No mixed/dynamic or generic document tree is built.
Complete document/schema validation precedes publication; failed loading/configuration
does not replace the previous configuration.

Metadata is retained in memory as immutable-by-contract startup input. Install ordinary
type recipes into the private registry first, then resolve signatures and constants
into separate private inventories against that canonical type
registry; only validated identities enter language scope. C++ bindings are installed
separately by the backend under coordinator sequencing. Ordinary compilation and
incremental updates perform no metadata IO or JSON parsing.

Exported names support root-qualified lookup and ordinary body-local imports,
including aliases of generic type definitions. Root source declarations shadow
runtime exports per symbol kind; imports neither copy nor re-export identities.
Metadata module names do not create source namespaces.

Semantic registry resets reuse retained typed metadata but recreate canonical type,
callable, constant and backend-binding identities. Old targets cannot reuse new bindings merely
because their string IDs match. Catalog edits take effect in a new compiler session;
live catalog replacement/hot reload is outside this slice.

The current my-try host selects `core`, `json`, `strings`, `datetime` and `filesystem`, preserving the
JSON/string APIs and enabling the established strict datetime/filesystem surfaces. Native validation snapshots
these same files and supplies their location
explicitly; installed/distributed compiler packaging must likewise supply resources.
Runtime-link/build-module policy remains with build owners. The backend publishes
the selected dependency closure's build requirements for the native consumer below.

## C++ build requirements

Modules may declare an optional `cpp_build` object; omitted arrays are empty:

```json
{"cpp_build": {
  "sources": ["modules/regex/regex.cpp"],
  "libraries": ["pcre2-8"],
  "definitions": ["EXAMPLE_MODE=1"]
}}
```

Sources are relative `.cpp` paths below the configured runtime include root, with
no absolute paths or traversal. Libraries are logical linker names, not flags or
shell commands. Definitions are `NAME=VALUE` tokens (identifier names and nonempty
alphanumeric/underscore/dot/minus values); conflicting values reject configuration.
Modules cannot override the PHP language-target definition. Arbitrary flags, build
scripts, package installation and additional search roots are outside this contract.

The selected closure contributes sources once in dependency-first order and libraries
in consumer-before-dependency order. Repeated entries are deduplicated; identical
definitions are shared. Requirements apply to the whole selected configuration,
not only referenced functions. Headers remain per-symbol backend bindings.

The coordinator publishes this resolved snapshot as `runtime_build` alongside the
artifact manifest. Native builds consume that authenticated snapshot without reopening
catalogs. Provider sources use ordinary object dependency tracking; libraries enter
the link key. Definitions apply consistently to generated units, providers and the
runtime PCH, and participate in their cache identities. Provider objects themselves
do not consume the generated-program PCH. Old publications lacking the snapshot
must be republished; there is no implicit core-source fallback.

## Initial contents and next boundary

`regex` is available as a separately selected catalog; it is **not** added to the
default module selection. It depends on core and preserves the existing opt-in
PCRE2-8 requirement. Its metadata supplies `modules/regex/regex.cpp` and PCRE2-8 to
the native builder; PCRE2 must already be installed. Its bounded
contents are `regex_jit_available`, both `regex_quote` arities, two/three-argument
forms of `regex_match`, `regex_match_named`, `regex_match_all`,
`regex_match_all_pattern_order` and `regex_match_all_named`; two-argument
`regex_grep`; three-argument `regex_replace`; and two/three/four-argument `regex_split`.
It exposes `PREG_SPLIT_NO_EMPTY=1` and `PREG_SPLIT_DELIM_CAPTURE=2` as int constants.
These are existing contracts from [regex intake](builtins/regex/first_pass.md), not
new aliases or implicit container-to-bool conversions. Empty successful captures
remain successful Result values. Filter, callbacks, count outputs and limited
  replacement remain outside this bounded catalog pending the
[documented prerequisites](../compiler/my-try/docs/planning/runtime_catalog_coverage.md#regex-module-inventory--2026-10-09).

- `core`: `strlen(string): int`, counting bytes through `scpp::str::byte_length`;
  `count(vector<T>)`, `count(hash<T,K>)`, `count(mixed)` and `count(dynamic)`, all
  observing inputs and returning `int` through `scpp::count`. Boxed kinds are checked
  at runtime; statically unsupported carriers reject during preparation.
  `memory_get_usage` and `memory_get_peak_usage` each expose zero arguments or one
  bool `real_usage` argument and return int process-memory byte observations. These
  retain the [runtime catalog](../runtime/specs/catalog.md#54-processmemory-helpers)
  contract, not PHP allocator accounting. Both bool values use the same measurement
  policy; unavailable measurements may return zero. Stable hashing is not registered
  pending algorithm/persisted-key compatibility review.
  `cli_argc(): int`, `cli_argv(): mixed` and `cli_args(): mixed` expose executable
  arguments through ordinary registered calls. The generated executable initializes
  the runtime CLI context once before authored entry code, using native argc/argv.
  Index zero is the program name; argument strings preserve their bytes, including
  empty strings and spaces. Each argument-container query returns an independent
  mixed-family value snapshot, not dynamic storage or a mutable view of native argv.
  This adds neither implicit source globals nor a public `set_cli_args` function.
  `getenv(string): result_or_false<string>` reads one environment variable; absence
  is false and a present empty value is successful. `shell_exec(string)` returns
  the same wrapper family: failure means the command pipe could not be opened;
  empty stdout and a nonzero child exit status are not false states. The host shell
  interprets the command, so callers must not concatenate untrusted input into it.
  Output is captured as bytes, including embedded NUL. These bindings use the
  existing PHP process provider, not managed child-process handle APIs.
- `json`: existing dynamic/mixed decode contracts and both encode overloads, preserving
  Result wrapping and family separation.
- `datetime`: all eleven strict `dt_*` helpers from the datetime builtin contracts.
  Monotonic microsecond/nanosecond results remain `uint64`; parsing returns `result<int>`;
  sleep returns `void`. Legacy `time`, `date` and `strtotime` aliases are not enabled.
  Metadata supplies the datetime provider source as well as the core dependency
  to the native build consumer.
- `filesystem`: nineteen strict `fs_*` helpers: path probes, whole-file get/put,
  directory creation/scanning/removal, size/mtime/touch, entry removal/copy/rename,
  canonical/lexical paths and checked `fs_read_snapshot`. Fallible reads return
  `result<string>`, `result<int>` or `result<vector<string>>`; ordinary mutations
  and probes retain their boolean contracts. The snippet `core/runtime.cpp` already
  bundles the filesystem and snapshot providers; do not link duplicate standalone
  objects beside it. File-lock handles require registered
  runtime types and remain unregistered. `fs_scan` uses shared container-payload
  Result operations without a module-specific extraction path. Internal `scpp_output_*` portability
  bindings are not public my-try source APIs; see [output ownership](portability/output_files.md).
- `strings`: `strtolower`, `strtoupper`, `lcfirst`, `ucfirst`, `str_contains`,
  `str_starts_with`, `str_ends_with`, both arities of `trim`/`ltrim`/`rtrim`, `bin2hex`
  and three-string `str_replace`, plus `str_pad` (2/3/4 arguments) and `number_format`
  (int/float, 1/2/4 arguments), bound to the existing `scpp::str` implementations.
  Padding constants are `STR_PAD_LEFT = 0`, `STR_PAD_RIGHT = 1`, `STR_PAD_BOTH = 2`.
  Formatting does not admit bool/string/mixed/dynamic inputs in this slice; boxed
  numeric-kind validation remains deferred. Padding validates empty padding and
  invalid modes even when the requested length would otherwise be a no-op.
  Case changes are ASCII/byte-oriented, not Unicode case folding. Predicates are
  byte-oriented and accept an empty needle. These fixed contracts use ordinary
  value arguments: ordered argument snapshots remain alive through the runtime call;
  this does not enable multi-argument observation.
  Trim masks are byte sets (no range expansion); omitted masks select the runtime's
  one-argument overload. Hex encoding is lowercase and binary-safe. Replacement is
  non-overlapping with an empty-search no-op; array forms and a count output are not
  enabled. Multiple documented arities use separate fixed contracts, not optional
  metadata parameters or new default-argument lowering.
  The module also registers `explode` (2/3 args, `vector<string>`), `implode`
  (`vector<string>` or inferred `hash<string,K>`), code-point `substr` (2/3 args),
  byte-based `substr_compare` (3/4/5 args), and `hex2bin` with its documented
  `result_or_false<string>` contract through the existing PHP adapter.
  Explicit byte helpers (`string_byte_len`, `string_byte_at`, `string_byte_find`,
  `string_byte_slice`, `string_byte_slice_equals`), UTF-8 helpers
  (`string_utf8_codepoint_count`, `string_utf8_codepoint_at`,
  `string_utf8_slice_codepoints`) and conservative grapheme helpers
  (`string_grapheme_count`, `string_grapheme_slice`) retain their existing units.
  `string_byte_find` returns `result_or_false<int>`, preserving position zero as
  success; internal nullable/Result helpers are not substituted for public adapters.
  All these added parameters use ordinary value boundaries, not observation.

  The strings catalog also declares the two ordinary value types
  `string_parts_builder` and `text_builder`, with storage/copy capabilities and
  separate C++ bindings. Twenty helper signatures cover creation, reservation,
  queries, string/int/bool appends, materialization, clear and text take-string.
  Mutators use writable-reference builder inputs; unary queries use non-retaining
  observation. All use shared call admission/lowering, not builder-name dispatch.
  The [builder contract](compiler_support_runtime_contract.md#v02-metadata-backed-builder-calls)
  owns copy, mutation and lifetime semantics. Byte-span append remains unregistered
  until the source module's span/view model is available.

The strings module's unresolved search-coordinate and replacement contracts, boxed
joining/formatting, and builder byte-span append are excluded from executable JSON.
See the [module inventory](../compiler/my-try/docs/planning/runtime_catalog_coverage.md#strings-module-inventory--2026-10-09).
The byte-length decision for `strlen` does not change `substr` or explicitly named
Unicode APIs. Explicit UTF-8 helpers reuse runtime behavior; they do not add the
separate portable-compiler framework's UTF-8-validation contract.

Fixed-array non-type parameters, compiler-module
Storage registration, non-scalar defaults,
reference-return lifetime contracts,
profile availability and the rest of the runtime inventory are not encoded by this
version's bounded consumer yet. Extend the shared models before enabling these rows;
do not generate fake concrete overloads or function-name-specific preparation paths.

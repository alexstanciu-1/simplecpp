# Metadata-driven public API generation
Doc Status: normative

This physical generation contract is subordinate to language semantics, runtime
module metadata and [the public boundary rules](core_public_api.md). It does not
admit source-language programs or infer semantic types. This first slice replaces
the scan/vector/Result-specific emitter, not every runtime API generator.

## Ownership

- `runtime/metadata/modules/*.json` owns native representation bindings.
- `runtime/metadata/public_api.json` selects public exposure, type transport,
  lifecycle, signatures, explicit dependencies and adaptation snippets.
- `runtime/specs/public_specializations.json` selects runtime publication demands.
- `runtime/tools/api_generator/` owns reusable C++ parsing, naming, dependency
  planning, storage/lifecycle emission and operation emission. No named-family
  switches belong here. The library entrypoint is `scpp::api_generation::generate`;
  it accepts/returns owned runtime strings containing prepared JSON documents.
- The native runtime retains behavior, including iteration preservation and Result
  extraction semantics. Metadata snippets bind that behavior; they are trusted
  build inputs, not untrusted source-language programs.
- `runtime/tools/concrete_types.py` is a process/filesystem driver: compiler ABI
  checks, target probes, generation staging and existing package validation. It
  does not emit C++ declarations or operations or resolve template families.

The focused tooling header is `scpp/api_generation.hpp`, in its own public include
root; private metadata/naming headers are not exposed to library consumers.
The static C++ library is `scpp_api_generation`; `scpp_api_generator` is a thin
stdin/stdout CLI. The compiler can call the library without launching the CLI or
invoking Python. The API currently transports JSON rather than exposing its private
model containers; no STL or template types enter that library's public header.
The library is reentrant and has no mutable generation-global state.

## Metadata vocabulary (api-v1)

`types` maps prepared leaf identities to `cpp`, `header`, `owner`, and optional
`native`, `borrow`, `construct`, `includes` fields. `borrow` describes adaptation of
an argument; `construct` describes constructing an owned public result from a native
binding result. The existing concrete string/error bridges implement this transport.
Application leaf definitions may add types but may not redefine runtime identities.

`families` contains parameter names, optional trailing `defaults`, an existing native `binding` when needed, a
parameterized `native` representation, explicit private `includes`, target-probed
`checks`, a list of `classes`, and optional free `functions`. A companion class is an
ordinary class entry, not a generator-recognized cursor. All classes are declared
before their signatures; concrete parameter dependencies are materialized first.
A family's first slice requires copyable, assignable, nothrow-destructible payloads
through explicit metadata checks. Unsupported payloads fail target probing.

Each class has `id`, `name`, `native`, `lifecycle`, `methods` and optional `includes`.
Lifecycle entries `default`, `copy`, `move`, `destroy` select `native` or `disabled`;
inline storage requires native nothrow destruction. Disabled copy/move operations
are explicitly deleted. Enabled operations are target-probed, and destruction and
layout are also asserted in the emitted implementation. Actual native objects live
in their probed inline storage; representations are never byte-copied as unrelated
classes. Native-result factory construction supports nonmovable objects directly
in final storage.

An operation describes `name`, `kind` (method/constructor/function), `returns`,
`parameters`, optional `const`, `static`, `noexcept`, `explicit`, `transport`, `uses`,
`includes`, and exactly one `expression` or `body`. Constructor expressions supply
native constructor arguments. Parameters specify `name`, `type`, and `passing`
(value/const_ref/ref/move_ref). Selected named signatures and dependencies are
resolved structurally; the tool does not parse C++ to discover snippet dependencies.
Unknown fields, bindings, placeholders and selected types are errors.

`static: true` selects a class-owned method without a receiver. It cannot be a
constructor/free function, `const`, or `explicit`; `${self}` is unavailable. Its
parameters and owned return transport use the ordinary operation mechanism, so a
metadata-defined factory can construct native storage directly without public tag
types or a payload copy. Only the declaration carries the C++ `static` qualifier.

Substitutions include:

- `${name}`, `${namebase}` for the selected concrete name.
- `${public:T}` / `${native:T}` for parameter or companion representations.
- `${self}` for the native object, preserving method constness.
- `${public:argument}` / `${native:argument}` for arguments.
- `uses` explicitly resolves additional type dependencies and makes their public/
  native representations available by identity, e.g. `${public:vector<string>}`.
- Leaf transport snippets use `${value}`, `${factory}`, `${cpp}`, `${native}`.

A body uses ordinary C++ statements and `return`. For a value-returning operation,
the generator places it in an immediately invoked `decltype(auto)` lambda, retaining
the binding result's references and value category. `transport: public` returns that
result through the declared public signature (an owned return copies a borrowed
value). `transport: native` applies the result type's `construct` binding to the
factory. Void bodies run directly. Snippets must not return dangling references;
compilation alone cannot prove arbitrary C++ safe. Native helpers and focused
lifetime/failure tests remain essential. No new snippet control-flow language exists.

## Template defaults

Defaults are type expressions attached to the trailing parameters. For example:

```json
{
  "parameters": ["T", "U", "V"],
  "defaults": ["string", "sequence<T>", "U"]
}
```

The resolver processes arguments left to right. Explicit arguments are normalized
first; an omitted argument substitutes already-resolved earlier parameters into its
default expression, then resolves the resulting type. References are whole type
nodes, not text replacement: `T` can appear inside `vector<T>`. Chained defaults and
explicit overrides therefore work without a family-specific rule. Earlier argument
aliases are normalized before substitution. A parameter cannot be used as a template
family (`T<string>`); template-template parameters are not part of this schema.

All-default families accept both the bare family name and `family<>`. Empty argument
lists on leaves are rejected. Required parameters still require explicit arguments.
Parameter names must be unique, defaults must be a trailing list no longer than the
parameter list, and a default cannot reference itself or a later parameter, even
when all arguments are supplied explicitly. Undefined selected types are rejected;
a depth bound rejects recursive cross-family default expansion without stack overflow.

Expansion happens before canonical identity, concrete naming, import lookup and
layout dependency planning. Omitted, partially explicit and fully explicit equivalent
forms share one definition and reuse the same imported specialization. Inner concrete
representations are resolved before outer storage is emitted.

The shared naming registry retains its `parameters` kind list and optional
`parameter_names` list; API metadata supplies that name list automatically. Existing
constant defaults without names remain supported. The naming registry also supports
its existing admitted integer values in dependent defaults; this does not add
value-parameter emission to the public API recipe schema.

## Cursor contract

The selected vector cursor is native `vector_range<public T>` in inline storage.
It is neither default-constructible, copyable nor movable. `iterate()` constructs it
directly. `valid()` and `key()` are non-const because the native range's accessors
are non-const; no generated const_cast is needed. Consumption follows
`valid -> value once -> next`. For vector, hash, mixed and dynamic cursors,
`next()` on a finished cursor is a no-op; finished `key()`/`value()` reads reject.
This also covers preservation that exhausts a cursor after its last consumed value.
Repeated reads after consumption/mutation are not an
additional public guarantee. Native traversal preservation, owned values, mutation
failure handling and survival beyond owner destruction remain unchanged.

## Naming, probing and publication

Canonical resolution and concrete naming are owned by C++ `names.cpp`. The existing
Python Registry interface delegates to it; its parsing/structural helper functions
remain compatibility utilities, not another canonical resolution path. Registered
aliases, family arity/defaults, escaped depth names and mapped integer arguments
retain the previous naming protocol. This physical API recipe schema currently
materializes type-parameter families; new value-parameter generation is future work.

The pure library consumes prepared descriptors; imported publication identities
must already have been verified by the calling build/publication layer. It does
not read dependency files or bypass the driver's ABI/integrity checks.
The pure library returns a deterministic plan: generated units, signature identities,
layout requests and metadata capability checks. The driver compiles a probe for each
dependent unit, combining companion layouts in that invocation, then supplies results
to the same library. It stages output and installs only after all probes and stale
inventory checks pass. This is not cross-compilation support: target probe execution
must be available. Public headers must remain independently template-free.

Published packages carry the generator executable and library/header, API metadata,
existing SDK and concrete export inventory. `compiler_api_generation` exposes only
the tooling header and public runtime headers. The existing publisher seals metadata,
SDK headers, archives and generator executable; import also checks API metadata identity
and compiler/target ABI. Import reuses compatible definitions before generating
application-owned demands. Python bytecode caches are excluded from source identities.
No separate runtime-public-middleware product or semantic registry is introduced.

## Reproduction and scope

Build the tool with `cmake --build <runtime-build> --target scpp_api_generator -j8`.
For a nonstandard build directory set `SCPP_API_GENERATOR` to that executable.
Then use `python3 runtime/tools/concrete_types.py --request
runtime/specs/public_specializations.json --output runtime`. Published copies use
their shipped executable. The source-tree driver can incrementally build the tooling
target in the compiler's configured runtime build; ordinary compiler builds retain
explicit runtime/middleware publication boundaries.

The first closure includes scan, vector<string>, its cursor, Result<vector<string>>,
existing concrete leaf transports, typed-hash composition and an app-defined stored
record. Leaf integer/string/error implementations are not regenerated by this slice.
Other families, reference-valued source semantics,
full scalar operations and compiler facade migration remain separate work.

## Capability conditions and typed hashes

The optional top-level `capabilities` object defines named physical capabilities.
Each entry has nonempty `checks` (C++ target-probe expressions) and optional private
`includes`; `${cpp}` and `${native}` refer to the checked representation. Leaf and
family recipes declare an optional `capabilities` array. A family may also declare
`conditional_capabilities`, mapping capability names to `requires_capabilities`
conditions on its arguments. The shared capability owner resolves these through
normalized argument/default bindings and publishes the resulting sorted facts.
Companion classes do not inherit the primary class's guarantees.
These are explicit exposure guarantees, not source-language admission or an
exhaustive discovery of native C++ operations. Missing facts do not prove native
impossibility. Family facts travel with their published concrete records.

A family or operation can specify `requires_capabilities`, mapping parameter names
to arrays of capability names. All listed capabilities are required. A failed family
requirement rejects the specialization; a failed operation requirement omits that
operation before resolving its dependencies or expanding its body. Unknown names,
parameters, duplicate claims and malformed lists fail generation. Required claims
are checked in the same target probe as the consumer's layout, with duplicate checks
removed. Unused claims do not pull their private headers into unrelated probes.

For example, typed hash requires `K: [typed_hash_key]`, while its `append` operations
require `K: [integer_key]`. These names and native checks belong to metadata; the
C++ generator has no hash/integer dispatch. This does not admit arbitrary native key
policies as source-language hash keys. Shared identity keys and mixed/dynamic families
remain outside this slice.

`${type:T}` expands a parameter's normalized canonical type expression, rather than
its C++ name. It lets a signature request `nullable<${type:V}>` through the same
resolver used for root demands, including nested parameters and imported types.

The typed hash recipe uses inline `hash_t<public V, public K>` storage, a default
string key, owned checked reads, non-inserting `find`, explicit writes/removal and
an insertion-order companion cursor. Its read-only `operator[]` delegates to `at`,
never the native inserting operator. Cursor keys and values are owned; the native
preservation/retirement implementation controls mutation and escaped lifetimes.
The nullable recipe supplies owned checked extraction, reset, fallback, constructors,
assignment, `take`, `was_found` and `is_nullopt`. Absence is not an error or stored null.
It does not expose interior references or the native optional.

Concrete integer append uses the private `hash_detail::integer_key_policy`, alongside
existing concrete key hashing/equality adapters. The existing next-key and overflow
algorithm remains the authority for both native and concrete integers; no global
numeric trait opt-in or metadata copy of the algorithm is introduced.

Publication imports treat a structural name ending in `_` as an assertion of the
canonical generated name, not as an authored alias. A mismatch is rejected; normal
authored-name restrictions remain unchanged. This permits application-owned nested
specializations to be reused even when they have no friendly name mapping.

## First mixed value surface

`mixed` and `float64` are zero-parameter recipes using the same class, lifecycle,
transport and companion mechanisms as parameterized families. No generator dispatch
or new signature form is needed. Runtime publication demands `mixed`, which pulls in
`float64`. The existing `null_t` declaration and `null` constant now live in the public
include root; the compatibility header forwards to that same definition.

`float64` stores native `float_t` inline and exposes construction from native double
or concrete int64 plus native-value inspection. This first scalar slice does not
claim the full floating operator surface. Double is an explicitly described native
interchange leaf, not a templated dependency. Mixed construction/assignment accepts
null, boolean, int64, float64 and string. Exact extraction returns owned public values;
it is not a permissive scalar cast. Numeric conversions/operators remain later work.

Mixed reads bind `null_index_read`, not the permissive legacy `mixed_t::get`: null or
missing entries produce mixed null, but scalar receivers reject. Writes use the
existing checked write helpers, including null bootstrap. Removal probes before
mutation, so an absent/null receiver is not turned into a table. Self-aliased writes
snapshot the input before mutating the receiver; nonaliased writes avoid that extra
copy. These adaptations are short metadata snippets, not new container algorithms.
`reset` and `clean` replace the binding with native null. `make_mixed_table` constructs
an explicitly empty owned mixed table, distinct from null.

The mixed companion cursor uses native `bound_range`, via `value_iteration`, rather
than a direct hash cursor. It therefore observes replacement/move/reset of the boxed
binding as well as table mutations. Keys and values return owned mixed values; null
and scalar traversal reject. The mixed cursor's `next()` is a no-op when finished, including when preservation
exhausts the remaining entries immediately after consumption; key/value reads still
reject a finished cursor. Internal pointers, mutable extraction, weak-table hooks
and native ownership carriers are not exposed. No dynamic/container cross-family
conversion is introduced. Public nullable/mixed recursive dependencies and implicit
C++ conversions remain outside this slice.

## First dynamic value surface

The `dynamic` zero-parameter recipe binds native `dynamic_t<>` entirely behind the
public header. It uses the same scalar types and generation mechanisms as mixed;
no C++ generator change or family-name branch is required. It owns `clean(dynamic&)`,
`make_dynamic_container`, and the explicit `to_dynamic(mixed)` / `to_mixed(dynamic)`
free functions. Both conversions delegate to the existing native cast owner, preserving
scalar/null kind and value and rejecting containers before a destination assignment.
The mixed recipe does not depend back on dynamic, so this introduces no declaration
or layout cycle. No implicit family conversion, truthiness or operator API is added.

A dynamic container copy shares identity. Owned return transport creates an owned
binding, not a deep copy of its container. Scalar/string copies retain value semantics.
Reset, assignment and `clean` replace a binding without clearing the containers of
other aliases. Writes into null reject; explicit container construction is required.
Reads, keyed removal and writes use the same native checked helper owners as mixed,
with their distinct dynamic behavior. Self-aliased writes retain native shared identity.

The binding-aware cursor returns dynamic values and scalar mixed keys (the existing
integer/string key domain), never mixed container entries. It survives mutation,
replacement, move and destruction under the existing preservation rules. Finished
`next()` is idempotent; finished key/value reads reject.

Strong-reference cycles remain supported and are not automatically reclaimed by
reference counting. This API does not reject them, weaken edges or introduce a
collector. Cycle reclamation is recorded as runtime debt in the public API plan.
Tests deliberately remove their cycle edges before checking leak freedom; this is
not evidence of automatic cyclic garbage collection.

## Global operator resolution and normalized publication (first slice)

`global_operators` in the same API metadata owns type-to-domain membership, each
numeric domain's `maximum`, group admission rules and operation bindings. The C++
`generate` entrypoint accepts `action: "resolve_operator"`, `metadata` and a `request`
with `operation`, `left` and `right` canonical type identities. It returns the common
`operand_type`, `result_type`, normalized `api`, function and header. It does not
execute operands, insert source casts, or grant source-language admission. The caller
must evaluate and convert the left operand before the right, following the language
contract. Explicit casts are resolved before this query.

Rules match domain pairs, not individual type combinations. `same_or_maximum` keeps
identical types and otherwise chooses the named domain maximum; `maximum` always
chooses that maximum. Missing rules reject; duplicate domain rules and unknown
policies reject. Signed/unsigned have no shared arithmetic rule. No generator branch
recognizes integer, float, mixed or dynamic names. Representation and transport stay
in the existing leaf/family descriptors rather than a second type registry.

Each operation defines a group, public function name, expression snippet, publication
list, optional result type and private includes. `${public:Selected}` denotes the
normalized type. Omitting `returns` selects that type. The same operation emitter
publishes one implementation per selected type, not one per original operand pair.
The original slice exposed `numeric_add` and `boxed_equal`/`boxed_not_equal` through
focused `scpp/operators/*.hpp` headers. It does not add heterogeneous C++ operator
syntax or wire the compiler frontend to this resolver. Boxed runtime-kind validation
remains in the shared native equality owner.

Normalized functions enter the ordinary `apis` demand list as operation/type names.
The existing specialization manifest now also carries API descriptors. Verified
imports pass `api_imports` to the generator; matching descriptors reuse definitions,
conflicts reject, and repeated local demands emit once. Metadata/ABI/artifact checks
remain the responsibility of the existing publisher. Native transport descriptors
carry their private representation dependencies so free-function implementations
compile independently, not only inside the core implementation bundle.

### Ordinary numeric operation completion (A1)

The global registry now binds add/subtract/multiply/divide, integer remainder and
six ordinary numeric comparisons. All share numeric normalization; integer remainder
uses an integer-only domain group. Comparison returns boolean rather than the operand
type. There is one compiled function per normalized type/operation, not per original
operand pair. The two equality binding categories remain `numeric_equal`/
`numeric_not_equal` and boxed `equal`/`not_equal`; source operator spelling/admission
is a frontend concern, not a new source token parser in this physical tool.

An optional type-level `operand` snippet supplies the operation-specific native
representation, substituting `${value}`. Operations use `${operand:left}` and
`${operand:right}`. This separates numeric representation from general transport
(for example uint64's raw count transport) without changing other API signatures.
The native type spellings live in metadata, never in a generator type-name switch.
All numeric computation delegates to existing `generated_operator_detail` helpers,
including checked zero-divisor handling; addition uses the same route now. Existing
native overflow limitations remain, including signed division overflow. Raw public
constructors are not advertised as the future checked-cast API.

## Checked conversion recipes

`conversions` uses the same operation emitter and API ownership/publication records
as ordinary functions and global operators. It has three metadata maps:

- `sources`: original public type to normalized source type. Normalization is one
  step and must preserve the value; signed integer widths use int64, unsigned widths
  use uint64, float64 and string retain themselves. These are authored semantic
  facts, not a generator inference from C++ type names.
- `bindings`: each normalized source supplies its native `operand` expression,
  private `includes`, and admitted `targets`. A binding must normalize to itself.
- `targets`: the checked conversion `expression` and private `includes` for each
  result type. `${operand}` is substituted from the source binding; ordinary public
  type substitutions and dependency resolution then use the existing emitter.

`resolve_conversion` accepts `source` and `target`, returning the normalized
`operand_type`, API identity, function/header and whether lossless normalization is
needed. Generated signatures take the normalized source. A compiler must evaluate
once and perform that lossless conversion before the call; raw C++ callers do not
receive implicit narrow-source overloads. Destination narrowing occurs only inside
the selected checked native cast. This is separate from representation access used
by unrelated APIs; existing leaf borrow/construct definitions are unchanged.

The first publication has nine numeric targets and four normalized sources
(int64, uint64, float64, string): 36 adapters. Each target calls `scpp::cast` so range
checking, strict parsing, exceptions and accepted floating precision loss remain
owned by the native cast contract. Public headers contain no templates. Boolean,
string-result, boxed and carrier conversions remain pending. Neither this registry
nor a raw C++ representation constructor admits those missing source-language casts.

Conversion target recipes may select `transport: native` to return their native
result through the existing owned return transport. String conversions use this
path; no extra heap wrapper or new ownership protocol is introduced. Boxed source
bindings use `${native:value}`, which resolves the existing representation borrow
and its private dependencies through ordinary operation generation.

The current publication contains 78 adapters. Original numeric/string sources
cast to all numeric destinations plus boolean/string. Boolean, mixed and dynamic
sources expose all integer widths, float64, boolean and string; null exposes string
only. Boxed casts preserve the original payload type and apply the requested native
checked cast directly: integer payloads remain int64 and floating payloads remain
float64 until conversion. Strings retain destination-specific strict parsing.
This neither changes boxed storage kinds nor admits null/container numeric casts.
Boolean string casts retain true-to-"1" and false-to-empty semantics.
String-to-boolean remains strict literal parsing, separate from truthiness.

Scalar boxing remains in existing generated family constructors; explicit family
crossing remains in generated `to_dynamic`/`to_mixed` functions. The coverage audit
tracks these paths without generating duplicate global cast adapters.

## Shared function sets

`function_sets` factors repeated ordinary function descriptors without adding a
family-specific emitter. Each named set has a `function` descriptor and a `variants`
object. A variant name plus the set name forms its API identity (`set_variant`).
`${variant}` substitutes that name in descriptor string values;
`${variant:key}` substitutes a string binding from the variant. Object keys are
not substituted. Inserted snippets can contain ordinary operation placeholders
such as `${native:value}`, resolved later by the existing emitter. Variant expansion
is one pass; recursive variant placeholders in bindings reject.

Expanded functions pass through the same dependency, transport, API ownership and
publication machinery as authored functions, normalized operators and conversions.
Duplicate identities/owners reject. The generator also rejects duplicate output
paths across generated units before returning a publication plan. This prevents
independent valid API names from overwriting each other's headers or sources.

The first set is `condition_truthy`, with 14 public overloads: eight integer widths,
boolean, float64, string, mixed, dynamic and error_value. All delegate to the existing
native condition helper; string truthiness is separate from strict boolean casts.
The public surface does not expose arbitrary template conditions. Bare null,
containers, ownership/callables and carrier specializations are not added by this
slice; boxed null follows its native runtime dispatch. Existing mixed-table rejection
and dynamic-object truthiness are preserved. Lazy evaluation remains compiler-owned.

## Bounded concatenation text

The `concat_text` function set publishes 11 admitted plain-source variants:
int8/16/32/64, uint8/16/32, float64, boolean, string and null. Its shared recipe calls
the existing PHP scalar text helper and uses native owned-string return transport.
It is distinct from `cast_string`: float formatting is context-specific. Byte aliases
resolve to uint8 before API selection. No uint64, boxed or carrier variant is
silently admitted by this bounded plain-scalar set.

After converting saved operands in source order, callers use the existing public
string `operator+`, `operator+=` or append primitive. There are no operand-pair
concat adapters. Compiler lowering owns left conversion before RHS evaluation,
captured destination identity and once-only compound write-back; this runtime API
accepts values already selected/evaluated by that process. Carrier text conversion
remains in C. Existing boxed/display/interpolation contracts remain separate.

## Same-type strict identity

The `strict_identical` and `strict_not_identical` function sets each publish seven
same-type variants: canonical int64, float64, boolean, string, null, mixed and dynamic.
Their ordinary binary signatures preserve each operand's representation and delegate
to the native identity helper. No arithmetic common-type selection is performed.

These are backend paths, not a complete strict-identity admission resolver. Plain
heterogeneous scalar pairs, boxed/plain pairs, fixed-width source admission and
carrier state-aware dispatch remain separate work; do not route them through numeric
promotion or infer permission from native template overloads. Carrier policy changes
must be implemented in their shared owner before publishing carrier identity.

Mixed table identity retains native ordered contents comparison; dynamic object
identity retains native shared-object identity. Publishing the mixed path does not
claim it is allocation-free: its current key iterator materializes boxed string keys.

## Representation-preserving binary plans

Global operator rules may use a domain name or a nonempty array of domain names
for each side. Validation rejects overlapping domain pairs, including overlap
introduced through arrays; this grouping changes metadata size, not emitted code
into a Cartesian product.

`selection: same_or_constant` preserves exact operand types. Equal types select the
published same-type implementation. Distinct admitted types return a plan with
`kind: constant_after_evaluation`, boolean `value`, `conversion: identity`, and
`evaluation: ordered_eager_left_then_right`. Such a plan has no API, header or common
operand type: it is a result after evaluating/snapshotting both operands, **not**
permission to fold away source effects or exceptions. Constant selection cannot
name a numeric promotion domain. `distinct_result` is a boolean spelling owned by
the operation; `operands` may bound exact input types independently of domain groups.

An operation may reference a `function_set` instead of defining another expression.
The resolver verifies the selected binary signature and obtains API/header/function
identity from the expanded ordinary descriptor. The operation emitter does not
emit duplicate definitions. Registry and function-set expansion are reused during
bulk operation generation rather than recomputed for every normalized signature.

Strict identity now uses this path for canonical int64, float64, boolean, string
and null pairs, plus existing same-family mixed/mixed and dynamic/dynamic calls.
Distinct plain scalar types return false for identity and true for its negation;
no truth conversion, string parsing or numeric promotion occurs. Fixed-width,
boxed/plain, cross-box-family and carrier admission is not inferred from this rule.
`check_identity_plans.py --core ...` compiles a small public-only consumer and native
counterpart to prove result/evaluation behavior; it is not compiler frontend adoption.

## Eager logical xor

The `logical_xor` operator selects the existing boolean operand domain for canonical
int/float pairings and bool/bool. Exact operand admission rejects bool/numeric mixing,
strings, fixed-width integers, boxed values and null. This is independent truth
conversion, not selection of a common arithmetic representation. Existing checked
conversion plans produce each boolean operand; the sole published implementation
`logical_xor_boolean` compares the two saved boolean values.

A consumer must evaluate and convert the left operand completely before starting
the right operand, then invoke the boolean implementation. False and true left
values both reach the RHS. Failure in either evaluation/conversion stops subsequent
work and preserves earlier effects. Public-only and native consumer proofs cover
all 85 sampled value combinations, including signed zero, infinities and NaN.

Carrier numeric xor remains C: require a successful payload before truth conversion.
Do not substitute the carrier condition observation, which intentionally treats
absence/sentinels differently. Lazy logical operators and source precedence are
compiler-owned; the eager boolean API does not implement those semantics.

## Primitive presence, emptiness and reset

The ordinary function-set emitter also owns the remaining concrete scalar helpers:
`isset` for float64/null/mixed/dynamic, `empty` for mixed/dynamic and `clean` for the
published numeric widths, boolean and string. Existing integer/string presence,
string emptiness and boxed resets remain type-owned; no duplicate signatures are
published. Public signatures borrow inputs and mutate reset targets by reference.

Presence and boxed emptiness bind the existing native observation authorities.
Emptiness is not negated truthiness: boxed null, booleans and numbers throw
`invalid_empty_operand`; strings and tables inspect cardinality without copying.
Reset expressions use the public representation's assignment/clear operations;
they do not detach aliases. Dynamic reset retains the existing binding-only semantics.
Keyed access, carrier protocols and lazy multi-operand evaluation remain separate;
a public single-value observation does not grant broad source admission.

## Ordered heterogeneous operator signatures

An `ordered_pair` selection preserves two independently typed arguments instead of
selecting a common operand type. `pair_function_set` identifies the ordinary recipe
set; the concrete variant is `<first>_<second>`. Signature validation requires two
const-reference arguments with those exact types and the declared result type.
The optional `reverse` field is the text `true` or `false`, and is legal only for
this selection. It changes **call argument order after evaluation**, never source
evaluation order. The returned plan has `argument_order` (`left_right`/`right_left`),
`conversion: identity`, and no misleading common `operand_type`.

Boxed/plain strict identity uses this mechanism: two boxed families times five
canonical plain kinds, shared by both operand directions. Identity and its negation
publish twenty implementations total. Native identity observes borrowed payloads;
it neither boxes plain strings nor copies boxed strings. Mixed/dynamic crossings
and noncanonical fixed-width operands remain rejected. No family name or scalar
case is hard-coded in the generator.

## Boxed text contexts

Mixed and dynamic use two additional variants of `concat_text`, binding the native
text-context authority through borrowed payload access and owned string transport.
They are not explicit string casts and do not introduce boxed `+` dispatch.
Existing native `Array`/`Object` labels and scalar formatting are preserved. Returned
strings own their bytes; a long boxed string needs one result allocation, not an
extra boundary wrapper allocation. Carrier text policy remains a separate recipe.

## Specialized integer binary operations

Bitwise and/or/xor and left/right shifts use ordinary function-set recipes and
homogeneous domain selection for int64, mixed and dynamic. Native return transport
preserves the selected public family. Exact operand admission prevents native
integer templates from implicitly admitting fixed widths or boxed/plain pairs.
Power uses the same mechanism with canonical int64 only. Sixteen implementations
cover these six operations; no generator change or original-pair expansion is needed.

The native helpers own integer-kind validation, shift-count checks and power domain/
overflow errors. These are eager binary calculations; they do not publish compound
mutation or change frontend evaluation order. Unary operations, compounds, spaceship
and carrier extraction/write-back remain separate B2/C work.

## Restricted domain members and spaceship

Operator domain rules may restrict `left_types` and/or `right_types` to a type name
or nonempty list. Each named type must belong to that side's selected domain, with
no duplicates. Restrictions narrow rule matching; they do not relax the existing
no-overlapping-domain-rules validation or introduce hidden family-name exceptions.

Spaceship reuses same-type/family-maximum selection for same-signedness integers.
Its floating rule accepts float64/float64 and canonical int64/float64 in either order;
fixed-width/float and unsigned/float combinations are not inferred from arithmetic
admission. Nine implementations return int64. The native comparison owner compares
integers directly rather than subtracting, and retains the floating NaN error.
Carrier extraction and source snapshots remain separate C/compiler responsibilities.

## Unary operator form

`form: unary` uses an explicit admitted operand list and the existing function-set
owner. It has no binary group or fake second operand. `resolve_operator` accepts
`operation` and `operand` and returns a borrowed one-argument call plan with
`evaluation: evaluate_operand_once`, identity conversion and the declared result
(or the input type by default). Publication validates the exact const-reference
signature and reuses ordinary API inventory ownership. Binary plans are unchanged.

Numeric positive/negative publish canonical int64/float64 and both boxed families;
complement publishes int64 and both boxed families. Native kind checks and native
integer overflow limitations remain unchanged. In particular this does not invent
checked negation of the minimum signed integer. Logical negation, updates and carrier
operations retain their separate admission/state protocols.

## Unary result composition

An optional unary `result_expression` describes a small C++ expression over exactly
one `${result}` marker. Resolve the observed value first, then substitute that saved
result; if observation throws, do not evaluate the expression. This supplements the
existing one-operand evaluation plan and introduces no new runtime implementation.
The metadata owns the expression; the generator has no hard-coded negation/type rule.
Normal C++ compilation still validates the expression's result type and syntax.

Logical negation composes the existing `condition_truthy` API with public boolean
`!`. Its admitted scalar-facing inputs are canonical int64/float64, boolean,
mixed/dynamic and error_value. String/fixed-width condition helpers do not by
themselves authorize those unary source operators. Carrier, ownership and callable
logical admission remain with their respective completion batches.

## Writable unary operands and update snapshots

Unary `operand_passing` defaults to `const_ref`; `ref` requires the function-set
signature to borrow mutable storage and produces `resolve_writable_operand_once`
in the plan. Other passing modes and binary uses of this field reject. The caller
must resolve and retain valid writable storage; metadata is not a source lvalue
validator. Public C++ reference signatures enforce mutability at that boundary.

Prefix/postfix increment/decrement publish canonical int64/float64 and both boxed
families. Each returns an owned value, including prefix forms whose native operators
return references. Integer metadata uses the existing public representation's
prefix methods; other types borrow mutable native storage. Native boxed kind checks
and numeric overflow behavior remain unchanged. This is separate from compound
snapshot/RHS/write-back protocols and does not admit additional fixed widths.

## Ordered compound plans

`global_operators.compounds` associates an operation identity with a binary
`operation` and nonoverlapping `rules`. Each rule names a `target` type and a
`right` type or nonempty type list. This admission is directional and independent
of ordinary binary promotion. The C++ generator has no compound type-name or
operator-name exceptions.

`resolve_compound` accepts `operation`, `target`, and `right`. It returns the
existing calculation plan, separate left/right/write-back conversion plans, and
an ordered `steps` protocol. The consumer captures writable storage and retains
owners, snapshots/converts the old left before RHS evaluation, computes/converts,
then commits once and returns an owned value snapshot. On failure it must skip
write-back without rolling back earlier effects. Identity conversions are explicit;
nonidentity conversions reuse the checked conversion registry. This is a compiler
lowering plan, not a two-argument C++ mutation function that could lose RHS ordering.

The first protocol supports common-operand calculations returning the destination
type. Metadata admits canonical int/int numeric compounds and float destinations
with int/float RHS for addition, subtraction, multiplication and division. It does
not infer int-destination/float-RHS admission from an available checked cast.
Fixed widths, boxed stored-kind checks, text replacement and carriers are not
admitted by this slice. General destination validation and owner retention remain
consumer obligations; indexed destinations need their separate access protocol.
No new runtime implementations are emitted: plans reuse published calculations and
casts through the existing API inventory.

## Boxed arithmetic through the same global operations

The five arithmetic operations use ordinary function sets for both existing plain
implementations and homogeneous mixed/dynamic implementations. Variant snippets
select native calculation and return transport; no generator type-name branch or
second operator registry is introduced. Plain generated implementation files remain
unchanged. Private includes are selected by the variant, so plain callers/adapters
do not gain boxed dependencies. Normalized signature uniqueness applies to function
sets as well as inline operator recipes.

Arithmetic-specific domain groups include same-family boxed pairs without extending
ordering/comparison groups. Results preserve the boxed family. Native arithmetic
owns payload-kind dispatch, numeric promotion, zero-divisor errors and integer-only
remainder. This slice does not admit boxed/scalar or mixed/dynamic pairs. Compounds
still require their separate preserved-left-kind check before live write-back.

## Checked compound replacement

A compound admission rule may name `writeback_function_set`. Its target-specific
variant must accept two const-reference target values and return an owned target
value. The resolver emits `checked_saved_value` with ordered arguments `old_left`
and `calculated`. The consumer calls it during `convert_writeback`, then commits
the returned replacement. It must not substitute the live destination for either
argument. Invalid mutable-argument or mismatched-result signatures reject.

Homogeneous boxed arithmetic uses two ordinary recipes, one per family. Each copies
the saved left into private storage, applies the existing native stored-kind check
and assignment there, and returns that checked value. It cannot modify the live
destination; native diagnostic ownership and kind rules remain unchanged. Successful
numeric storage stays inline with zero allocations. The same recipes serve all five
arithmetic compounds without per-operator or operand-pair implementations. Text,
scalar-peer, carrier and indexed destination protocols remain separate work.

## Operator operand conversion recipes

Common-operand binary plans now include `left_conversion` and `right_conversion`.
The consumer snapshots/converts the left before evaluating/converting the right,
then calls the selected backend. Compounds use the same conversion owner while
retaining their separate write-back protocol.

`global_operators.operand_conversions` may associate a target with explicit
`sources`, a focused public `header`, and an `expression` containing exactly one
`${value}` marker. Substitute the saved operand, not its unevaluated source
expression. Duplicate sources and identity overrides reject. Nonidentity conversions
without a matching recipe use the checked conversion registry; identity remains a
no-op. These recipes describe operator operands, not permission for implicit
assignment, arbitrary casts or other operation groups.

Two constructor recipes box canonical int64/float64 into mixed/dynamic. Arithmetic
rules select the existing same-family backend for either scalar-peer order; remainder
admits only int64 peers. Boxed destinations accept those peers for the corresponding
compound and retain the saved-kind replacement check. Scalar destinations with boxed
RHS, cross-family boxing and extra fixed-width/bool/string peers are not admitted.
No new implementation units or operator-pair overloads are generated.

## Context-specific operand conversion owners

A binary operation may select an `operand_conversion_function_set`. For each
nonidentity source, the selected variant must borrow that source by const reference
and return the common operand type. The resolver publishes its API/header/function
as the conversion plan. Identity remains a no-op; consumers may bind a reference to
an already owned snapshot instead of making another copy. Compounds reuse the
calculation's conversion plans, so a context cannot accidentally fall back to an
unrelated explicit cast.

Plain scalar concatenation uses `concat_text` conversion variants and one string
calculation calling the existing primitive string concatenation. The bounded eleven
scalar types produce 121 admitted binary plans; string storage accepts eleven RHS
forms for `.=`. The float context formatter stays distinct from explicit float-to-
string casts. Byte preservation, ordered conversion and owned result rules apply.
Boxed and carrier text admission/write-back are separate completion slices; this
plain scalar rule does not silently authorize them or uint64 text.

## Explicit replacement sources and converted snapshots

A compound rule may declare `writeback_source` when its calculation returns a type
different from the destination. The calculation must return exactly that declared
source, and the existing operand conversion mechanism constructs the destination.
Without this declaration, the calculation must return the destination type. This
permission belongs to the admitted rule; an available cast alone does not authorize
narrowing or kind replacement. It cannot combine with saved-kind write-back.

`compound_left_snapshot: converted` on a binary operation replaces the compound's
raw snapshot/conversion steps with `snapshot_converted_left`. This step reads the
captured destination and produces an owned converted value before RHS evaluation.
Even an identity conversion must save a value, not retain a reference to the live
destination. Saved-kind replacement requires the original snapshot and rejects this
mode. Numeric compounds retain their original snapshot protocol.

Text uses converted snapshots. Boxed text accepts canonical scalar/null peers and
the same boxed family, including string-storage destinations with boxed RHS. Known
cross-family and compact integer peers remain excluded. Existing conversion helpers
preserve mixed container `Array` and dynamic container `Object` text. Boxed `.=`
explicitly constructs its replacement from the calculated string, allowing kind
replacement while preserving failure-before-commit semantics. There are no new
runtime adapters; the string calculation, context helpers and constructors are reused.

## One-layer checked carrier projection

A family can declare `carrier: {"payload":"T", "require_value":"require_value"}`.
The payload names a family parameter. The checked member must exist exactly once
on `self`, borrow a const receiver, accept one by-value `c_string` context and
return the payload by value. Conditional or reference-returning extraction does
not satisfy this first protocol. Its implementation remains an ordinary family
method recipe; the resolver does not know native carrier class names.

`global_operators.carrier_projection` contains explicit `operations` and `payloads`
selectors. These authorize projection only for those operation and plain payload
identities. Family metadata alone does not delegate every operator. The initial
policy covers five arithmetic operations and the existing concrete numeric types;
it excludes boxed values, bool, string, ownership and containers.

`resolve_operator` also accepts an envelope-level `names` map, using the same
metadata naming adapter and ordered family binding as physical generation. Aliases,
defaults and application specialization names therefore resolve consistently. A
carrier plan has kind `checked_payload_binary`, independent left/right projection
records and a `calculation` containing the existing plain operator plan. Identity
projections retain a plain operand; `checked_member` projections supply the concrete
header, C++ type, owned-value transport and a `${value}` expression. Extraction is
one layer only; the projected identities must pass the explicit payload selector
and ordinary numeric admission before a plan is returned.

The caller follows `steps`: evaluate/project/convert left, then evaluate/project/
convert right, then calculate. Each intermediate is an owned value or a stable
reference to that owned value, never a borrowed carrier interior. Extraction and
conversion failures suppress later evaluation while retaining prior effects. The
calculation returns its ordinary numeric result. No carrier-pair implementation
units are emitted, and no source compiler adoption is implied.

The initial implementation describes nullable and Result extraction. The execution
proof covers application-owned `nullable<int64>`; Result state/lifetime execution,
false/bool-sentinel recipes, conditions, comparisons and conversions remain separate
completion work. This does not claim the first full carrier review gate has passed.

## Carrier state observations and required-scalar casts

The private `carriers` component owns descriptor validation and checked projection
for arithmetic and conversion planning. Family state descriptions use an ordered
`states` array. Each state has a unique `name`; every state except the final fallback
names a `test` member. A test must be a const, unconditional, zero-argument member
returning public boolean. This first protocol describes nullable payload/absence
and Result payload/error without recognizing family names in the resolver.

A state may supply `casts`, keyed by a scalar conversion target. Each recipe is an
independent result `expression` and focused public `header`; it cannot contain a
payload substitution. Nullable absence uses this for the empty string result.
Without an override, conversion requires the checked payload. String exceptions
are metadata recipes, not target/family-name branches in the generator.

`resolve_conversion` accepts the same envelope-level `names` map as operator
resolution. For a carrier it returns `kind: carrier_conversion`, the owned
`projection`, a plain `payload_conversion`, and ordered `states` branches. A branch
contains a `test_expression` unless it is the fallback, plus either an `expression`
result recipe or `kind: checked_payload`. With no state descriptors the caller
uses checked payload conversion directly. The source is evaluated once and stays
alive during observation/extraction; selected payload conversion then follows its
existing source-normalization protocol. Factories and casts may throw, but never
mutate the source or synthesize a Result error.

Plain payload conversion is resolved before returning any state plan. This prevents
an empty-state override from admitting an otherwise invalid cast and forbids
recursive carrier peeling. The initial execution proof is nullable<int64> to bool,
int8 and string; Result state plans are checked, with Result error execution and
sentinel-family recipes still pending. Same-family reconstruction, conditions and
state comparisons remain separate completion slices.

## Condition plans and carrier negation

`conditions.function_set` names the existing scalar observation function set.
`resolve_condition` accepts `request: {"source": "..."}` and the usual optional
`names` envelope. Plain observations must borrow the canonical operand by const
reference and return boolean; their existing API/header/function is reused.

Carrier states may declare `condition: payload`, `true` or `false`. The resolver
checks the payload condition contract before returning a plan, then emits an
ordered state dispatch (`kind: carrier_condition`) with the shared projection and
`payload_condition`. Literal states do not extract a payload. Payload states follow
the payload plan, so zero is false even though the carrier is present. Admission
is not inferred from a non-payload state's literal result: absent vector/hash
carriers still reject. Required bool casts remain their separate checked protocol.

Condition delegation can compose through a nested carrier with a condition
contract, unlike arithmetic/required casts, which project only one layer. Nesting
is bounded by the existing type-depth limit. This physical composition does not
grant source type formation; formation/capability checks remain independently
required. Owned payload extraction is capability-selected for nullable and Result.
Scoped payload observation and avoiding unnecessary large-payload copies remain
required completion work; scalar zero-allocation evidence does not cover them.

A unary operation may opt into `condition_operand: true` only when it uses the
configured condition function-set owner, const operand passing and a boolean result.
For a carrier, its plan becomes `condition_operator`, containing the condition plan
and the existing optional `result_expression`. Logical negation uses `!${result}`;
the condition must be evaluated once before that expression. Plain operands keep
the operation's original explicit admission list. Lazy source branches and operand
evaluation stay caller-owned, and no wrapper-pair adapter is generated.

## State-aware carrier comparison plans

Carrier states may expose a `comparison_tag`, independent of their local state name
and predicate spelling. `global_operators.carrier_comparisons` selects numeric
payloads, a `payload_state` tag and operation policies. Each policy supplies a boolean
`different_states` result and `same_states` constants or state-data functions. The
payload tag cannot have a same-state policy. It must use the existing payload operator plan. This initial
policy covers numeric equality/inequality and canonical numeric strict identity.

The resolver checks one-layer payload admission first, returns independent
`left_states`/`right_states` observations and a `calculation` plan, and requires a
policy for every matching non-payload state reachable on both sides. A plain peer
has only the payload state. Unimplemented matching states reject explicitly. A state may instead select a
registered comparison function over owned state data, as described below. Ordinary
Result error equality has a literal state result; it does not inspect message/file/line.

`kind: state_comparison` requires whole left and right snapshots before state tests.
Different or constant same-state results skip payload extraction and conversion.
Matching payloads use the scalar calculation, including numeric promotion or a
strict constant-after-evaluation result. This is a bounded state vocabulary, not a
family × payload overload matrix, and does not impose exact carrier-family identity.
Ordering reuses `checked_payload_binary` and its early checked extraction; absence
is never assigned an ordering rank.

The compiled proof covers nullable<int64> and nullable<float64>, including differing
payload identities, matching absence, inequality, alias mutation during RHS evaluation
and ordered-extraction failure. Scalar numeric state policies do not close boxed,
string/bool or other carrier comparison work; later sections record the structured-
error and literal-null additions.

## Same-family state reconstruction

A carrier state can describe `reconstruct: {kind, expression}`. `kind: payload`
requires exactly one `${target}` and `${value}` marker and no `${source}`: checked
payload conversion runs first, then its owned result enters the destination factory.
`kind: state` requires one `${target}`, no `${value}`, and at most one `${source}`
for copying non-payload state data. Unknown/duplicate markers reject. This permits
nullable default absence and Result error-copy factories without generator family
exceptions or public borrowed error references.

`resolve_conversion` recognizes a carrier destination through the shared naming
and descriptor path. Source and destination must have the same canonical family;
all source states must have matching destination reconstruction recipes. Plain
payload conversion is validated before a plan is returned, including for absent or
error sources. No recursive carrier cast lookup is performed. Returned plans use
`kind: carrier_reconstruction`, resolved `target_cpp`/`target_header`, the shared
source `projection`, ordinary `payload_conversion` and ordered state branches.
Target type markers are resolved; `${value}` is the converted owned payload and
`${source}` is the once-evaluated live source.

The consumer selects the source state, executes its factory, and commits an outer
assignment only after complete construction. Cast/constructor exceptions remain
exceptions, not Result errors. No move from the original source or extra wrapper-
pair runtime implementation is introduced. The initial nullable/Result recipes
support scalar payload casts; conditional lifecycle, broader payload conversion
and sentinel families remain separate completion work.

## Literal carrier presence observations

A carrier state may declare `literals`, a nonempty list of registered singleton
literal types that match that state. `global_operators.carrier_literals` selects
literal types and operations, with `matched` / `unmatched` boolean results. The
initial policy admits only strict identity and strict nonidentity against null;
nullable absence declares the match. Result states do not declare null matches.
This is independent of payload comparison admission and does not make ordinary
nullable equality against null valid.

The resolver emits `carrier_literal_observation`, identifying the nonliteral
`operand_side`, canonical `operand_type`, public header/class, registered result
type and ordered state tests/results. Its evaluation contract is
`observe_nonliteral_operand_once`, `operand_passing: const_ref`, and
`payload_access: none`. Consumers bind the operand once for the duration of the
observation and test state without copying, extracting or consuming the payload.
Existing plain scalar/boxed comparisons retain their own operator path. Literal
presence does not recursively peel Result or other carriers. Boolean sentinels and
ownership observations require their own admitted metadata; this slice does not
infer them from the broader native C++ overload set.

## Owned Result error transfer

The Result family recipe exposes both const-reference and move-reference error
construction/assignment. A mutable owned `error_value` rvalue is passed through
its private native access and moved into native Result storage; public headers
expose only the concrete error type. The existing parameter-passing/snippet
mechanism owns this behavior; no generator family special case is needed.

State-preserving reconstruction still reads the original Result through a const
reference and obtains an owned error snapshot. The destination consumes that
snapshot instead of copying it again. It never moves from the original carrier.
The existing native real-error check rejects `error_value::no_error`; failed
assignment must preserve the destination. Moved-from error objects remain valid
for destruction and assignment; this API does not promise their message contents.

## Comparisons of owned non-payload state data

A carrier state may declare `comparison_value: {type, member}` for a concrete
registered type. The member must be an unconditional const method returning that
owned type and taking one by-value diagnostic c_string, using the same validation
as checked payload access. No borrowed heap-interior reference is exposed.

A `carrier_comparisons` operation's `same_states` entry may be a boolean constant
or `{function_set: name}`. For reachable matching states with function policy,
both state projections must have the same target type. The chosen function-set
variant must borrow two values of that type and return the operator's registered
result type. It does not infer a conversion between different state data types.

The `state_comparison` plan separates reachable constant `same_states` from
`same_state_calculations`. Each calculation carries its public API/header/function,
result identity and left/right owned projections. After evaluating whole operands
left-to-right and identifying matching states, the consumer extracts left state
data, then right state data, and invokes the selected function. Different states
skip both projections. Payloads still use the original payload comparison plan;
invalid payload admission is not rescued by non-payload states.

Result strict identity selects the published error_value identity functions, which
compare error presence, message, line and file through the existing native owner.
Ordinary error equality remains a constant state result. The implementation has no
Result-name or error-tag branch. These state functions do not themselves broaden
standalone source error-expression admission or expose native templates.

## Capability-selected lifecycle

Each class lifecycle entry (`default`, `copy`, `move`, `destroy`) accepts `native`,
`disabled`, or `{requires_capabilities: {parameter: [capability, ...]}}`. Conditional
entries reuse ordinary operation/family capability selection: all declared facts
must be present for native emission; otherwise the lifecycle operation is deleted.
Selected capability checks are emitted into the target probe. Copy and move each
retain the existing paired constructor/assignment contract. This does not expose
C++ requires clauses or templates in a public header.

The generator resolves these entries before emitting probe metadata, so target
probing still receives the same native/disabled lifecycle vocabulary. A missing,
unknown or malformed condition rejects. Destruction must resolve to native because
inline storage cannot safely omit destruction. Conditional method selection remains
separate: suppressing copying must also suppress APIs that inherently copy payloads.

The first execution proof uses an application optional-storage recipe backed by
native nullable, with move-only and copyable record descriptors. It proves default
absence independent of payload default construction, deleted copy APIs, move/reset
lifetime and publication reuse. The production nullable migration is described below;
Sentinel-family lifecycle migrations remain pending.

## Shared capability facts and production nullable

Physical generation, operation planning and import validation share capability
selection, recursive family propagation and leaf merging. Resolver envelopes may
supply application `leaves` and `imports` in addition to `names`. Conflicting leaf
facts reject; imported concrete capabilities must match those derived from the
current metadata and leaves. Public packages retain effective facts, not unresolved
conditions. Selected conditional family guarantees contribute their prerequisite
checks to the existing target probe.

`copy_storage` and `move_storage` are physical constructor/assignment pairs, checked
with native C++ traits. They do not replace source copyable_value/result_payload or
ownership formation rules. Runtime leaves declare their guarantees; generated
families propagate their documented guarantees through parameter conditions.

Nullable uses these facts for copy/move lifecycle, payload construction/assignment,
owned extraction, value_or and take. Default absence, state observation, reset and
destruction do not require payload copying/default construction. A noncopyable
payload therefore produces a usable concrete nullable with deleted copy members
and no owned extraction/take APIs, while preserving move when guaranteed.

The carrier descriptor may name conditional owned payload access. Its resolved
projection includes `payload_available`; unavailable projections contain no expression
naming the omitted method. Arithmetic/comparison, cast and condition consumers require
availability before selecting an extraction path. Strict literal-null presence uses
state observation and remains valid without payload extraction. This does not invent
truthiness for an application record or provide scoped payload borrowing yet.

The public proof uses production nullable for move-only and copyable application
records and nullable<vector<int64>>, including a newly generated inner vector.
Vector/hash still have other copying requirements and incomplete operation
capabilities; Result lifecycle is covered below. Descriptor propagation alone does not complete those family migrations,
source formation validation, or the first full carrier review.

## Result default and payload lifecycle

`default_storage` declares physical default-constructibility, checked by the existing
target-probe mechanism. Nullable, empty containers and fixed runtime value families
advertise their own default guarantee; Result propagates it only when its payload
has that guarantee. Default storage is separate from copying and movement. It does
not imply source-language default initialization permission.

Result's default/copy/move lifecycle and payload APIs now use the same conditional
mechanism as nullable. A non-default-constructible payload deletes Result's default
constructor without removing explicit success/error constructors. A noncopyable
payload deletes wrapper copying and omits owned payload extraction/take. Guaranteed
moves remain available. Error construction, assignment, state observation and owned
error inspection do not require payload copying or default construction.

Default `result<nullable<T>>` is a successful Result containing an absent nullable,
even when T cannot be default-constructed or copied. No nested state flattening or
new borrowed extraction is introduced. Rejected no-error assignment preserves an
existing success payload. Source result_payload/formation and explicit source
transfer permissions remain independently required; physical test records do not
broaden source Result argument admission.


### Matching-value equality and carrier domains

The ordinary nonnumeric equality owner now publishes `value_equal` and
`value_not_equal` for matching boolean, string, mixed and dynamic values, replacing
the early boxed-only helper names. API identities and header paths remain
`equal_TYPE` / `not_equal_TYPE`; boolean/string add four implementations. Numeric
promotion continues to use `numeric_equal` / `numeric_not_equal`.

Prepared domain rules reject incompatible ordinary payload pairs; carrier states
cannot rescue a rejected pair. The same carrier state policy applies to all four
families, and strict identity admits its existing distinct-kind constant results
only after eager snapshots. Native operand adaptation stays in type metadata,
including the concrete boolean to native bool_t conversion; the generator has no
boolean/string or carrier-family branches.


### Carrier operand conversion contexts

A binary operation with an `operand_conversion_function_set` may explicitly declare
`carrier_operands`: `payloads`, `peers`, and `state_cast`. The first two select the
admitted carrier payload and plain-peer identities. Admission is checked before
state dispatch; one carrier layer is projected. The ordinary operator group still
selects and validates the normalized calculation.

`state_cast` selects existing non-payload state exceptions from the carrier cast
policy and must match the calculation's normalized operand type. It does not select
the payload formatter: present payloads retain the operation's conversion function
set. The shared carrier conversion planner serves both explicit casts and these
contexts, avoiding a second state interpretation. Missing state exceptions use
checked extraction. The returned `converted_carrier_binary` plan converts the left
operand fully before evaluating/converting the right, then invokes the normalized
calculation on the owned converted values. Source evaluation remains compiler-owned.

Concatenation uses this mechanism for the bounded canonical carrier text domain;
its payload formatting uses concat_text, while absent/sentinel states reuse their
empty-string result. Boxed peers, uint64, fixed-width carrier payloads and nested
carriers are not admitted by this policy. Compound write-back remains separately
specified and is not inferred from binary conversion support.


### Carrier compound destinations

A compound registry entry may declare `carrier_targets`, listing admitted stored
payload identities. This requires the binary operation's carrier operand policy
and converted-left snapshot mode. It does not admit arbitrary numeric compounds or
infer write-back from an available cast. Plain rules are still selected first.

For an admitted carrier destination, the planner selects its single payload
reconstruction recipe for `construct_payload` write-back. Non-value state
reconstruction is intentionally not used: successful text replacement stores the
calculated string as a value even when the old destination was absent or a sentinel.
A plain admitted destination with a carrier RHS uses the same binary conversions
and ordinary write-back. Compound requests now propagate prepared naming bindings
through the same naming owner as binary/conversion requests.

Both paths reuse the existing ordered compound protocol: capture destination and
retain owners, snapshot converted left, evaluate/convert RHS, calculate, construct
write-back, commit, return a snapshot of the destination type. Failures before
commit perform no compound write-back and preserve earlier RHS side effects. The
public-library proof does not replace frontend destination/owner preparation.


### Carrier unary values and updates

A unary operation may declare `carrier_unary` with admitted `payloads` and a
`result` policy: `payload`, `updated_carrier`, or `old_carrier`. Payload policies
require the scalar operation's const-reference input; update policies require its
writable-reference input. Conditions retain their separate state-aware owner and
cannot also select this checked-payload policy. Payload lists cannot exceed the
ordinary scalar operation's admission.

The `carrier_unary` plan performs one checked payload projection and calls the
existing scalar implementation. Value operators return that scalar result. Updates
capture the destination once, project into an owned local payload, optionally save
the old carrier for postfix, mutate the local payload and construct/commit payload
write-back through the shared carrier reconstruction helper. The scalar result must
preserve payload identity. Prefix returns an updated carrier snapshot; postfix
returns the saved old carrier. No borrowed result or writable interior reference is
exposed. Missing payloads fail before mutation or old-carrier copying.

This policy selects canonical numeric carrier values/updates and integer complement;
it does not grant boxed, fixed-width, nested or arbitrary application payloads, new
writable destinations, or new checked-overflow semantics.

### Checked-payload compound plans

`carrier_targets` also opts a compound into checked carrier projection for its
scalar calculation. For this path the list bounds both operand payload domains, and
an existing ordinary compound rule must admit the projected target/RHS pair. Rules
requiring saved-kind or different-source write-back are not inferred for carriers.
This preserves directional admission without repeating a family-pair rule table.

The resulting `ordered_compound` includes `left_projection` and `right_projection`
from the ordinary binary planner. Its steps insert `project_left` between the left
snapshot and conversion, and `project_right` between RHS evaluation and conversion.
The normalized scalar calculation remains unchanged. Carrier destinations reuse the
single payload construction recipe; plain destinations use ordinary conversion.
Text's state-aware converted-operand path retains its distinct policy and steps.

Canonical integer bitwise and shifts reuse `carrier_projection.operations` and
compound `carrier_targets`, with no additional recipe fields or generator branches.
Their ordinary special-operator selector restricts binary payloads to canonical int;
compound rules enforce that same identity. Physical carrier presence never bypasses
the ordinary operation domain or the checked shift-count implementation.

### Independent compound calculation admission

Checked-payload `carrier_targets` admission belongs to the compound definition and
its ordinary directional rules. It no longer requires the binary operation to be
listed in `carrier_projection.operations`. After admission, binary and compound
planners share `resolve_projected_binary` for one-layer owned projection and ordinary
scalar calculation. Binary entrypoints retain their own policy checks. This permits
a carrier compound without granting its binary source form, as required by checked
integer power. It adds no implicit cast, payload recursion or saved-kind write-back.

### String relational bindings

The `numeric_or_text_order` group shares ordinary numeric promotion rules and adds
one string/string `ordered_pair` rule. Each relational operation selects a single
string binding through its existing `pair_function_set`; the four bindings expose
`value_less`, `value_less_equal`, `value_greater` and `value_greater_equal`. They delegate
to native string byte ordering. The operation entrypoint remains `less`/`less_equal`/
`greater`/`greater_equal` regardless of payload, and numeric public bindings retain
their existing names. Carrier projection applies only after operation admission;
adding string to the candidate payload vocabulary does not admit string arithmetic.

## Exact coalescing selection plans

`selections.coalesce` records candidate `payloads`, admitted carrier family-to-payload
state mappings, admitted structural `forms` and the concrete `require_selected` API.
A usable state must be an observed payload state from the existing carrier descriptor;
condition truthiness is not consulted. The error-check signature must borrow boolean
and return void. Policy has no C++ family-name special cases.

`resolve_coalesce` accepts `left` and `right` type identities and prepared names. It
validates both branches, exact payload identity and the selected structural join.
`plain_plain`, `carrier_plain` and `same_carrier` are the initial forms; the last
requires identical canonical carrier types, not just compatible payloads. The result
contains each branch's usability expression and owned extraction, a concrete selected
value check, the plain result identity and ordered steps. Consumers must put RHS
evaluation inside the fallback path, not supply eager callback arguments.

The ordinary native selected-domain helper and the concrete public check share one
error implementation. A failed selected branch reports the existing coalesce code,
component and operator. No error construction recipe is copied into plan consumers.
This first policy is bounded to canonical scalar payloads; further source joins need
explicit metadata admission and proofs rather than inference from physical copying.

## Exact conditional joins

`selections.conditional` supplies candidate payloads, carrier families and structural
join forms. `resolve_conditional` accepts `form` (`ternary` or `elvis`), `then`, `else`,
and an independent `condition` type only for full ternary. Both alternatives validate
before execution. Current joins require an exact common payload and either identical
carrier identities or an explicitly admitted carrier/plain arrangement. Effective
copy-storage capability is required for these owned-result plans.

The result carries its canonical type, a direct-condition plan and separate branch
conversions. Same-type alternatives use identity copying; a plain payload selected
for a carrier result uses that carrier's existing payload-construction recipe. State
observation or extraction is not inserted into whole-carrier branch copying.

Ternary stages evaluate/convert the condition, select exactly one branch, then evaluate
and convert that branch. Elvis stages snapshot the left once, test that saved value,
return its converted snapshot if truthy, otherwise evaluate/convert the fallback.
Consumers must implement these branch boundaries; eager argument evaluation is not
an equivalent runtime API. The planner adds no public callback templates and does
not make general logical operators synonyms for direct condition conversion.

## Constant condition facts

Optional `conditions.constants` maps explicit canonical leaf identities to boolean
facts supplied by the owning preparation/metadata producer. The generator requires
an existing leaf, a boolean value and no competing function-set condition binding.
It does not infer constant truth from copy/move traits. Inline-record source semantics
supply true; supporting a false metadata fixture does not change that source contract.

A direct plan has `kind: constant_after_evaluation`, `operand_access: none` and still
requires source evaluation once, including its failures and side effects. For a
carrier whose payload has a constant condition, the ordinary state-dispatch plan
records `payload_access: none`; its payload branch returns the constant without
calling a possibly unavailable owned extractor. Other carrier conditions retain
`payload_access: owned_value` and their existing capability checks.

This exposes no interior reference and needs no runtime implementation or template
callback. Consumers must not extract a payload when the plan says none. Nested
state-dependent observations cannot use this shortcut merely because a deeper leaf
is constant. Prepared condition facts remain explicit planner inputs; sealed native
storage reuse does not synthesize or validate source-language formation facts.

## Conditional companion classes

A recipe companion may declare `requires_capabilities` using the same parameter
facts as methods and lifecycle entries. An unselected companion emits no forward
class declaration, layout probe, implementation or public definition. Methods
returning that companion must carry compatible guards; references to an omitted
companion fail generation. The primary `self` class cannot be conditional: family
admission owns whether a requested specialization exists.

Vector uses this mechanism to omit copying iteration cursors for move-only elements.
Container move transfers storage, so its move capability is independent of payload
move; copying remains conditional on payload copy storage. Copy-returning access,
fill resize and copying insertion require copy storage. Moving insertion, indexed
removal and explicit-capacity compaction require move storage. Native layout probes
still verify relocatability and nonthrowing destruction; these physical checks do
not grant source-language template eligibility.

Hash uses the same conditional class/lifecycle mechanism. Owned `at`/`find`, copying
insertion and cursor operations require copyable payloads; moving insertion requires
move storage. Integer-key append additionally retains its integer-key requirement.
The hash itself can move by storage transfer regardless of payload move capabilities.
Current key storage still requires copyable keys. Native removal resets tombstone
slots with default-constructed key/value objects, so its public method additionally
requires default and move storage for both. This is a current implementation limit,
not a source-language prohibition on removing other admitted payload types; replacing
that native slot strategy remains container work.

## Scoped callable condition observations

A const boolean method may declare `condition_observation: "T"`. Its expression is
the checked internal payload access, not an owned result. The existing condition
function set selects a callable for the canonical parameter type; without one, the
method is omitted. The generator lowers the selected method through the ordinary
operation emitter, verifies `decltype((access))` is exactly `const PublicT&`, binds
that reference inside the method and invokes the existing const-reference boolean
condition helper. No pointer/reference/callback escapes in the public signature.

A carrier descriptor names that method with `condition_observation`. Validation
requires exactly one matching const boolean no-argument observation for its payload
parameter. Condition planning records `payload_access: scoped_operation` and a
`payload_expression` calling the method. The consumer must use that expression only
in the present-payload state, rather than manufacture an owned extraction. Original
state dispatch, one-time source evaluation and constant-condition shortcuts remain.

The selected helper API is a real demand dependency: generation queues it alongside
explicit APIs, reuses published helpers, and emits an unpublished helper once through
the same API publication path. It is not merely an untracked private include.

This first scoped path covers registered callable payload conditions. Nested carrier
payloads retain their existing owned observation path; their state-dependent scoped
composition is not claimed. Constant leaf observations still need no payload access.
Direct conditions and guarded logical-operator policy remain separate contracts.

## Scoped nested condition delegation

`conditions.scoped_delegation: "carrier_states"` enables scoped composition of
carrier-state conditions. It is an explicit metadata policy so publication identity
changes when a nested specialization gains an observation method. Without it,
callable leaf observation remains available and nested conditions retain their owned
fallback. Unknown policy values reject.

The shared condition planner first determines whether a payload condition can be
observed without owned extraction. Registered calls, explicit constants and admitted
carrier state plans compose. The operation emitter binds the exact internal const
payload reference, emits the inner carrier's state predicates, and delegates its
payload branch to the inner scoped method (or returns its constant fact). Each
concrete type owns a bounded method; code is not generated for every nested-type pair.
No general callback, raw reference API or recursive arithmetic/cast unwrapping is
introduced. Source evaluation remains once at the caller; the internal observation
cannot move or copy the observed payload.

Generation still omits an observation for unsupported payload conditions. Noncopyable
nested observations are available when their condition facts explicitly support this
path; physical copyability does not manufacture a condition fact. The prepared source
fact producer and source formation decisions remain separate from these native proofs.

## Exact boxed ternary and Elvis joins

The conditional selection payload list also admits mixed and dynamic. The existing
exact join forms and ordinary condition/state plans apply without new generator
branches: plain/plain, carrier/plain, plain/carrier and matching carrier/carrier.
Both sides must retain the exact payload identity; no mixed/dynamic crossing or
boxed-to-scalar unwrapping is implied. A full ternary can select a falsey boxed value
without observing that branch's truthiness. Elvis observes its saved left once and
only evaluates the fallback if falsey. Mixed container conditions retain their runtime
rejection; dynamic containers retain their presence/identity behavior.

Coalescing uses a different policy and is unchanged by this extension. In particular,
boxed null usability must not be inferred from the successful ternary/Elvis proof.

## Coalescing fallback observation versus selected value domain

Optional `selections.coalesce.payload_rules` maps an admitted canonical payload to
its permitted structural forms plus two small plain-value expressions:
`plain_usable_expression` decides whether the left avoids fallback;
`plain_selected_usable_expression` validates a value after fallback selection.
The only substitution is `${value}`, bound to the already evaluated operand.
Rules must name an admitted payload and a subset of the policy's join forms.
Carrier branches keep their existing state-based usability independently of the
payload's plain-value rule. No boxed type names occur in the resolver algorithm.

Every resolved branch now carries both `usable_expression` and
`selected_usable_expression`. Consumers use the first for left selection and the
second for `require_right_usability`; they must not substitute general truthiness
or reuse the first expression for selected-value validation.

For exact mixed/dynamic pairs, a direct null box selects fallback, but a null box
selected as that fallback is a valid owned result. A successful carrier containing
boxed null is usable immediately. Current boxed forms are plain/plain and approved
carrier/plain with exact payload identity. Broader mixed cross-type joins remain
unimplemented; homogeneous boxed carrier pairs are not enabled by this slice.
result_or_bool continues to reject on either side before execution.

## Strict vector helper bindings

Vector family functions expose the eight signatures in `specs/builtins/vector_helpers.md`
through ordinary parameter transport and conditional capabilities. Signed source
counts go to the native provider in `scpp/vector_helpers.hpp`; they must not be
converted to the unsigned storage-method sizes before the provider checks them.
Fill arguments remain public element representations inside native vector storage.
Filled construction returns through the existing inline-storage owned transport.
The PHP adapter includes the same provider rather than duplicating its behavior.
Source evaluation order and fill snapshots belong to the compiler, not these C++
entry points. There is no vector-specific branch in the API generator.

## Metadata file sections

`metadata/public_api.json` is the package entry point. Its optional `sections`
object maps a top-level metadata field to one JSON object file relative to the
metadata directory, for example `"families": "public_api/families.json"`.
The entry point must not also define that field. Section paths must stay inside
the metadata directory, including after symlink resolution. Missing files,
non-object sections, invalid paths and duplicate inline/section ownership reject.
There are no recursive includes, overlays or precedence rules.

The existing packaging driver `concrete_types.load_api_metadata()` assembles these
objects; `metadata()` then resolves native bindings from module catalogs as before.
The C++ generator receives exactly the same complete metadata object. Type rules,
capability checks, dependency resolution and code generation remain C++-owned.
Self-contained metadata objects remain accepted for tests and external demands.
All in-repository readers use the loader rather than assume the entry point holds
every section inline.

API identity hashes the assembled semantic object, so moving a section without
changing its content does not invalidate specialization compatibility. SDK sealing
still hashes all physical metadata files; changing a section changes SDK integrity.
Keep each authored metadata file below 200KB and split at an existing section
boundary when needed. This file layout adds no second semantic registry.

### Result-only factory names in copied recipes

A distinct application family copied from a recipe must give result-only factories
its own C++ name. C++ cannot overload two factories by return type alone. In the
defaults composition fixture, `sequence<string>` and `vector<string>` both accept
`(int64, const string&)` for filled construction, so the copied family uses
`${name}_filled` through ordinary name substitution. Its nested family gets its own
concrete factory name the same way. This is metadata authoring, not a family-name
exception in the generator. Ordinary vector specializations retain `vector_filled`;
different element parameter types distinguish their overloads.

## Source helper names versus concrete provider names

Metadata API identities need not equal C++ function names. The registered source
`memory_get_usage`/`memory_get_peak_usage` helpers map to concrete public
`scpp::process_memory_usage`/`scpp::process_peak_memory_usage`, with zero-argument and
boolean overloads. Their focused headers live under `scpp/memory/` using the catalog
spellings. This avoids colliding with the existing native zero-argument functions
in namespace scpp, whose int_t return cannot distinguish a C++ overload. Both APIs
share the private process-memory provider; no source spelling or accounting semantics
change. Consumers use the function identity/name in metadata, not a naming guess.

## Constant sets

`constant_sets` is an optional map of API identities to namespace/header/source and
nonempty `constants` arrays. Each member has `name`, `type` and `expression`, for
example `{"name":"DBG_TYPE","type":"int64","expression":"1"}`. The C++ emitter
resolves the public type and emits `inline constexpr scpp::int64 DBG_TYPE{1};` in
the selected namespace. Expressions are authored metadata C++ snippets; the public
compiler must accept their literal initialization. This does not infer source-type
constant eligibility or import private template definitions into the public header.

Constant sets use the existing `request.apis`, API ownership records, output collision
checks, artifact inventory and sealed reuse path. They cannot share an API identity
with a function or function set. Empty sets, duplicate member names and malformed
entries reject. The emitted source is a declaration-check unit that includes its
public header; inline constants require no runtime archive definition. There is no
parallel constant publication mechanism or runtime lookup table.

The `debug_flags` set exposes the core catalog's 19 integer flags in `scpp::debug`.
That namespace avoids colliding with existing native scpp constants. A regression
checks all values against the catalog. Generated public-only constexpr compilation
proves the constants introduce no dynamic initialization or boundary allocation.
Debug-call bindings and their source-location context remain a separate slice.

## Scalar debug calls and source context

`dbg_value` and `dbg_labelled` function sets expose the four registered scalar
payloads (int64, float64, boolean, string) as `scpp::debug::dbg_at`. Every public
signature starts with a borrowed source-file C string and concrete int32 source line;
the native formatter consumes them synchronously and retains neither. The value-only
form uses the native default flags. The labelled form accepts a public string label,
the scalar value and explicit int64 flags. Source omitted flags are supplied from the
existing catalog default (or public DBG_DEFAULT); this adds no public default-argument
emission mechanism. Argument evaluation/snapshots and authored source locations remain
compiler-owned.

Explicit native payload conversion preserves existing type labels and formatting,
including bool_t versus a native bool. The public calls forward to the same formatter;
no new formatting, caller introspection, debug-gate or object-inspection policy is
introduced. In particular, existing invalid-option reporting remains inside dbg_emit.

### Exact-mixed predicates and empty hash construction

`mixed_predicate` generates `scpp::predicates::is_bool/is_int/is_float` from one
function set, with a const mixed observation parameter and concrete boolean result.
The namespace avoids the native `scpp::is_bool` concept; it does not change source
spellings or authorize implicit boxing of other source types. The body delegates
to the existing public kind observer, requiring no private template include.

Hash `make()` is a static method returning self through native transport. It
constructs an empty hash and shares ordinary family admission; no payload-copy
capability is needed. It is separate from source collection initialization.

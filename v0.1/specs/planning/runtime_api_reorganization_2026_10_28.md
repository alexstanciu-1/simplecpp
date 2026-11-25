# Runtime and public API reorganization for v0.2

Doc Status: planning

Date: 2026-10-28

## Rust compiler decision — 2026-10-29

The compiler and API generator/physical pre-lowering will be implemented in Rust,
with a shared typed tooling library and standalone generator CLI. The runtime and
a small native layout probe remain C++. This extends the earlier compiler-only
Rust decision; the production generator has not yet migrated. The active decision, ownership
boundaries and staged migration are recorded in
the now-retired v0.2 Rust migration plan (retained in Git history). Current
implementation ownership is documented in the [PHP compiler README](../../v0.2/compiler/README.md).
This supersedes earlier instructions below to evolve my-try-cpp or adapt the C++
compiler to generated runtime ownership handles. Preserve its reviewed contracts
and tests as migration evidence; do not translate its implementation mechanically.
Earlier checkpoints remain historical. This update changes planning only: no Rust
implementation, code moves, build changes or deletion is authorized by this edit.

## Status and scope

This document records the discussion and agreed direction for a deep runtime
cleanup and reorganization. Writing this plan does not authorize implementation,
activate a goal, replace normative contracts, or claim that the examples work today.
Exact schema, paths and generated signatures below are design sketches.

The v0.2 backbone is:

1. The runtime.
2. The metadata-driven runtime public API generator and physical pre-lowering,
   migrating from C++ to a shared Rust tooling library.
3. An independent Rust compiler, following the migration decision above.

Reuse and evolve runtime behavior; migrate compiler and generator responsibilities
to Rust in reviewed slices, preserving reviewed preparation/emission contracts. Review other tooling and code, then keep,
replace or remove it based on responsibilities and consumers. This is not permission
to bulk-delete legacy tooling or rewrite working engines.

Primary objectives: simplify ownership, remove independently authored duplicate
facts and adaptation paths, group files usefully, preserve a template-free public
boundary, and make the runtime pleasant to use directly from plain C++.

Background: the [side audit](runtime_public_api_simplification_side_audit_2026_10_28.md)
and [paused runtime completion handoff](runtime_core_completion.md), available
locally at checkpoint `a2dbdb018`.
Its findings are inspection evidence from recorded revisions, not newly verified
facts about this checkout. Its file counts are not completion targets.

For the existing tree, authority remains with [the spec map](../spec_map.md), language contracts,
[runtime layering](../architecture/runtime_layering.md),
[the public API contract](../../runtime/specs/core_public_api.md), and
[generation contracts](../../runtime/specs/public_api_generation.md).
Future implementation must update affected normative contracts deliberately.

## Agreed clean-project boundary

`/home/alexv/__AI/simple_cpp/simple_cpp_02/v0.2/` is the new project and its sole
active source of truth. Everything elsewhere in this repository is reference
material to inspect and mine for explicitly agreed reuse, not an active dependency
of the new project. This supersedes the earlier suggestion of transitional builds
against components outside `v0.2/`.

- Active source, metadata, contracts, tooling, tests and required bootstrap inputs
  must be inside `v0.2/` before they are usable by the new project.
- Builds, generation and tests must not reach into the old tree through include
  paths, scripts, symlinks, prebuilt libraries, generated artifacts or runtime data.
  Normal external toolchains and explicitly declared external dependencies remain
  distinct from the old repository code.
- Each reuse slice requires explicit confirmation/agreement on its responsibility
  and scope before code is imported. Approval of this direction is not blanket
  approval to copy modules or their transitive dependency closures.
- Reuse and adapt the existing runtime and generator/pre-lowering engine in small
  coherent slices. The Rust compiler selectively reuses reviewed contracts and tests
  from my-try-cpp; no competing generator or bulk compiler import is planned.
- Review and explicitly adopt relevant contracts and proofs inside `v0.2/`.
  Existing specs remain evidence and the basis for identifying intentional changes;
  their old location is not a hidden ongoing contract dependency of the new project.
- Each imported responsibility has one active owner inside `v0.2/`. Old copies
  remain reference material, without dual maintenance or synchronization.
- Early slices may be incomplete until their foundations are admitted. Record
  missing dependencies honestly rather than compiling against the old tree.
- Do not delete or archive old code merely because a slice has been imported.
  Promotion of `v0.2/` to the repository root and archival of the remainder are
  separate future actions requiring explicit approval.

This planning document remains outside the destination as the current discussion
record. Its future adoption inside `v0.2/` is part of agreeing the project foundation;
this documentation update creates no destination directories and moves no code.

Before importing a slice, record: responsibility, source revision/files, destination
owner, adopted contracts and intentional changes, complete required dependencies,
acceptance proof, and explicit scope agreement. Distinguish unimported reference
material from active supported code; its continued existence is not a second owner.

## Agreed design direction

- One behavioral implementation owner and one public spelling per contract where
  C++ syntax expresses it faithfully. Ordinary operators have no duplicate public
  named functions; shared implementations remain private.
- Generate repeated public declarations, operation relationships, adaptations and
  publication views from metadata. Do not maintain equivalent tables by hand.
- Retain handwritten runtime algorithms. Metadata binds behavior and describes
  policy; it should not grow into an unrestricted implementation language.
- Public headers and their transitive closure remain template-free. Concrete
  aliases to exposed template instantiations do not meet this boundary.
- Private runtime implementations may use templates. Requests such as `vector<T>`
  become concrete public types through the existing generation/pre-lowering path.
- Plain C++ applications can request specializations without compiling PHP/JSS
  source. Use the same generation interface and publication checks as the compiler.
- Integer construction and `static_cast` use checked representability conversion.
  Lossless conversions cannot fail; narrowing must not silently truncate.
- Mixed integer arithmetic uses domain-maximum promotion, not the smallest common
  integer type. Same-type arithmetic retains its type. Details and exceptions below.
- Constructors remain explicit. Support conveniences such as `a + 1`,
  `names.append("Ada")`, and `names.at(0)` through concrete generated overloads,
  not broad implicit numeric conversion. Carrier-to-payload extraction has the
  separate implicit default recorded below.
- Begin with one small default metadata policy and generate only the requested
  concrete surface. Evolve defaults through real compiler use cases rather than
  deciding every possible combination in advance.
- Nullable and Result-family carriers default to implicit, checked, one-layer
  extraction to their exact payload type. Compiler preparation may instead emit
  explicit extraction and conversion according to source facts and language flavor.
- Keep library declarations in `scpp`. Offer an optional generated
  `scpp/short_names.hpp` containing selective `using` declarations.
- Reuse public concrete types internally where they naturally simplify identity,
  storage and composition. Do not require every private primitive to call a public
  facade, or require a universal second native type universe.
- Reduce per-operation files by grouping coherent families. Public include grouping
  and implementation compilation partitioning are separate decisions.

## Ownership boundaries

### Guiding principle: a symbol-aware compiler and a smaller runtime

Agreed 2026-10-28: v0.2 must not inherit runtime responsibilities solely because
the earlier type-blind S2S generator could not resolve symbols and types. Review
each reused facility against the symbol-aware `my-try-cpp` preparation model.
Preserve useful behavior, not workarounds for missing compiler knowledge.

The runtime must add identifiable value and stay close to standard C++ behavior
where that satisfies the adopted contract. Prefer standard-library algorithms and
storage internally over recreating them. This does not expose `std::string` or
`std::vector<T>` as the public API, relax the template-free boundary, or silently
replace agreed promotion and checked-conversion semantics with native C++ rules.
Our concrete string/vector types can retain their own names and public contracts.

- The compiler resolves source symbols and operand types, rejects invalid source
  combinations, preserves evaluation order and short-circuiting, and determines
  necessary conversions/extractions using the shared metadata policy. It requests
  concrete generated types and operations; it must not duplicate policy tables.
- The generator/pre-lowering engine resolves those demands into physical types,
  signatures, dependencies and explicit adaptation plans. It remains reusable by
  plain C++ consumers and does not become another source semantic compiler.
- The runtime owns value representation, lifecycle, ownership, actual operations
  and checks requiring runtime values: for example, narrowing representability
  and vector bounds. Static type knowledge alone does not prove a value is in range.
- A check may be omitted only with a compiler proof and a deliberately specified
  lowering path. Symbol awareness is not blanket permission to remove checks.
  Plain C++ callers must still receive the promised runtime behavior.

For example, the compiler can resolve `int32 + int64`, emit an explicit conversion
of the first operand, and call the same-type int64 operator. Compiled Simple C++
therefore need not demand every mixed public overload merely to express its source
operator matrix. Plain C++ publication may request mixed adapters for convenience,
and the optional native-int bridge remains a separate interoperability choice.
This direction does not remove currently published overloads without a reviewed
migration; it changes the default question from exhaustive coverage to actual need.

For every migration slice, record:

1. The value it adds for compiled Simple C++ and/or direct C++ consumers.
2. Which decisions can be made during semantic preparation and which require data
   available only at runtime.
3. Which standard C++ facility can provide the implementation, and which wrapper
   behavior is justified by the agreed public contract.
4. Which legacy adapters, dispatch paths and overloads become unnecessary.
5. The proof that compiler-generated explicit operations and direct C++ use agree.

Do not import a broad runtime matrix, generic dispatch layer or compatibility
facade before identifying its remaining consumers. Preserve one metadata policy
owner across source preparation, physical generation and C++ interoperability.

| Area | Owns | Must not independently own |
| --- | --- | --- |
| Normative specs | Language meaning, runtime contracts, approved conversion/promotion policies | Generated physical inventories |
| Runtime implementations | Arithmetic, range checks, strings, containers, errors, lifecycle and representation behavior | Another public admission or promotion registry |
| Metadata | Concrete identities, representation bindings, operation signatures, policy references, spellings, adaptation and specialization descriptions | Duplicate algorithms or new semantics contrary to specs |
| API generator and pre-lowering engine | Prepare concrete demands, resolve dependencies and physical identities, emit public/private artifacts and generation inventories | Source symbol resolution, source eligibility or expression sequencing |
| Compiler `my-try-cpp` | Source typing/admission, semantic preparation, evaluation order, lowering and runtime requests | A separate runtime API generator or reconstructed physical naming policy |
| Publication driver | Target probes, staging, integrity, ownership/ABI compatibility and sealing | A competing declaration emitter or canonical resolver |

The compiler decides what a source expression means. The generator determines how
its prepared runtime request becomes concrete C++ artifacts. Reuse the current
prepared models and interfaces; repair their boundaries locally before adding
behavior. Do not introduce a parallel schema/engine merely to match this sketch.

Prepared requests/results must carry the facts needed at that boundary. Imported
publications do not bypass source admission. Public C++ operators obey C++ evaluation
rules; compiler-controlled sequencing remains explicit in lowering.

Publication demands select exports; they do not redefine operations. Generated
manifests record resolved identities, layouts, ownership, dependencies and checksums.
Preserve bootstrap, probe, nonmovable-result construction and lifecycle guarantees.
Orchestration may remain in Python where it serves a distinct purpose.

## Target plain C++ experience

These are proposed consumer examples, not compiled acceptance fixtures yet.

```cpp
#include <scpp/short_names.hpp>

int64 zero;                         // Zero initialization.
int64 a{20};
int64 b{3};
int32 small{42};
int64 wide{small};                  // Lossless.
int32 narrow{wide};                 // Checked.
auto converted = static_cast<int32>(wide); // Same conversion policy.

int64 sum = a + b;
int64 convenient = a + 1;
int64 reversed = 1 + a;
a += 1;
int64 other_sum = a + b;

string greeting{"Hello, "};
greeting += string{"Ada"};
greeting += string{"!"};

vector_string names;
names.append("Ada");
names.append("Grace");
string first = names.at(0);
```

Consumers preferring narrow includes can use `scpp/integers.hpp`,
`scpp/numeric.hpp`, `scpp/string.hpp` and `scpp/vector_string.hpp`, with selective
`using scpp::int64;` declarations. The optional short-name header is a convenience
umbrella for the selected publication, not a compulsory dependency or a second
type identity. Public headers must not inject `using namespace scpp;`.

The generator concretizes `vector<string>` as, for example,
`scpp::vector_string`; no public `vector<T>` declaration is needed. Common
specializations can ship with the runtime; application-owned specializations use
the same process and compatible import/reuse rules.

Native arguments must be enumerated systematically, including signed and unsigned
types. Avoid accidental C++ standard conversions that narrow before our check;
avoid duplicate overloads caused by target-dependent type aliases. Unsupported
types must not silently gain admission through convenience overloads.

String pointer conveniences synchronously construct owned values through the
string owner. Preserve pointer/null/length contracts unless explicitly revised.
Native negative indices must fail through the defined access contract rather than
wrap to unsigned. Copy/move overloads retain distinct ownership costs.

## Integer metadata sketch

This is conceptual schema, to be expressed through the existing metadata model
after inspecting its owners. The named groups are explicitly defined once.
The later first-slice section narrows the initial publication and proposes the
two-file organization; this broader sketch is not a second metadata authority.

```json
{
  "types": {
    "int64": {
      "kind": "integer",
      "representation": "signed_64",
      "default_value": 0,
      "public_header": "scpp/integers.hpp"
    }
  },
  "conversions": {
    "checked_integer": {
      "sources": ["public_integer_types", "native_cpp_integer_types"],
      "targets": ["public_integer_types"],
      "policy": "require_representable",
      "failure": "integer_range_error",
      "implementation": "checked_integer_conversion",
      "spellings": {"constructor": {"explicit": true}}
    }
  },
  "promotion_policies": {
    "integer_domain_maximum": {
      "same_type": "preserve",
      "different_signed": "int64",
      "different_unsigned": "uint64",
      "signed_with_unsigned_below_64_bits": "int64",
      "signed_with_uint64": "reject"
    }
  },
  "operations": {
    "integer_add": {
      "operands": "public_integer_types",
      "promotion": "integer_domain_maximum",
      "execution": "same_type_after_promotion",
      "result": "execution_type",
      "implementation": "integer_add",
      "spellings": [
        {"kind": "operator", "name": "+"}
      ]
    }
  }
}
```

Native convenience overloads use the subsequently adopted type-range policy: map
the native type's full range to an integer identity, then use the same domain-maximum
resolver. A C++ literal has a native type; its value does not select a different
overload policy. The earlier convert-to-public-operand proposal is superseded.
The error name is illustrative, not a newly established runtime error identity.

Generate concrete explicit constructors, copy/move declarations as appropriate,
operator declarations, native convenience overloads, representation
adaptations, source inventories and short-name exports. Derive `noexcept` from
actual source/target ranges and implementation guarantees; do not assume native
`int`, `long` or `long long` widths. `static_cast<int64>(value)` uses the explicit
constructor: it needs no public template or duplicate conversion operator.

Schematic generated implementation:

```cpp
int64::int64(unsigned long long value)
{
    access::construct(
        *this, detail::checked_integer_cast<native_int64>(value));
}

int64 operator+(int64 lhs, int64 rhs)
{
    return access::make<int64>(
        detail::integer_add(access::get(lhs), access::get(rhs)));
}

int64 operator+(int8 lhs, int16 rhs)
{
    return int64{lhs} + int64{rhs};
}
```

Private checked conversion, arithmetic and error construction stay handwritten
and reuse existing runtime owners. Mixed overloads can call the same-type public
operator or reuse its private primitive through generated adaptation, depending on
measured call costs. No public `numeric_add` or named integer cast is required.
Preserve useful inline representation access; no new heap wrapper is implied.

## Adopted promotion and public-spelling policy

This decision supersedes earlier examples exposing both `operator+` and
`numeric_add`, or a named integer cast alongside construction. Metadata retains
semantic operation identities such as `add` and `checked_integer_conversion`;
these do not require public functions with those names.

### Integer arithmetic

| Operand types | Execution/result type |
| --- | --- |
| Identical integer types | That same type |
| Different signed integer types | int64 |
| Different unsigned integer types | uint64 |
| Signed and unsigned, unsigned width below 64 | int64 |
| Signed and uint64, either order | Reject implicit combination |

Promotion is type-based and lossless for admitted integer pairs. Explicit checked
casts can resolve otherwise rejected pairs based on actual value. Do not silently
use floating point as a fallback for rejected integer pairs. Same-type arithmetic
retains its primitive overflow contract; promotion does not rescue overflow after
execution. The common preparation model must distinguish execution/result types
for operations whose result is boolean or whose semantics preserve one operand.
Do not mechanically apply arithmetic promotion to comparisons, shifts or mutation
without their own operation contract.

With eight integer types, addition has 56 admitted ordered pairs: eight same-type
and 48 mixed-type pairs. Full concrete public coverage requires 56 `operator+`
signatures but only eight same-type arithmetic implementations, which may themselves
share private templates. Smallest-common-type promotion admits the same pair count;
domain maximum simplifies policy and mixed execution targets, not declarations.

If all integer/float64 pairs are admitted, counts become 73 operator signatures
and nine same-type arithmetic implementations. Exact-only type-level admission
for binary64 would instead give 69 signatures. These are scenarios, not a settled
floating policy, and exclude native conveniences, carriers and boxed values.
Generated adapters still contain code: implementation counts refer to arithmetic
owners, not total emitted function bodies. Removing the duplicate named-function
surface avoids doubling these public signature counts.

### Public spelling and exceptions

| Contract | Target public spelling / boundary |
| --- | --- |
| Ordinary arithmetic | `+ - * / %`, where admitted; no duplicate named arithmetic API |
| Bitwise and shifts | `&`, `\|`, `^`, `~`, `<<`, `>>`, preserving their specific checks |
| Value comparisons | `== != < <= > >=`, with their own admission/result rules |
| Unary numeric operations | Unary `+` and `-` |
| Compound assignment | `+= -= *=` etc.; calculation and checked write-back remain a distinct mutation contract |
| Increment/decrement | Prefix/postfix operators; old/new return behavior remains distinct |
| Integer conversion | Explicit checked constructor, also used by `static_cast`; no duplicate public named integer cast |
| Power | Named operation because C++ has no exponentiation operator; `^` remains XOR |
| Identity distinct from value equality | Separate named operation where the contract requires it |
| Lazy logical operations | Do not use overloaded `&&`/`\|\|` to promise short-circuit evaluation; use built-in logic through suitable explicit condition conversion where valid, or compiler-controlled plans |
| Coalescing and conditional selection | Compiler-controlled plans and required observations/extraction; C++ has no overloadable `??` or `?:` |

String parsing, boxed extraction and carrier conversion can have distinct failure,
state or ownership contracts. Carrier-to-payload conversion follows the simple
default below; other carrier conversions are not automatically converted into
constructors or deleted as duplicate casts. Named functions remain justified by a distinct
contract or missing suitable C++ syntax. Indexing and interior references remain
separate unresolved work; numeric simplification does not approve their redesign.

The compiler evaluates and normalizes operands according to source semantics,
then emits the public operator. Named functions do not inherently enforce source
evaluation order. Both plain C++ and compiler output consume the same concrete
operator surface; shared arithmetic/conversion implementation stays private.

### Floating conversion remains open

For IEEE binary64, all int8/16/32 and uint8/16/32 values are exactly representable;
the complete int64/uint64 ranges are not. Decide whether mixed integer/float
arithmetic permits rounding, checks exact conversion at runtime, or rejects
int64/uint64 operands until explicitly converted. Exact operand conversion does
not guarantee exact floating arithmetic. Float-to-integer casts also need explicit
range, nonfinite and fractional-value policies. Checked integer construction does
not settle these floating contracts.

## Simple starting defaults and incremental policy development

Adopt one small default metadata policy first. The initial rules are same-type
arithmetic, domain-maximum mixed integer promotion, public operators with private
shared implementations, checked explicit integer construction/static_cast, and
implicit one-layer carrier extraction. Publish only requested concrete types and
operations. These are starting decisions, not a requirement to design all payloads,
states, numeric domains or language flavors before implementing a bounded slice.

### Implicit carrier extraction

The default for `nullable<T>`, `result<T>`, `result_or_false<T>` and
`result_or_bool<T>` is a conversion to the exact payload type `T`:

- Extract one successful payload as a value.
- Non-payload states fail through the checked extraction contract; they do not
  silently become zero, false or a default payload.
- Do not recursively unwrap nested carriers, add a numeric conversion, or expose
  an interior reference as part of this conversion.
- Generate the conversion only for supported payload ownership/capabilities.
  Unsupported cases remain explicitly unsupported until their slice defines them.

For example, the intended numeric consumer experience is:

```cpp
nullable_int64 maybe = /* ... */;
result_int64 computed = /* ... */;

int64 a = maybe;              // Implicit checked extraction.
consume_int64(computed);      // The same conversion may occur at a call boundary.
auto sum = maybe + a;         // Reuse the available payload operator where C++ permits.
```

The generator can emit a concrete, non-explicit conversion operator to the payload.
This is a deliberate exception to the restriction on broad implicit numeric
conversion. Implicit extraction is generally available where C++ permits it; it is
not restricted to arithmetic. Merely publishing it does not guarantee that every
mixed/nested expression resolves: C++ lookup, overload resolution and conversion
sequence limits still apply. Additional requested adapters may be needed.

This extraction does not introduce lifted arithmetic with carrier-valued results.
Arithmetic after extraction follows the ordinary payload operation plan. Existing
carrier state, lifecycle and extraction machinery is reuse material; its old lifted
operation surface is not automatically adopted into `v0.2/`.

Exact exception mapping, boolean-payload/condition interactions, and copy/move
extraction rules are deferred to the first relevant implementation slices. Do not
publish ambiguous or unsupported cases merely to make the default appear universal.
These questions need not block unrelated numeric metadata work.

### Compiler-controlled explicit lowering

Runtime metadata defines available behavior and its default C++ exposure. Compiler
preparation owns source admission, language-flavor rules, operand sequencing and
the explicit steps needed to implement a source expression. It may emit checked
extraction and conversion into temporaries even when the public C++ API permits
the same extraction implicitly, then invoke ordinary operators on the payloads.

PHS compilation derives concrete API demands from admitted expressions; plain C++
applications request their surface through metadata. Both use the existing API
generation/pre-lowering engine. Language-flavor decisions must not become duplicate
runtime arithmetic implementations or conflicting definitions of a published type.
Plain C++ retains its own evaluation rules; implicit conversion does not supply
the source language's sequencing guarantees.

As compiler work exposes real requirements, revise the default metadata and the
corresponding supported generator policy deliberately. Record semantic changes,
regenerate affected artifacts and validate relevant consumers. New metadata policy
requires generator support; metadata cannot override C++ language rules. Avoid
building a general policy framework or exhaustive matrix before there is a need.

## First integer metadata slice inside v0.2

Agreed planning direction: start with ordinary C++ mechanisms and a small metadata
surface. Checked integer construction and domain-maximum promotion remain deliberate
runtime policies; they are not claims of native C++ conversion/promotion semantics.
Use explicit constructors, ordinary operators and concrete declarations. The exact
C++ language baseline remains a build-contract decision, not established by this plan.

Proposed files:

```text
v0.2/runtime/metadata/
  integers.json
  publication.json
```

These are schema proposals and an agreed first-slice scope, not executable inputs
to the current generator. No import or implementation is authorized by this
documentation update. Adapt the existing preparation/emission owners rather than
introducing a second resolver. Required dependencies must be separately reviewed
and admitted inside `v0.2/` before the slice can run.

### Proposed integers.json

```json
{
  "version": 1,
  "types": {
    "int8":   {"signed": true,  "bits": 8},
    "int16":  {"signed": true,  "bits": 16},
    "int32":  {"signed": true,  "bits": 32},
    "int64":  {"signed": true,  "bits": 64},
    "uint8":  {"signed": false, "bits": 8},
    "uint16": {"signed": false, "bits": 16},
    "uint32": {"signed": false, "bits": 32},
    "uint64": {"signed": false, "bits": 64}
  },
  "representation": {
    "binding": "fixed_width_integer",
    "default_initialization": "zero"
  },
  "construction": {
    "public_integer_inputs": "checked",
    "native_integer_inputs": [
      "int", "unsigned int",
      "long", "unsigned long",
      "long long", "unsigned long long"
    ],
    "native_conversion": "checked",
    "explicit": true
  },
  "promotion_policies": {
    "integer_domain_maximum": {
      "same_type": "preserve",
      "different_signed": "int64",
      "different_unsigned": "uint64",
      "signed_and_unsigned_below_64_bits": "int64",
      "signed_and_uint64": "reject"
    }
  },
  "operations": {
    "add": {
      "public_operator": "+",
      "promotion": "integer_domain_maximum",
      "implementation": "integer_add",
      "result": "execution_type"
    }
  }
}
```

The named promotion record makes the operation reference explicit. Integer
identity/signedness/width are authored once. The representation binding owns the
private storage and transport mechanism; its name is a placeholder for the reviewed
existing owner, not permission to infer an implementation from a string. Addition
references one implementation owner. Avoid repeating native C++ expressions or
generated source paths in each integer or operation record.

One checked conversion rule supplies constructors; `static_cast` uses those
constructors and needs no independent API record. Derive checks and exception
specifications from actual source/target ranges. The native input list is the
initial proposed scope, not a claim that other C++ inputs automatically reject.
Define and prove handling of bool, character, floating and extended integer inputs
so standard conversions cannot silently narrow them before the checked constructor
sees them. Do not assume host widths or emit duplicate signatures for aliases.

### Proposed publication.json

```json
{
  "version": 1,
  "namespace": "scpp",
  "types": ["int32", "int64"],
  "operators": [
    {"operation": "add", "operands": ["int32", "int32"]},
    {"operation": "add", "operands": ["int64", "int64"]},
    {
      "operation": "add",
      "operands": ["int32", "int64"],
      "both_orders": true
    }
  ],
  "headers": {
    "types": "scpp/integers.hpp",
    "operators": "scpp/numeric.hpp",
    "short_names": "scpp/short_names.hpp"
  }
}
```

Describing eight type identities does not publish all eight types or every operand
pair. Initially publish int32/int64, their checked cross-construction and approved
native construction, plus these four addition signatures:

```cpp
int32 operator+(int32, int32);
int64 operator+(int64, int64);
int64 operator+(int32, int64);
int64 operator+(int64, int32);
```

Mixed addition executes as int64. There are two same-type arithmetic implementation
targets, potentially sharing private generic code. Header grouping is publication
policy and does not change operation meaning. `short_names.hpp` selectively exports
the published type names; operators are found through ordinary C++ lookup/ADL.

Intended first consumer example:

```cpp
#include <scpp/short_names.hpp>

int32 a{20};
int64 b{3};
int64 sum = a + b;
int32 narrowed{b};
auto also_narrowed = static_cast<int32>(b);
```

### Reuse and changes

| Existing asset | Reuse inside the approved slice | Required change |
| --- | --- | --- |
| Public integer records, module representation bindings and concrete-integer config | Reviewed identities, representations and scalar layout facts | Establish one authored integer owner instead of repeated lists |
| Conversion resolver and normalization | Lossless signed/unsigned source normalization and prepared conversion planning | Drive explicit checked constructors from the same owner; remove duplicate public named integer-cast exposure |
| Operator domains/groups and validation | Domain selection, admission checks and prepared operation plans | Apply the adopted domain-maximum policy and expose only operators |
| Leaf/operation transport | Existing representation adaptation mechanisms | Reference a shared binding instead of repeating per-operation operand expressions |
| Concrete emission and publication | Declaration/implementation emission, dependency inventories, ownership and reuse checks | Consolidate integer class exposure into the common path; retire the separate generator responsibility when replaced |
| Existing runtime helpers | Reviewed range checking, arithmetic and error behavior | Bind privately; do not copy algorithms into metadata or create another behavior owner |
| Existing focused tests | Range/signedness/failure and public-boundary evidence | Adopt tests inside v0.2 and revise unchecked-construction expectations deliberately |
| Existing output and demand records | Publication ownership and selected generation concepts | Group headers through publication policy and emit only requested surface |

Source locations to inspect for reuse include `runtime/metadata/public_api.json`,
`runtime/metadata/modules/core.json`, `runtime/metadata/public_api/global_operators.json`,
`runtime/specs/config.json`, and the existing `conversions.cpp`, `operators.cpp`,
transport/emission and publication owners under `runtime/tools/`. These are old-tree
reference locations, not permitted dependencies of `v0.2/`.

### Scope and acceptance

First settle the schema-to-prepared-model mapping, then approve imports required
for execution. The first implemented acceptance target is two published integer
types, zero initialization, explicit checked construction/static_cast, four addition
overloads, private shared implementations and the optional short-name header.
Prove successful widening, failing narrowing without premature native conversion,
mixed addition's int64 result, independent public inclusion/linking, deterministic
generation and isolation from the old tree. Adopt the relevant primitive overflow
contract before claiming arithmetic failure coverage.

Native convenience arithmetic such as `a + 1` was excluded from the first publication
and is implemented in the subsequent native-operand checkpoint below. Floating point, carriers, further operators and full
compiler integration are outside this first publication. The carrier extraction
default remains adopted for its later slice; do not add empty carrier configuration
or an exhaustive generic policy framework to these first files.

## Proposed physical organization

All proposed active paths below are relative to `v0.2/`. Paths are illustrative,
not approved moves. Map current files and consumers first. The project-level
structure can start with `README.md`, `specs/`, `runtime/`, `compiler/` and `tests/`;
do not create a large empty hierarchy in advance.

```text
runtime/
  specs/
  metadata/
    types.json
    families.json
    numeric.json
    string.json
    collections.json
    publication.json
  src/
    numeric/
    string/
    collections/
    values/
    support/
  api_generator/
    src/
    specs/
  generated/
    public/scpp/
      integers.hpp
      numeric.hpp
      string.hpp
      vector_string.hpp
      short_names.hpp
    private/
    src/
      integers.cpp
      numeric.cpp
      string.cpp
      vector_string.cpp
    manifests/
      publication.json
      sources.cmake
  tests/
```

Group by coherent semantic family, rather than one file per operation or overload.
Keep implementation headers near their owner. A small numeric consumer must not
pull strings, containers or optional modules into its include closure. Respect
current generated-file size limits or explicitly revise the owning contract.

There is one public build root; installation publishes that product as
`include/scpp`. Application-generated artifacts live in application staging, not
the runtime-owned output tree. Any compatibility adapters used by the new project
must themselves be reviewed and contained inside `v0.2/`; none may forward into
the old tree. Their supported consumers and retirement gates must be explicit.

Generate the compilation inventory from publication ownership. Compare bounded
family units/shards with the existing bundle using clean and incremental timings.
Never link a bundle and shards containing the same definitions together. Decide
explicitly whether to adopt the old bundle requirement into the new project; it is
not an automatic requirement to import the historical build system.

## Migration slices

The following are capability milestones, not permission to import their full
dependency closures. First agree a small self-contained foundation slice and its
bootstrap strategy. Generator/compiler integration milestones can remain pending
until those components' required foundations exist inside `v0.2/`.
The first integer metadata slice above is the initial bounded target; the broader
integer milestones below follow incrementally rather than expanding its first import.

### 1. Establish current ownership and contract changes

Trace integer construction, conversion and addition through existing metadata,
preparation, emission, runtime execution, publication and `my-try-cpp` lowering.
Identify reusable owners, legacy consumers, overlapping inputs and bootstrap needs.
Resolve the open policies below and prepare scoped normative updates.

### 2. Complete the integer vertical slice

Evolve the existing common operation model to generate checked construction/casts,
addition, public operators, native conveniences and short names. Migrate admitted
named arithmetic/cast consumers to operators and checked construction; retain names
only for the distinct contracts listed above. Prove plain
C++ and compiler-generated C++ use the same concrete types and behavior owners.
Include range failures, signed/unsigned edges, overload resolution, and neighboring
division failure coverage before generalizing the operation mechanism.

### 3. Consolidate and retire the superseded integer path

Migrate admitted callers and checks inside `v0.2/`; replace the old concrete-integer generation
responsibilities, including private key adapters. Remove obsolete inputs, outputs
and tool plumbing inside the new project when no consumers remain. Old reference
copies need no synchronized changes or immediate deletion. Generate from empty staging and verify
deterministic freshness. Temporary coexistence must have a named removal gate.

### 4. Strings and one generated vector

Extend the proven model to string construction/lifecycle and a concrete vector,
including native convenience arguments. Consolidate transport/probes where their
contracts fit. Prove ownership, allocations, lifetime, layout and application reuse.
Resolve vector access/reference policy before exposing new signatures. Migrate
compiler middleware in a separately bounded ownership slice where appropriate;
equal names do not prove representation interchangeability.

### 5. Remaining families and physical consolidation

Apply the proven ownership model to remaining families. Repair shared-to-language
dependency detours; retain language-specific semantics in their real owners.
Group generated files, generate build inventories, measure compilation partitioning,
and retire compatibility paths after consumer migration. Extract focused private
primitives only at real semantic boundaries with evidence that the split helps.

Each slice includes its cleanup; do not postpone all deletion to the final phase.
If concept-correctness requires a wider refactor across ownership areas, stop and
present affected areas, risks, validation cost and options for confirmation before
expanding implementation scope.

## Cleanup and completion criteria

For every slice:

1. Inventory old inputs, generated outputs, consumers, checks and behavior owners.
2. Move required behavior/proofs to the common path; record intentional differences.
3. Migrate build, package, compiler and plain C++ consumers together as applicable.
4. Delete replaced tools, metadata fields, outputs and stale documentation inside
   `v0.2/` when their consumers are gone. Search for remaining references. Archival
   or deletion of the old reference tree remains a separate approved action.
5. Recreate the publication from empty staging and prove deterministic regeneration;
   avoid timestamp-driven output churn and reliance on stale checked-in artifacts.
6. Verify template-free public include closure, independent inclusion/linking,
   publication integrity, ownership/ABI checks and application specialization reuse.
7. Run focused behavior/failure, lifecycle and allocation proofs. Use sanitizers
   where relevant; preserve tests proving distinct layers rather than deleting them
   merely because their inputs overlap.
8. Measure caller compilation separately from runtime compilation, and clean builds
   separately from touched-family rebuilds. Benchmark changed call/representation
   paths; do not assume LTO makes poor boundaries free.
9. Record intentionally retained tooling and its responsibility. Any temporary
   duplicate path names its remaining consumers and removal condition.
10. Prove the slice's build/generation/test inputs are contained in `v0.2/`, apart
    from declared external dependencies. Before final promotion, prove the agreed
    workflows from an isolated copy/checkout without the old tree and verify that
    promotion to root does not break path discovery.

Success means one authored policy description and one behavioral owner per
operation, clear public/private/generated placement, working compiler and plain C++
consumers, and actual retirement of superseded paths. File count alone is not proof.
No audited tool is assumed immediately safe to delete. Config validators, operator
matrix tools, naming bridges, publication drivers and historical experiments need
individual responsibility/consumer review.

## Contract changes and open decisions

- **Agreed future change:** checked integer construction replaces current unchecked
  narrowing. `core_public_api.md` explicitly documents the old policy. Update that
  contract, exception specifications, tests and affected consumers before claiming
  the transition complete. No general ABI stability is assumed; record actual breaks.
- **Native operand policy adopted:** exact type-range normalization followed by
  domain-maximum promotion, requested-only overloads and deleted fundamental-input
  rejection declarations. Compound assignment remains a separate future contract.
- **Floating conversion policy:** integer domain maximum is settled; integer/float
  rounding versus exactness, and float-to-integer fractional/nonfinite handling,
  remain open as described above.
- **Carrier details:** implicit checked one-layer extraction is the adopted default.
  Resolve boolean conditions, failure mapping and payload copy/move behavior when
  their concrete slices require them; do not predesign the full carrier matrix.
- **Vector access:** the earlier discussion illustrated `at()` returning references
  and assignment through `names.at(1)`. Current public contracts prohibit escaped
  interior references and use owned accessors. Reference return, mutation spelling
  and invalidation rules remain proposals requiring explicit resolution; this plan
  does not approve that semantic change. Nested indexing/proxy redesign is excluded.
- **String observations:** byte-explicit length naming was suggested; exact method
  names and any changes to current string contracts remain to be settled.
- **Truncating conversion:** if needed, design an explicit separate operation;
  ordinary checked construction must not hide truncation.
- **Metadata/schema and paths:** adapt current prepared owners and representation
  bindings; do not create a second registry. Final grouping, migration compatibility,
  short-name collision policy and application publication composition need design.
- **Build/probe organization:** choose source partitioning from measurements; retain
  correctness, ABI probes, sealing, lifecycle guarantees and a documented bootstrap.

Non-goals: rewriting functioning engines for directory aesthetics, silently changing
arithmetic/promotion or source evaluation semantics, bulk removal of legacy tools,
forcing universal public-wrapper use internally, and implementing nested indexing
or a module-system redesign in this reorganization slice.

## Implementation checkpoint: native JSON foundation (2026-10-28)

After the bounded dependency review, the user authorized continuing with the first
native JSON foundation batch. `v0.2/tools/support/json/` adapts the existing lexical
reader and native pull cursor to standard C++ types. The runtime Result/shared
language adapter is excluded. A self-contained CMake target, focused tests and reuse
provenance are in `v0.2/`; old implementation files are unchanged. This is a scoped
exception to the earlier discussion-only status, not authorization to import the
generator/compiler or implement the integer metadata slice in bulk.

Clang 18/libc++ release and isolated-copy libc++ UBSan proofs passed. Clang/libstdc++
ASan+UBSan passed with leak detection disabled because LeakSanitizer cannot run in
the traced environment. libc++ ASan is blocked by an exception-library allocation
mismatch reproduced with a standalone std::runtime_error throw/catch. The
[foundation README](../../v0.2/README.md) records the scope and validation limits.

## Validation of the earlier planning changes

The earlier planning updates were documentation only. The implemented subset is
recorded in the subsequent checkpoints; examples outside that subset remain proposals.

## Implementation checkpoint: metadata value model (2026-10-28)

The next authorized bounded batch imports/adapts the existing generator's
`value.hpp`/`value.cpp` into `v0.2/tools/api_generator/`. It uses the native reader,
preserves JSON kinds and number tokens, and serializes deterministic object order.
Local ownership repairs reject non-object access and inconsistent output trees;
serialization validates numeric tokens, booleans, UTF-8 and depth, with
locale-independent string escaping. The model remains private tooling, not the
runtime public API. No integer metadata, resolver, emitter or compiler was imported.

Focused round-trip, malformed-data, schema-helper and diagnostic tests pass alongside
the reader tests in Clang/libc++ release, isolated libc++ UBSan and isolated
Clang/libstdc++ ASan+UBSan (leak detection disabled due to the recorded environment
limitation). libc++ ASan remains subject to the previously reproduced exception
library failure. Required build inputs remain entirely inside v0.2 plus the declared
native toolchain. Integer preparation/emission followed as the next separate slice.

## Implementation checkpoint: first integer generation slice (2026-10-28)

The user authorized continuing to the agreed integer slice. The two metadata files
now live inside `v0.2/runtime/metadata/`. Typed preparation validates identities,
native input ranges, checked constructors, domain maxima and requested operator
pairs. Emission consumes that plan and generates template-free int32/int64 headers,
four addition overloads, short names, one implementation source and diagnostic
plan/inventory JSON. Runtime implementation remains private and independent of the
generator. At the user's request, generated outputs live under `v0.2/runtime/generated/`,
and CMake generates and consumes that directory. These remain derived artifacts;
edit metadata or generator owners rather than generated C++.

Reuse/adaptation draws on the old scalar operator/conversion model, concrete integer
emission and range/arithmetic primitives; no broad carrier-dependent engine or old
runtime library was imported. The bounded native error contract is std::out_of_range
for failed representability checks. Signed addition retains its existing native
overflow domain. The [adopted slice contract](../../v0.2/specs/integer_slice.md)
records these limits, native-input exclusions and provenance. Generation currently
targets its host ABI, with emitted native-range assertions; sealed publication and
source-prepared/compiler integration remain separate work.

All seven focused tests passed in release and isolated libc++ UBSan and libstdc++
ASan+UBSan builds, with the existing sanitizer environment limits retained. Planning
coverage includes 56 admitted integer pairs; compiled coverage is the default four
addition overloads. Fresh staging is deterministic, stale/obsolete inventories
reject, and independent public header compilation requires no private includes.
No carriers, floats, native convenience arithmetic or further operators are claimed.

## Implementation checkpoint: requested native integer addition (2026-10-28)

The user approved and requested native integer convenience operands. Addition
metadata now enables type-range normalization, and publication requests use explicit
`{"native":"int"}` operand descriptors. A shared resolved-operand model carries C++
spelling, integer identity and native/public role into the existing promotion owner.
The emitter consumes these facts and reuses private integer addition; constructors
remain explicit and the result remains a public concrete integer.

The default publication has twenty supported signatures: the original four plus
int, unsigned int, long and long long in both directions with int32/int64. Exact
deleted declarations prevent unrequested fundamental inputs from being accepted
through ordinary C++ numeric conversions; these have no implementation bodies.
Signed/full-range uint64 mixtures reject at preparation. No literal-value-based
promotion, float/carrier support or compiler integration is added.

Eight focused tests cover the existing integer domain plus host native ranges,
expected result types, both directions, cv/value categories and large native values.
A restricted one-direction publication proves other inputs/reverse directions fail
with an intentionally deleted candidate. Release and isolated libc++ UBSan and
libstdc++ ASan+UBSan checks use the previously recorded environment limitations.
Generated artifacts remain under `v0.2/runtime/generated/`.

## Expanded family goal: decisions and pause (2026-10-28)

The user requested a goal covering operators, casts and overloads for numerics,
boolean, null, string and generated vectors. The goal was created and then paused
at the user's request to reconsider runtime scope around the symbol-aware compiler.
No expanded-family implementation has started. The detailed implementation plan
must apply the guiding principle above before work resumes.

Answers already agreed for that plan:

- Implement runtime, metadata, API generation and physical pre-lowering first.
  End-to-end `my-try-cpp` integration is a later goal, not an implicit compiler import.
- Float64 conversion follows the existing C++-style runtime direction: explicit
  integer-to-float conversion may round; explicit float-to-integer conversion
  truncates toward zero and rejects nonfinite/out-of-range values. Mixed arithmetic
  with int64/uint64 requires explicit conversion to float so possible precision loss
  remains visible. This is an agreed direction, not implemented floating support.
- The first generated vectors use value-copy reads through `at(index)` and explicit
  `set(index, value)` mutation, rather than exposing interior references.

The smaller-runtime principle does not silently revoke these decisions. Any later
change to them must be identified explicitly. It also leaves explicit constructors,
the template-free public boundary and optional C++ conveniences in place.

## Decision and checkpoint: optional native-int bridge (2026-10-28)

This supersedes the broader native-operand default above. Keep constructors explicit
and checked; handwritten C++ uses `int64 x{12}` or `static_cast<int64>(12)`. The proposed
implicit int constructor was rejected after a Clang probe showed copy initialization
could bypass other explicit constructors and narrow native values before checking.
No public constructor templates or implicit native conversions are introduced.

The default publication now requests only native int convenience addition, in both
orders with int32/int64. Those four inline adapters live in optional generated
`scpp/native_bridge.hpp`, cast explicitly to the prepared execution type and delegate
to a requested same-type core operator. Missing forwarding dependencies reject during
preparation. Core headers retain four runtime/runtime operators and never include the
bridge. Deleted bridge declarations prevent accidental standard numeric fallback.
Other native constructor inputs remain available through explicit checked casts.

Literal-only usage is coding discipline: C++ sees the same native int type for literals
and variables. Simple C++ source typing/lowering remains the compiler's responsibility
and should target core types directly. The existing general native-range resolver is
reused without publishing the earlier long/unsigned adapters by default. No compiler,
carrier, floating or further-operator implementation is included in this slice.

All eight release tests pass, including core-only exclusion, combined bridge overload
resolution, explicit-constructor checks, negative publication probes and regeneration.

## Resumed family goal and first binary slice (2026-10-28)

The goal resumed after the smaller-runtime discussion. Its active, coherent plan
was recorded in the now-retired v0.2 runtime type operations plan. The current
contracts are indexed by the [runtime API summary](../../v0.2/specs/runtime_api_summary.md).
The original scope is retained, with compiler integration separately excluded.
The first slice replaces addition-only generation assumptions with operation-aware
prepared facts and adds the remaining binary arithmetic/bitwise primitives. Default
demands emphasize same-type operators and optional native-int adapters; mixed source
expressions can be explicitly normalized by the future compiler. No exhaustive
mixed matrix is generated by default. Other family slices remain pending.

## Runtime plan, simple values and integer operation forms (2026-10-28)

The active metadata root is now `v0.2/runtime/metadata/runtime.json`, replacing the
integer-only filename. The same generator owns runtime-wide preparation/emission;
its consumer target is `scpp_runtime`. Boolean/null values, exact integer comparisons,
unary operations, shifts, compound assignment and prefix/postfix operations are
implemented. The [value contract](../../v0.2/specs/values_slice.md) records the bounded
reuse, runtime checks, condition conversion and explicitly unsupported scalar casts.

The default requests 69 core integer signatures and 48 optional native-int adapters;
a separate 602-signature test publication validates all integer widths and comparison
pairs. These are generated declarations over shared private primitives, not another
runtime dispatch engine. The compiler remains responsible for contextual resolution
and can request only its needed operations. Floats, strings and vectors remain
unimplemented pending their planned slices; the overall goal remains active.

## Public type naming: bool_t and default aliases (2026-10-28)

At the user's request, the concrete boolean class is `bool_t`, replacing the public
`boolean` spelling. `int_t` aliases the configured signed integer default, currently
int64; the source type mapping and C++ alias resolve from the same metadata fact.
`float_t` will similarly alias the configured floating default when implemented.
These names do not add wrapper identities or duplicate overloads. The bool_t rename
and int_t alias are implemented; float_t remains part of the pending floating slice.

## Floating and build checkpoints (2026-10-28)

The floating/scalar-cast slice is implemented inside v0.2. Its active contract is
[floating_slice.md](../../v0.2/specs/floating_slice.md); wide integer/float operations
require explicit conversion, and bool_t/numeric casts remain explicit. Per the user,
scpp::float_t aliases the configured floating type but is not globally imported by
short_names.hpp, avoiding collision with the C math typedef. int_t remains imported.

The requested build setup is recorded in v0.2/CMakePresets.json: Ninja, eight jobs,
Clang, retained libc++, and required mold linking. The private/generated scalar files
were renamed to match their broader ownership, with obsolete generated output retired.
String/vector implementation and final goal verification remain outstanding.

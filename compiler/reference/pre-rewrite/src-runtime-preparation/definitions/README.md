# Local exposure and semantic definitions, version 1
Doc Status: supporting

Every `.json` file in the configured definitions directory contributes `types`
and `operations` arrays with `schema_version: 1`. Files are read in sorted order;
IDs must be unique within each dataset. Unknown fields, unsupported kinds,
invalid references and inconsistent lifecycle bindings are errors. This vocabulary
is intentionally limited to the first preparation proof.

## Extension rule

Adding a type or operation that fits supported contracts requires only JSON
definitions. Concrete runtime types, member names and exposed operation identities
belong in those definitions; tool processing must not special-case them.

A new calling or lifetime pattern requires a reusable adaptation implemented once
in the tool, with an explicit metadata contract and a behavior proof. For example,
methods with arguments need an additional supported contract;
they must not be implemented as exceptions for individual methods or types.
Unsupported contracts fail preparation clearly. The standalone tests exercise a
second, distinct C++ type through the same adapters to verify this extension rule.

## Generated symbol identities

Symbols encode the complete ordered identity, with no hashing, truncation or
allocation history. Type facts use `type`, provider ID, local type ID and fact
name; callable bridges use `op`, provider ID and local operation ID.

- Prefix the encoded identity with `rp_`.
- Separate components with `_X_` (uppercase X).
- Preserve ASCII letters and digits; encode `_` as `__`.
- Encode every other byte as `_xHH_`, using uppercase hexadecimal digits.
  Non-ASCII text is encoded byte by byte from its UTF-8 representation.

For example, `type`, `simple_cpp`, `size`, `move_constructible` becomes
`rp_type_X_simple__cpp_X_size_X_move__constructible`. The operation
`string.byte_length` in that provider becomes
`rp_op_X_simple__cpp_X_string_x2E_byte__length`.

Decode tokens from left to right: `__` is an underscore, `_xHH_` is a byte,
and `_X_` starts the next component. Do not split by raw substrings: a literal
`_X_` inside a name is encoded as `__X__`. Component boundaries and escape-like
names remain distinct. Reordering or adding definitions cannot change an existing
symbol. Different providers and entity kinds occupy distinct namespaces.

Generated type metadata exports `fact_prefix`; append the encoded fact component
to locate its Clang constant. Its `declaration_artifact`, when present, locates
the raw Clang AST in a short package-local filename independent of symbol length.
Operation metadata exports the complete `symbol`
for callers to consume directly. This spelling convention does not establish
ABI compatibility across changed signatures. Content fingerprints remain for
change detection and artifact verification, separate from entity identity.

## Types

All types declare `id`, `cpp_name`, `header` and `kind`. `cpp_name` selects a named
C++ type through its qualified identifier; template-expression parsing is deferred.
`header` is relative to an include root. Generated C++ includes the actual header.

Named aliases of runtime class specializations are supported as opaque runtime
values: Clang measures the actual aliased type and metadata records the alias.
They do not advertise native fields. The experimental request exporter creates
such aliases from declared family/type arguments without parsing arbitrary C++
template expressions in `cpp_name`.

- `runtime_value`: a named C++ class/struct with `storage: "inline"` and a
  `lifecycle` object with an optional `construct` operation and either a `destroy`
  operation or explicit `cleanup: "none"`. The latter is verified by a C++ trivial
  destruction assertion; it is a semantic declaration, not an inferred default.
- `byte_span`: an alias of `std::string_view`, verified by a C++ assertion. The
  compiler uses it for borrowed literal byte operands, with measured pointer/length
  ABI expansion rather than passing the C++ view by value.
- `void`: a C++ void alias, verified by assertion; language binding must select
  the existing void definition. No storage or value is produced.
- `integer`: a native C++ integral type excluding bool, often named through an
  alias such as `std::size_t`. Clang supplies value width and signedness.

Optional `language_type: {"name": "int", "namespace": ""}` requests an exact
binding to a compiler language type. Integers must match an existing global
integer definition's measured width/signedness. A runtime value introduces a new
global named opaque definition. Compiler consumption accepts verified
`cleanup: "none"` or a validated `destroy` operation for the exact type.
Destruction and copy construction are implicit; their operations must omit
`expose_as`. Optional `lifecycle.copy_construct` names a same-type copy constructor.
Without that binding, copying is unavailable even if C++ supports it. Moves remain
unsupported. Optional `lifecycle.default_construct` binds an implicit `construct`
operation with no parameters and no `expose_as`. It authorizes default initialization;
`lifecycle.construct` alone does not. Optional `struct_field: true` on a runtime
value declares permission to use that type as an inline field. It must reflect the
provider/language contract; neither C++ traits nor size implies this permission.
Containing source records derive operation availability from these explicit bindings.
Omitting a required binding produces an unsupported-operation diagnostic.

Size, alignment and C++ traits come from Clang-emitted constant expressions, not
from guessed field layouts or handwritten numbers. Class declarations are extracted
from Clang's JSON AST. Private members are descriptive; they do not become exposed
fields, and member offsets are not exported in version 1. C++ copy/move availability
does not advertise executable language operations; copying requires its explicit
lifecycle binding and implemented adaptation.

## Operations

All operations declare `id`, `kind` and `error_policy: "terminate"`. Object
operations also declare `type` (the owning runtime value ID). Supported adaptations are:

| Kind | Validated C++ action | Published semantics and ABI |
|---|---|---|
| `construct` | Construct the selected type from ordered declared integer `parameters` (possibly empty) | Construct an owned value into aligned uninitialized caller storage; integer inputs pass directly |
| `construct_from_bytes` | Construct the selected type from `std::string_view` | Borrow bytes and their `std::size_t` count; construct an owned value into aligned uninitialized caller storage |
| `const_method` | Invoke the named no-argument `member` through a const object reference; exact result must match `result_type` | Borrow a live object by address; return a declared integer value directly |
| `copy_construct` | Placement-construct the owning type from a const source reference | Borrow a live source for the call; initialize independent aligned caller storage; preserve source ownership; two-address void ABI |
| `destroy` | Invoke the selected type's destructor | Consume the owned live value; leave its storage with the caller |
| `free_function` | Invoke qualified `cpp_name` from `header` using an exact function-pointer signature | Direct integer values or call-scoped const object borrows; integer, void or owned inline result |

`free_function` requires `parameters` (ordered declarations, possibly empty) and
`result_type`. Integer parameters use their type ID; object parameters use
`{"type":"item","passing":"const_address","borrow_scope":"call"}`. Its exact C++ function-pointer cast rejects mismatched overloads.
Optional `expose_as: {"name": "runtime_abs", "namespace": ""}` publishes a source
callable name. Global direct-integer functions, `construct` operations and
call-scoped `const_method` operations on supported inline types are consumable.
Unexposed operations stay available in preparation metadata. See
[scalars.json](scalars.json) and the [consumer contract](../../docs/details/runtime_package_consumption.md).

The byte-span constructor copies according to the selected C++ implementation;
our string contract requires its contents to remain valid independently of the
input bytes. This semantic obligation is tested for the real string provider.
Other types selecting this adapter must provide their own behavior proof.

`const_method` additionally requires `member` and `result_type`. An exposed method
must declare `borrow_scope: "call"`: it may inspect its receiver only during the
call and must not retain its address. C++ `const` alone cannot prove this semantic
promise. Providers must validate that behavior; the compiler checks that the
contract is present. Unsupported fields
are rejected for each kind. Symbol names use the [identity encoding](#generated-symbol-identities)
above. No runtime type or method names are embedded in the tool's dispatch.

The exported operation records distinguish semantic parameters/results from ABI
parameter indices, storage passing and measured LLVM types/attributes. A
caller-storage result is an explicit bridge parameter, not a claim that all C++
aggregate returns have the same ABI. Clang validates actual C++ construction and
method selection before any package is published.

Generated bridges catch C++ exceptions, report the failing operation and terminate
with a failing exit status. They also diagnose null object/storage pointers and
invalid null byte spans. Storage size/alignment, object lifetime and ownership are
caller obligations; pointers do not carry enough information to check them all.
No errors propagate as language exceptions and no recovery result is advertised.

## Literal and output bindings

An exposed `construct_from_bytes` may name a `parameter_type` of kind `byte_span`
and declare `language_binding: "byte_literal"`. One may also declare
`default_literal: true`. Typed contexts use the bound result type; untyped literals
use the unique default. Existing operations without `parameter_type` remain
preparation-only byte-span bridges.

An exposed free function with one borrowed object parameter and a void result may
declare `language_binding: "echo"`. The compiler resolves the role by canonical
operand type, then uses the same ordinary callable contract. Only one operation
per role/type and one default literal binding are permitted. Neither role implies
copying permission or permission to retain an argument address.

See the [implementation and source limits](../../docs/details/runtime_string_literals.md)
and [two-type proof](../../tests/integration/runtime_strings.php).

## Named conversions and returning functions

An exposed `free_function` may declare `conversion_purpose: "explicit_cast"` or
`"text"`. It must take one semantic argument and return a non-void value. These
permissions are independent; neither enables implicit assignments. Duplicate
purpose/source/destination bindings and identity overrides are rejected.

Free functions may return an inline runtime value. The bridge initializes an
owned result in hidden caller storage at ABI index zero; arguments follow it.
A type produced only by such functions need not declare a lifecycle constructor.
Its cleanup contract remains mandatory.

Optional `cpp_template_arguments` contains declared type IDs in specialization
order. A direct integer argument may use
`{"type":"native_int","passing":"direct","cpp_passing":"const_reference"}`
to adapt a bridge scalar to the native function's const-reference parameter.
No borrowed source-object access is introduced by this adaptation.

See [conversion selection](../../docs/details/conversion_selection.md) for
compiler ownership, supported execution forms and proof boundaries.

## Default console surface

`strings.json` selects provider implementations for strict string-to-integer
conversion, line input, concatenation and checked byte length through the existing
`free_function` contract. Concrete Simple C++ adaptation code lives in
`../include/scpp_provider`, not in generation or compiler dispatch. Input returns
an owned string; inspection/parsing borrows; concatenation borrows two inputs and
returns an owned value. See [contracts and proof](../../docs/details/runtime_console.md).

## Complete native value records

A `value_record` declares `storage: "inline"`, `construction: "zero"`, `copy: "value"`,
`cleanup: "none"`, a new `language_type`, and the complete ordered `fields` list. Example:

```json
{
  "id": "point",
  "cpp_name": "geometry::point",
  "header": "geometry.hpp",
  "kind": "value_record",
  "storage": "inline",
  "construction": "zero",
  "copy": "value",
  "cleanup": "none",
  "language_type": {"name": "point", "namespace": ""},
  "fields": [
    {"name": "x", "member": "native_x", "type": "signed32", "writable": true},
    {"name": "y", "member": "native_y", "type": "signed32", "writable": true}
  ]
}
```

`signed32` must be a declared integer exposure with a matching language definition
eligible for scalar struct fields. `member` is the actual C++ member; `name` is the
source-visible field name. Setting `writable: false` restricts direct source writes;
it does not change the native member type or whole-record value-copy permission.

Preparation requires a complete public field list in native declaration order, an
aggregate with standard layout, trivial default construction/copy/destruction and
copy assignment. Clang verifies each exact native scalar type and measures offsets,
size and alignment. Bases, bit fields, defaults, references and const/volatile fields
are unsupported. The compiler additionally verifies that its generated value layout
matches all imported physical facts. Incompatible packing or over-alignment fails.

This contract enables local by-value construction, copying and field access. It does
not enable aggregate ABI parameters/results. A free function can accept a `value_record`
using `{"type":"item","passing":"const_address","borrow_scope":"call"}`;
its C++ parameter must be `const T &`. The compiler passes an existing local of the
exact declared language type without a copy. The function must neither mutate the
object nor retain access after returning; preparation checks the signature, while
the local semantic contract supplies the non-retention promise. Record temporaries
and structurally equal but differently named source records are not accepted.
No field-name/type-name dispatch is needed when another record meets this contract.

### Mutable object borrowing and prepared record results

Free functions accept
`{"type":"item","passing":"mutable_address","borrow_scope":"call"}`.
The exact C++ parameter is `T &`; metadata retains mutable borrowing, and the
physical ABI passes `void *`. The provider must honor call-scoped non-retention.
The compiler adapter now consumes this passing mode for existing writable locals.
Const references and temporary mutable arguments are rejected.

A free function may return a `value_record` through aligned uninitialized caller
storage, using the same owned-result adaptation as other inline objects. The bridge
placement-constructs the actual result type; metadata publishes a hidden address
and physical `void` result. This is not native aggregate-by-value ABI support, and
compiler consumption of record results is not implemented by this preparation proof.

### Explicit allocation effects

A `runtime_value` may declare `"resource": "allocation"`. It must be noncopyable
(no `copy_construct` binding), cannot grant `struct_field`, and can only be produced
by zero-argument `construct` operations, which promise empty ownership. Its object
cleanup policy remains explicit and separate from allocation release.

Resource call parameters require `allocation_effect`, for example:

```json
{"kind": "transfer", "owner": 0, "destination": 1}
```

Positions are zero-based semantic parameters, excluding hidden ABI result storage.
`acquire`, `release` and `inspect` have just `kind` and `owner`; `transfer` also
requires a distinct same-type `destination`. Every resource parameter is covered.
Inspection requires `const_address`; other effects require `mutable_address`.
All borrows are call-scoped. Empty release is valid; inspection requires owned
storage. Source object destruction never implicitly satisfies the allocation
obligation. See [the compiler contract](../../docs/details/allocation_ownership.md)
and [the configured provider](allocation.json). Resource fields
are not supported by this first contract.

### Typed storage families

A runtime descriptor can declare `storage_family` with `counter_type`, `primitives`
and `operations`; see [element_storage.json](element_storage.json). Its language
name becomes a generic family, requiring one eligible element type. It has an
implicit empty constructor, no copy operation, no cleanup, and no field permission.
The concrete compiler type acquires the allocation obligation.

The primitive roles are `allocate`, `next`, `commit`, `at`, `pop`, `count`, `release`
and `transfer`. Their fixed signatures are validated before export and again on
import. `operations` names the source functions for allocate/push/pop/count/release/
transfer. These names are configurable; JSON does not define arbitrary compiler
instructions. `next` and `at` return an internal `address` type, measured as a native
pointer. It cannot have a source name or an exposed callable result.

The signed counter supplies capacity, stride, alignment, index and count ABI facts.
Clang verifies its actual width; LLVM measures concrete element layout separately.
Raw descriptor fields and prefix publication are inaccessible to source code.
This protocol prepares no C++ specialization of the source element.

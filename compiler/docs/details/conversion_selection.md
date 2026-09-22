# Conversion requests and selection
Doc Status: supporting

Status: implemented for existing implicit integer widening and named provider
conversion calls, including owned inline results and borrowed-object inputs with
scalar results. Cast expression syntax, general
numeric casts, boolean conversion and condition/text syntax integration remain
separate slices. The source entry for the first explicit conversion is
`string_from_int(value)`, declared by runtime definitions rather than compiler code.
The [console slice](runtime_console.md) adds `int_from_string(value)` through the
same selector and ordinary call path.

## Semantic request and selected execution

`check_bodies/conversion_request` contains canonical source and destination type
IDs plus a `load_runtime/conversion_purpose`. The purposes distinguish implicit
typed boundaries, explicit casts, condition truthiness and text conversion.
They are semantic permissions; the same destination does not imply the same rule.

`Conversion_Resolver::resolve()` reads one fixed `Type_Resolution` and returns a
short-lived `conversion_selection`, or null for unsupported requests:

| Form | Meaning |
|---|---|
| `identity` | Reuse the value's representation; ownership rules still govern initialization/copying. |
| `primitive` | Apply the selected compiler operation; currently same-family, same-signed integer widening. |
| `provider_call` | Invoke the exact accepted callable ID for the purpose/source/destination tuple. |

Implicit initialization, assignment, arguments and returns now create requests
with `implicit_boundary`. Existing family permission, signedness and width rules
are unchanged; conversion nodes still lower to `sext` or `zext`. No lossy or
cross-signed implicit conversion was added. Explicit numeric casts are not inferred
from implicit widening support. Condition requests currently report unsupported;
existing integer condition handling remains in its current owner.

For named conversion calls, ordinary argument checking first applies the declared
parameter boundary. Checking then requests that parameter type, result type and
operation's declared purpose from the selector. The accepted provider target becomes
an ordinary checked call, with the existing signature dependency and argument range.
Thus `string_from_int` can accept a supported implicit widening to its declared
integer parameter; this does not enable arbitrary conversion chains or cast syntax.
No conversion request or selection object is retained per value. Primitive nodes
and ordinary call records remain the execution representation.

## Local definitions and the adapter

An exposed `free_function` may declare `conversion_purpose: "explicit_cast"` or
`"text"`. It must have one value/const-borrow input and a non-void result. It cannot
also declare a language binding, replace identity or duplicate another operation's
purpose/source/destination key. Other purposes are represented in the model but
are not accepted as provider conversion bindings in this slice.

`Package_Adapter::call_conversion()` imports this optional permission into the
shared `runtime_callable`. Whole-package validation rejects duplicate bindings.
`Type_Resolution` indexes accepted signatures by purpose and canonical type IDs;
workers query this fixed index. No runtime name, cast name or concrete string type
selects compiler behavior. Missing bindings remain unsupported.

Definitions are still our local semantic authority. Existing Simple C++ metadata
is not imported. Simple C++'s contracts distinguish explicit casts, condition
truthiness and text coercion; future integrations must preserve that distinction.

## Generic C++ functions and owned results

The preparation tool now supports free functions returning a declared inline
runtime value, in addition to integer/void results. The physical bridge returns
void and receives hidden destination storage at ABI position zero. Ordinary
semantic arguments follow it. Metadata states owned result, aligned uninitialized
storage before the call and a live object afterwards.

The bridge uses placement initialization from the function's returned C++ prvalue.
That initializes destination storage without requiring a copy or move constructor.
Clang verifies the exact function signature and actual ABI in each artifact variant.
The adapter validates the hidden position, semantic/physical argument mapping and
storage transition. Existing checking, lifetime analysis, lowering and LLVM call
emission consume the same caller-storage result contract used by constructors.
A runtime type produced only by returning functions need not advertise a constructor;
its cleanup contract remains required.

For exact native signatures, free functions may declare:

- `cpp_template_arguments`: ordered declared type IDs selecting a C++ function
  specialization; no arbitrary C++ expressions or template-family materialization.
- A direct integer parameter with `cpp_passing: "const_reference"`: the bridge
  receives a scalar by value, and the native function borrows that bridge-local
  scalar. This does not create a borrowed source-object lifetime.

The default package binds `scpp::cast<scpp::string_t, std::int64_t>` through the
public `scpp/lang/php.hpp` header as `string_from_int`. The public header establishes
the runtime's include order; combining its internal text header with a later cast
header caused a C++ specialization-after-instantiation error in the preparation
probe. Header selection stays in provider configuration.

Errors retain the existing bridge-contained terminate policy. Owned returns from
source-defined functions, moves, assignment and exception unwinding are not added.

## Proof and update boundaries

[Integer conversion tests](../../tests/features/integer_conversions.php)
retain native widening and update proofs, including arbitrary provider names and
13/37-bit widths. They also reject use of implicit family permission for other
conversion purposes.

[Runtime conversion tests](../../tests/integration/runtime_conversions.php)
compare execution with native C++ using the same providers. Coverage includes real
string results at signed integer limits, local copies, temporary output, and an
aligned heap-owning type with deleted copy/move constructors. That second type is
added through definitions and supports returning functions with value and borrowed
arguments. Construction/address checks and destruction traces cover early/normal
returns. Two integer-to-string bindings with different purposes prove that purpose
participates in selection.

The proof checks reversed body workers, lifecycle preparation joins, unchanged
snapshots and one body increment. Unchanged callers and unrelated conversion bodies
retain modules/native objects. Malformed purpose, ownership, destination transition,
physical position and duplicate metadata are rejected. Explicit conversion does
not authorize implicit assignment, general echo coercion or object copying.

Requests/selections and lookup scratch work remain private or fixed for workers.
Shared provider signatures retain their existing invalidation behavior; package
changes use the existing broad replacement boundary. Actual multithreading and
native performance/memory claims remain outside this proof.

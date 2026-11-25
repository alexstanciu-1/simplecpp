# Shared base views for classes and structs

Doc Status: supporting

Bare classes retain shared-by-default source semantics. Bare structs remain
inline values. An explicit `shared<DerivedStruct>` converts to `shared<BaseStruct>`
along the same settled base chain as a class owner. Empty handles stay empty.
Copying the base view keeps the concrete payload alive. The final release destroys
the derived fields as well as the base. Explicit struct downcasts require a polymorphic source; see [checked access](object_access.md).
[Three-flavor struct proof](../tests/shared_struct_views_native.py).
The [virtual-method slice](virtual_methods.md)
adds inherited method lookup and explicitly marked virtual replacements.
Nonvirtual method hiding remains rejected.

```php
class Node { public int $start = 0; }
class IntegerLiteral extends Node { public int $value = 0; }
function start_of(Node $node): int { return $node->start; }
$literal = new IntegerLiteral();
echo start_of($literal);
```

The common record AST retains its one base reference. Existing layout preparation
resolves and watches ancestry before bodies; no flattened member index or extra
per-expression ancestry state is introduced. Fields and effective constructors
keep their original declaring owners. Class base initializers resolve to the
payload identity, independently of the class's default shared value type.

Conversion preparation selects `owner_view` or `require_owner_view` for an admitted
upcast. Lowering consumes that choice, for example:

```cpp
// Adapter identity abbreviated here; emitted names use the full deterministic hash.
start_of_(scpp_adapter_HASH(var_literal_0));
```

The demand-emitted [concrete inline adapter](inline_adapters.md) transfers ownership of its by-value input into
the result and uses C++ pointer conversion for the view. An lvalue input first
retains its control block; a temporary transfers without an extra allocation.
Optional-to-required conversions wrap that result in the existing presence check.
Optional views preserve null. Related shared equality compares control identity.
References remain invariant; this does not authorize a writable derived handle
slot to be treated as a base handle slot.

Both strong and weak generated owners hold a typed view and a pointer to
`scpp::application_control`. The runtime owns strong/weak counters and releases.
Count increments and decrements are inline; only final strong cleanup enters the
compiled runtime. Generated allocation callbacks destroy the concrete payload
and eventually delete its allocation. One implicit weak hold survives through
payload destruction; explicit weak observers keep the allocation alive afterward.
Locking retains strong ownership before returning its saved typed view, and never
uses that view after expiry. Counters remain thread-confined.

The concrete allocation inherits the runtime control and contains the payload
in a union. Last-strong destruction ends the payload lifetime; deleting the
allocation does not destroy it twice. C++ allocation/constructor unwinding handles
failed construction before an owner is published. This ownership mechanism itself requires no virtual payload destructor or vtable.
Virtual method hierarchies acquire both through their separate dispatch contract.

`scpp/owner_access.hpp` publishes the control through the runtime API generator's
ownership template. Its include closure has no standard-library dependencies.
This changes generated owner layout and requires rebuilding native consumers and
the runtime together. It does not migrate the separately published runtime
ownership wrappers used by native metadata bindings.

Verification: `XDEBUG_MODE=off python3 -B compiler/tests/shared_upcasts_native.py --memcheck`.
The suite covers all three flavors on Clang/GCC, isolated runtime headers,
constructor failures, concrete destruction, weak lifetime, nullability and
retained/fresh equivalence across ancestry edits/removal/recovery. It also rejects
cross-kind bases, cycles, downcasts, unrelated conversions and nonvirtual method hiding.
On the tested 64-bit targets, handles and weak handles are 16 bytes each, the
control is 24 bytes and the fixture Leaf allocation is 48 bytes. Upcasts allocate
nothing; no performance timing claim is made.

# Class defaults and explicit object boundaries

Doc Status: supporting

The common `ast_record_declaration` retains an immutable `record_kind` and owns
its original members, optional written base and nominal `record_type_definition`.
`ast_instance_field` is shared by classes and structs. Existing syntax inventories,
singular duplicate links and stable symbol publication remain the owners of their
respective relationships. There is no second class preparation pipeline.

Bare structs resolve to their inline payload. Bare classes resolve to a required
shared identity; `?Class` resolves to its optional identity. The payload owns these
canonical definitions once per declaration generation. Both class contracts lower
to the same typed owner view and counted allocation. Structs do not allocate an
additional required identity. Written type references remain unchanged.

```php
class Node { public int $start = 3; }
class Addition { public ?Node $left; }
function start_of(Node $node): int { return $node->start; }
$a = new Addition();
$a->left = new Node();
echo start_of($a->left);
$a->left = null;
```

A class instance field written as `Node` retains the required identity. A default
or a direct write by its declaring constructor must establish initialization
before normal exit. Constructor preparation uses its existing readonly
initialization flow to check early reads and method calls. A mutable required
field still admits reassignment at the ordinary required destination boundary.
Use `?Node` for a field that may be empty after construction, as above. Struct
instance fields and static fields retain their respective bootstrap exceptions.
The [normative contract](../../specs/type_use_modifiers.md) defines the rules.

Existing conversion preparation rejects known null at required destinations and
selects `require_owner` for potentially empty values. Lowering emits the owner's
presence helper exactly once around that argument/return value; the helper checks
before the callee executes or the return succeeds. It preserves allocation identity
and uses the existing empty-owner runtime failure. Required-to-required conversion
is identity. Optional parameters/results and optional literal-null defaults accept
absence. Passing a class by value copies its owner: mutation is shared, while
parameter rebinding does not replace the caller's handle.

The existing nominal and signature watches invalidate changed declaration kinds,
field targets and required/optional annotations. A failing nullable lookup attaches
its signature watches before resolution, so retry and retirement use the same owner.
Nullable elements resolve once; the resolved identity selects either the class
owner or the existing runtime specialization. No new index, preparation traversal
or expression-level presence cache is added.

## Bounded coverage

Exact-class allocation, instance fields, existing constructors/methods, by-value
function boundaries, optional defaults and exact weak locking are covered.
`weak<Class>` locks to `?Class`; `shared<Class>` is rejected as a redundant storage
spelling. Explicit shared struct behavior remains unchanged.

Class inheritance and derived-to-base shared views are now covered by the
[shared base-view slice](shared_upcasts.md). Concrete virtual methods and mandatory
overrides are covered by the [virtual-method slice](virtual_methods.md). Borrows,
new value/unique modes and general object-reference parameter extensions remain deferred. Required class
locals currently require an initializer: the normative definite-assignment feature
is not implemented by this slice. `$this` may be a field/method receiver, but obtaining
an owning `$this` value is deferred until its ownership binding is defined.

## Verification

`XDEBUG_MODE=off python3 -B compiler/tests/classes_native.py --memcheck`
compares retained and fresh diagnostics/output through edits, deletion, errors,
retries and recovery in cpp/php/js. Native programs run on Clang and GCC with strict
warnings; the ownership probe checks aliasing, parameter rebinding, single evaluation,
empty argument/return/dereference failures, weak expiry and cleanup. Optional Valgrind
checks cover that probe. Unrelated sources remain reused; no-op builds prepare and
emit nothing. Existing extends, constructor and weak suites protect struct behavior.

The review regressions additionally add/delete/restore a missing class declaration
without editing its signature or caller. Successful optional/required return edits
must add/remove the caller's presence conversion despite identical native owner
spelling, with fresh-output equivalence and native execution after each edit.
Source-level `weak()`/`lock()` tests prove optional results, live allocation
identity, retention by a locked value, expiry and required-boundary failure before
the callee body. Runtime carrier tests cover the shared specialization path.

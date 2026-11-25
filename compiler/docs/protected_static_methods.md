# Protected static methods

Doc Status: supporting

The [inheritance access contract](../../specs/inheritance_v0_2.md#access) owns semantics.
This slice adds visibility to the existing static-call path for classes and structs.

```php
class Base {
    protected static function hidden(int $n = 3): int { return $n; }
    public static function read(): int { return self::hidden(); }
}
class Child extends Base {
    public static function readBase(): int { return Base::hidden(); }
}
echo Child::readBase();
```

Calls from the declaring record or its descendants are allowed, including from
instance methods. Unrelated code receives a protected-method diagnostic. Static
access has no instance-receiver restriction and does not grant access to `this`.
Existing defaults, checked conversions and writable-reference binding are unchanged.

PHP `self::`, C++ `Self::` and JS `class.` continue to name the lexical declaring
record. JS uses `Base.hidden()` for explicit qualification; PHP/C++ use
`Base::hidden()`. Both `Child::hidden()` and lexical self inside Child start lookup at Child.
The existing method lookup watches each visited record and named method slot and
binds the selected Base declaration directly, including its defaults and visibility.
PHP `parent::`, C++ `Parent::` and JS `super.` start method lookup at the lexical base.
Protected static properties and constants use declaring-type names, descendant names or lexical self.
A descendant may read `Base::STEP` and update `Base::$count`. Inside Base,
`self::$count`, `Self::count` or `class.count` selects the same storage. Inside Child,
`Child::$count` and `self::$count` also resolve to Base’s storage. Each visited record
is watched for replacements, and the selected member symbol supplies storage or the
constant fact through the ordinary binder. The literal
declaration stores one visibility enum. Binding uses the same protected ancestry
query and dependency watches as static calls, including on a failed access.

## Static redeclarations in descendants

A class or struct can redeclare an inherited static method. Declaration preparation
checks the nearest ancestor declaration once using the shared callable compatibility
check. Parameter count, canonical types, passing modes, optional positions and return
type must match. Parameter names and default values can differ. Visibility can widen,
including from private, or stay equal. Source `final` seals a static declaration at
any level. Virtual, abstract and override modifiers are invalid for static methods.

Each declaration keeps its own symbol and defaults. A call finds
the nearest declaration from its qualifier. A lexical self call inside an inherited
body still starts at the record that owns that body. A parent call starts at the base.
Static finality is checked at preparation and adds no C++ virtual slot.

## Implementation and retained state

Methods carry their existing `is_static` and `is_protected` flags. The declaration's shared
`require_access` query supplies lexical-only checks for static methods. The shared
protected-access query subscribes the body to the lexical caller's layout before
checking access. For instance members it also observes a distinct receiver layout.
A layout relays invalidation from its base, including through an intermediate
record. The same query checks protected constructors without a receiver. The usual stable method
entry tracks visibility, signature and deletion changes.

Ancestor data access uses a fieldless `ast_ancestor_name` marker for PHP and JS.
Both data access and calls ask preparation for the lexical base, so an edit to
the base link invalidates the selection even when the caller's tokens are reused.
Named C++ access uses the existing qualified name. All three bind the selected
member's stable symbol and reuse ordinary property reads, writes and constant facts.
The existing binder settles arguments; lowering emits the same direct helper call,
with no receiver, retain/release operation or runtime visibility test.

## Called-type implementation ownership

An explicit call through `Left` or `Right` checks access and arguments against the selected
method declaration. The session interns the method symbol and the qualifier’s record symbol as a pair.
The defining unit shares the authored signature and tokens across pairs and prepares one private
body per reachable pair. Recursive calls reuse the same pair. C++ calls the selected helper directly.
Only helpers with a caller in another unit need a header declaration.

Signatures, defaults and syntax are checked eagerly. A static body receives semantic checks on demand.
Ordinary function and instance-method bodies remain preparation roots, as does the selected entry.
A successful preparation retires implementations unreachable from those roots, including recursive
cycles, and forgets their watches. Failed preparation leaves executable publication pending for retry.
An unchanged incremental invocation skips demand traversal and body preparation.
Inside a static implementation, a lexical self or parent call forwards the current called-type symbol
to the selected method. For example, when `Leaf::run()` enters a method declared on Middle,
`self::step()` starts lookup at Middle and carries Leaf. Writing `Middle::step()` establishes Middle.
PHP and JS `static::step()` and C++ `Static::step()` start method lookup at the current called type
and forward it. Visibility is checked from the body’s lexical owner. The selected declaration supplies
defaults and argument conversions. Lookup watches each nearer method slot, including empty slots,
so adding or deleting a descendant declaration updates the inherited caller on the next compilation.
Late-static property reads, writes and constant reads start at the same called type.
For example, PHP `static::$n = static::STEP;` uses the called type to find both declarations.
C++ spells this `Static::n = Static::STEP;` and JS spells it `static::n = static::STEP;`.
The ordinary static-data binder checks visibility from the lexical owner and watches the selected
declarations. Siblings that inherit a property write the same storage. Siblings that each declare
their own property write separate storage. Discarded compound assignments use the selected
property's operator and checked write-back rules. Prefix/postfix updates also work in the final
update clause of a for loop. A failed conversion leaves the final store unexecuted.

An expression qualifier establishes its declared record type. Ordinary instance bodies use their
existing lexical lookup without a runtime called-type context. Static factories use `new static(args)` in PHP and JS and `new Static(args)` in C++.
Preparation selects the called class and binds its effective constructor from the lexical owner's
access context. The expression yields a required shared owner of the called class. Returning it
through a base-class signature uses the ordinary return conversion. A change to the called class
or selected constructor invalidates the prepared factory through its existing watches.

Run `python3 -B compiler/tests/called_methods_native.py` to compare incremental and fresh
artifacts and diagnostics and check that retired bodies are collectible while a sibling retains its body.

## Verification

`XDEBUG_MODE=off python3 -B compiler/tests/protected_static_native.py`

Nineteen incremental checkpoints per flavor compare retained and fresh diagnostics
or emitted artifacts. They cover defaults, private/public/protected edits, intermediate-base changes that select a different implementation, deletion, retries without edits and recovery. Successful revisions
build and execute with Clang. Negative checks cover external calls through the declaring and descendant types.
The protected static data fixture compares 38 checkpoints per flavor against fresh
builds, including visibility edits, base replacement, deletion and recovery.
Twelve additional ancestor-call checkpoints per flavor exercise both caller kinds,
intermediate and immediate base changes, private access, deletion and recovery.
The static redeclaration fixture adds 28 checkpoints per flavor covering defaults,
visibility, signature mismatches, both static/instance collisions, final declarations
on classes and structs, edits, deletion and recovery.
Existing protected-instance and ordinary method regressions protect receiver rules
and shared binder behavior. Native timing results are reported separately from
cumulative frontend/LLVM work; no preparation speedup is claimed.

Before inherited lookup, all 48 new incremental checkpoints passed, as did the 24
protected-instance checkpoints, ordinary method suite, namespace checks and PHP style.
The registered protected-static test took 18.27 seconds; the broader method suite
took 110.04 seconds. No runtime or generator changes were needed.

Fresh single-observation timings in milliseconds:

| Flavor | PHP compiler | Native wall | Frontend sum | LLVM sum | Optimizer | Codegen | Link | End-to-end |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| PHP | 7.09 | 207.29 | 26.08 | 49.16 | 19.79 | 15.93 | 64 | 316.23 |
| C++ | 7.54 | 212.57 | 30.88 | 46.31 | 18.61 | 14.66 | 64 | 314.19 |
| JS | 6.42 | 220.92 | 30.75 | 53.38 | 22.04 | 17.41 | 65 | 320.27 |

O2/g0, Ninja8, mold, no PCH/LTO, prebuilt libc++ runtime, direct Ninja qualification.
Frontend/LLVM sums accumulate translation-unit work; optimizer/codegen are nested
inside LLVM. End-to-end includes fresh service startup, compilation/emission, native
setup/build and execution. These observations do not establish a performance change.

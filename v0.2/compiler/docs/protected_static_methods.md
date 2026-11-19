# Protected static methods

Doc Status: supporting

The [inheritance access contract](../../../specs/inheritance_v0_2.md#access) owns semantics.
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
PHP `parent::` and JS `super.` start at the lexical base. C++ names the base explicitly.
Static redeclarations, protected static properties/constants
and expression-qualified static calls are separate work.

## Implementation and retained state

The parser preserves existing `is_static`/`is_protected` flags and rejects deferred
data members after distinguishing them from methods. The declaration's shared
`require_access` query supplies lexical-only checks for static methods. Source-call
preparation subscribes to the lexical caller's ancestry before the access decision,
including failed access, so intermediate-base edits can invalidate unchanged callers.
Only protected static calls perform that ancestry walk. The usual stable method
entry tracks visibility, signature and deletion changes.

There are no new AST fields, nodes, occurrence facts, indexes or preparation passes.
The existing binder settles arguments; lowering emits the same direct helper call,
with no receiver, retain/release operation or runtime visibility test.

## Verification

`XDEBUG_MODE=off python3 -B v0.2/compiler/tests/protected_static_native.py`

Nineteen incremental checkpoints per flavor compare retained and fresh diagnostics
or emitted artifacts. They cover defaults, private/public/protected edits, intermediate-base changes that select a different implementation, deletion, retries without edits and recovery. Successful revisions
build and execute with Clang. Negative checks cover external/unrelated access,
inherited protected calls from outside the hierarchy, and deferred protected data members.
Twelve additional ancestor-call checkpoints per flavor exercise both caller kinds,
intermediate and immediate base changes, private access, deletion and recovery.
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

# 07. References, ownership and lifetime
Doc Status: planning

> **Important: LLVM is deferred by default. Work on the direct LLVM backend only when the user explicitly requests it. The v0.2 default scope is Frontend + C++ S2S.**

[Catalog and workflow](README.md) · [Source merge ledger](source_merge.json)

Requires storage places and calls. Discuss aliasing and lifetime before enabling reference paths.

Order is a discussion sequence, not a claim that every row is a prerequisite. Split combined examples before implementation.

## Progress

Edit these rows as work proceeds. Imported source support is recorded below, independently of this progress.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [FUNC-REF-001](#func-ref-001) | pending-discussion | `function f(int &$a): void {}` | unverified | unverified | deferred | — |
| [FUNC-REF-002](#func-ref-002) | pending-discussion | `function &f(): int {}` | unverified | unverified | deferred | — |
| [FUNC-REF-003](#func-ref-003) | pending-discussion | `function &f(int &$a): int {}` | unverified | unverified | deferred | — |
| [FUNC-REF-004](#func-ref-004) | pending-discussion | `function &f($a) { return $a; }` | unverified | unverified | deferred | — |
| [VAR-UNSET-001](#var-unset-001) | pending-discussion | `unset($a);` | unverified | unverified | deferred | — |
| [VAR-CLEAN-001](#var-clean-001) | pending-discussion | `clean($a);` | unverified | unverified | deferred | — |
| [REF-RETURN-001](#ref-return-001) | pending-discussion | `function &bounce(array &$a) { return get_inner($a); }` | unverified | unverified | deferred | — |
| [REF-RETURN-002](#ref-return-002) | pending-discussion | `function &get_inner(array &$arr): array { return $arr["inner"]; }` | unverified | unverified | deferred | — |
| [REF-BIND-001](#ref-bind-001) | pending-discussion | `$inner =& $arr["inner"];` | unverified | unverified | deferred | — |
| [TYPE-VAR-003A](#type-var-003a) | pending-discussion | Strict example to define (legacy spelling retained below) | unverified | unverified | deferred | Strict source adaptation; imported legacy form retained |
| [TYPE-VAR-003B](#type-var-003b) | pending-discussion | Strict example to define (legacy spelling retained below) | unverified | unverified | deferred | Strict source adaptation; imported legacy form retained |
| [TYPE-VAR-003C](#type-var-003c) | pending-discussion | Strict example to define (legacy spelling retained below) | unverified | unverified | deferred | Strict source adaptation; imported legacy form retained |
| [TYPE-PARAM-003](#type-param-003) | pending-discussion | `function f(string &$s, vector_t &$v): void {}` | unverified | unverified | deferred | — |
| [TYPE-PARAM-003B](#type-param-003b) | pending-discussion | `function f(array &$a): void {}` | unverified | unverified | deferred | — |
| [TYPE-PARAM-003F](#type-param-003f) | pending-discussion | `function f(const vector<int> &$values): int { return count($values); }` | unverified | unverified | deferred | — |
| [WARN-TYPE-PARAM-001](#warn-type-param-001) | pending-discussion | `function f(array $a): void { $a = []; }` | unverified | unverified | deferred | — |
| [TYPE-VAR-007A](#type-var-007a) | pending-discussion | Strict example to define (legacy spelling retained below) | unverified | unverified | deferred | Strict source adaptation; imported legacy form retained |
| [TYPE-VAR-008](#type-var-008) | pending-discussion | Strict example to define (legacy spelling retained below) | unverified | unverified | deferred | Strict source adaptation; imported legacy form retained |
| [NOTE-038](#note-038) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-056](#note-056) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-059](#note-059) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-060](#note-060) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
## FUNC-REF-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:102](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:263](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int &$a): void {}
```

**Existing C++ lowering / result**

```cpp
void_t f(int_t& a);
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** reference syntax is explicit and valid

**Normalized pattern:** free-function reference parameter

**General rule:** Reference parameters are supported for free functions.

**Diagnostics:** Error if generator drops the reference marker.

**Notes:** Added after explicit approval.


## FUNC-REF-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:103](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:264](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function &f(): int {}
```

**Existing C++ lowering / result**

```cpp
int_t& f();
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** reference syntax is explicit and valid

**Normalized pattern:** free-function reference return

**General rule:** Reference returns are supported for free functions when the PHP return type is explicitly declared, and must preserve the native `&` marker in both declaration and definition.

**Diagnostics:** Error if generator drops the reference marker or returns a copied value.

**Notes:** Added after explicit approval.


## FUNC-REF-003

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:104](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
function &f(int &$a): int {}
```

**Existing C++ lowering / result**

```cpp
int_t& f(int_t& a);
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** explicit reference markers present

**Normalized pattern:** free-function reference preservation

**General rule:** Reference markers are preserved literally in generated free-function signatures when the PHP return type is explicitly declared, and return statements stay on lvalue-capable paths; direct calls statically known to return by reference are also accepted.

**Diagnostics:** No reference inference is performed.

**Notes:** Also subject to the global no-overloading rule.


## FUNC-REF-004

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:105](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
function &f($a) { return $a; }
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Function

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** by-reference return lacks an explicit PHP return type

**Normalized pattern:** untyped free-function reference return

**General rule:** By-reference function returns must have an explicit declared PHP return type so the generated C++ signature can use a concrete `T&` instead of deduced `auto&`.

**Diagnostics:** Emit an explicit generator error when a free function returns by reference without a declared PHP return type.

**Notes:** This avoids forward-declaration and deduction issues for reference-returning functions.


## VAR-UNSET-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:49](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
unset($a);
```

**Existing C++ lowering / result**

```cpp
php::unset(a);
```

**Category:** Variable

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** lowered target type is nullable / pointer-like / handle-like and supports an empty state

**Normalized pattern:** `unset(<var>)`

**General rule:** `unset` must be mapped to the PHP runtime only for resettable nullable / pointer-like targets. It must not be used as a fake delete for non-nullable value types, containers, or native references.

**Diagnostics:** Emit a generation error or route to `clean` when the target type has no null/empty state compatible with `unset`.

**Notes:** Current project rule: `unset` is intentionally narrower than PHP variable removal semantics.


## VAR-CLEAN-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:50](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
clean($a);
```

**Existing C++ lowering / result**

```cpp
php::clean(a);
```

**Category:** Variable

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** lowered target type is non-nullable but has a defined default/empty cleanup state

**Normalized pattern:** `clean(<var>)`

**General rule:** `clean` is the current reset/cleanup path for non-nullable value/container-like targets that should not use `unset`. It resets the value to its defined default/empty state rather than modeling PHP symbol removal.

**Diagnostics:** Emit a generation error if the target type has no supported cleanup/default semantics.

**Notes:** Temporary project-level direction until/if a different reset API is chosen.


## REF-RETURN-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:266](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function &bounce(array &$a) { return get_inner($a); }
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Function

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** forwarded reference target is not proven native-reference safe

**Normalized pattern:** direct ref-return call forwarding

**General rule:** Forwarding a by-reference call result is rejected unless the returned target is already known to satisfy the native-reference safety rule.

**Diagnostics:** Emit an explicit generator error when the forwarded reference target is unresolved or rooted in dynamic/container interior storage.

**Notes:** See `specs/native_reference_safety.md`.


## REF-RETURN-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:267](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function &get_inner(array &$arr): array { return $arr["inner"]; }
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Function

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** returned expression is rooted in dynamic interior slot access

**Normalized pattern:** simple direct slot ref-return

**General rule:** Returning a direct array/property slot chain by native reference is outside the current safe subset because it would expose interior storage owned by another object.

**Diagnostics:** Emit an explicit generator error.

**Notes:** See `specs/native_reference_safety.md`.


## REF-BIND-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:268](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$inner =& $arr["inner"];
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Function

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** reference assignment source is a direct DIM expression rooted in dynamic interior storage

**Normalized pattern:** direct DIM reference binding

**General rule:** Reference binding from a direct DIM slot is outside the current safe subset.

**Diagnostics:** Emit an explicit generator error.

**Notes:** See `specs/native_reference_safety.md`.


## TYPE-VAR-003A

**Strict-first scope:** Define the strict source form and verify its type/ownership contract when this feature is discussed. The imported annotation spelling below is legacy reference only.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:326](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$x /** weak<A> */ = null;
```

**Existing C++ lowering / result**

```cpp
weak_p<A> x = null;
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** explicit runtime weak-handle PHPDoc variable type present

**Normalized pattern:** `typed weak handle local variable`

**General rule:** Explicit local PHPDoc handle aliases `weak<T>` and `weakref<T>` lower directly to `weak_p<T>` and must not be wrapped again as object handles.

**Diagnostics:** Error if generation emits `shared_p<weak_p<T>>`, `shared_p<weakref<T>>`, or any other nested wrapper shape.

**Notes:** This rule treats runtime weak handles as first-class handle values.


## TYPE-VAR-003B

**Strict-first scope:** Define the strict source form and verify its type/ownership contract when this feature is discussed. The imported annotation spelling below is legacy reference only.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:327](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$x /** shared<A> */ = null;
```

**Existing C++ lowering / result**

```cpp
shared_p<A> x = null;
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** explicit runtime shared-handle PHPDoc variable type present

**Normalized pattern:** `typed shared handle local variable`

**General rule:** Explicit local PHPDoc handle alias `shared<T>` lowers directly to `shared_p<T>` and must not be wrapped as `shared_p<shared_p<T>>`.

**Diagnostics:** Error if generation introduces nested shared-handle wrapping.

**Notes:** This keeps runtime handle aliases canonical.


## TYPE-VAR-003C

**Strict-first scope:** Define the strict source form and verify its type/ownership contract when this feature is discussed. The imported annotation spelling below is legacy reference only.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:328](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$x /** unique<A> */ = null;
```

**Existing C++ lowering / result**

```cpp
unique_p<A> x = null;
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** explicit runtime unique-handle PHPDoc variable type present

**Normalized pattern:** `typed unique handle local variable`

**General rule:** Explicit local PHPDoc handle alias `unique<T>` lowers directly to `unique_p<T>` and must not be wrapped again as an object handle.

**Diagnostics:** Error if generation emits a nested wrapper shape such as `shared_p<unique_p<T>>`.

**Notes:** Unique handles are treated as first-class runtime handle values.


## TYPE-PARAM-003

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:331](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(string &$s, vector_t &$v): void {}
```

**Existing C++ lowering / result**

```cpp
void_t f(string_t& s, vector_t& v) {}
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** explicit PHP reference syntax present

**Normalized pattern:** `explicit mutable string/vector reference`

**General rule:** Explicit PHP `&` must be preserved literally and disables the default `const &` convention for `string_t` and `vector_t`.

**Diagnostics:** Error if the reference marker is dropped or rewritten to `const &`.

**Notes:** Applies to free functions and methods.


## TYPE-PARAM-003B

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:333](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(array &$a): void {}
```

**Existing C++ lowering / result**

```cpp
void_t f(mixed_t& a) { ::scpp::php::expect_array_argument(a, false, "a"); }
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** explicit PHP reference syntax present

**Normalized pattern:** `explicit mutable array reference`

**General rule:** Explicit PHP `&` must be preserved literally and lowers to `mixed_t&` for the fat-variable array surface.

**Diagnostics:** Error if the reference marker is dropped or rewritten away from `mixed_t&`.

**Notes:** Applies to free functions and methods.


## TYPE-PARAM-003F

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:337](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(const vector<int> &$values): int { return count($values); }
```

**Existing C++ lowering / result**

```cpp
int_t f(const vector_t<int_t<>>& values) { return count(values); }
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** prefix parameter syntax contains leading `const`, the parameter type is explicit, and optional PHP `&` is present before the variable

**Normalized pattern:** `explicit const parameter`

**General rule:** Source-level `const` on a function, method, closure, interface, or abstract method parameter is a semantic read-only contract, separate from the generator's automatic read-only `const &` convention. Explicit `const T&` lowers to a generated C++ `const T&` parameter and bypasses mutable by-reference normalization.

**Diagnostics:** STAN must report a build-blocking diagnostic if the body writes, reassigns, unsets, increments/decrements, or writes through a slot/property rooted at the const parameter. Generated C++ signatures must preserve `const`.

**Notes:** First pass supports scanner-owned prefix syntax such as `const T $x` and `const T &$x`; existing doc-comment-only parameters remain non-const unless a later doc syntax is specified.


## WARN-TYPE-PARAM-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:339](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(array $a): void { $a = []; }
```

**Existing C++ lowering / result**

```cpp
void_t f(mixed_t a) { expect_array_argument(a, false, "a"); a = table_(); }
```

**Category:** Type system

**Rule kind:** warning

**Source support status:** supported with warning

**Preconditions:** by-value composite param is reassigned

**Normalized pattern:** `reassignment replaces the guarded local value`

**General rule:** Reassigning a by-value `array` param is supported and simply replaces the guarded local `mixed_t`.

**Diagnostics:** Warning-level documentation only.

**Notes:** Prefer a new local when the distinction between replacing the local and mutating nested slots matters.


## TYPE-VAR-007A

**Strict-first scope:** Define the strict source form and verify its type/ownership contract when this feature is discussed. The imported annotation spelling below is legacy reference only.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:359](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$p /** unique<Point> */ = new Point(1, 2);
```

**Existing C++ lowering / result**

```cpp
unique_p<Point> p = ::scpp::unique<Point>(static_cast<int_t>(1), static_cast<int_t>(2));
```

**Category:** Type system

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** explicit unique local wrapper annotation present and the initializer is exactly `new Point(...)`

**Normalized pattern:** `typed unique object local variable`

**General rule:** The local PHPDoc form `unique<T>` lowers to `unique_p<T>`. When the initializer is `new T(...)`, generation must use `::scpp::unique<T>(...)` rather than `create<T>(...)` so ownership remains unique. The generator must reject mismatches such as `/** unique<A> */ = new B()` instead of default-initializing `A` or silently discarding the RHS constructor target.

**Diagnostics:** Error if the generator falls back to `create<T>(...)`, `shared_p<T>`, or any non-unique ownership path for this explicit local form.

**Notes:** This applies both to the canonical `unique<T>` form and to the bare `/** unique */` shortcut after it is normalized to `unique<T>`.


## TYPE-VAR-008

**Strict-first scope:** Define the strict source form and verify its type/ownership contract when this feature is discussed. The imported annotation spelling below is legacy reference only.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:360](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$r /** ref int */ = &$x;
```

**Existing C++ lowering / result**

```cpp
int_t& r = x;
```

**Category:** Type system

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** explicit reference local annotation present

**Normalized pattern:** `typed reference local variable`

**General rule:** The local PHPDoc form `ref T` lowers to a native C++ lvalue reference over the lowered declared type. Object-like locals therefore become handle references such as `shared_p<T>&`, not nested wrapper shapes. The feature remains a reduced write-through alias and is not a promise of full PHP `&` semantics.

**Diagnostics:** Emit an error if generation would require forbidden type-definition syntax such as `&&` or `*`, or if initialization is not a reference assignment.

**Notes:** This rule keeps the reference feature aligned with the native C++ no-layering contract and with the explicit non-PHP-reference scope.


## NOTE-038

**Source:** [generators/php/specs/rules.md:771](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 9. Parameter passing rules
>
> ### 9.1 Pass by value
> - `int_t`
> - `float_t`
> - `bool_t`
> - `nullable<int_t>`
> - `nullable<float_t>`
> - `nullable<bool_t>`
>
> ### 9.2 Pass by const &
> - `string_t`
> - `vector_t`
> - `nullable<string_t>`
> - `nullable<vector_t>`
> - future heavy wrapper types

## NOTE-056

**Source:** [generators/php/specs/rules.md:1249](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## Warning: reassignment of by-value composite params
>
> - Reassigning a by-value parameter of type `array`, `string`, or `vector_t<...>` is supported, but it is **not recommended** for large values.
> - Once the function body proves a reassignment or write on that parameter, the emitted C++ signature becomes owning `T x` instead of `const T& x`.
> - That means the **entire incoming value is copied at function entry**.
> - This can be expensive for large `hash_t`, `string_t`, or `vector_t` values, even if the original incoming value is used only briefly before reassignment.
> - Prefer introducing a new local variable instead of overwriting the parameter when avoiding that full copy matters.

## NOTE-059

**Source:** [generators/php/specs/rules.md:1272](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## Return-by-reference warnings
>
> - Return-by-reference is not recommended in Prism++ and must always surface a generator warning even when generation is still allowed.
> - The generator must also warn for local copy-after-alias patterns rooted in a by-reference call result, for example `$inner =& get_inner($arr); $copy = $arr;`, because Prism++ may not preserve PHP alias semantics for that flow.

## NOTE-060

**Source:** [generators/php/specs/rules.md:1277](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## Historical note â€” typed scalar by-reference proxy lowering
>
> The runtime may still contain legacy helper/proxy infrastructure, but that legacy path is not part of the supported safe subset. The current design direction is the native-reference safety rule documented in `../../specs/native_reference_safety.md`.

# 09. Value types and enums
Doc Status: planning

> **Important: LLVM is deferred by default. Work on the direct LLVM backend only when the user explicitly requests it. The v0.2 default scope is Frontend + C++ S2S.**

[Catalog and workflow](README.md) · [Source merge ledger](source_merge.json)

Requires canonical types and declarations. This imported inventory primarily covers enums; record coverage must be added when discussed.

Order is a discussion sequence, not a claim that every row is a prerequisite. Split combined examples before implementation.

## Progress

Edit these rows as work proceeds. Imported source support is recorded below, independently of this progress.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [TYPE-VAR-006](#type-var-006) | pending-discussion | Strict example to define (legacy spelling retained below) | unverified | unverified | deferred | Strict source adaptation; imported legacy form retained |
| [TYPE-VAR-006A](#type-var-006a) | pending-discussion | Strict example to define (legacy spelling retained below) | unverified | unverified | deferred | Strict source adaptation; imported legacy form retained |
| [TYPE-VAR-007](#type-var-007) | pending-discussion | Strict example to define (legacy spelling retained below) | unverified | unverified | deferred | Strict source adaptation; imported legacy form retained |
| [ENUM-001](#enum-001) | pending-discussion | `enum Suit { case Hearts; case Spades; }` | unverified | unverified | deferred | — |
| [ENUM-002](#enum-002) | pending-discussion | `enum HttpStatus: int { case Ok = 200; case NotFound = 404; }` | unverified | unverified | deferred | — |
| [ENUM-003](#enum-003) | pending-discussion | `Color::tryFrom('green')?->name ?? 'null'` | unverified | unverified | deferred | — |
| [ENUM-004](#enum-004) | pending-discussion | `enum Status implements HasLabel { case Open; public function label(): string { return 'open'; } }` | unverified | unverified | deferred | — |
## TYPE-VAR-006

**Strict-first scope:** Define the strict source form and verify its type/ownership contract when this feature is discussed. The imported annotation spelling below is legacy reference only.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:355](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$p /** value<Point> */ = new Point(1, 2);
```

**Existing C++ lowering / result**

```cpp
value_p<Point> p = value<Point>(static_cast<int_t>(1), static_cast<int_t>(2));
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** explicit inline-storage local annotation present

**Normalized pattern:** `typed inline-storage object local variable`

**General rule:** The local PHPDoc form `value<T>` is an explicit opt-in to inline storage and lowers to `value_p<T>`. When the initializer is `new T(...)`, generation uses `value<T>(...)` rather than `create<T>(...)`. The resulting local remains object-like at the usage surface, so property and method access continue to lower through `->`.

**Diagnostics:** Error if the generator falls back to `shared_p<T>` or `create<T>(...)` for this explicit local form, or if later member access is emitted as direct `.` access on the wrapper instead of object-like access through `->`.

**Notes:** This rule applies only to typed local variables in the current prototype. Legacy `value T` comments may still be accepted for compatibility, but `value<T>` is canonical.


## TYPE-VAR-006A

**Strict-first scope:** Define the strict source form and verify its type/ownership contract when this feature is discussed. The imported annotation spelling below is legacy reference only.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:356](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$p /** value<?Point> */ = new Point(1, 2);
```

**Existing C++ lowering / result**

```cpp
nullable<Point> p{Point(static_cast<int_t>(1), static_cast<int_t>(2))};
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** explicit nullable inline-storage local annotation present

**Normalized pattern:** `typed nullable inline object local variable`

**General rule:** The local PHPDoc form `value<?T>` is an explicit opt-in to nullable inline object storage. When the initializer is `new T(...)`, generation lowers to `nullable<T>{T(...)}` rather than `value_p<T>` or `mixed_t`. The resulting local remains object-like at the usage surface through nullable `->` forwarding.

**Diagnostics:** Error if the generator silently degrades this explicit local form to `mixed_t`, `shared_p<T>`, or any non-nullable storage, or if later member access does not preserve object-like `->` behavior.

**Notes:** `shared_p<T> x = null` is not a valid test for this rule because it exercises handle-null behavior rather than `nullable<T>`.


## TYPE-VAR-007

**Strict-first scope:** Define the strict source form and verify its type/ownership contract when this feature is discussed. The imported annotation spelling below is legacy reference only.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:358](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$p /** value */ = new Point(1, 2);
```

**Existing C++ lowering / result**

```cpp
value_p<Point> p = value<Point>(static_cast<int_t>(1), static_cast<int_t>(2));
```

**Category:** Type system

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** the typed local initializer is exactly `new ClassName(...)` and the class target is statically known

**Normalized pattern:** `bare local wrapper shortcut`

**General rule:** Bare local wrapper comments `value`, `shared`, and `unique` are strict shortcuts only for direct constructor assignment. The generator must immediately normalize them to `value<ClassName>`, `shared<ClassName>`, or `unique<ClassName>` before later lowering steps. After normalization, later wrapper-aware lowering must still validate that an explicit wrapper inner type matches the class named by any direct `new ClassName(...)` initializer.

**Diagnostics:** Emit an error when the wrapper is bare but the assignment is not a normal direct local assignment from `new ClassName(...)`, or when the class target is not statically known.

**Notes:** This shortcut is intentionally narrow and does not authorize general inference from factories, conditionals, variables, or dynamic class names.


## ENUM-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:375](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
enum Suit { case Hearts; case Spades; }
```

**Existing C++ lowering / result**

generated native C++ scoped enum with compact underlying storage

**Category:** Enum

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** enum body contains only simple cases

**Normalized pattern:** unit enum

**General rule:** PHP unit enums lower to `enum class <Name> : <smallest unsigned storage>` with one enumerator per case. Storage stays at 1 byte while the case set fits, then widens as needed.

**Diagnostics:** Error on unsupported enum members or unsupported case expressions.

**Notes:** Current stage intentionally targets only simple enum declarations and case references.


## ENUM-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:376](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
enum HttpStatus: int { case Ok = 200; case NotFound = 404; }
```

**Existing C++ lowering / result**

generated native C++ scoped enum with explicit integral enumerator values

**Category:** Enum

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** backing type is `int` and every case value is a literal integer

**Normalized pattern:** int-backed enum

**General rule:** Int-backed enums lower to `enum class <Name> : <smallest fitting integer storage>` and preserve the declared integer case values.

**Diagnostics:** Error if the backing type is not `int` or if a case value is not a literal integer.

**Notes:** Signed storage is selected only when negative case values require it.


## ENUM-003

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:377](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
Color::tryFrom('green')?->name ?? 'null'
```

**Existing C++ lowering / result**

helper-based enum API

**Category:** Enum

**Rule kind:** generation

**Source support status:** unsupported

**Preconditions:** n/a

**Normalized pattern:** enum helper API

**General rule:** Helper synthesis such as pseudo-properties, `cases()`, `from()`, and `tryFrom()` is not part of the current simple-enum stage.

**Diagnostics:** Error / unsupported lowering if used.

**Notes:** Revisit after the base native-enum path is stable.


## ENUM-004

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:378](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
enum Status implements HasLabel { case Open; public function label(): string { return 'open'; } }
```

**Existing C++ lowering / result**

enum methods / interfaces

**Category:** Enum

**Rule kind:** generation

**Source support status:** unsupported

**Preconditions:** n/a

**Normalized pattern:** enriched enum bodies

**General rule:** Methods, interfaces, and other class-like enum features are out of scope for the current simple-enum stage.

**Diagnostics:** Error / unsupported lowering if used.

**Notes:** Revisit after simple enums are validated end-to-end.

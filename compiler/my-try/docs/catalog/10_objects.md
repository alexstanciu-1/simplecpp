# 10. Classes, properties, construction and methods
Doc Status: planning

> **Important: LLVM is deferred by default. Work on the direct LLVM backend only when the user explicitly requests it. The v0.2 default scope is Frontend + C++ S2S.**

[Catalog and workflow](README.md) · [Source merge ledger](source_merge.json)

Requires functions, type identities and ownership. Start with a plain class before methods and lifecycle combinations.

Order is a discussion sequence, not a claim that every row is a prerequisite. Split combined examples before implementation.

## Progress

Edit these rows as work proceeds. Imported source support is recorded below, independently of this progress.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [CLASS-DECL-001](#class-decl-001) | pending-discussion | `class A {}` | unverified | unverified | deferred | — |
| [CLASS-PROP-001](#class-prop-001) | pending-discussion | `class A { int $x; }` | unverified | unverified | deferred | Source variants need reconciliation |
| [CLASS-PROP-002](#class-prop-002) | pending-discussion | `class A { int $x = 1; }` | unverified | unverified | deferred | Source variants need reconciliation |
| [CLASS-PROP-003](#class-prop-003) | pending-discussion | `class A { int $x; }` | unverified | unverified | deferred | — |
| [CLASS-PROP-004](#class-prop-004) | pending-discussion | `$this->x = 1;` | unverified | unverified | deferred | — |
| [CLASS-PROP-005](#class-prop-005) | pending-discussion | `$a->x = 1;` | unverified | unverified | deferred | — |
| [CLASS-PROP-006](#class-prop-006) | pending-discussion | `A::$x = 1;` | unverified | unverified | deferred | — |
| [CLASS-PROP-006A](#class-prop-006a) | pending-discussion | `self::$x = 1;` | unverified | unverified | deferred | — |
| [CLASS-PROP-006B](#class-prop-006b) | pending-discussion | `parent::$x = 1;` | unverified | unverified | deferred | — |
| [CLASS-PROP-006C](#class-prop-006c) | pending-discussion | `static::$x = 1;` | unverified | unverified | deferred | — |
| [CLASS-PROP-007](#class-prop-007) | pending-discussion | `$a::$x = 2;` | unverified | unverified | deferred | — |
| [CLASS-PROP-008](#class-prop-008) | pending-discussion | `class B extends A { function f(): void { $this->x = 1; } }` | unverified | unverified | deferred | — |
| [CLASS-PROP-009](#class-prop-009) | pending-discussion | `$this->y = 1;` where `y` is undeclared | unverified | unverified | deferred | — |
| [CLASS-PROP-010](#class-prop-010) | pending-discussion | `$this->$name = 1;` | unverified | unverified | deferred | — |
| [CLASS-PROP-011](#class-prop-011) | pending-discussion | `class A { public int $x = 1; public static int $s = 2; }` | unverified | unverified | deferred | — |
| [CLASS-CALL-001](#class-call-001) | pending-discussion | `$a->f(1);` | unverified | unverified | deferred | — |
| [CLASS-CALL-002](#class-call-002) | pending-discussion | `A::f(1);` | unverified | unverified | deferred | — |
| [CLASS-CALL-003](#class-call-003) | pending-discussion | `$a::f(1);` | unverified | unverified | deferred | Source variants need reconciliation |
| [CLASS-CALL-004](#class-call-004) | pending-discussion | `$this->f(1);` | unverified | unverified | deferred | — |
| [CLASS-CALL-005](#class-call-005) | pending-discussion | `parent::f(1);` | unverified | unverified | deferred | — |
| [CLASS-CALL-005A](#class-call-005a) | pending-discussion | `static::f(1);`<br>`static::f(1)` | unverified | unverified | deferred | Source variants need reconciliation |
| [CLASS-CALL-006](#class-call-006) | pending-discussion | `function f(): int { return 1; }` | unverified | unverified | deferred | — |
| [CLASS-CALL-007](#class-call-007) | pending-discussion | `$i->f(1);` | unverified | unverified | deferred | — |
| [CLASS-CALL-008](#class-call-008) | pending-discussion | `new A(1)` | unverified | unverified | deferred | Source variants need reconciliation |
| [CLASS-CALL-008A](#class-call-008a) | pending-discussion | `new static(1)` | unverified | unverified | deferred | — |
| [CLASS-CALL-009](#class-call-009) | pending-discussion | `$a->$name();` | unverified | unverified | deferred | — |
| [CLASS-CALL-010](#class-call-010) | pending-discussion | `[$a, 'f']` | unverified | unverified | deferred | — |
| [CLASS-CALL-011](#class-call-011) | pending-discussion | explicit destructor invocation pattern | unverified | unverified | deferred | — |
| [CLASS-CALL-012](#class-call-012) | pending-discussion | `static function g(int $x): int { return $x; }` | unverified | unverified | deferred | — |
| [CLASS-CONST-001](#class-const-001) | pending-discussion | `class A { public const X = 1; }` | unverified | unverified | deferred | Source variants need reconciliation |
| [CLASS-CONST-002](#class-const-002) | pending-discussion | `A::X` | unverified | unverified | deferred | — |
| [CLASS-CONST-003](#class-const-003) | pending-discussion | `self::X` | unverified | unverified | deferred | — |
| [CLASS-CONST-004](#class-const-004) | pending-discussion | `parent::X` | unverified | unverified | deferred | — |
| [CLASS-CONST-005](#class-const-005) | pending-discussion | `static::X` | unverified | unverified | deferred | — |
| [CLASS-MEMBER-001](#class-member-001) | pending-discussion | `function f(int $a): int { return $a; }` | unverified | unverified | deferred | Source variants need reconciliation |
| [CLASS-MEMBER-001B](#class-member-001b) | pending-discussion | `function f(vector<int> $a): int { return 1; }` | unverified | unverified | deferred | Strict source adaptation; imported legacy form retained |
| [CLASS-MEMBER-002](#class-member-002) | pending-discussion | `function f(int $a): int { return $a; }` | unverified | unverified | deferred | — |
| [CLASS-MEMBER-003](#class-member-003) | pending-discussion | `function f(int $value): int { return $value; }` | unverified | unverified | deferred | — |
| [CLASS-MEMBER-004](#class-member-004) | pending-discussion | `static function f(int $a): int { return $a; }` | unverified | unverified | deferred | — |
| [CLASS-MEMBER-005](#class-member-005) | pending-discussion | `function f(int $a): int { return $a; }` | unverified | unverified | deferred | — |
| [CLASS-MEMBER-006](#class-member-006) | pending-discussion | `abstract function f(int $a): int;` | unverified | unverified | deferred | — |
| [CLASS-MEMBER-007](#class-member-007) | pending-discussion | `#[\Override] function f(int $a): int { return $a; }` | unverified | unverified | deferred | — |
| [CLASS-MEMBER-008](#class-member-008) | pending-discussion | `final function f(int $a): int { return $a; }` | unverified | unverified | deferred | — |
| [CLASS-MEMBER-009](#class-member-009) | pending-discussion | `abstract static function f(): int;` | unverified | unverified | deferred | — |
| [CLASS-MEMBER-010](#class-member-010) | pending-discussion | `#[\Override] static function f(): int { return 1; }` | unverified | unverified | deferred | — |
| [CLASS-MEMBER-011](#class-member-011) | pending-discussion | `function __construct(int $x) { $this->x = $x; }` | unverified | unverified | deferred | — |
| [CLASS-MEMBER-012](#class-member-012) | pending-discussion | `function __destruct() {}` | unverified | unverified | deferred | — |
| [CLASS-MEMBER-013](#class-member-013) | pending-discussion | `interface I { function f(int $a): int; }` | unverified | unverified | deferred | — |
| [CLASS-MEMBER-014](#class-member-014) | pending-discussion | `private function f(): int { return 1; }` | unverified | unverified | deferred | Source variants need reconciliation |
| [CLASS-MEMBER-015](#class-member-015) | pending-discussion | `function f(int $a): int {} function f(string $a): int {}` | unverified | unverified | deferred | — |
| [CLASS-MEMBER-016](#class-member-016) | pending-discussion | `function f(int $a = 1): int { return $a; }` | unverified | unverified | deferred | — |
| [CLASS-MEMBER-017](#class-member-017) | pending-discussion | `function f(int ...$a): int { return 1; }` | unverified | unverified | deferred | — |
| [CLASS-MEMBER-018A](#class-member-018a) | pending-discussion | `function f(int &$a): void {}` | unverified | unverified | deferred | — |
| [CLASS-MEMBER-018B](#class-member-018b) | pending-discussion | `function &f(): int {}` | unverified | unverified | deferred | — |
| [CLASS-MEMBER-018C](#class-member-018c) | pending-discussion | `function &f(int &$a): int {}` | unverified | unverified | deferred | — |
| [CLASS-MEMBER-018D](#class-member-018d) | pending-discussion | explicit PHP reference syntax on method/member signature | unverified | unverified | deferred | — |
| [CLASS-MEMBER-018E](#class-member-018e) | pending-discussion | `function &f() { return $this->x; }` | unverified | unverified | deferred | — |
| [CLASS-PROP-002A](#class-prop-002a) | pending-discussion | `class A { static int $x = 1; }` | unverified | unverified | deferred | — |
| [CLASS-PROP-002B](#class-prop-002b) | pending-discussion | `self::$x` | unverified | unverified | deferred | — |
| [CLASS-PROP-002C](#class-prop-002c) | pending-discussion | `parent::$x` | unverified | unverified | deferred | — |
| [CLASS-PROP-002D](#class-prop-002d) | pending-discussion | `static::$x` | unverified | unverified | deferred | — |
| [CLASS-METHOD-001](#class-method-001) | pending-discussion | `class A { function f(): int { return 1; } }` | unverified | unverified | deferred | — |
| [CLASS-METHOD-002](#class-method-002) | pending-discussion | `class A { static function f(int $a): int { return $a; } }` | unverified | unverified | deferred | — |
| [CLASS-THIS-001](#class-this-001) | pending-discussion | `class A { int $x; function f(): int { return $this->x; } }` | unverified | unverified | deferred | — |
| [CLASS-NEW-001](#class-new-001) | pending-discussion | `$a = new A();` | unverified | unverified | deferred | — |
| [CLASS-NEW-002](#class-new-002) | pending-discussion | `$a = new \A\B();` | unverified | unverified | deferred | — |
| [CLASS-CONSTRUCT-001](#class-construct-001) | pending-discussion | `class A { function __construct(int $x) { $this->x = $x; } }` | unverified | unverified | deferred | — |
| [OBJ-PROP-READ-001](#obj-prop-read-001) | pending-discussion | `$a = $obj->x;` | unverified | unverified | deferred | — |
| [OBJ-PROP-WRITE-001](#obj-prop-write-001) | pending-discussion | `$obj->x = 1;` | unverified | unverified | deferred | — |
| [OBJ-METHOD-CALL-001](#obj-method-call-001) | pending-discussion | `$a = $obj->f();` | unverified | unverified | deferred | — |
| [OBJ-METHOD-CALL-002](#obj-method-call-002) | pending-discussion | `$a = $obj->f(1);` | unverified | unverified | deferred | — |
| [TYPE-VAR-003](#type-var-003) | pending-discussion | `$x A = new A();` | unverified | unverified | deferred | Strict source adaptation; imported legacy form retained |
| [TYPE-VAR-004](#type-var-004) | pending-discussion | `$x ?A = null;` | unverified | unverified | deferred | Strict source adaptation; imported legacy form retained |
| [TYPE-PARAM-004](#type-param-004) | pending-discussion | `function f(A $a, ?B $b, I $i): A { return $a; }` | unverified | unverified | deferred | — |
| [TYPE-PROP-001](#type-prop-001) | pending-discussion | `class A { protected string $s; protected B $b; }` | unverified | unverified | deferred | — |
| [TYPE-PROP-001B](#type-prop-001b) | pending-discussion | `class A { public int $x; }` | unverified | unverified | deferred | Strict source adaptation; imported legacy form retained |
| [TYPE-PROP-002](#type-prop-002) | pending-discussion | `class A { protected ?string $x; protected ?B $b; }` | unverified | unverified | deferred | — |
| [NOTE-029](#note-029) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-050](#note-050) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |

### Deferred legacy syntax

These syntax-specific entries are retained for traceability, not scheduled before strict-mode features.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [CLASS-PROP-001B](#class-prop-001b) | deferred-legacy | `class A { public /** int */ int $x; }` | unverified | unverified | deferred | Legacy annotation syntax only; outside strict-first work |
## CLASS-DECL-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:22](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:185](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A {}
```

**Existing C++ lowering / result**

```cpp
class A { public: };
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** class declaration is syntactically valid

**Normalized pattern:** `class <name> {}`

**General rule:** A PHP class lowers to a C++ class declaration in the generated header.

**Diagnostics:** Error if the class declaration cannot be normalized.

**Notes:** User PHP classes participate in header/source splitting.


## CLASS-PROP-001

**v0.2 decision / target C++:** Pending discussion.

Different source versions share this ID. Preserve both until their differences are discussed; neither is silently selected as the v0.2 decision.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:45](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
class A { int $x; }
```

**Existing C++ lowering / result**

```cpp
class A { public: int_t x; };
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** property has explicit supported type

**Normalized pattern:** typed instance property

**General rule:** Instance properties must be typed and are emitted in the header under their declared visibility section. Omitted visibility defaults to `public`.

**Diagnostics:** Error if property type is missing or member access violates declared visibility.

**Notes:** Visibility is part of the member contract.

### Imported version 2

**Source:** [generators/php/specs/rules_catalog.md:187](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A { int $x; }
```

**Existing C++ lowering / result**

```cpp
class A { public: int_t x; };
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** property has explicit supported type

**Normalized pattern:** `class <name> { <typed-property>; }`

**General rule:** Instance properties must be typed and are emitted in the generated header under their declared visibility section. Omitted visibility defaults to `public`.

**Diagnostics:** Error if a property type is missing or if source/build analysis detects access outside the declared visibility.

**Notes:** Visibility is part of the member contract.


## CLASS-PROP-001B

**Scope:** Legacy annotation syntax; deferred. This is not a strict-mode implementation prerequisite.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:46](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
class A { public /** int */ int $x; }
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Class

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** both native and doc-comment types are present

**Normalized pattern:** conflicting property type sources

**General rule:** A property must use exactly one explicit type source.

**Diagnostics:** Emit an error rather than choosing precedence between native PHP typing and doc-comment typing.

**Notes:** Applies to static and instance properties alike.


## CLASS-PROP-002

**v0.2 decision / target C++:** Pending discussion.

Different source versions share this ID. Preserve both until their differences are discussed; neither is silently selected as the v0.2 decision.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:47](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
class A { int $x = 1; }
```

**Existing C++ lowering / result**

```cpp
class A { public: int_t x = static_cast<int_t>(1); };
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** property type and initializer are supported

**Normalized pattern:** inline property initialization

**General rule:** Non-static property defaults lower to in-class default member initializers in the generated header.

**Diagnostics:** Error if the initializer expression is outside the supported property-default subset.

**Notes:** Static properties have separate storage-definition rules.

### Imported version 2

**Source:** [generators/php/specs/rules_catalog.md:188](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A { int $x = 1; }
```

**Existing C++ lowering / result**

```cpp
class A { public: int_t x = static_cast<int_t>(1); };
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** property type and initializer are supported

**Normalized pattern:** `class <name> { <typed-property> = <expr>; }`

**General rule:** Non-static property defaults lower to in-class default member initializers in the generated header.

**Diagnostics:** Error if the initializer expression is outside the supported property-default subset.

**Notes:** Static properties follow separate storage-definition rules.


## CLASS-PROP-003

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:48](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
class A { int $x; }
```

**Existing C++ lowering / result**

property declaration appears only in header

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** property is valid

**Normalized pattern:** property declaration placement

**General rule:** Properties are emitted in the header only.

**Diagnostics:** Error if a source file declaration/definition is emitted for non-static instance properties.

**Notes:** Static properties have separate rules.


## CLASS-PROP-004

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:49](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
$this->x = 1;
```

**Existing C++ lowering / result**

```cpp
this->x = static_cast<int_t>(1);
```

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** current context allows `$this`

**Normalized pattern:** `$this-><prop>`

**General rule:** `$this->prop` lowers to `this->prop`.

**Diagnostics:** Error if `$this` is invalid in current context.

**Notes:** Body expression normalization still applies.


## CLASS-PROP-005

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:50](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
$a->x = 1;
```

**Existing C++ lowering / result**

```cpp
a->x = static_cast<int_t>(1);
```

**Category:** Object

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** object expression is valid handle-like instance expression

**Normalized pattern:** `<expr>-><prop>`

**General rule:** Object property access lowers using pointer/handle member access syntax.

**Diagnostics:** Error if generator emits direct object-dot semantics for user PHP objects.

**Notes:** Consistent with handle-like object model.


## CLASS-PROP-006

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:51](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
A::$x = 1;
```

**Existing C++ lowering / result**

```cpp
A::x = static_cast<int_t>(1);
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** static property is declared on class

**Normalized pattern:** `<class>::$<prop>`

**General rule:** Static property access through a class name lowers to `Class::prop`.

**Diagnostics:** Error if generator rewrites static property access through instance syntax.

**Notes:** Static property declaration/definition rules apply.


## CLASS-PROP-006A

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:52](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
self::$x = 1;
```

**Existing C++ lowering / result**

```cpp
CurrentClass::x = static_cast<int_t>(1);
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** current context is a class body/method

**Normalized pattern:** `self::$<prop>`

**General rule:** `self::$prop` lowers to the current generated class static storage.

**Diagnostics:** Error if no current class context exists.

**Notes:** Lexical current-class lowering.


## CLASS-PROP-006B

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:53](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
parent::$x = 1;
```

**Existing C++ lowering / result**

```cpp
ParentClass::x = static_cast<int_t>(1);
```

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** current class has a parent

**Normalized pattern:** `parent::$<prop>`

**General rule:** `parent::$prop` lowers to the parent class static storage.

**Diagnostics:** Error if there is no parent class in the current context.

**Notes:** Uses the already normalized parent-class name.


## CLASS-PROP-006C

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:54](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
static::$x = 1;
```

**Existing C++ lowering / result**

file-local late-static lowering helper path

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** current class-family candidates are visible in the current lowered file

**Normalized pattern:** `static::$<prop>`

**General rule:** Late-static property access is supported only through file-local structural lowering. The generator may use current-file class-family context but must not resolve symbols across source files.

**Diagnostics:** Error if the required current-file class context is unavailable or if cross-file knowledge would be required.

**Notes:** First-pass late-static property support.


## CLASS-PROP-007

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:55](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
$a::$x = 2;
```

**Existing C++ lowering / result**

```cpp
::scpp::class_t<decltype(a)>::x = static_cast<int_t>(2);
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** source uses static access through instance expression

**Normalized pattern:** `<expr>::$<prop>`

**General rule:** Static property access through an instance expression lowers syntactically using `::scpp::class_t<decltype(expr)>::prop`.

**Diagnostics:** Let invalid resulting C++ fail at C++ compile time if the type lacks such a member.

**Notes:** Mirrors the already approved static method form.


## CLASS-PROP-008

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:56](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
class B extends A { function f(): void { $this->x = 1; } }
```

**Existing C++ lowering / result**

```cpp
this->x = static_cast<int_t>(1);
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** property exists through inheritance

**Normalized pattern:** inherited instance property access

**General rule:** Inherited properties are accessed normally via `this->prop`.

**Diagnostics:** No cross-file validation of inherited members is performed.

**Notes:** The generator remains syntactic/local.


## CLASS-PROP-009

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:57](../../../../generators/php/specs/catalog.md)

**PHP input example**

`$this->y = 1;` where `y` is undeclared

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Class

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** property is not declared in class model

**Normalized pattern:** dynamic property creation

**General rule:** Dynamic properties are not supported.

**Diagnostics:** Emit an error when an undeclared property is introduced dynamically.

**Notes:** Explicit project decision.


## CLASS-PROP-010

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:58](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
$this->$name = 1;
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Class

**Rule kind:** rejection

**Source support status:** rejected

**Normalized pattern:** dynamic property name

**General rule:** Dynamic property access is not supported.

**Diagnostics:** Emit an error for variable property names.

**Notes:** Applies to reads and writes.


## CLASS-PROP-011

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:59](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
class A { public int $x = 1; public static int $s = 2; }
```

**Existing C++ lowering / result**

`class A { public: int_t x = static_cast<int_t>(1); static int_t s; };` and `int_t A::s = static_cast<int_t>(2);`

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** property types and initializers are supported

**Normalized pattern:** property default initialization

**General rule:** Instance-property defaults stay in the header as in-class initializers, while static-property defaults become out-of-class storage definitions in the source file.

**Diagnostics:** Error if the initializer expression is outside the supported subset.

**Notes:** Keeps C++ storage duration rules correct.


## CLASS-CALL-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:60](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:225](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a->f(1);
```

**Existing C++ lowering / result**

```cpp
a->f(static_cast<int_t>(1));
```

**Category:** Object

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** object expression is valid handle-like instance

**Normalized pattern:** `<expr>-><method>(<args>)`

**General rule:** Instance method calls lower to handle/pointer member calls.

**Diagnostics:** Error if generator emits direct object-dot semantics for user PHP objects.

**Notes:** Consistent with `create<T>()` object model.


## CLASS-CALL-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:61](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:226](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
A::f(1);
```

**Existing C++ lowering / result**

```cpp
A::f(static_cast<int_t>(1));
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** static method is declared

**Normalized pattern:** `<class>::<method>(<args>)`

**General rule:** Static method calls through class names remain `Class::method(...)`.

**Diagnostics:** Error if instance-call syntax is emitted.

**Notes:** Straight syntactic lowering.


## CLASS-CALL-003

**v0.2 decision / target C++:** Pending discussion.

Different source versions share this ID. Preserve both until their differences are discussed; neither is silently selected as the v0.2 decision.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:62](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
$a::f(1);
```

**Existing C++ lowering / result**

```cpp
::scpp::class_t<decltype(a)>::f(static_cast<int_t>(1));
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** source uses static access through instance expression

**Normalized pattern:** `<expr>::<method>(<args>)`

**General rule:** Static method calls through an instance expression lower syntactically using `::scpp::class_t<decltype(expr)>::method(...)`.

**Diagnostics:** Let invalid generated C++ fail at C++ compile time if needed.

**Notes:** This preserves the earlier namespace/class decision.

### Imported version 2

**Source:** [generators/php/specs/rules_catalog.md:227](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a::f(1);
```

**Existing C++ lowering / result**

```cpp
::scpp::class_t<decltype(a)>::f(static_cast<int_t>(1));
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** source uses static access through instance expression

**Normalized pattern:** `<expr>::<method>(<args>)`

**General rule:** Static method calls through an instance expression lower syntactically using `::scpp::class_t<decltype(expr)>::method(...)`.

**Diagnostics:** Let invalid generated C++ fail at C++ compile time if needed.

**Notes:** Mirrors the earlier namespace/class decision.


## CLASS-CALL-004

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:63](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:228](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$this->f(1);
```

**Existing C++ lowering / result**

```cpp
this->f(static_cast<int_t>(1));
```

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** current context allows `$this`

**Normalized pattern:** `$this-><method>(<args>)`

**General rule:** `$this->method(...)` lowers to `this->method(...)`.

**Diagnostics:** Error if `$this` appears in invalid context.

**Notes:** Body rules still normalize arguments.


## CLASS-CALL-005

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:64](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:229](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
parent::f(1);
```

**Existing C++ lowering / result**

```cpp
base::f(static_cast<int_t>(1));
```

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** current class has parent and `base` alias

**Normalized pattern:** `parent::<method>(<args>)`

**General rule:** Parent method calls lower to `base::method(...)`.

**Diagnostics:** Error if no parent exists.

**Notes:** This is the final approved form.


## CLASS-CALL-005A

**v0.2 decision / target C++:** Pending discussion.

Different source versions share this ID. Preserve both until their differences are discussed; neither is silently selected as the v0.2 decision.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:65](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
static::f(1);
```

**Existing C++ lowering / result**

file-local late-static lowering helper path

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** current class-family candidates are visible in the current lowered file

**Normalized pattern:** `static::<method>(<args>)`

**General rule:** Late-static method calls are supported only through file-local structural lowering. The generator may use current-file class-family context but must not resolve symbols across source files.

**Diagnostics:** Error if the required current-file class context is unavailable or if cross-file knowledge would be required.

**Notes:** First-pass late-static method support.

### Imported version 2

**Source:** [generators/php/specs/rules_catalog.md:230](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
static::f(1)
```

**Existing C++ lowering / result**

file-local late-static lowering helper path

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** current class-family candidates are visible in the current lowered file

**Normalized pattern:** `static::<method>(<args>)`

**General rule:** Late-static method calls are supported only through file-local structural lowering. The generator may use current-file class-family context but must not resolve symbols across source files.

**Diagnostics:** Error if the required current-file class context is unavailable or if cross-file knowledge would be required.

**Notes:** First-pass late-static method support.


## CLASS-CALL-006

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:66](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:231](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(): int { return 1; }
```

**Existing C++ lowering / result**

ordinary non-virtual declaration unless another explicit rule applies

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** method is not abstract/interface and lacks explicit `#[\Override]`

**Normalized pattern:** ordinary method declaration

**General rule:** The generator does not infer `virtual`; virtual-related markers are emitted only from explicit source forms such as abstract/interface methods and `#[\Override]`.

**Diagnostics:** No semantic hierarchy analysis is performed.

**Notes:** Explicitly approved.


## CLASS-CALL-007

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:67](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:232](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$i->f(1);
```

**Existing C++ lowering / result**

```cpp
i->f(static_cast<int_t>(1));
```

**Category:** Interface

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** `i` is already typed as an interface-compatible handle

**Normalized pattern:** interface dispatch call

**General rule:** Interface instance calls use the normal handle member-call syntax.

**Diagnostics:** Error handling remains local/syntactic.

**Notes:** No special lowering beyond declared type.


## CLASS-CALL-008

**v0.2 decision / target C++:** Pending discussion.

Different source versions share this ID. Preserve both until their differences are discussed; neither is silently selected as the v0.2 decision.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:68](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
new A(1)
```

**Existing C++ lowering / result**

```cpp
create<A>(static_cast<int_t>(1))
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** target is user PHP class

**Normalized pattern:** object creation expression

**General rule:** Object creation for user PHP classes lowers to `create<Class>(...)`.

**Diagnostics:** Error if raw `new` is emitted.

**Notes:** Preserved for method-call catalog completeness.

### Imported version 2

**Source:** [generators/php/specs/rules_catalog.md:233](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
new A(1)
```

**Existing C++ lowering / result**

```cpp
create<A>(static_cast<int_t>(1))
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** target is a user PHP class

**Normalized pattern:** object creation expression

**General rule:** Object creation for user PHP classes lowers to `create<Class>(...)`.

**Diagnostics:** Error if raw `new` is emitted.

**Notes:** Preserved for method-call catalog completeness.


## CLASS-CALL-008A

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:69](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:234](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
new static(1)
```

**Existing C++ lowering / result**

file-local late-static lowering helper path

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** current class-family candidates are visible in the current lowered file

**Normalized pattern:** `new static(<args>)`

**General rule:** Late-static construction is supported only through file-local structural lowering. The generator may use current-file class-family context but must not resolve symbols across source files.

**Diagnostics:** Error if the required current-file class context is unavailable or if cross-file knowledge would be required.

**Notes:** First-pass late-static construction support.


## CLASS-CALL-009

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:70](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:235](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a->$name();
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Class

**Rule kind:** rejection

**Source support status:** rejected

**Normalized pattern:** dynamic method name

**General rule:** Dynamic method names are not supported.

**Diagnostics:** Emit an error for variable method names.

**Notes:** Explicit project decision.


## CLASS-CALL-010

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:71](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:236](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
[$a, 'f']
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Class

**Rule kind:** rejection

**Source support status:** rejected

**Normalized pattern:** method callable array

**General rule:** Method callable indirection forms are out of scope for now.

**Diagnostics:** Emit an error for callable-array method forms.

**Notes:** Explicitly rejected for current S2S scope.


## CLASS-CALL-011

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:72](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:237](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

explicit destructor invocation pattern

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Class

**Rule kind:** rejection

**Source support status:** rejected

**Normalized pattern:** explicit source-level destructor call

**General rule:** Explicit destructor invocation is not supported in source lowering.

**Diagnostics:** Emit an error if such a source-level form is encountered.

**Notes:** Lifecycle remains runtime/object-model driven.


## CLASS-CALL-012

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:73](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:238](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
static function g(int $x): int { return $x; }
```

**Existing C++ lowering / result**

header contains qualifiers; source contains plain out-of-line definition

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** method declaration is valid

**Normalized pattern:** declaration-site qualifiers

**General rule:** `static`, `override`, `virtual`, `final`, and default arguments belong to declaration site only; source definitions are emitted without redeclaring those qualifiers.

**Diagnostics:** Error if qualifiers/defaults are repeated in source definitions.

**Notes:** Pure virtual methods have no source definition.


## CLASS-CONST-001

**v0.2 decision / target C++:** Pending discussion.

Different source versions share this ID. Preserve both until their differences are discussed; neither is silently selected as the v0.2 decision.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:74](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
class A { public const X = 1; }
```

**Existing C++ lowering / result**

```cpp
class A { public: static constexpr auto X = static_cast<int_t>(1); };
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** initializer expression is inside the supported constant subset

**Normalized pattern:** class constant declaration

**General rule:** Class constant declarations are supported and lower to class-scope constant storage in the generated header.

**Diagnostics:** Error if the initializer expression is outside the supported constant subset.

**Notes:** Declaration-only constant emission.

### Imported version 2

**Source:** [generators/php/specs/rules_catalog.md:199](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A { public const X = 1; }
```

**Existing C++ lowering / result**

```cpp
class A { public: static constexpr auto X = static_cast<int_t>(1); };
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** initializer expression is inside the supported constant subset

**Normalized pattern:** `class constant declaration`

**General rule:** Class constant declarations are supported and lower to class-scope constant storage in the generated header.

**Diagnostics:** Error if the initializer expression is outside the supported constant subset.

**Notes:** Declaration-only constant emission.


## CLASS-CONST-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:75](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:200](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
A::X
```

**Existing C++ lowering / result**

```cpp
A::X
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** constant is declared on class

**Normalized pattern:** `<class>::<const>`

**General rule:** Class constant access through a class name lowers directly to `Class::CONST`.

**Diagnostics:** Error if generator rewrites constant access through instance syntax.

**Notes:** Lexical class-name constant access.


## CLASS-CONST-003

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:76](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:201](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
self::X
```

**Existing C++ lowering / result**

```cpp
CurrentClass::X
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** current context is a class body/method

**Normalized pattern:** `self::<const>`

**General rule:** `self::CONST` lowers to the current generated class constant.

**Diagnostics:** Error if no current class context exists.

**Notes:** Lexical current-class constant access.


## CLASS-CONST-004

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:77](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:202](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
parent::X
```

**Existing C++ lowering / result**

```cpp
ParentClass::X
```

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** current class has a parent

**Normalized pattern:** `parent::<const>`

**General rule:** `parent::CONST` lowers to the parent class constant.

**Diagnostics:** Error if no parent class exists.

**Notes:** Uses the normalized parent class name.


## CLASS-CONST-005

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:78](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:203](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
static::X
```

**Existing C++ lowering / result**

file-local late-static lowering helper path

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** current class-family candidates are visible in the current lowered file

**Normalized pattern:** `static::<const>`

**General rule:** Late-static constant access is supported only through file-local structural lowering. The generator may use current-file class-family context but must not resolve symbols across source files.

**Diagnostics:** Error if the required current-file class context is unavailable or if cross-file knowledge would be required.

**Notes:** First-pass late-static constant support.


## CLASS-MEMBER-001

**v0.2 decision / target C++:** Pending discussion.

Different source versions share this ID. Preserve both until their differences are discussed; neither is silently selected as the v0.2 decision.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:79](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
function f(int $a): int { return $a; }
```

**Existing C++ lowering / result**

```cpp
int_t f(int_t a);
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** parameter types are explicit and supported

**Normalized pattern:** typed method parameter list

**General rule:** Method parameters must be typed.

**Diagnostics:** Error if a parameter type is omitted.

**Notes:** Applies to methods and, by extension, constructors where relevant.

### Imported version 2

**Source:** [generators/php/specs/rules_catalog.md:239](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int $a): int { return $a; }
```

**Existing C++ lowering / result**

```cpp
int_t f(int_t a);
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** parameter types are explicit and supported

**Normalized pattern:** typed method parameter list

**General rule:** Method parameters must be typed.

**Diagnostics:** Error if a parameter type is omitted.

**Notes:** Applies to methods and constructors where relevant.


## CLASS-MEMBER-001B

**Strict-mode PHP input example:** `function f(vector<int> $a): int { return 1; }`

This is the working source spelling. Imported examples and C++ expectations below remain reference material; the v0.2 result still needs agreement and proof.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:80](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:240](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(/** vector<int> */ $a): int { return 1; }
```

**Existing C++ lowering / result**

```cpp
int_t f(const vector_t<int_t>& a);
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** method parameter doc-comment type is explicit and supported

**Normalized pattern:** doc-typed method parameter

**General rule:** Method parameters may take their explicit type from an attached doc-comment when no native PHP type is present.

**Diagnostics:** Error if the doc-comment is detached or conflicts with a native PHP type.

**Notes:** The same parameter rule set applies to methods and constructors.


## CLASS-MEMBER-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:81](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:241](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int $a): int { return $a; }
```

**Existing C++ lowering / result**

```cpp
int_t f(int_t a);
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** return type is explicit and supported

**Normalized pattern:** explicit method return type

**General rule:** Method return type must be explicit.

**Diagnostics:** Error if return type is omitted.

**Notes:** Constructor/destructor are separate special forms.


## CLASS-MEMBER-003

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:82](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:242](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int $value): int { return $value; }
```

**Existing C++ lowering / result**

```cpp
int_t f(int_t value);
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** declaration is otherwise valid

**Normalized pattern:** parameter name preservation

**General rule:** Parameter names are preserved in the generated declaration/definition.

**Diagnostics:** Error only if normalization makes the name invalid.

**Notes:** Name normalization rules still apply globally.


## CLASS-MEMBER-004

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:83](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:244](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
static function f(int $a): int { return $a; }
```

**Existing C++ lowering / result**

`static int_t f(int_t a);` in header, `int_t A::f(int_t a) { ... }` in source

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** static method declaration is valid

**Normalized pattern:** static method

**General rule:** Static methods remain static C++ methods; `static` appears only in the header declaration.

**Diagnostics:** Error if `static` is repeated in the source definition.

**Notes:** Approved explicitly.


## CLASS-MEMBER-005

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:84](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:245](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int $a): int { return $a; }
```

**Existing C++ lowering / result**

```cpp
int_t f(int_t a);
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** method declaration is valid

**Normalized pattern:** instance method

**General rule:** Non-static methods have implicit instance context (`this`).

**Diagnostics:** Error only on invalid member combinations.

**Notes:** Paired with `$this` rules.


## CLASS-MEMBER-006

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:85](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:246](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
abstract function f(int $a): int;
```

**Existing C++ lowering / result**

```cpp
virtual int_t f(int_t a) = 0;
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** abstract method syntax is valid

**Normalized pattern:** abstract method

**General rule:** Abstract methods lower to pure virtual declarations.

**Diagnostics:** Error if a body is present.

**Notes:** No source definition.


## CLASS-MEMBER-007

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:86](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:247](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
#[\Override] function f(int $a): int { return $a; }
```

**Existing C++ lowering / result**

```cpp
int_t f(int_t a) override;
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** `#[\Override]` attribute explicitly present

**Normalized pattern:** override-marked method

**General rule:** Emit `override` only when the explicit attribute is present.

**Diagnostics:** No semantic override validation.

**Notes:** Mirrors CLASS-INH-011.


## CLASS-MEMBER-008

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:87](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:248](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
final function f(int $a): int { return $a; }
```

**Existing C++ lowering / result**

```cpp
int_t f(int_t a) final;
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** final method syntax is valid

**Normalized pattern:** final method

**General rule:** `final` is preserved on method declarations.

**Diagnostics:** Error on invalid modifier combinations.

**Notes:** Declaration-site only.


## CLASS-MEMBER-009

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:88](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:249](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
abstract static function f(): int;
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Class

**Rule kind:** rejection

**Source support status:** rejected

**Normalized pattern:** abstract + static method

**General rule:** `abstract static` methods are not supported.

**Diagnostics:** Emit an error for the combination.

**Notes:** Explicit project decision.


## CLASS-MEMBER-010

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:89](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:250](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
#[\Override] static function f(): int { return 1; }
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Class

**Rule kind:** rejection

**Source support status:** rejected

**Normalized pattern:** override + static method

**General rule:** `static` and explicit `#[\Override]` cannot be combined.

**Diagnostics:** Emit an error for the combination.

**Notes:** Explicit project decision.


## CLASS-MEMBER-011

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:90](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:251](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function __construct(int $x) { $this->x = $x; }
```

**Existing C++ lowering / result**

`A(int_t x);` in header and `A::A(int_t x) { this->x = x; }` in source

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** constructor declaration is valid

**Normalized pattern:** constructor

**General rule:** `__construct` lowers to a C++ constructor named after the class.

**Diagnostics:** Error if a return type is declared on the constructor.

**Notes:** Constructor bodies are emitted out of line.


## CLASS-MEMBER-012

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:91](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:252](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function __destruct() {}
```

**Existing C++ lowering / result**

`~A();` in header and `A::~A() {}` in source

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** destructor declaration is valid

**Normalized pattern:** destructor

**General rule:** `__destruct` lowers to a C++ destructor.

**Diagnostics:** Error if parameters or return type are present.

**Notes:** Destructor bodies are emitted out of line.


## CLASS-MEMBER-013

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:92](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:253](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
interface I { function f(int $a): int; }
```

**Existing C++ lowering / result**

```cpp
virtual int_t f(int_t a) = 0;
```

**Category:** Interface

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** interface method has no body

**Normalized pattern:** interface member

**General rule:** Interface methods are always pure virtual and bodyless.

**Diagnostics:** Error if body is present.

**Notes:** Interface fields remain unsupported.


## CLASS-MEMBER-014

**v0.2 decision / target C++:** Pending discussion.

Different source versions share this ID. Preserve both until their differences are discussed; neither is silently selected as the v0.2 decision.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:93](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
private function f(): int { return 1; }
```

**Existing C++ lowering / result**

declaration emitted under `private:` section

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** visibility syntax is otherwise valid

**Normalized pattern:** visibility modifier

**General rule:** Visibility is preserved for properties, methods, and class constants.

**Diagnostics:** Error if source/build analysis detects access outside the declared visibility.

**Notes:** Replaces the previous public-normalization compromise.

### Imported version 2

**Source:** [generators/php/specs/rules_catalog.md:254](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
private function f(): int { return 1; }
```

**Existing C++ lowering / result**

declaration emitted under `private:` section

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** visibility syntax is otherwise valid

**Normalized pattern:** visibility modifier

**General rule:** Member visibility is preserved for properties, methods, and class constants. Omitted visibility defaults to `public`; `protected` and `private` must be enforced by source/build analysis and reflected in generated C++ access sections.

**Diagnostics:** Error if source/build analysis detects access outside the declared visibility.

**Notes:** Replaces the earlier practical public-normalization compromise.


## CLASS-MEMBER-015

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:94](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:255](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int $a): int {} function f(string $a): int {}
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Class

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** same class/member namespace

**Normalized pattern:** same-name multiple methods

**General rule:** Method overloading is forbidden by Prism++ design.

**Diagnostics:** Emit an error on duplicate method names regardless of parameter differences.

**Notes:** Also applies project-wide to functions.


## CLASS-MEMBER-016

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:95](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:256](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int $a = 1): int { return $a; }
```

**Existing C++ lowering / result**

`int_t f(int_t a = static_cast<int_t>(1));` in header and `int_t A::f(int_t a) { return a; }` in source

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** default value expression is otherwise supported

**Normalized pattern:** default parameter value

**General rule:** Default parameter values are supported and are emitted in the declaration only.

**Diagnostics:** Error if default values are repeated in the source definition.

**Notes:** Approved explicitly after revision.


## CLASS-MEMBER-017

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:96](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:257](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int ...$a): int { return 1; }
```

**Existing C++ lowering / result**

```cpp
int_t f(const vector_t<int_t>& a) { return static_cast<int_t>(1); }
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variadic parameter type is explicit and supported; variadic parameter is trailing

**Normalized pattern:** variadic method

**General rule:** Typed trailing variadics are supported and lower to `const vector_t<T>&`.

**Diagnostics:** Emit a normal method signature/body using the mapped element type. Error if the variadic type is missing, unsupported, or the variadic parameter is not trailing.

**Notes:** Untyped/mixed variadics remain unsupported.


## CLASS-MEMBER-018A

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:97](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:258](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int &$a): void {}
```

**Existing C++ lowering / result**

```cpp
void_t f(int_t& a);
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** parameter reference syntax is explicit and valid

**Normalized pattern:** reference parameter

**General rule:** Reference parameters are supported for methods.

**Diagnostics:** Error if generator drops the reference marker.

**Notes:** Subject to the native-reference safety rule; explicit reference syntax alone does not justify exposing dynamic interior storage.


## CLASS-MEMBER-018B

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:98](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:259](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function &f(): int {}
```

**Existing C++ lowering / result**

```cpp
int_t& f();
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** return reference syntax is explicit and valid

**Normalized pattern:** reference return

**General rule:** Reference returns are supported for methods when the PHP return type is explicitly declared.

**Diagnostics:** Error if generator drops the reference marker.

**Notes:** Subject to the native-reference safety rule; explicit reference syntax alone does not justify exposing dynamic interior storage.


## CLASS-MEMBER-018C

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:99](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:260](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function &f(int &$a): int {}
```

**Existing C++ lowering / result**

```cpp
int_t& f(int_t& a);
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** explicit reference markers present

**Normalized pattern:** reference preservation

**General rule:** Reference markers are preserved in generated method signatures exactly as requested by source syntax.

**Diagnostics:** No inference is performed.

**Notes:** Purely explicit.


## CLASS-MEMBER-018D

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:100](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:261](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

explicit PHP reference syntax on method/member signature

**Existing C++ lowering / result**

literal reference-preserving C++ signature

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** source explicitly contains reference semantics

**Normalized pattern:** explicit-only reference semantics

**General rule:** Reference semantics are emitted only from explicit source syntax; the generator does not infer references.

**Diagnostics:** No semantic inference or alias analysis is performed.

**Notes:** Approved explicitly.


## CLASS-MEMBER-018E

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:101](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:262](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function &f() { return $this->x; }
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Class

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** by-reference method return lacks an explicit PHP return type

**Normalized pattern:** untyped method reference return

**General rule:** By-reference method returns must have an explicit declared PHP return type so the generated C++ signature can use a concrete `T&` instead of deduced `auto&`.

**Diagnostics:** Emit an explicit generator error when a method returns by reference without a declared PHP return type.

**Notes:** This keeps method declarations and out-of-class definitions consistent.


## CLASS-PROP-002A

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:189](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A { static int $x = 1; }
```

**Existing C++ lowering / result**

`class A { public: static int_t x; };` and `int_t A::x = static_cast<int_t>(1);`

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** static property type and initializer are supported

**Normalized pattern:** `class <name> { static <typed-property> = <expr>; }`

**General rule:** Static property declarations stay in the header and their storage definitions are emitted in the source file.

**Diagnostics:** Error if generator keeps the initializer inline on the header declaration.

**Notes:** Matches C++ static member storage rules.


## CLASS-PROP-002B

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:190](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
self::$x
```

**Existing C++ lowering / result**

```cpp
CurrentClass::x
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** current context is a class body/method

**Normalized pattern:** `self::$<prop>`

**General rule:** `self::$prop` lowers to the current class static member.

**Diagnostics:** Error if no current class context exists.

**Notes:** Lexical current-class lowering.


## CLASS-PROP-002C

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:191](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
parent::$x
```

**Existing C++ lowering / result**

```cpp
ParentClass::x
```

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** current class has a parent

**Normalized pattern:** `parent::$<prop>`

**General rule:** `parent::$prop` lowers to the parent class static member.

**Diagnostics:** Error if no parent class exists.

**Notes:** Uses the normalized parent class name.


## CLASS-PROP-002D

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:192](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
static::$x
```

**Existing C++ lowering / result**

file-local late-static lowering helper path

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** current class-family candidates are visible in the current lowered file

**Normalized pattern:** `static::$<prop>`

**General rule:** Late-static property access is supported only through file-local structural lowering. The generator may use current-file class-family context but must not resolve symbols across source files.

**Diagnostics:** Error if the required current-file class context is unavailable or if cross-file knowledge would be required.

**Notes:** First-pass late-static property support.


## CLASS-METHOD-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:193](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A { function f(): int { return 1; } }
```

**Existing C++ lowering / result**

header declaration + out-of-line source definition

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** method declaration is otherwise valid

**Normalized pattern:** `class <name> { <method>; }`

**General rule:** Ordinary instance methods are supported and are declared in the header and defined out of line in the source file.

**Diagnostics:** Error on invalid member combinations.

**Notes:** Instance methods carry implicit `this` context.


## CLASS-METHOD-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:194](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A { static function f(int $a): int { return $a; } }
```

**Existing C++ lowering / result**

`static int_t f(int_t a);` in header and `int_t A::f(int_t a) { return a; }` in source

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** static method declaration is valid

**Normalized pattern:** `class <name> { static <typed-method>; }`

**General rule:** Static methods are supported; `static` appears in the declaration only.

**Diagnostics:** Error if `static` is repeated in the source definition.

**Notes:** Same-namespace and rooted static access rules remain separate.


## CLASS-THIS-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:195](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A { int $x; function f(): int { return $this->x; } }
```

**Existing C++ lowering / result**

```cpp
int_t A::f() { return this->x; }
```

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** current context is an instance method, constructor, or destructor

**Normalized pattern:** `$this-><property>`

**General rule:** `$this` is allowed only in instance methods, constructors, and destructors. `$this->member` lowers to `this->member`.

**Diagnostics:** Error if `$this` appears in static methods or outside instance context.

**Notes:** Applies to both properties and methods.


## CLASS-NEW-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:196](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = new A();
```

**Existing C++ lowering / result**

```cpp
auto a = create<A>();
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** class name is used in the same namespace context and lowering is syntactic

**Normalized pattern:** `<var> = new <class>()`

**General rule:** Object construction must not emit raw `new`. `new Class(...)` lowers to `create<Class>(...)`.

**Diagnostics:** Error if raw `new` is emitted.

**Notes:** Ownership/runtime semantics are delegated to `create<...>()`.


## CLASS-NEW-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:197](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = new \A\B();
```

**Existing C++ lowering / result**

```cpp
auto a = create<::scpp::A::B>();
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** class name is fully-qualified in PHP input

**Normalized pattern:** `<var> = new <qualified-class>(<args>)`

**General rule:** Fully-qualified class construction lowers syntactically into `create<::scpp::...>(...)`. The generator must not attempt semantic resolution beyond explicit fully-qualified name lowering.

**Diagnostics:** Error if the rooted `::scpp::...` form is not used for fully-qualified PHP class names.

**Notes:** Example: `new \A\X()` â†’ `create<::scpp::A::X>()`.


## CLASS-CONSTRUCT-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:198](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A { function __construct(int $x) { $this->x = $x; } }
```

**Existing C++ lowering / result**

`A(int_t x);` in header and `A::A(int_t x) { this->x = x; }` in source

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** constructor declaration is valid

**Normalized pattern:** `constructor method`

**General rule:** `__construct` lowers to a C++ constructor named after the class. Constructor bodies are emitted out of line.

**Diagnostics:** Error if a return type is declared on the constructor.

**Notes:** Parent constructor calls have a separate base-initializer rule.


## OBJ-PROP-READ-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:278](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $obj->x;
```

**Existing C++ lowering / result**

_Not supplied in source._

**Category:** Object

**Rule kind:** generation-minimum

**Normalized pattern:** `<var> = <expr>-><property>`


## OBJ-PROP-WRITE-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:279](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$obj->x = 1;
```

**Existing C++ lowering / result**

_Not supplied in source._

**Category:** Object

**Rule kind:** generation-minimum

**Normalized pattern:** `<expr>-><property> = <expr>`


## OBJ-METHOD-CALL-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:280](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $obj->f();
```

**Existing C++ lowering / result**

_Not supplied in source._

**Category:** Object

**Rule kind:** generation-minimum

**Normalized pattern:** `<var> = <expr>-><method>()`


## OBJ-METHOD-CALL-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:281](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $obj->f(1);
```

**Existing C++ lowering / result**

_Not supplied in source._

**Category:** Object

**Rule kind:** generation-minimum

**Normalized pattern:** `<var> = <expr>-><method>(<expr>)`


## TYPE-VAR-003

**Strict-mode PHP input example:** `$x A = new A();`

This is the working source spelling. Imported examples and C++ expectations below remain reference material; the v0.2 result still needs agreement and proof.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:324](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$x /** A */ = new A();
```

**Existing C++ lowering / result**

```cpp
shared_p<A> x = create<A>();
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** explicit class/interface/abstract PHPDoc variable type present

**Normalized pattern:** `typed object local variable`

**General rule:** Explicitly typed object local variables lower to `shared_p<T>` handles.

**Diagnostics:** Error only if the explicit type form itself is unsupported.

**Notes:** Object-handle details are runtime-defined.


## TYPE-VAR-004

**Strict-mode PHP input example:** `$x ?A = null;`

This is the working source spelling. Imported examples and C++ expectations below remain reference material; the v0.2 result still needs agreement and proof.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:325](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$x /** ?A */ = null;
```

**Existing C++ lowering / result**

```cpp
shared_p<A> x = null;
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** explicit nullable object PHPDoc variable type present

**Normalized pattern:** `typed nullable object local variable`

**General rule:** Object-handle types are inherently nullable for current code generation, so `A` and `?A` both emit `shared_p<A>`.

**Diagnostics:** No generator-side distinction is required between `A` and `?A` for emitted handle type.

**Notes:** Runtime nullability enforcement for non-nullable object annotations is deferred.


## TYPE-PARAM-004

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:340](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(A $a, ?B $b, I $i): A { return $a; }
```

**Existing C++ lowering / result**

```cpp
shared_p<A> f(shared_p<A> a, shared_p<B> b, shared_p<I> i) { return a; }
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** object/interface/abstract types are explicit and supported

**Normalized pattern:** `object-handle parameter and return convention`

**General rule:** Class, interface, and abstract object types are emitted as `shared_p<T>` handles passed and returned by value. Current code generation emits the same handle type for `T` and `?T`.

**Diagnostics:** No generator-side nullability distinction is required for object-handle type emission.

**Notes:** Future runtime checks may enforce non-null annotations.


## TYPE-PROP-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:341](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A { protected string $s; protected B $b; }
```

**Existing C++ lowering / result**

```cpp
class B; class A { protected: string_t s; shared_p<B> b; };
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** property types are explicit and supported

**Normalized pattern:** `typed property mapping`

**General rule:** Property types follow the same mapping rules as parameters and locals: value-like types map to runtime value wrappers, object types map to `shared_p<T>`, and declared property visibility determines the generated access section.

**Diagnostics:** Error if a property type form is unsupported or access violates the declared visibility.

**Notes:** Forward declarations should be emitted where sufficient for object-typed properties.


## TYPE-PROP-001B

**Strict-mode PHP input example:** `class A { public int $x; }`

This is the working source spelling. Imported examples and C++ expectations below remain reference material; the v0.2 result still needs agreement and proof.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:342](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A { public /** int */ $x; }
```

**Existing C++ lowering / result**

```cpp
class A { public: int_t x; };
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** property doc-comment type is explicit and supported

**Normalized pattern:** `doc-typed property mapping`

**General rule:** Class properties may take their explicit type from an attached doc-comment when no native PHP type is present.

**Diagnostics:** Error if the property has no explicit type or if both native and doc-comment types are present.

**Notes:** Applies to static and instance properties.


## TYPE-PROP-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:343](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A { protected ?string $x; protected ?B $b; }
```

**Existing C++ lowering / result**

```cpp
class B; class A { protected: nullable<string_t> x; shared_p<B> b; };
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** nullable property types are explicit and supported

**Normalized pattern:** `nullable property mapping`

**General rule:** Nullable value-like properties lower to `nullable<T>`, while nullable object properties remain `shared_p<T>` under the current object-handle model.

**Diagnostics:** Error if the generator emits `nullable<shared_p<T>>` for object properties.

**Notes:** Current object-handle typing keeps `T` and `?T` identical in emitted type.


## NOTE-029

**Source:** [generators/php/specs/rules.md:520](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 15. Class Construction and Static Access
>
> ### 15.1 Object Construction
> `new Class(...)` must be lowered to `create<Class>(...)`.
>
> This rule applies to ordinary classes. For a declared struct, `new Struct()`
> must emit a value initializer `Struct{}` and expression typing must report the
> same value type. Construction emission and typing must use the same declaration
> identity/kind classification, including project metadata and imported names.
> Struct construction with any arguments (including named or unpacked arguments)
> is rejected with a source diagnostic. Explicit ownership-wrapper initialization
> from `new Struct()` is unsupported and must not bypass value construction.
> See `specs/compact_layout_types.md`, section 2.4.
>
> Examples:
> - `new X()` â†’ `create<X>()`
> - `new \A\B\X()` â†’ `create<::scpp::A::B::X>()`
>
> The generator must not emit raw `new` for these supported construction forms.
>
> ### 15.2 Static Access
> - same-namespace static access remains unqualified, for example `X::make()`
> - fully-qualified PHP static access lowers to rooted C++ access, for example `\A\X::make()` â†’ `::scpp::A::X::make()`
>
> ### 15.3 Static Access Through Instances
> PHP static access through an instance must be lowered syntactically using `::scpp::class_t<decltype(...)>`.
>
> Example:
> - `$x::make()` â†’ `::scpp::class_t<decltype(x)>::make()`
>
> The generator must not attempt to validate whether `::scpp::class_t<decltype(...)>::member` is semantically valid for the produced C++ type.
>
> If the emitted C++ is invalid, it must fail at C++ compile time rather than being rejected by the generator.
>
> ---
>
> # Appendix: Full Original Rules (verbatim)
>
> # Prism++ â€“ rules.md
>
> This is the single source of truth for generation rules and runtime assumptions.

## NOTE-050

**Source:** [generators/php/specs/rules.md:1102](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 16. Classes, Inheritance, and Members
>
> ### 16.1 File Split
> - each user PHP class lowers to a header declaration unit and a source implementation unit
> - the header contains the class definition, fields, constructor declarations, destructor declarations, and method declarations
> - the source contains out-of-line constructor, destructor, and method bodies
>
> ### 16.2 Supported Forms
> - inheritance is supported
> - interfaces are supported
> - traits are not supported
> - only one parent class is allowed
> - multiple interfaces are allowed
>
> ### 16.3 Base Alias
> - if a class has a parent, emit `using base = Parent;` in the class body
> - `parent::method(...)` lowers to `base::method(...)`
> - `parent::__construct(...)` lowers to a base initializer call
>
> ### 16.4 Construction and Lifetime Surface
> - `new Class(...)` lowers to `create<Class>(...)`
> - direct construction of user-defined PHP classes is forbidden
> - direct construction is required for whitelisted runtime/value types such as `string_t` and `vector_t`
> - explicit runtime ownership forms such as `weak($object)`, `unique(new MyClass)`, and `shared(new MyClass)` are allowed surface forms when separately supported by the generator; `shared(new MyClass)` is the explicit counterpart of `create<MyClass>(...)`
>
> ### 16.5 Instance Context
> - `$this` is valid only in instance methods, constructors, and destructors
> - `$this->prop` lowers to `this->prop`
> - `$this->method(...)` lowers to `this->method(...)`
>
> ### 16.6 Properties
> - properties without defaults must be typed explicitly
> - properties with default values may omit the explicit type; the generator infers the emitted C++ member type from the default initializer
> - if both a native PHP type and a supported doc-comment type are present, emit an error
> - instance properties are emitted in the header only
> - non-static property default values are supported and lower to in-class default member initializers
> - dynamic properties are not supported
> - dynamic property names are not supported
> - object-typed fields lower to handle fields such as `shared_p<B>`
> - when needed for headers, forward declarations such as `class B;` may be emitted
> - static object-typed properties use the same handle model
> - static property fetch/read/write/increment lower to `Class::prop` storage access in generated C++
> - supported static-property class forms are `ClassName::$prop`, `self::$prop`, and `parent::$prop`
> - `static::$prop` is supported in the current pass as file-local late-static lowering only; the generator may use current-file structural class-family context but must not resolve symbols across source files
>
> ### 16.7 Methods and Special Members
> - non-static methods are supported
> - static methods are supported
> - `static::method(...)` is supported in the current pass as file-local late-static lowering only; the generator may use current-file structural class-family context but must not resolve symbols across source files
> - `static::CONST` is supported in the current pass as file-local late-static lowering only
> - `new static(...)` is supported in the current pass as file-local late-static lowering only
> - constructors are supported
> - destructors are supported
> - abstract classes are supported when explicitly declared abstract
> - abstract methods lower to pure virtual methods
> - interface methods lower to pure virtual methods
> - `#[\Override]` is required to emit `override`; the generator must not infer overrides
> - `final` is preserved on declarations
> - `abstract static` methods are rejected
> - `static` with `#[\Override]` is rejected
> - class and method overloading are forbidden
>
> ### 16.8 Dispatch and Validation Boundary
> - ordinary methods are not made virtual unless an explicit rule requires it
> - dispatch remains ordinary C++ dispatch
> - the generator must not attempt hierarchy validation, symbol resolution across files, or override correctness checks unless a generation rule explicitly requires a local structural check
> - let the C++ compiler fail for semantic issues outside generator scope
>
> ### 16.9 Static Access Through Instances
> - PHP static access through an instance must be lowered syntactically using `::scpp::class_t<decltype(...)>`
> - `$x::make()` â†’ `::scpp::class_t<decltype(x)>::make()`
> - `$x::$prop` â†’ `::scpp::class_t<decltype(x)>::prop`
> - the generator must not attempt to validate whether the generated C++ member access is semantically valid

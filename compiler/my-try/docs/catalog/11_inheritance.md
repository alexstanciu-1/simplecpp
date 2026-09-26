# 11. Inheritance, interfaces and dispatch
Doc Status: planning

> **Important: LLVM is deferred by default. Work on the direct LLVM backend only when the user explicitly requests it. The v0.2 default scope is Frontend + C++ S2S.**

[Catalog and workflow](README.md) · [Source merge ledger](source_merge.json)

Requires classes and methods. Resolve relationships before advanced dispatch cases.

Order is a discussion sequence, not a claim that every row is a prerequisite. Split combined examples before implementation.

## Progress

Edit these rows as work proceeds. Imported source support is recorded below, independently of this progress.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [CLASS-INH-001](#class-inh-001) | pending-discussion | `class B extends A {}` | unverified | unverified | deferred | — |
| [CLASS-INH-002](#class-inh-002) | pending-discussion | `class A extends B implements I, J {}` | unverified | unverified | deferred | — |
| [CLASS-INH-003](#class-inh-003) | pending-discussion | `class A { use T; }` | unverified | unverified | deferred | — |
| [CLASS-INH-004](#class-inh-004) | pending-discussion | `interface I { function f(int $a): int; }` | unverified | unverified | deferred | — |
| [CLASS-INH-006](#class-inh-006) | pending-discussion | `$a = new A(1);` | unverified | unverified | deferred | — |
| [CLASS-INH-007](#class-inh-007) | pending-discussion | `A a(1);` | unverified | unverified | deferred | — |
| [CLASS-INH-008](#class-inh-008) | pending-discussion | `$this->f();` | unverified | unverified | deferred | — |
| [CLASS-INH-009](#class-inh-009) | pending-discussion | `parent::f(1);` | unverified | unverified | deferred | — |
| [CLASS-INH-010](#class-inh-010) | pending-discussion | `parent::__construct($x);` | unverified | unverified | deferred | — |
| [CLASS-INH-011](#class-inh-011) | pending-discussion | `#[\Override] function f(): int { return 1; }` | unverified | unverified | deferred | — |
| [CLASS-INH-012](#class-inh-012) | pending-discussion | `class B extends A {}` | unverified | unverified | deferred | — |
| [CLASS-ABS-001](#class-abs-001) | pending-discussion | `function __destruct() { $this->cleanup(); }` | unverified | unverified | deferred | — |
| [CLASS-ABS-002](#class-abs-002) | pending-discussion | `function __destruct() {}` | unverified | unverified | deferred | — |
| [CLASS-ABS-003](#class-abs-003) | pending-discussion | `function __destruct() { return $this->x; }`<br>`function __destruct() { $this->x; }` | unverified | unverified | deferred | Source variants need reconciliation |
| [CLASS-ABS-004](#class-abs-004) | pending-discussion | `static function __destruct() {}` | unverified | unverified | deferred | Source variants need reconciliation |
| [CLASS-ABS-005](#class-abs-005) | pending-discussion | `abstract class A {}` | unverified | unverified | deferred | — |
| [CLASS-ABS-006](#class-abs-006) | pending-discussion | `abstract function f(int $x): int;` | unverified | unverified | deferred | — |
| [CLASS-ABS-007](#class-abs-007) | pending-discussion | `abstract function f(): int;` | unverified | unverified | deferred | — |
| [CLASS-ABS-008](#class-abs-008) | pending-discussion | `abstract class A { int $x; function f(): int { return $this->x; } }` | unverified | unverified | deferred | — |
| [CLASS-ABS-009](#class-abs-009) | pending-discussion | `interface I { function f(): int; }` | unverified | unverified | deferred | — |
| [CLASS-ABS-010](#class-abs-010) | pending-discussion | `class A extends B implements I {}` | unverified | unverified | deferred | — |
| [TYPE-PARAM-003G](#type-param-003g) | pending-discussion | `interface I { public function f(const string &$s): int; } class C implements I { public function f(string $s): int { return 1; } }` | unverified | unverified | deferred | — |
## CLASS-INH-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:24](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:204](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class B extends A {}
```

**Existing C++ lowering / result**

```cpp
class B : public A { public: using base = A; };
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** parent class is explicitly present in source form

**Normalized pattern:** `class <name> extends <parent>`

**General rule:** Single class inheritance is supported and lowers to public C++ inheritance.

**Diagnostics:** Error if more than one parent class is declared.

**Notes:** Also emits `using base = Parent;`.


## CLASS-INH-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:25](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:205](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A extends B implements I, J {}
```

**Existing C++ lowering / result**

```cpp
class A : public B, public I, public J { public: using base = B; };
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** one parent class; zero or more interfaces

**Normalized pattern:** `class <name> extends <parent> implements <iface-list>`

**General rule:** A class may implement multiple interfaces; interfaces lower as additional public bases.

**Diagnostics:** Error if trait syntax is used.

**Notes:** Interface lowering is declaration-only.


## CLASS-INH-003

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:26](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:206](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A { use T; }
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Class

**Rule kind:** rejection

**Source support status:** rejected

**Normalized pattern:** `trait use`

**General rule:** Traits are not supported.

**Diagnostics:** Emit an error for any trait declaration or trait use.

**Notes:** Explicit project decision.


## CLASS-INH-004

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:27](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:207](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
interface I { function f(int $a): int; }
```

**Existing C++ lowering / result**

```cpp
class I { public: virtual int_t f(int_t a) = 0; virtual ~I() = default; };
```

**Category:** Interface

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** interface methods are declaration-only

**Normalized pattern:** `interface <name> { <methods> }`

**General rule:** A PHP interface lowers to a C++ class containing pure virtual methods.

**Diagnostics:** Error if interface members include bodies or fields.

**Notes:** Multiple interface inheritance remains allowed on implementing classes.


## CLASS-INH-006

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:28](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:208](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = new A(1);
```

**Existing C++ lowering / result**

```cpp
auto a = create<A>(static_cast<int_t>(1));
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** target is a user PHP class

**Normalized pattern:** `new <class>(<args>)`

**General rule:** User-defined PHP class construction lowers to `create<Class>(...)`.

**Diagnostics:** Error if raw `new` or direct stack construction is emitted.

**Notes:** Constructor arguments are forwarded to `create<T>(...)`.


## CLASS-INH-007

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:29](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:209](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
A a(1);
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Class

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** target is a user PHP class

**Normalized pattern:** direct native construction of user class

**General rule:** Direct construction of user-defined PHP classes is forbidden in generated Prism++.

**Diagnostics:** Emit an error if codegen attempts direct construction for user PHP classes.

**Notes:** Whitelisted native/runtime value types remain separate.


## CLASS-INH-008

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:30](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:210](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$this->f();
```

**Existing C++ lowering / result**

```cpp
this->f();
```

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** current context is an instance method, constructor, or destructor

**Normalized pattern:** `$this-><member>`

**General rule:** `$this` is valid only in instance methods, constructors, and destructors.

**Diagnostics:** Error if `$this` appears in static methods or outside instance context.

**Notes:** Applies to properties and methods.


## CLASS-INH-009

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:31](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:211](../../../../generators/php/specs/rules_catalog.md)

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

**Preconditions:** current class has a parent and therefore a `using base = Parent;` alias

**Normalized pattern:** `parent::<method>(<args>)`

**General rule:** Parent method calls lower to `base::method(...)`.

**Diagnostics:** Error if `parent::...` is used in a class without a parent.

**Notes:** The alias is emitted in the class body.


## CLASS-INH-010

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:32](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:212](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
parent::__construct($x);
```

**Existing C++ lowering / result**

```cpp
A::A(int_t x) : base(x) {}
```

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** current class has parent constructor call in its constructor

**Normalized pattern:** `parent::__construct(<args>)`

**General rule:** Parent constructor calls lower to a base initializer call.

**Diagnostics:** Error if base initializer cannot be placed because the construct is outside a constructor.

**Notes:** Uses the approved `base` alias convention.


## CLASS-INH-011

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:33](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:213](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
#[\Override] function f(): int { return 1; }
```

**Existing C++ lowering / result**

```cpp
int_t f() override;
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** method declaration carries `#[\Override]` attribute

**Normalized pattern:** `#[\Override] <method>`

**General rule:** `override` is emitted only from explicit `#[\Override]`; the generator does not infer override relationships.

**Diagnostics:** No semantic override validation is performed by S2S.

**Notes:** Explicit user annotation is trusted as-is.


## CLASS-INH-012

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:34](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:214](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class B extends A {}
```

**Existing C++ lowering / result**

```cpp
class B : public A { public: using base = A; };
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** class has a parent class

**Normalized pattern:** `class <name> extends <parent>`

**General rule:** If a class has a parent, emit `using base = Parent;` in the class body.

**Diagnostics:** Error if `parent::...` lowering is requested without the alias being present.

**Notes:** Provides a deterministic local base name.


## CLASS-ABS-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:35](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:215](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function __destruct() { $this->cleanup(); }
```

**Existing C++ lowering / result**

`~A();` in header and `A::~A() { this->cleanup(); }` in source

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** declaration occurs inside class body

**Normalized pattern:** `__destruct`

**General rule:** `__destruct` lowers to a C++ destructor with out-of-line body emission.

**Diagnostics:** Error if parameters or return type are present on destructor.

**Notes:** `$this` is valid in the destructor body.


## CLASS-ABS-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:36](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:216](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function __destruct() {}
```

**Existing C++ lowering / result**

header declaration + source definition

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** destructor is user-defined

**Normalized pattern:** destructor declaration/body split

**General rule:** Destructor declaration goes to header and destructor body goes to source.

**Diagnostics:** Error if destructor body is emitted inline.

**Notes:** Matches the general class split model.


## CLASS-ABS-003

**v0.2 decision / target C++:** Pending discussion.

Different source versions share this ID. Preserve both until their differences are discussed; neither is silently selected as the v0.2 decision.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:37](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
function __destruct() { return $this->x; }
```

**Existing C++ lowering / result**

`$this` lowers normally; semantic body rules apply separately

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** destructor body is otherwise valid

**Normalized pattern:** `$this` inside destructor

**General rule:** `$this` is allowed inside destructors.

**Diagnostics:** Error only if `$this` appears outside valid instance context.

**Notes:** This extends the `$this` rule explicitly to destructors.

### Imported version 2

**Source:** [generators/php/specs/rules_catalog.md:217](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function __destruct() { $this->x; }
```

**Existing C++ lowering / result**

`$this` lowers normally inside the destructor body

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** destructor body is otherwise valid

**Normalized pattern:** `$this` inside destructor

**General rule:** `$this` is allowed inside destructors.

**Diagnostics:** Error only if `$this` appears outside valid instance context.

**Notes:** This extends the `$this` rule explicitly to destructors.


## CLASS-ABS-004

**v0.2 decision / target C++:** Pending discussion.

Different source versions share this ID. Preserve both until their differences are discussed; neither is silently selected as the v0.2 decision.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:38](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
static function __destruct() {}
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Class

**Rule kind:** rejection

**Source support status:** rejected

**Normalized pattern:** static destructor

**General rule:** Destructors are never static.

**Diagnostics:** Emit an error for any static destructor form.

**Notes:** Directly approved.

### Imported version 2

**Source:** [generators/php/specs/rules_catalog.md:218](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
static function __destruct() {}
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Class

**Rule kind:** rejection

**Source support status:** rejected

**Normalized pattern:** static destructor

**General rule:** Destructors are never static.

**Diagnostics:** Emit an error for any static destructor form.

**Notes:** Explicit project decision.


## CLASS-ABS-005

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:39](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:219](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
abstract class A {}
```

**Existing C++ lowering / result**

`class A { public: };` with abstract semantics contributed by pure virtual members

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** class uses `abstract` keyword

**Normalized pattern:** `abstract class <name>`

**General rule:** Abstract classes are supported.

**Diagnostics:** Error only if later members violate abstract/class member rules.

**Notes:** Abstractness is represented through pure virtual members.


## CLASS-ABS-006

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:40](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:220](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
abstract function f(int $x): int;
```

**Existing C++ lowering / result**

```cpp
virtual int_t f(int_t x) = 0;
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** abstract method declared in abstract-capable context

**Normalized pattern:** `abstract function <name>(...) : <type>;`

**General rule:** Abstract methods lower to pure virtual C++ methods.

**Diagnostics:** Error if an abstract method includes a body.

**Notes:** No source definition is emitted.


## CLASS-ABS-007

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:41](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:221](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
abstract function f(): int;
```

**Existing C++ lowering / result**

declaration only in header; no `.cpp` body

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** abstract method is syntactically valid

**Normalized pattern:** abstract method declaration

**General rule:** Abstract methods have declarations only and no source body.

**Diagnostics:** Error if generator emits an implementation body.

**Notes:** Aligned with pure virtual lowering.


## CLASS-ABS-008

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:42](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:222](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
abstract class A { int $x; function f(): int { return $this->x; } }
```

**Existing C++ lowering / result**

fields + ctor/dtor + concrete methods remain allowed

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** class remains syntactically valid

**Normalized pattern:** abstract class with concrete members

**General rule:** Abstract classes may contain fields, constructors, destructors, and concrete methods.

**Diagnostics:** Error only on unsupported member forms.

**Notes:** Approved explicitly.


## CLASS-ABS-009

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:43](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:223](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
interface I { function f(): int; }
```

**Existing C++ lowering / result**

pure virtual interface class

**Category:** Interface

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** interface syntax is valid

**Normalized pattern:** interface declaration

**General rule:** Interfaces remain distinct from abstract classes.

**Diagnostics:** Error if interface tries to include fields or bodies.

**Notes:** Distinction preserved in the catalog.


## CLASS-ABS-010

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:44](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:224](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A extends B implements I {}
```

**Existing C++ lowering / result**

`virtual ~A();` declaration policy where applicable

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** class has parent or implemented interfaces

**Normalized pattern:** polymorphic class destructor policy

**General rule:** If a class has a parent or interfaces, emit a virtual destructor as generation policy.

**Diagnostics:** No hierarchy inference beyond local declaration form.

**Notes:** This is a generation policy, not semantic checking.


## TYPE-PARAM-003G

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:338](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
interface I { public function f(const string &$s): int; } class C implements I { public function f(string $s): int { return 1; } }
```

**Existing C++ lowering / result**

```cpp
STAN ERROR
```

**Category:** Type system

**Rule kind:** STAN diagnostic

**Source support status:** supported-with-diagnostic

**Preconditions:** interface or abstract method contract contains a const parameter and the implementation differs in constness

**Normalized pattern:** `const parameter contract`

**General rule:** Constness is part of function-like parameter contract metadata for interface and abstract method matching.

**Diagnostics:** STAN must report a build-blocking contract mismatch when an implementation drops or adds source-level const compared with the declared interface or abstract method parameter.

**Notes:** This does not turn the generator into a full overload resolver.

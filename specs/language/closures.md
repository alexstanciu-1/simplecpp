# Closures (Lambda Functions)
Doc Status: normative
## Overview

A closure is an anonymous function value defined inline:

```php
$f = function (int $a): int {
	return $a + 1;
};
```

Closures are **first-class local values** with a **concrete compiler-generated type**.

---

## Semantics

- Each closure expression produces a **unique, anonymous type**
- Closures are **not dynamically typed**
- Closures are **not interchangeable unless explicitly coerced**
- Invocation uses standard call syntax:

```php
$ret = $f(10);
```

---

## Allowed usage

Inferred closure locals retain their concrete anonymous type:

```php
$f = function (int $a): int {
	return $a + 1;
};

$ret = $f(10);
```

---

## Explicit callable boundaries

`function<R(A, B, ...)>` denotes a callable value contract with return type `R`
and ordered parameter types. It does not encode parameter names or a particular
closure expression's identity.

```php
$f function<int(int)> = function (int $value): int { return $value + 1; };
function apply(function<int(int)> $operation, int $value): int {
	return $operation($value);
}
```

An explicit local, property, typed-container element, parameter or return contract permits a compatible concrete closure to
cross that boundary. Inferred closure locals remain concrete; this conversion is
not applied merely because a value is callable. Different closure expressions may
initialize or replace the same explicitly typed callable storage.

The current v0.2 slice requires exact canonical parameter and return types, input
modes, read-only contracts and variadic shape. No variance or numeric signature adaptation is
inferred. Explicit callable locals default to an absent callable. Copying and reassignment
preserve the callable contract. Function and instance/static method parameters use
the same rule, for classes and structs alike.

Closures with defaulted parameters cannot cross this erased boundary: preparation
rejects conversion rather than silently losing defaults. Fixed reference inputs and
typed variadics use the explicit input contracts below.

### Explicit reference and variadic inputs

Callable input types use `T&` for a writable reference, `const T&` for a read-only
reference and trailing `T...` for a typed variadic input. A fixed plain `T` is passed
by value. These distinctions are part of the callable's canonical identity.

```php
$increment function<void(int&)> = function (&$n) { $n++; };
$read function<int(const int&)> = fn(const &$n) => $n;
$sum function<int(int...)> = function (...$values) {
    $total = 0;
    foreach ($values as $n) { $total += $n; }
    return $total;
};
```

An expected contract can supply omitted parameter types, but the closure must
explicitly spell its reference, `const` and variadic markers. Preparation requires
an exact match; it does not reinterpret a by-value closure as a reference closure.
References use ordinary writable/read-only storage, type and lifetime checks;
they do not acquire temporary binding or ownership extension through erasure.

There is at most one variadic input, it is last, and it must be by value without a
default. `const` requires a fixed reference, following ordinary parameter rules.
Invocation packs trailing arguments into `vector<T>`, including an empty pack,
using ordinary source-order evaluation and element conversion. A contract accepting
one `vector<T>` is distinct from `T...`, despite their common C++ parameter storage.
C++ callable representation uses the same parameter spelling rules as declarations.

These contracts work at existing local, argument, return, property and typed-container
boundaries. Reference **inputs** do not grant reference **returns**, permit captured
borrows to escape, or enable reference/variadic parameters whose payload itself is
an erased callable; the latter retain their existing ordinary-parameter gate.

Borrowing closures also cannot cross this boundary, including when a borrowing
closure is captured by value inside another closure. Erasure must not hide lifetime
obligations.

Wrapper storage remains deferred in this slice. The C++ backend
represents explicit callable contracts with `std::function`; this does not change
the concrete-lambda lowering of inferred locals.

### Explicit reference-return contracts

`function<R&(Args...)>` preserves a writable reference result. Exactly one fixed
reference input supplies its whole storage, and its type must exactly match `R`.
Other by-value inputs, including a trailing typed variadic, are permitted.

```php
$select function<int&(int&)> = fn&(&$value) => $value;
$value = 3;
$alias =& $select($value);
$copy = $select($value);
```

The return marker and supplying parameter position are part of canonical callable
identity. Conversion requires an exact origin contract, not just matching payload
types. Context supplies omitted types but never invents `fn&` / `function &`.
Invocation substitutes the supplying argument's place and lifetime, using ordinary
reference argument, alias and return checks. Destroying the callable does not destroy
that caller-owned storage. Normal assignment copies under ordinary copyability rules;
`=&` aliases, including for noncopyable payloads. Captured/local/interior return origins,
const-reference results, borrowed environments and defaulted inputs remain excluded.

Reference-cycle checking conservatively treats every prepared concrete closure with
an exact compatible signature as a possible target of an erased invocation. This
includes by-value erased calls that could connect a reference-return cycle. It does
not track assignments or prove that a compatible closure is unreachable; safe programs
can therefore reject. Components containing only value returns remain permitted.
This bounded policy preserves the existing recursive-reference-return restriction;
more precise target-flow analysis is deferred, not delegated to C++ or runtime.

### Typed property and container storage

Instance/static properties in classes and structs may explicitly declare
`function<R(Args...)>`. They default to absent, and assignments and constructor/data
inputs use the same exact conversion and contextual signature rules as callable
locals. Authored closure-valued declaration defaults remain deferred; this includes
closures nested inside a property's container default.

`vector<function<...>>` and `hash<function<...>>` carry owned callable values.
Construction, writes and appends obtain their expected signature from the element
contract. Reads, iteration and copying retain ordinary container value semantics
and callable-environment copy semantics; shared captured objects remain shared.
Nested typed containers use these same rules. No concrete anonymous type is inferred
as a heterogeneous container's common element type.

```php
class Handler {
    public function<int(int)> $run;
}
$handler = new Handler();
$handler->run = fn($n) => $n + 1;
$result = ($handler->run)(4);
$callbacks vector<function<int(int)>> = [fn($n) => $n * 2];
$other = $callbacks[0](4);
```

`($object->name)(...)` invokes the property value; `$object->name(...)` selects a
method. Those names may coexist. Static property values likewise support
`(Type::$name)(...)`. Expression calls snapshot the selected callable before
evaluating arguments; argument-side replacement does not change that invocation.
Absent invocation retains the runtime exception contract below.

Keyed `isset` requires both an existing element and a present callable. Missing
paths remain lazy and non-creating. `empty` on the vector/hash itself tests its
size; `empty` on a callable element rejects. General direct-property probes and
property/indexed cleanup are not enabled by callable storage admission. A property
can be observed through conditions or copied to a local for `isset`.

Direct/transitive borrowed environments and borrowed `$this` remain rejected at
every erased boundary, including properties and collection entries. Callable
wrappers and mixed/dynamic callable payloads remain deferred.

### Callable presence and reset

An explicit declaration such as `$f function<int(int)>;` creates valid absent
storage, not an uninitialized value. `isset($f)` and conditions test whether it
contains a callable; unary `!` negates that presence. Concrete inferred closures
are always present. These observations evaluate the operand once without copying
its environment and do not test the callable's return value.

Assignment can establish or replace the callable under the same signature and
borrow checks as initialization. Copying absent storage preserves absence; copying
present storage copies its callable environment under ordinary capture semantics.
Resetting either copy does not reset the other. `clean($f)` and `unset($f)` reset
explicit callable storage to absent without removing its binding. Existing mutable
storage and reference-detachment restrictions still apply. Concrete inferred
closure storage cannot be reset this way.

Invoking an absent callable throws a runtime exception. The callee is evaluated
once, then the arguments in their established order, before invocation detects
absence; completed side effects are not rolled back. The C++ backend uses
`std::function`'s `std::bad_function_call`; structured error integration remains
outside this slice. No definite-initialization analysis is required.

Callable absence does not enable `nullable<function<...>>`, null assignment or
null comparisons. `empty` and `count` do not apply to callable values.

### Contextual signature completion

An explicit expected `function<R(Args...)>` contract may supply omitted closure or
arrow parameter/return types at a typed local initializer, assignment to established
callable storage, property/typed-container input, selected call argument, or explicit callable return boundary.

```php
$add function<int(int)> = function ($value) { return $value + 1; };
function apply(function<int(int)> $operation): int { return $operation(4); }
$result = apply(fn($value) => $value + 1);
```

Only omitted types are completed. Authored annotations remain authoritative and must
match the expected contract under exact callable compatibility. Parameter count must
match; parameter names remain authored and do not become part of the erased type.
Partial annotations are allowed. A nested factory's expected result contract can
supply context to its returned closure through the ordinary return path.

Without an expected callable contract, all parameter/return types must be authored.
No types are inferred from the body, argument values, later calls or overload-candidate
guessing. Named declarations retain their existing explicit-signature requirements.
Defaults and borrowing environments remain rejected across
erased boundaries. Omitted types remain absent in syntax; resolved types belong to
prepared signature facts. A source occurrence retains one anonymous identity when
its surrounding contract changes during incremental preparation.

### Callable returns and factories

Functions, instance/static methods and anonymous callables may declare an explicit
`function<R(Args...)>` return contract. Returning a compatible concrete closure uses
the same conversion as an explicit callable local/parameter; returning an already
erased callable preserves its exact contract. Nested callable result contracts are
allowed, including an erased callable that itself returns an erased callable.

```php
function make_adder(int $base): function<int(int)> {
    return fn(int $value): int => $base + $value;
}
$add = make_adder(3);
$result = $add(4); // 7, after make_adder has returned
```

Capture-free and copyable by-value environments may cross the return boundary.
Ordinary value/container-copy and shared-handle semantics apply; this is not deep
copying. Direct or transitive reference captures and borrowed `$this` reject during
preparation, even if a particular caller might keep their origins alive. Capturing
a value from a reference parameter by value does not itself retain a borrow.
Noncopyable captures and incompatible signatures remain rejected. No inferred
anonymous return type, owning receiver capture,
or extension of erased defaults is enabled here. Reference-return contracts follow
the explicit origin rules above.

## Forbidden usage (concrete closures)

Closures **cannot be stored in dynamic or untyped containers**:

```php
$data = [];
$data['fn'] = function () { ... }; // âŒ compile-time error
```

Closures **cannot be assigned to mixed_t**:

```php
$m = function () { ... }; // âŒ if $m is mixed_t
```

Closures **cannot be implicitly unified across different types**:

```php
$f = $cond
	? function (int $x): int { return $x + 1; }
	: function (int $x): int { return $x + 2; }; // âŒ
```

---

## Shared parameter contracts

Explicit closures and arrows use the same parameter preparation and invocation rules
as functions and methods: supported scalar/enum defaults, trailing typed variadics,
and writable/read-only reference parameters. Existing restrictions on default values,
parameter ordering, exact reference storage and mutable borrowing apply unchanged.

```php
$f = function (int &$total, int ...$values): void {
	foreach ($values as $value) { $total += $value; }
};
$g = fn(int $value = 4): int => $value + 1;
```

Omitted arguments are supplied by the shared call path. Variadic arguments become
the ordinary `vector<T>` parameter storage. References alias the admitted caller
storage; readonly references cannot be used to mutate it. Copying a concrete closure
preserves its full parameter contract. Capturing a reference parameter by value still
copies its current value; it does not introduce reference capture.

## Bounded reference returns

Concrete closures and arrows inherit the [reference-return contract](../references.md)
of named functions. An explicit `&` return marker selects exactly one fixed writable
reference parameter whose storage type exactly matches the return type:

```php
$select = function &(int &$value): int {
	$alias =& $value;
	return $alias;
};
$select_arrow = fn&(int &$value): int => $value;
$value = 3;
$borrowed =& $select($value);
$copy = $select_arrow($value);
```

Every return must preserve that whole parameter's origin, directly, through local
aliases or supported nonrecursive forwarding. Calls substitute the caller's storage
origin; destroying a temporary closure does not invalidate that caller-owned storage.
Ordinary assignment copies the returned value under its normal copyability contract;
it neither leaks nor implicitly moves. `=&` binds to the returned storage instead.

Closure locals, captured fields (including aliases of captures), captured-object
properties, interior storage and temporaries cannot escape by reference. Value
captures may still be used while returning the permitted parameter. Copying a
concrete closure preserves its reference-return contract. Ordinary default/variadic
by-value parameters may accompany the single reference parameter.

The existing recursive-reference-return restriction includes concrete anonymous
callables and cycles through by-value helpers. Merely creating an uninvoked closure
does not call its body. Erased invocations additionally use the conservative compatible
target rule above. Const-reference returns remain deferred; `function<R(Args...)>`
must not silently erase an origin contract: use `function<R&(Args...)>` explicitly.

## Capture semantics

Closures may capture variables from the surrounding scope:

```php
$x = 10;

$f = function (int $a) use ($x): int {
	return $a + $x;
};
```

Captured values are stored as fields in the generated closure object.

### v0.2 explicit value captures

`use ($x, $y)` copies the current values when the closure expression executes.
It uses each type's ordinary copy contract: value/COW containers retain value
semantics, while shared objects and dynamic containers retain shared identity.
Reassigning the outer variable does not replace the captured value. This is not
a deep snapshot. Unique/noncopyable values are rejected; capture never implies move.

Captured bindings are read-only in this slice. Reassignment, increments, mutable
borrowing and mutation of captured inline/value-container storage are rejected
during preparation. Shared pointees and dynamic container contents remain mutable;
read-only binding does not imply deep immutability. Copying a closure copies its
captured fields using the same ordinary type rules.

Capture names must resolve to established visible locals or parameters at creation.
Duplicate captures and capture/parameter name collisions are errors. A nested closure
can capture only from its immediate enclosing environment (including that closure's
parameters, locals and explicit captures); it cannot bypass a missing outer capture.
Existing local initialization, lexical scope and shadowing rules apply unchanged.

The syntax order is `function (parameters) use (captures): ReturnType { body }`.
Mutable by-value capture bindings remain deferred. Receiver capture follows the
separate borrowed-instance rule below.
Parameter/return rules are shared with other source callables;
capture admission does not enable deferred callable signatures.

### v0.2 bounded reference captures

`use (&$x)` borrows existing whole local or parameter storage. Value and reference
captures may coexist. An eligible alias borrows its original whole-storage target,
not the alias name's lifetime:

```php
$count = 1;
$increment = function () use (&$count): void { $count++; };
$copy = $increment;
$count = 4;
$copy(); // count is 5
```

There is no heap promotion or lifetime extension. Reassigning the original variable
is visible through the capture. Borrowing a unique/noncopyable value does not copy it;
copying the closure copies the borrow, not its target. Existing explicit moves and
reset semantics operate on the original storage. Readonly sources remain readonly.
`clean` may reset supported values through the borrow; whole-variable `unset` on a
reference capture rejects, as on an ordinary native alias, rather than detaching it.

Every local destination must fit within all borrowed origins' lifetimes, respecting
block boundaries and establishment order. These obligations propagate through closures
captured by value. A closure invocation may use its captured borrows in invocation-local
storage; that guarantee does not authorize copying them into external longer-lived
storage. Conversion to `function<R(Args...)>` rejects direct or transitive borrowing
environments, even for synchronous-looking calls: that contract cannot yet guarantee
nonescape.

Field/interior references and borrowing another closure's captured fields are deferred,
including attempts through aliases. Captured storage cannot supply a reference return
under the current whole-reference-parameter contract. Returning borrowing closures
is rejected; borrowing closures cannot enter erased property/container storage. This is a bounded lexical lifetime check, not
general escape analysis, exclusive borrowing or PHP-style lifetime extension.

### v0.2 borrowed receiver captures

An explicit closure in an instance body may name `use ($this)`. Arrows capture the
receiver automatically when required by their expression. Unlike ordinary value
capture, this borrows the current instance: it neither copies the object nor retains
an owning shared handle. Classes and structs use the same rule.

```php
$read = function () use ($this): int { return $this->count; };
$increment = fn(): int => ++$this->count;
```

Receiver-capturing closures and their transitive copies are bounded by the creating
instance invocation's lifetime. Existing local-copy checks and erased-boundary
rejection apply. Member mutations affect the original object. The receiver's original
declaring context supplies private/protected member access; nested captures preserve
that context. An explicit nested closure must name `$this` itself; it cannot bypass
an enclosing explicit closure that did not capture it.

`use (&$this)` rejects: the receiver is not a rebindable variable. Static methods and
non-instance contexts provide no receiver. Ordinary copying, passing, returning or
rebinding of `$this` remains disabled; captured properties cannot escape by reference.
Owning/escaping receiver captures and PHP-style receiver lifetime extension are not
part of this contract. This does not enable instance late-static binding.

## Arrow functions

`fn(parameters): ReturnType => expression` is a closure with an expression body
and automatic by-value capture:

```php
$factor = 3;
$f = fn(int $x): int => $x * $factor;
```

The expression is its return expression; ordinary return checking and conversion
apply. Parameter and return types must be explicit or supplied by an expected callable
contract as described above.
The shared void-return rule below permits `fn(): void => void_call()`; contextual
`function<void(...)>` contracts can supply the omitted return type as usual.
An arrow has the same concrete closure identity and explicit callable-boundary
conversion rules as an authored `function` closure. It has no explicit `use` list.

Referenced outer variables are captured once per name at creation, using the same
copyability, read-only-binding and shared-payload rules as explicit value captures.
Parameters are local and do not become captures. Nested arrows propagate their
outer-variable requirements through enclosing arrows, stopping at a parameter that
provides the name. Explicit closures still require their own `use` list: neither
their body locals nor undeclared body references become enclosing-arrow captures.
An explicit nested closure's authored capture list does contribute creation-site
requirements to its enclosing arrow. Unavailable names and noncopyable captures
are preparation errors. Arrows remain by-value capture syntax; `fn&` marks a reference
return, not reference capture. Capturing a borrowing closure preserves its existing
lifetime obligations. `$this` uses the borrowed receiver contract above, not an
ordinary by-value object copy.

---

## Shared void-return forwarding

Functions, instance/static methods, explicit closures and arrows follow one rule:
a callable declared `void` may use `return expression;` exactly when the expression
also has type `void`. Evaluate the expression once, then return immediately. Any
exception propagates normally; completed effects are not undone. A non-void value
is rejected, not implicitly discarded. This is forwarding completion, not storing
or converting a void value. Ordinary `return;` remains valid in a void callable.

```php
function notify(): void { /* effects */ }
function forward(): void { return notify(); }
$callback = fn(): void => notify();
```

Non-void callables cannot return void expressions or omit their required result.
Program-entry returns retain their exit-value contract and cannot forward void.
This rule adds no void variables, parameters, container elements or general-purpose
void conversion. C++ lowering may directly emit `return void_expression;`.

## Lowering (C++)

Closures lower to native C++ lambdas:

```php
$f = function (int $a): int {
	return $a + 1;
};
```

â†“

```cpp
auto f = [](int a) -> int {
	return a + 1;
};
```

Captured variables become lambda captures.

The backend may emit C++ `mutable` to use non-const shared-payload APIs and invoke
captured callable values. This is a lowering detail, not permission to reassign
source captured bindings; shared preparation enforces their read-only contract.

No type erasure is introduced unless required by a callable boundary.

---

## Design constraints

- Closures are **compile-time concrete objects**, not runtime dynamic values
- Concrete local closures require no type-erasure allocation; explicit callable boundaries may allocate.
- Inference does not introduce a generic callable type. Conversion requires an explicit callable contract.

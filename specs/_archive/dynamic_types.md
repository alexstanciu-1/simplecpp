## Prism++ — Dynamic Type (`mixed_t`)

### Intro (User View)

### Dynamic Type (`mixed`)

Prism++ allows you to opt into a **dynamic type** when flexibility is needed.

By default, values are **native and statically known**:

$v = 5;               // native / compiler-known

To make a value dynamic, you must state it explicitly:

$v /** mixed */ = 5;  // dynamic

Dynamic values are useful for:
- prototyping
- heterogeneous data
- flexible APIs

The goal is simple:
- write code naturally
- use dynamic behavior only when needed
- keep conversions predictable

---

# 1. Coding Language (User View)

### 1.1 Basic Idea

Dynamic values are introduced explicitly:

$v /** mixed */ = 5;
$y /** int */ = $v;

Generated:

mixed_t v = 5;
int_t y = cast_int(v);

✔ Works because:
- dynamic intent is explicit
- target type is clear

---

### 1.2 Rule: Intent Drives Conversion

If the intended type is clearly stated, the system applies the required explicit conversion.

$v /** mixed */ = 5;

$y /** int */ = $v;
function f(/** int */ $x) {}
f($v);

---

### 1.3 Explicit Cast in User Code

$v /** mixed */ = 5;
$y /** int */ = $v;

If the target type is not stated, no conversion is assumed.

---

### 1.4 Operations with `mixed`

$v /** mixed */ = 5;

$a = $v + 1;
$b = 1 + $v;

These expressions are valid in user code.

---

### 1.5 String Concat (Special Case)

$v /** mixed */ = 5;

$s1 = "value=" . $v;
$s2 = $str . $v;

- the `.` operator always produces a string
- both operands are converted using string-context conversion
- this is NOT a general implicit cast

---

### 1.6 Typed Calls

$v /** mixed */ = 5;

function f(/** int */ $x) {}
f($v);

This is allowed because the parameter type makes the intended conversion explicit.

---

### 1.7 Mental Model

- native by default
- dynamic introduced explicitly or via structural constructs
- conversions follow explicit intent

---

### 1.8 Constructs That Become Dynamic

A value becomes dynamic in two ways:

- explicitly, when the code states dynamic intent
- structurally, when the construct belongs to the dynamic/runtime-carrier path

---

#### 1.8.1 Explicit Dynamic Introduction

$v /** mixed */ = 5;

---

#### 1.8.2 Untyped PHP Array Construction

$a = [];
$b = [1, 2, 3];
$c = ["id" => 1, "name" => "test"];

All untyped PHP arrays are dynamic.

---

#### 1.8.3 PHP `array` Surface Type

function f(array $x) {}

PHP `array` is part of the dynamic boxed/runtime model.

---

#### 1.8.4 Dynamic / Bootstrap `null`

$x = null;
$x["k"] = 1;

Untyped `null` is supported, but only as a dynamic/bootstrap holder.

---

#### 1.8.5 Reads from Dynamic Containers

$x = $a["id"];

Reads from dynamic containers produce dynamic values.

---

#### 1.8.6 Dynamic Expression Propagation

$v /** mixed */ = 5;

$a = $v + 1;
$c = $v == 1;
$s = "value=" . $v;

If `mixed` participates:
- evaluation becomes dynamic
- result stays dynamic unless context defines otherwise

---

### 1.9 What Does Not Become Dynamic Automatically

Native values remain native:

$x = 5;
$y = 1.5;
$s = "abc";

Typed containers remain native.

---

### 1.10 Summary

Dynamic is introduced by:
1. explicit mixed
2. untyped PHP arrays
3. PHP array type
4. bootstrap null
5. dynamic reads
6. dynamic expressions

---

# 2. Runtime Model (Internal)

### 2.1 Core Rules

- no implicit mixed → native conversion
- no runtime overload guessing
- no silent data loss

---

### 2.2 Execution Rules

If mixed participates:
- execution is dynamic
- result stays dynamic unless operator defines native result

Examples:
- concat → string
- comparison → bool

---

### 2.3 Summary

- mixed is controlled flexibility
- native remains default
- boundaries are explicit

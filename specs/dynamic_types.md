See `specs/spec_map.md` for document hierarchy, authority, and v1 conflict-resolution rules.

# Dynamic Types Specification (v2.1)

Status: Active  
Replaces: dynamic_types.md (v1)

---

## Prism++ — Dynamic Type (`mixed_t`)

### Intro (User View)

### Dynamic Type (`mixed`)

Prism++ allows you to opt into a **dynamic type** when flexibility is needed.

By default, values are **native and statically known**:

```php
$v = 5;               // native / compiler-known
```

To make a value dynamic, you must state it explicitly:

```php
$v /** mixed */ = 5;  // dynamic
```

Dynamic values are useful for:
- prototyping
- heterogeneous data
- flexible APIs

---

# 1. Coding Language (User View)

## 1.1 Core Rule

> If the intended type is clearly stated, the system may accept a non-explicit conversion.

This is a **language / S2S rule**, not a runtime rule.

---

## 1.2 Visible Intention

### Definition

> A conversion has **Visible Intention** when the destination type is explicitly identifiable at the conversion site in source code.

If Visible Intention exists, a **non-explicit conversion** from `mixed` to the target type is allowed and may be performed by the current implementation.

### Valid Visible Intention sites

| Case | Example | Allowed |
|---|---|---|
| Typed variable assignment | `$x /** int */ = $v;` | ✔ |
| Typed property assignment | `$obj->x = $v; // x is int` | ✔ |
| Typed function argument (by-value) | `f($v); // f(int $x)` | ✔ |
| Typed method argument (by-value) | `$obj->f($v); // f(int $x)` | ✔ |
| Typed return | `return $v; // function(): int` | ✔ |

### Not Visible Intention

| Case | Example | Allowed |
|---|---|---|
| By-reference arguments | `f($v); // f(int& $x)` | ✖ |
| Untyped assignment | `$x = $v;` | ✖ |
| Expressions without typed destination | `$x = $v + 1;` | ✖ |
| Operator candidate expansion via implicit mixed extraction | `f($v + 1);` or overload-created operator paths | ✖ |
| Overload resolution | `f($v); // multiple overloads` | ✖ |
| Intermediate expressions | `$z = foo($v) + 1;` | ✖ |

---

## 1.3 Technical Compromises to Achieve Visible Intention in v1

### Normative priority for v1

For **current v1 user-visible behavior**, **Section 1.2 Visible Intention** and **Section 1.3 Technical Compromises to Achieve Visible Intention in v1** are **normative priority rules**.

If they conflict with a stricter long-term runtime preference such as "all dynamic-to-native bridges should already be explicit in generated C++", then **Section 1.2** and **Section 1.3** take precedence until the generator can actually materialize those explicit bridges (or another approved mechanism replaces them).

This means:
- implementations must not remove currently-required v1 bridges merely because they are not the preferred long-term runtime shape
- runtime/spec/generator cleanup must preserve the valid Visible Intention sites listed in **Section 1.2**
- any future removal of a v1 compromise requires generator parity (or an explicitly documented replacement path)

### Context

The long-term model is:

- the runtime remains strict
- the S2S generator emits explicit `cast_*()` calls when Visible Intention exists

Current implementation limits prevent full realization of that model.

### v1 constraints

| Constraint | Impact |
|---|---|
| S2S does not fully resolve function / method symbols | cannot reliably inject typed call-site casts |
| S2S cannot always distinguish call-context conversions from other contexts | cannot always materialize explicit casts exactly where desired |
| the runtime cannot infer source-level intention | runtime cannot tell whether a non-explicit conversion originated from a call, assignment, property write, or return |

### v1 compromise conversions

These are accepted in v1 when Visible Intention exists, even though the long-term model prefers S2S-emitted explicit casts:

| Case | Example | Why accepted in v1 |
|---|---|---|
| Typed by-value function call | `add($hash["value"], $hash["add"]);` where `add(int $value, int $add)` | common usage; S2S cannot reliably resolve the callee yet |
| Typed by-value method call | `$obj->setValue($v);` where `setValue(int $v)` | same reason |
| Typed variable assignment | `$x /** int */ = $v;` | intention is directly visible |
| Typed property assignment | `$obj->x = $v;` where `x` is `int` | intention is directly visible |
| Typed return | `return $v;` in `function f(): int` | return type is directly visible |

### Not accepted, even in v1

| Case | Example | Status |
|---|---|---|
| By-reference parameter | `f($v);` where `f(int& $x)` | not allowed |
| Native to `mixed&` | `f($x);` where `f(mixed& $x)` and `$x` is native | not supported |
| Overload disambiguation | `f($v);` with multiple typed candidates | not allowed |
| Untyped destination | `$x = $v;` | no conversion assumed |
| Intermediate expression only | `$z = $v + 1;` | no typed target visible |

### Important clarification

> The explicit-boundary model is a **runtime rule**.  
> Visible Intention and its v1 compromises belong to the **language / S2S layer**.

The current implementation may therefore accept some non-explicit `mixed → native` conversions that the long-term runtime model would prefer to see as generator-emitted explicit casts.

---


## 1.3A Generator-owned string concatenation

PHP string concatenation (`.` and `.=`) is resolved at generator level.

The generator must lower:
- `$a . $b`
- `$a .= $b`

into explicit string-context conversion plus primitive `string_t` concatenation.

Normative rules:
- the runtime does **not** define `mixed_t` concat dispatch
- the runtime supplies primitive `string_t` concatenation and text-conversion helpers only
- concat semantics must not be modeled as `mixed_t + mixed_t`
- any generated concat must reach the runtime already lowered into explicit text conversion plus `string_t` concat

## 1.4 Destination-Cast Matrix (Language Intention)

| Source | Destination | Allowed | Meaning |
|---|---|---|---|
| native | mixed | ✔ | boxing |
| mixed | typed variable | ✔ | convert |
| mixed | typed parameter (by-value) | ✔ | convert for call |
| mixed | typed return | ✔ | convert on return |
| mixed | typed property | ✔ | convert |
| mixed | untyped | ✔ | stays mixed |
| mixed | overload selection | ✖ | no implicit disambiguation |
| mixed | typed by-reference parameter | limited ✔ | native-equivalent typed refs use template-normalized `(T|mixed)&` with runtime validation; no implicit by-ref auto-conversion outside that rule |

---


### Native-equivalent by-reference normalization rule

Normative exception to the general no-by-ref-auto-conversion policy:
- all native-equivalent typed by-reference parameters are normalized through template dispatch
- in the current supported set, this applies to `int&`, `float&`, `bool&`, and `string&`
- the accepted semantic domain is `(T|mixed)&`
- native `T&` binds directly
- `mixed_t&` must be runtime-validated and, on success, normalized through the exact `as_*_ref()` accessor
- non-matching runtime kinds fail at runtime
- sibling `mixed_t&` bridge overloads and implicit typed reference casts are not part of the contract

## 1.5 Operator Matrix (Language Intention)

`mixed_t` does not define an independent flat operator matrix. Instead, operator behavior is resolved by **runtime kind dispatch** and then delegated to the already-defined native wrapper rule.

Examples:
- `mixed(kind=int) + mixed(kind=int)` → same rule as `int_t + int_t`
- `mixed(kind=int) + mixed(kind=float)` → same rule as `int_t + float_t`
- `mixed(kind=float)++` → same rule as `float_t++`
- `mixed(kind=string) += string_t` → same rule as `string_t += string_t`
- `mixed(kind=table) + ...` → error for now

Global rules:
- implicit `mixed -> native` extraction is allowed only at approved Visible Intention typed boundaries (assignment/init, by-value arg passing, return)
- operator resolution must **not** use implicit `mixed` extraction to manufacture extra overload candidates
- compound assignment is valid only when the delegated native binary op exists **and** assignment back into the stored lhs kind also remains valid
- table carriers are excluded from arithmetic dispatch
- direct table comparison supports only `==` and `!=`, with identity-only semantics

| Expression | Allowed | Meaning | Result |
|---|---|---|---|
| mixed + mixed | ✔ | dispatch by kinds, then delegate to native rule | mixed |
| mixed + native | ✔ | box native if needed, dispatch by kinds | mixed |
| native + mixed | ✔ | box native if needed, dispatch by kinds | mixed |
| mixed - / * / / / % | ✔ | same delegation model | mixed |
| mixed == / != / < / <= / > / >= | ✔ | dispatch by kinds, then delegate to native comparison rule | bool |
| mixed && / || / !mixed | ✔ | dispatch by kinds, then delegate to native logical rule | bool |
| +mixed / -mixed | ✔ | unary numeric dispatch by kind | mixed |
| ++mixed / --mixed | ✔ | delegate to native increment/decrement rule of contained kind | mixed |
| mixed[index] | ✔ | indexing | mixed |
| typed = mixed | ✔ | Visible Intention boundary conversion | typed |
| mixed += native | ✔ | delegated op + assign-back check | mixed |
| mixed .= native | ✔ | generator-owned concat lowering, not runtime mixed concat dispatch | string/mixed |

Additional runtime-kind rules:
- `mixed(kind=null)` delegates to `null_t`
- `mixed(kind=bool)` delegates to `bool_t`
- `mixed(kind=int)` delegates to `int_t`
- `mixed(kind=float)` delegates to `float_t`
- `mixed(kind=string)` delegates to `string_t`
- `mixed(kind=table/shared_table/weak_table)` never participates in arithmetic dispatch
- shared-table equality/inequality is pointer identity
- weak-table equality/inequality compares locked target identity and returns `false` if either side is expired
- owned direct `table_v` equality/inequality is an error for now


## 1.6 Array / Indexing Matrix (Language Intention)

| Expression | Allowed | Meaning | Result |
|---|---|---|---|
| `[]` | ✔ | dynamic array / hash-table creation | mixed structure |
| `[1,2]` | ✔ | dynamic array | mixed structure |
| `["x"=>1]` | ✔ | dynamic hash-table | mixed structure |
| nested arrays | ✔ | dynamic nested structure | mixed structure |
| `$a[] = native` | ✔ | append boxed value | mixed |
| `$a[$k] = native` | ✔ | assign boxed value | mixed |
| `$a[] = mixed` | ✔ | append dynamic value | mixed |
| `$a[$k] = mixed` | ✔ | assign dynamic value | mixed |
| `$a[$k]` | ✔ | indexed read | mixed |
| `$a[$k1][$k2]` | ✔ | nested read | mixed |
| typed from `$a[$k]` | ✔ | convert | typed |

---

## 1.7 Reference Semantics (Language Intention)

| Case | Rule |
|---|---|
| `mixed&` parameter | requires actual mixed variable |
| native → `mixed&` | not supported |
| typed by-ref param (`string&`) | requires compatible typed storage; no proxy adaptation |
| cast insertion for by-ref | not allowed |
| array element reference | dynamic via mixed container |
| foreach by-ref | generator-dependent, may fail |

---

# 2. Runtime Model (Internal)

## 2.1 Runtime scope note

The runtime model is intentionally stricter than the current v1 language surface:

- runtime does not infer Visible Intention
- runtime should prefer explicit typed bridges such as `cast<T>(mixed_t)`
- current non-explicit acceptance in some call/assignment/property/return cases is a language/S2S compromise, not a runtime feature goal

For the rationale and allowed compromise cases, see:
- **Section 1.2 Visible Intention**
- **Section 1.3 Technical Compromises to Achieve Visible Intention in v1**

For v1, those two sections are not advisory commentary; they are the governing compatibility rules for current user-visible behavior. Runtime cleanup must therefore defer to them until generator parity exists.

## 2.1A `mixed_t` surface contract (v1)

The runtime `mixed_t` surface is intentionally split into three layers:

### Exact accessors

These do **not** coerce and do **not** autovivify:

- `get_bool()` / `try_get_bool()`
- `get_int()` / `try_get_int()`
- `get_float()` / `try_get_float()`
- `get_string()` / `try_get_string()`
- `get_hash()` / `try_get_hash()`

Rules:

- `get_*()` requires the exact stored runtime type and fails otherwise
- `try_get_*()` is a non-throwing probe helper
- exact hash access returns the underlying stored `hash_t`, not a copy
- exact accessors never create containers on read

### Explicit typed extraction

Typed extraction from `mixed_t` must go through explicit helpers such as:

- `cast<T>(mixed_t)`

`mixed_t` does not create new conversion rights. It may only expose conversions that already exist in the Prism++ native cast rules.

Current v1 typed-boundary bridge rule:
- non-explicit `mixed -> native` use is accepted only at Visible Intention sites for initialization/assignment, by-value arg passing, and typed returns
- operator resolution must not use implicit extraction to create extra candidates
- failed typed extraction remains a runtime error

### Indexing / write context

`mixed_t` indexing remains context-sensitive:

- read path: no autovivification
- write path: v1 may autovivify `null` into `hash_t` to preserve visible intention for generated code

### Failure model

For v1, failed exact access or failed typed extraction uses one generic runtime failure path with an explicit message that names:

- the stored runtime type
- the requested target type
- that the conversion / extraction is not allowed under Prism++ rules

### Public type inspection

`mixed_t` should expose a public type tag (`type()`) and convenience predicates (`is_*()`).

---

## 2.2 Destination-Cast Matrix (Runtime)

| Source | Destination | Generator | Runtime |
|---|---|---|---|
| native | mixed | box | store dynamic |
| mixed | typed variable | explicit `cast<T>(...)` generation preferred; v1 may still contain compromise sites | checked conversion via explicit bridge |
| mixed | typed parameter (by-value) | explicit `cast<T>(...)` generation preferred; v1 may still contain compromise sites | checked conversion via explicit bridge |
| mixed | typed return | explicit `cast<T>(...)` generation preferred; v1 may still contain compromise sites | checked conversion via explicit bridge |
| mixed | typed property | explicit `cast<T>(...)` generation preferred; v1 may still contain compromise sites | checked conversion via explicit bridge |
| mixed | untyped | none | stays dynamic |
| mixed | overload selection | none | not a cast site |
| mixed | typed by-reference parameter | normalized template dispatch for native-equivalent refs only | runtime-validated reference normalization via exact `as_*_ref()` accessors; otherwise not allowed |

---

## 2.3 Operator Matrix (Runtime)

Runtime operator behavior for `mixed_t` is **dispatch-table based**:
- establish the runtime kind(s) of the operand(s)
- look up the matching native rule tuple
- execute that native rule
- box the result back into `mixed_t` where required

The runtime does **not** define PHP concat semantics for `mixed_t`. PHP `.` and `.=` are already lowered by the generator into explicit text conversion plus primitive `string_t` concat.

| Expression | Generator | Runtime | Result |
|---|---|---|---|
| mixed + mixed | dynamic op helper | dispatch by kinds, delegate to native numeric rule | mixed |
| mixed + native | box native if needed | dispatch by kinds, delegate to native numeric rule | mixed |
| mixed == / != / < / <= / > / >= | compare helper | dispatch by kinds, delegate to native comparison rule | bool |
| mixed && / || / !mixed | truthiness helper | dispatch by kinds, delegate to native logical rule | bool |
| +mixed / -mixed | unary numeric helper | dispatch by kind, delegate to native unary rule | mixed |
| ++mixed / --mixed | mutation helper | dispatch by kind, delegate to native increment/decrement rule | mixed |
| mixed[index] | dynamic index helper | runtime lookup/access | mixed |
| typed = mixed | cast_*() (long-term); v1 may use non-explicit conversion at Visible Intention boundaries | checked conversion / current implementation bridge | typed |
| mixed += native | dynamic op + assign | delegated op followed by assign-back validity check | mixed |
| mixed .= native | explicit `to_string(...)` lowering + primitive `string_t` concat | no `mixed_t` concat dispatch | string/mixed |

Table-carrier exceptions:
- arithmetic on table carriers is an error
- ordering comparisons on table carriers are an error
- direct owned `table_v` `==` / `!=` is an error for now
- shared-table `==` / `!=` is identity-only by shared target pointer
- weak-table `==` / `!=` compares locked target identity and returns `false` if either side is expired
- expired weak-table `_find_val` returns `null`, `find` returns not-found, `at`/writes are runtime errors


## 2.4 Array / Indexing Matrix (Runtime)

| Expression | Generator | Runtime result |
|---|---|---|
| `[]`, `[ ... ]` | create dynamic structure | dynamic array / hash-table |
| `$a[] = native` | box if needed | store as mixed |
| `$a[$k] = native` | box if needed | store as mixed |
| `$a[] = mixed` | store directly | store as mixed |
| `$a[$k] = mixed` | store directly | store as mixed |
| `$a[$k]` | emit dynamic index access | mixed |
| `$a[$k1][$k2]` | emit nested dynamic index access | mixed |
| typed assignment from `$a[$k]` | inject cast_*() (long-term); v1 may use non-explicit conversion | checked cast / current implementation bridge |
| typed call from `$a[$k]` | inject cast_*() (long-term); v1 may use non-explicit conversion | checked cast / current implementation bridge |
| typed return from `$a[$k]` | inject cast_*() (long-term); v1 may use non-explicit conversion | checked cast / current implementation bridge |
| invalid nested indexing | no compile-time rejection | runtime exception |

---

## 2.5 Reference Semantics (Runtime)

| Case | Behavior |
|---|---|
| mixed& | aliases dynamic slot |
| native → mixed& | not supported |
| typed& mismatch | no cast, may fail |
| foreach by-ref | generator-dependent |
| invalid by-ref | may fail in C++ compile |

---

## 2.6 Guarantees

- no unrestricted by-reference auto casts; the only approved v1 exception is native-equivalent typed by-reference parameter normalization through template dispatch with runtime validation
- arrays are dynamic structures
- indexing returns mixed
- failed casts throw exception
- long-term goal remains explicit S2S-emitted casts at visible-intention sites
- PHP concat remains generator-owned and is not a `mixed_t` runtime operator family

## 1.4 `dynamic_t` v1

`dynamic_t` is the runtime dynamic-object form used for shared property-bag semantics.

- public runtime handle: `using dynamic_t = shared_p<hash_t<mixed_t>>;`
- `mixed_t` stores it under a dedicated `dynamic_v` kind
- storage is shared
- plain copy preserves shared identity
- dynamic/native inheritance or structural mixing is forbidden
- explicit conversion only:
	- `to_dynamic(const hash_t<mixed_t>&)`
	- `to_hash(const dynamic_t&)`
- v1 source lowering currently supported:
	- `new stdClass()`
	- `(object)[ ... ]`
- current generator does not lower dynamic-property syntax yet; runtime-side property/index access remains available through the existing mixed/hash access surface

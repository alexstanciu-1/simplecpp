See `specs/spec_map.md` for document hierarchy, authority, and v1 conflict-resolution rules.

# Dynamic Types Specification (v2.1)

Status: Active  
Replaces: dynamic_types.md (v1)

---

## Simple C++ — Dynamic Type (`mixed_t`)

### Intro (User View)

### Dynamic Type (`mixed`)

Simple C++ allows you to opt into a **dynamic type** when flexibility is needed.

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
| mixed | typed by-reference parameter | ✖ | no by-ref auto-conversion |

---

## 1.5 Operator Matrix (Language Intention)

| Expression | Allowed | Meaning | Result |
|---|---|---|---|
| mixed + mixed | ✔ | dynamic op | mixed |
| mixed + native | ✔ | dynamic op | mixed |
| native + mixed | ✔ | dynamic op | mixed |
| mixed - mixed | ✔ | dynamic op | mixed |
| mixed - native | ✔ | dynamic op | mixed |
| native - mixed | ✔ | dynamic op | mixed |
| mixed * mixed | ✔ | dynamic op | mixed |
| mixed * native | ✔ | dynamic op | mixed |
| native * mixed | ✔ | dynamic op | mixed |
| mixed / mixed | ✔ | dynamic op | mixed |
| mixed / native | ✔ | dynamic op | mixed |
| native / mixed | ✔ | dynamic op | mixed |
| mixed % mixed | ✔ | dynamic op | mixed |
| mixed % native | ✔ | dynamic op | mixed |
| native % mixed | ✔ | dynamic op | mixed |
| mixed . mixed | ✔ | concat | string |
| string . mixed | ✔ | concat | string |
| literal . mixed | ✔ | concat | string |
| mixed . string | ✔ | concat | string |
| mixed == mixed | ✔ | comparison | bool |
| mixed == native | ✔ | comparison | bool |
| native == mixed | ✔ | comparison | bool |
| mixed === mixed | ✔ | strict comparison | bool |
| mixed === native | ✔ | strict comparison | bool |
| native === mixed | ✔ | strict comparison | bool |
| mixed != mixed | ✔ | comparison | bool |
| mixed != native | ✔ | comparison | bool |
| native != mixed | ✔ | comparison | bool |
| mixed !== mixed | ✔ | strict comparison | bool |
| mixed !== native | ✔ | strict comparison | bool |
| native !== mixed | ✔ | strict comparison | bool |
| mixed < mixed | ✔ | comparison | bool |
| mixed < native | ✔ | comparison | bool |
| native < mixed | ✔ | comparison | bool |
| mixed <= mixed | ✔ | comparison | bool |
| mixed <= native | ✔ | comparison | bool |
| native <= mixed | ✔ | comparison | bool |
| mixed > mixed | ✔ | comparison | bool |
| mixed > native | ✔ | comparison | bool |
| native > mixed | ✔ | comparison | bool |
| mixed >= mixed | ✔ | comparison | bool |
| mixed >= native | ✔ | comparison | bool |
| native >= mixed | ✔ | comparison | bool |
| mixed && mixed | ✔ | logical op | bool |
| mixed && native | ✔ | logical op | bool |
| native && mixed | ✔ | logical op | bool |
| mixed || mixed | ✔ | logical op | bool |
| mixed || native | ✔ | logical op | bool |
| native || mixed | ✔ | logical op | bool |
| !mixed | ✔ | logical negation | bool |
| +mixed | ✔ | unary numeric op | mixed |
| -mixed | ✔ | unary numeric op | mixed |
| mixed[index] | ✔ | indexing | mixed |
| mixed = native | ✔ | assign boxed | mixed |
| typed = mixed | ✔ | convert | typed |
| mixed += native | ✔ | dynamic op + assign | mixed |
| mixed -= native | ✔ | dynamic op + assign | mixed |
| mixed *= native | ✔ | dynamic op + assign | mixed |
| mixed /= native | ✔ | dynamic op + assign | mixed |
| mixed %= native | ✔ | dynamic op + assign | mixed |
| mixed .= native | ✔ | concat + assign | string/mixed |

---

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

`mixed_t` does not create new conversion rights. It may only expose conversions that already exist in the Simple C++ native cast rules.

### Indexing / write context

`mixed_t` indexing remains context-sensitive:

- read path: no autovivification
- write path: v1 may autovivify `null` into `hash_t` to preserve visible intention for generated code

### Failure model

For v1, failed exact access or failed typed extraction uses one generic runtime failure path with an explicit message that names:

- the stored runtime type
- the requested target type
- that the conversion / extraction is not allowed under Simple C++ rules

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
| mixed | typed by-reference parameter | none | not allowed |

---

## 2.3 Operator Matrix (Runtime)

| Expression | Generator | Runtime | Result |
|---|---|---|---|
| mixed + mixed | dynamic op helper | runtime dispatch | mixed |
| mixed + native | box native if needed | runtime dispatch | mixed |
| native + mixed | box native if needed | runtime dispatch | mixed |
| mixed - mixed | dynamic op helper | runtime dispatch | mixed |
| mixed - native | box native if needed | runtime dispatch | mixed |
| native - mixed | box native if needed | runtime dispatch | mixed |
| mixed * mixed | dynamic op helper | runtime dispatch | mixed |
| mixed * native | box native if needed | runtime dispatch | mixed |
| native * mixed | box native if needed | runtime dispatch | mixed |
| mixed / mixed | dynamic op helper | runtime dispatch | mixed |
| mixed / native | box native if needed | runtime dispatch | mixed |
| native / mixed | box native if needed | runtime dispatch | mixed |
| mixed % mixed | dynamic op helper | runtime dispatch | mixed |
| mixed % native | box native if needed | runtime dispatch | mixed |
| native % mixed | box native if needed | runtime dispatch | mixed |
| mixed . mixed | string-context conversion on both operands | concat | string |
| string . mixed | string-context conversion on mixed operand | concat | string |
| literal . mixed | string-context conversion on mixed operand | concat | string |
| mixed . string | string-context conversion on mixed operand | concat | string |
| mixed == mixed | compare helper | runtime compare | bool |
| mixed == native | box native if needed, compare helper | runtime compare | bool |
| native == mixed | box native if needed, compare helper | runtime compare | bool |
| mixed === mixed | strict compare helper | runtime compare | bool |
| mixed === native | box native if needed, strict compare helper | runtime compare | bool |
| native === mixed | box native if needed, strict compare helper | runtime compare | bool |
| mixed != mixed | compare helper + negate | runtime compare | bool |
| mixed != native | box native if needed, compare helper + negate | runtime compare | bool |
| native != mixed | box native if needed, compare helper + negate | runtime compare | bool |
| mixed !== mixed | strict compare helper + negate | runtime compare | bool |
| mixed !== native | box native if needed, strict compare helper + negate | runtime compare | bool |
| native !== mixed | box native if needed, strict compare helper + negate | runtime compare | bool |
| mixed < mixed | compare helper | runtime compare | bool |
| mixed < native | box native if needed, compare helper | runtime compare | bool |
| native < mixed | box native if needed, compare helper | runtime compare | bool |
| mixed <= mixed | compare helper | runtime compare | bool |
| mixed <= native | box native if needed, compare helper | runtime compare | bool |
| native <= mixed | box native if needed, compare helper | runtime compare | bool |
| mixed > mixed | compare helper | runtime compare | bool |
| mixed > native | box native if needed, compare helper | runtime compare | bool |
| native > mixed | box native if needed, compare helper | runtime compare | bool |
| mixed >= mixed | compare helper | runtime compare | bool |
| mixed >= native | box native if needed, compare helper | runtime compare | bool |
| native >= mixed | box native if needed, compare helper | runtime compare | bool |
| mixed && mixed | truthiness helper | runtime truthiness + logical op | bool |
| mixed && native | truthiness helper | runtime truthiness + logical op | bool |
| native && mixed | truthiness helper | runtime truthiness + logical op | bool |
| mixed || mixed | truthiness helper | runtime truthiness + logical op | bool |
| mixed || native | truthiness helper | runtime truthiness + logical op | bool |
| native || mixed | truthiness helper | runtime truthiness + logical op | bool |
| !mixed | truthiness helper | runtime truthiness + negate | bool |
| +mixed | unary numeric helper | runtime dispatch | mixed |
| -mixed | unary numeric helper | runtime dispatch | mixed |
| mixed[index] | dynamic index helper | runtime lookup/access | mixed |
| mixed = native | box | store dynamic | mixed |
| typed = mixed | cast_*() (long-term); v1 may use non-explicit conversion | checked conversion / current implementation bridge | typed |
| mixed += native | dynamic op + assign | runtime dispatch then store | mixed |
| mixed -= native | dynamic op + assign | runtime dispatch then store | mixed |
| mixed *= native | dynamic op + assign | runtime dispatch then store | mixed |
| mixed /= native | dynamic op + assign | runtime dispatch then store | mixed |
| mixed %= native | dynamic op + assign | runtime dispatch then store | mixed |
| mixed .= native | string-context conversion + assign | concat then store | string/mixed |

---

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

- no by-reference auto casts
- arrays are dynamic structures
- indexing returns mixed
- failed casts throw exception
- long-term goal remains explicit S2S-emitted casts at visible-intention sites

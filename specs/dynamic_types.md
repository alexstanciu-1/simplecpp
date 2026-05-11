Doc Status: normative


See `specs/spec_map.md` for document hierarchy, authority, and v1 conflict-resolution rules.
See also `specs/array_semantics.md` for the authoritative current array/table subset.
See `specs/strict_mode.md` for recommended extraction and usage patterns to avoid dynamic type propagation.

# Dynamic Types Specification (v2.2)

> Transitional implementation note: see `specs/mixed_boundary_transitional.md`.

Status: Active  
Replaces: dynamic_types.md (v1)

---

## Prism++ â€” Dynamic Type (`mixed_t`)

### Intro (User View)

### Dynamic Type (`mixed`)

Prism++ allows you to opt into a **dynamic type** when flexibility is needed.

Dynamic expressions remain `mixed_t` by default. They become native only at:
- explicit typed boundaries
- explicit user/runtime narrowing points

A typed function or method call counts as an explicit boundary.

By default, values are **native and statically known** when the source directly establishes a native contract:

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

> Dynamic expressions remain `mixed_t` by default. They become native only at explicit typed boundaries or explicit user/runtime narrowing points.

A typed function or method call counts as an explicit typed boundary.

This is a **language / S2S rule**, not a runtime inference rule.

For accepted current implementation fallback behavior, see `specs/mixed_boundary_transitional.md`.

---

## 1.2 Explicit Typed Boundaries

### Definition

> A conversion crosses an **Explicit Typed Boundary** when the destination type is explicitly identifiable at the conversion site in source code or by an immediately-applicable callable/property contract.

If an Explicit Typed Boundary exists, a `mixed` value may be normalized to the target native type there. The current v1 contract still permits approved implementation bridges where the current generator does not materialize that boundary explicitly in emitted C++.

Stable-left-side rule:

> When the receiving side of an assignment or append has a stable explicit destination type, that destination type is explicit enough to authorize ordinary `mixed -> typed` normalization there.

This means the typed destination does not need to appear only as a standalone local or direct property. It may also be provided by:

- a typed container element destination such as `hash<T>[key]`
- a typed append destination such as `vector<T>[]`
- a nested destination reached through a typed property or typed local, so long as the final receiving slot still has a stable explicit value type

If the stable explicit destination type is `mixed`, then no normalization occurs and the value remains `mixed_t`.

### Valid Explicit Typed Boundary sites

| Case | Example | Allowed |
|---|---|---|
| Typed variable assignment | `$x /** int */ = $v;` | âœ” |
| Typed property assignment | `$obj->x = $v; // x is int` | âœ” |
| Typed container element assignment | `$counts["x"] = $v; // counts is hash<int>` | âœ” |
| Typed append destination | `$items[] = $v; // items is vector<int>` | âœ” |
| Typed function argument (by-value) | `f($v); // f(int $x)` | âœ” |
| Typed method argument (by-value) | `$obj->f($v); // f(int $x)` | âœ” |
| Typed return | `return $v; // function(): int` | âœ” |
| Explicit user cast | `$x = (int)$v;` | âœ” |
| Explicit runtime narrowing guard | `if (is_int($v)) { takesInt($v); }` | âœ” |

### Not Explicit Typed Boundaries

| Case | Example | Allowed |
|---|---|---|
| By-reference arguments | `f($v); // f(int& $x)` | âœ– |
| Untyped assignment | `$x = $v;` | âœ– |
| Expressions without typed destination | `$x = $v + 1;` | âœ– |
| Operator candidate expansion via implicit mixed extraction | `f($v + 1);` or overload-created operator paths | âœ– |
| Overload resolution | `f($v); // multiple overloads` | âœ– |
| Intermediate expressions | `$z = foo($v) + 1;` | âœ– |

---

## 1.3 Technical Compromises to Preserve Explicit Typed Boundaries in v1

### Normative priority for v1

For **current v1 user-visible behavior**, **Section 1.2 Explicit Typed Boundaries** and **Section 1.3 Technical Compromises to Preserve Explicit Typed Boundaries in v1** are **normative priority rules**.

If they conflict with a stricter runtime-cleanup preference such as "all dynamic-to-native bridges should already be explicit in generated C++", then **Section 1.2** and **Section 1.3** take precedence until an approved implementation path exists for those explicit bridges.

This means:
- implementations must not remove currently-required v1 bridges merely because they are not the preferred cleanup shape
- runtime/spec/generator cleanup must preserve the valid Explicit Typed Boundary sites listed in **Section 1.2**
- removal of a v1 compromise requires approved generator parity (or an explicitly documented replacement path)

### Context

Current implementation limits mean the runtime remains responsible for preserving approved Explicit Typed Boundary behavior even where the S2S generator does not emit explicit `cast_*()` calls yet.

### v1 constraints

| Constraint | Impact |
|---|---|
| S2S does not fully resolve function / method symbols | cannot reliably inject typed call-site casts |
| S2S cannot always distinguish call-context conversions from other contexts | cannot always materialize explicit casts exactly where desired |
| the runtime cannot infer source-level intention | runtime cannot tell whether a non-explicit conversion originated from a call, assignment, property write, or return |

### v1 compromise conversions

These are accepted in v1 when an Explicit Typed Boundary exists, even when the current generator does not emit an explicit cast at that site:

| Case | Example | Why accepted in v1 |
|---|---|---|
| Typed by-value function call | `add($hash["value"], $hash["add"]);` where `add(int $value, int $add)` | common usage; S2S cannot reliably resolve the callee yet |
| Typed by-value method call | `$obj->setValue($v);` where `setValue(int $v)` | same reason |
| Typed variable assignment | `$x /** int */ = $v;` | intention is directly visible |
| Typed property assignment | `$obj->x = $v;` where `x` is `int` | intention is directly visible |
| Typed container element assignment | `$counts["x"] = $v;` where `counts` is `hash<int>` | the receiving slot value type is directly visible from the stable typed destination |
| Typed append destination | `$items[] = $v;` where `items` is `vector<int>` | the receiving element type is directly visible from the stable typed destination |
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

> The explicit-boundary model is a **language / S2S rule enforced through the runtime boundary layer**.  
> Explicit Typed Boundaries and their v1 compromises belong to the **language / S2S layer**; `mixed_t` by itself does not reveal enough compile-time information to invent those boundaries.

The current implementation may therefore accept some non-explicit `mixed â†’ native` conversions at approved boundary sites that are still part of the current v1 contract.

This includes stable-left-side destinations such as typed container slots and typed append targets. The rule is about whether the receiving slot's type is explicit and stable, not about whether the source expression itself is simple.

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
| native | mixed | âœ” | boxing |
| mixed | typed variable | âœ” | convert |
| mixed | typed parameter (by-value) | âœ” | convert for call |
| mixed | typed return | âœ” | convert on return |
| mixed | typed property | âœ” | convert |
| mixed | untyped | âœ” | stays mixed |
| mixed | overload selection | âœ– | no implicit disambiguation |
| mixed | typed by-reference parameter | âœ– | by-reference normalization from `mixed_t` is not part of the current safe subset |

---


### By-reference boundary rule

There is no approved `mixed_t` to native by-reference normalization rule in the current safe subset. Array/property reads may still feed typed **value** destinations under the ordinary explicit typed-boundary rules, but they do not become approved by-reference sources.

Normative rule:
- by-reference boundaries do not create an explicit typed boundary for `mixed_t`
- a missing array/table read still yields its ordinary read result before typed value-boundary handling is considered
- `mixed_t&` must not be normalized into native `T&` through `.as_*_ref()` in the supported safe subset
- native references are allowed only when the referenced source is already directly stable and native-reference bindable
- any source-language form that would require a native reference or pointer into dynamic interior storage is rejected

See `specs/native_reference_safety.md` and `specs/references.md`.

## 1.5 Operator Matrix (Language Intention)

`mixed_t` does not define an independent flat operator matrix. Instead, operator behavior is resolved by **runtime kind dispatch** and then delegated to the already-defined native wrapper rule.

Examples:
- `mixed(kind=int) + mixed(kind=int)` â†’ same rule as `int_t + int_t`
- `mixed(kind=int) + mixed(kind=float)` â†’ same rule as `int_t + float_t`
- `mixed(kind=float)++` â†’ same rule as `float_t++`
- `mixed(kind=string) += string_t` â†’ same rule as `string_t += string_t`
- `mixed(kind=table) + ...` â†’ error for now

Global rules:
- implicit `mixed -> native` extraction is allowed only at approved Explicit Typed Boundaries (typed initialization/assignment, typed by-value arg passing, typed return, typed property write)
- stable explicit typed destinations include typed container element writes and typed append targets
- operator resolution must **not** use implicit `mixed` extraction to manufacture extra overload candidates
- compound assignment is valid only when the delegated native binary op exists **and** assignment back into the stored lhs kind also remains valid
- table carriers are excluded from arithmetic dispatch
- direct table comparison supports only `==` and `!=`, with identity-only semantics

| Expression | Allowed | Meaning | Result |
|---|---|---|---|
| mixed + mixed | âœ” | dispatch by kinds, then delegate to native rule | mixed |
| mixed + native | âœ” | box native if needed, dispatch by kinds | mixed |
| native + mixed | âœ” | box native if needed, dispatch by kinds | mixed |
| mixed - / * / / / % | âœ” | same delegation model | mixed |
| mixed == / != / < / <= / > / >= | âœ” | dispatch by kinds, then delegate to native comparison rule | bool |
| mixed && / || / !mixed | âœ” | dispatch by kinds, then delegate to native logical rule | bool |
| +mixed / -mixed | âœ” | unary numeric dispatch by kind | mixed |
| ++mixed / --mixed | âœ” | delegate to native increment/decrement rule of contained kind | mixed |
| mixed[index] | âœ” | indexing | mixed |
| typed = mixed | âœ” | Explicit Typed Boundary conversion | typed |
| mixed += native | âœ” | delegated op + assign-back check | mixed |
| mixed .= native | âœ” | generator-owned concat lowering, not runtime mixed concat dispatch | string/mixed |

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
| `[]` | âœ” | dynamic array / hash-table creation | mixed structure |
| `[1,2]` | âœ” | dynamic array | mixed structure |
| `["x"=>1]` | âœ” | dynamic hash-table | mixed structure |
| nested arrays | âœ” | dynamic nested structure | mixed structure |
| `$a[] = native` | âœ” | append boxed value | mixed |
| `$a[$k] = native` | âœ” | assign boxed value | mixed |
| `$a[] = mixed` | âœ” | append dynamic value | mixed |
| `$a[$k] = mixed` | âœ” | assign dynamic value | mixed |
| `$a[$k]` | âœ” | indexed read | mixed |
| `$a[$k1][$k2]` | âœ” | nested read | mixed |
| typed from `$a[$k]` | âœ” | convert | typed |

---

## 1.7 Reference Semantics (Language Intention)

| Case | Rule |
|---|---|
| `mixed&` parameter | requires actual mixed variable |
| native â†’ `mixed&` | not supported |
| typed by-ref param (`string&`) | requires compatible typed storage; no proxy adaptation |
| cast insertion for by-ref | not allowed |
| array element reference | dynamic via mixed container |
| foreach by-ref | generator-dependent, may fail |

---

# 2. Runtime Model (Internal)

## 2.1 Runtime scope note

The runtime model is intentionally stricter than the current v1 language surface:

- runtime does not infer boundary intent from `mixed_t` alone
- runtime should prefer explicit typed bridges such as `cast<T>(mixed_t)`
- current non-explicit acceptance in some call/assignment/property/return cases is a language/S2S compromise, not a runtime feature goal

For the rationale and allowed compromise cases, see:
- **Section 1.2 Explicit Typed Boundaries**
- **Section 1.3 Technical Compromises to Preserve Explicit Typed Boundaries in v1**

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

A typed function or method parameter contract counts as an explicit typed boundary even when the PHP user does not write a source-level cast. In that case, the call boundary itself is the explicit normalization site from the language-design point of view.

Current v1 typed-boundary bridge rule:
- non-explicit `mixed -> native` use is accepted only at Explicit Typed Boundary sites for typed initialization/assignment, typed property write, typed by-value arg passing, and typed returns
- the same rule applies when a stable explicit left side supplies the receiving value type through `hash<T>[...]`, `vector<T>[]`, or the same destinations reached through a typed property/local prefix
- if the stable explicit destination is `mixed`, no cast is inserted and the value remains `mixed`
- operator resolution must not use implicit extraction to create extra candidates
- failed typed extraction remains a runtime error

### Indexing / write context

`mixed_t` indexing remains context-sensitive:

- read path: no autovivification
- write path: v1 may autovivify `null` into `hash_t` to preserve the current explicit-boundary language behavior for generated code

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
| mixed | typed by-reference parameter | none | not supported in the current safe subset |

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
| typed = mixed | explicit `cast_*()` generation preferred; v1 may use non-explicit conversion at Explicit Typed Boundaries | checked conversion / current implementation bridge | typed |
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
| typed assignment from `$a[$k]` | explicit `cast_*()` generation preferred; v1 may use non-explicit conversion | checked cast / current implementation bridge |
| typed call from `$a[$k]` | explicit `cast_*()` generation preferred; v1 may use non-explicit conversion | checked cast / current implementation bridge |
| typed return from `$a[$k]` | explicit `cast_*()` generation preferred; v1 may use non-explicit conversion | checked cast / current implementation bridge |
| invalid nested indexing | no compile-time rejection | runtime exception |

---

## 2.5 Reference Semantics (Runtime)

| Case | Behavior |
|---|---|
| native stable source â†’ native `T&` | allowed |
| `mixed_t` â†’ native `T&` | not supported in the current safe subset |
| dynamic slot / element / property â†’ native `T&` | forbidden |
| `try_ref(...)` on `shared_p<T>` element | allowed; returns a copy |
| `try_ref(...)` on any other element type | throws |

---

## 2.6 Guarantees

- no by-reference auto casts from `mixed_t` to native references in the current safe subset
- arrays are dynamic structures
- indexing returns mixed
- failed casts throw exception
- explicit S2S-emitted casts remain the preferred cleanup shape at Explicit Typed Boundaries
- PHP concat remains generator-owned and is not a `mixed_t` runtime operator family

## 1.4 `dynamic_t` v1

`dynamic_t` is the runtime dynamic-object form used for shared property-bag semantics.

- committed v1 language/runtime-facing default handle: `dynamic_t<>`, meaning shared storage backed by `hash_t<mixed_t, mixed_t>`
- the runtime headers currently define `dynamic_t` as a template alias family whose default instantiation is that v1 handle
- non-default `dynamic_t<...>` instantiations are runtime-side generalization only for now and do not widen the committed language surface in this phase
- `mixed_t` stores it under a dedicated `dynamic_v` kind
- storage is shared
- plain copy preserves shared identity
- dynamic/native inheritance or structural mixing is forbidden
- explicit conversion only:
	- `to_dynamic(const hash_t<mixed_t, mixed_t>&)` for the default v1 dynamic form
	- `to_hash(const dynamic_t<>&)` for the default v1 dynamic form
- v1 source lowering currently supported:
	- `new stdClass()`
	- `(object)[ ... ]`
- current generator does not lower dynamic-property syntax yet; runtime-side property/index access remains available through the existing mixed/hash access surface

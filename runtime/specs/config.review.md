# Runtime Config – Human Review Version

Non-authoritative review companion for `runtime/specs/config.json`.

This file is for human inspection only. `config.json` remains the sole machine-readable source of truth.

## 1. Runtime Defaults

### Description
Defines the global rules the runtime expects frontends and generators to assume when no narrower rule applies.

| Rule / Directive | Meaning | PHP Example | Expected C++ Generated Code | Explanation |
|---|---|---|---|---|
| `namespace = scpp` | All public runtime wrappers and helpers live under one namespace. | `$x = 1;` | `::scpp::int_t x = ::scpp::int_t(1);` | The generated code should target the configured runtime namespace directly. |
| `umbrella_header = scpp/runtime.hpp` | One umbrella include is the default integration point. | `echo 1;` | `#include <scpp/runtime.hpp>` | Keeps generated translation units stable even when internal headers evolve. |
| `create_default_owner = shared` | Default managed object creation uses shared ownership. | `$x = new Service();` | `auto x = ::scpp::create<Service>();` | This matches the current PHP object-lowering model. |
| `default_cast_policy = forbidden` | A cast is illegal unless it is explicitly listed in the cast table. | `$x = (bool)$value;` | `auto x = ::scpp::cast<::scpp::bool_t>(value);` | Prevents accidental C++ implicit conversions from becoming part of the language model. |
| `default_overload_policy = forbidden` | An operator is illegal unless an overload family enables it. | `$x + $y;` | `auto z = x + y; // only when an enabled family covers it` | Operator surface area is opt-in, not inherited from underlying C++ types. |
| `emit_deleted_for_forbidden_operations = true` | Forbidden operations should fail loudly at compile time. | `$a + $b; // where the combo is forbidden` | `auto operator+(...) = delete;` | This turns policy mistakes into local compiler errors instead of runtime surprises. |
| `comparison_result_type = bool_t` | Comparisons standardize on one semantic boolean type. | `if ($a == $b) {}` | `::scpp::bool_t same = (a == b);` | The runtime does not leak raw native `bool` as its semantic result type. |
| `condition_lowering.semantic_type = bool_t` | Conditions are expressed semantically as `bool_t`. | `if ($flag) {}` | `if (flag.native_value()) { ... }` | The generator first produces `bool_t`, then bridges to native C++ condition syntax. |
| `default_assignment_policy = forbidden` | Assignments are illegal unless covered by the assignment matrix. | `$a = $b;` | `a = b; // only when an assignment rule exists` | This keeps assignment semantics explicit, especially for wrappers and sentinels. |

## 2. Runtime Types

### Description
Defines the semantic wrappers, sentinels, and template wrappers the runtime exposes. The intent here is not to restate every field mechanically, but to make the design reviewable.

### 2.1 Sentinel tags

#### Description
These are zero-value semantic sentinels. They are not payload carriers; they exist to express different kinds of emptiness in a controlled way.

| Rule / Directive | Meaning | PHP Example | Expected C++ Generated Code | Explanation |
|---|---|---|---|---|
| `null_t: scalar_tag` | Generic null sentinel. | `$x = null;` | `::scpp::null_t x = ::scpp::null_t();` | Used as the main null-like semantic value across casts, assignments, and comparisons. |
| `nullopt_t: scalar_tag` | Optional-empty sentinel. | `$x = null; // optional-like empty state` | `::scpp::nullopt_t x = ::scpp::nullopt_t();` | Separates “empty optional” from other domains while still participating in sentinel equivalence where configured. |
| `nullptr_t: scalar_tag` | Pointer-empty sentinel. | `$x = null; // pointer-like empty state` | `::scpp::nullptr_t x = ::scpp::nullptr_t();` | Allows handle-like wrappers to accept an explicit pointer-empty sentinel without exposing raw `nullptr` as the semantic API. |

### 2.2 Scalar wrappers

#### Description
These wrappers provide stable semantic types over native C++ storage. They expose a small core API instead of inheriting the full behavior of the underlying native type.

| Rule / Directive | Meaning | PHP Example | Expected C++ Generated Code | Explanation |
|---|---|---|---|---|
| `bool_t -> bool` | Semantic scalar wrapper over a native C++ storage type. | `$flag = true;` | `::scpp::bool_t flag = ::scpp::bool_t(true);` | Wrapper exposes `native_value()` but keeps semantic boolean distinct from raw C++ `bool`. |
| `int_t -> std::int64_t` | Semantic scalar wrapper over a native C++ storage type. | `$x = 42;` | `::scpp::int_t x = ::scpp::int_t(42);` | Standard integer wrapper for arithmetic and comparisons. |
| `float_t -> double` | Semantic scalar wrapper over a native C++ storage type. | `$x = 3.5;` | `::scpp::float_t x = ::scpp::float_t(3.5);` | Standard floating wrapper for arithmetic and mixed numeric promotion. |
| `string_t -> std::string` | Semantic scalar wrapper over a native C++ storage type. | `$s = "hello";` | `::scpp::string_t s = ::scpp::string_t("hello");` | String wrapper keeps construction explicit and text conversion centralized through helpers. |

### 2.3 Template wrappers

#### Description
These wrappers add policy on top of native C++ templates: storage, ownership, borrowing, or optionality.

| Rule / Directive | Meaning | PHP Example | Expected C++ Generated Code | Explanation |
|---|---|---|---|---|
| `vector_t<T> -> std::vector<T>` | Vector wrapper with a narrow stable API. | `$items = [];` | `::scpp::vector_t<::scpp::int_t> items;` | Only the approved vector surface is part of the runtime contract. |
| `value_p<T> -> inline storage` | Inline value wrapper, not a heap-owning handle. | `$point = make_point();` | `::scpp::value_p<Point> point(Point{...});` | Useful for small inline aggregates while staying inside the wrapper model. |
| `ref_p<T> -> T* (non-null borrow)` | Reference wrapper for borrowing/aliasing without ownership. | `function useThing(Thing $x): void {}` | `void useThing(::scpp::ref_p<Thing> x);` | Config marks it as non-nullable and non-owning. |
| `shared_p<T> -> std::shared_ptr<T>` | Primary managed ownership model for PHP object-like values. | `$svc = new Service();` | `::scpp::shared_p<Service> svc = ::scpp::create<Service>();` | Supports null construction, pointer comparison rules, and covariant upcast. |
| `unique_p<T> -> std::unique_ptr<T>` | Exclusive ownership wrapper. | `// not the primary PHP lowering model` | `::scpp::unique_p<File> file = ::scpp::unique<File>();` | Move-only by policy; copy assignment is intentionally absent. |
| `weak_p<T> -> std::weak_ptr<T>` | Non-owning observer of shared ownership. | `// derived from a shared-owned object` | `::scpp::weak_p<Service> w = ::scpp::weak(svc);` | Explicit lock/expired flow keeps liveness checks visible. |
| `nullable<T> -> std::optional<T>` | Optional wrapper for explicit presence/absence. | `function f(?int $x) {}` | `void f(::scpp::nullable<::scpp::int_t> x);` | The config treats nullability as an explicit wrapper, not an ambient property. |

## 3. Memory Helpers

### Description
These helpers are the stable factory/adaptation surface for object creation, ownership conversion, inline value creation, and borrow adaptation.

| Rule / Directive | Meaning | PHP Example | Expected C++ Generated Code | Explanation |
|---|---|---|---|---|
| `create<T>() -> shared_p<T>` | Default managed creation helper. | `$svc = new Service();` | `auto svc = ::scpp::create<Service>();` | Matches `create_default_owner = shared` and keeps allocation policy centralized. |
| `shared<T>() -> shared_p<T>` | Explicit shared factory. | `// explicit shared ownership` | `auto svc = ::scpp::shared<Service>(...);` | Useful when the generator or hand-written runtime code wants to spell out ownership intent. |
| `unique<T>() -> unique_p<T>` | Exclusive-ownership factory. | `// unique owner` | `auto file = ::scpp::unique<File>(...);` | Creates move-only ownership explicitly. |
| `weak(shared_p<T>) -> weak_p<T>` | Derives a weak observer from shared ownership. | `// derived observer` | `auto w = ::scpp::weak(svc);` | Marked as non-allocating in config. |
| `value<T>() -> value_p<T>` | Explicit inline-value creation. | `// inline stored value` | `auto p = ::scpp::value<Point>(...);` | This is the sanctioned path for inline storage. |
| `ref(T&) / ref(value_p<T>&) / ref(ref_p<T>)` | Borrow adapter with identity and unwrap rules. | `$a = $obj; // borrowed view in lowered form` | `auto r = ::scpp::ref(obj);` | The helper is overloaded so borrowing stays explicit but ergonomic. |
| `ref(handle_like) -> same_type` | Ownership handles pass through unchanged. | `// borrowing a shared object-like handle` | `auto same = ::scpp::ref(sharedObj);` | Config explicitly prevents wrapping handle-like values inside `ref_p`. |

## 4. Cast Rules

### Description
Only the listed casts exist. The default policy is forbidden, so every allowed conversion is a deliberate part of the language/runtime contract.

| Rule / Directive | Meaning | PHP Example | Expected C++ Generated Code | Explanation |
|---|---|---|---|---|
| `null_t -> shared_p<T> / unique_p<T> / weak_p<T>` | Null sentinel may construct empty ownership wrappers implicitly. | `$x = null; // assigned to object-like handle` | `::scpp::shared_p<MyClass> x = ::scpp::null_t();` | This is how null-like PHP object state becomes an empty handle wrapper. |
| `null_t -> nullable<T>` | Null sentinel may construct an empty nullable implicitly. | `$x = null; // assigned to ?int` | `::scpp::nullable<::scpp::int_t> x = ::scpp::null_t();` | Keeps nullable construction uniform with handle-null construction. |
| `T -> nullable<T>` | A present value may construct a nullable implicitly. | `$x = 7; $y = $x; // into ?int slot` | `::scpp::nullable<::scpp::int_t> y = x;` | This is the normal “wrap present value” path. |
| `shared_p<T> -> weak_p<T>` | Shared ownership may downgrade to weak observer implicitly. | `// derive observer from shared object` | `::scpp::weak_p<Service> w = svc;` | Weak-from-shared is considered safe and non-owning. |
| `int_t -> float_t` | Integer widens to float implicitly. | `$x = 1; $y = 2.5; $z = $x + $y;` | `::scpp::float_t z = x + y;` | Supports mixed numeric families without requiring manual casts. |
| `bool_t -> int_t / float_t` | Boolean to numeric is explicit only. | `$x = (int)$flag;` | `auto x = ::scpp::int_t(flag.native_value() ? 1 : 0);` | Config keeps this out of implicit conversion to avoid accidental arithmetic on booleans. |
| `int_t / float_t -> bool_t` | Numeric to boolean uses explicit named cast. | `$x = (bool)$n;` | `auto x = ::scpp::cast<::scpp::bool_t>(n);` | Boolean semantics are centralized through `cast` rather than native implicit conversion. |
| `float_t -> int_t` | Float to integer narrowing is explicit named cast. | `$x = (int)$f;` | `auto x = ::scpp::cast<::scpp::int_t>(f);` | Makes narrowing a policy decision instead of a silent C++ conversion. |
| `string_t -> string_t` | Identity cast exists explicitly. | `$x = (string)$s;` | `auto x = ::scpp::cast<::scpp::string_t>(s);` | This avoids special-casing identity in the frontend. |
| `nullable<T> -> T` | Unwrapping a present nullable is explicit. | `$x = $maybe; // after presence check` | `auto x = ::scpp::cast<T>(maybe);` | Config labels this as `unwrap_present_value`, so the generator must only use it when presence is guaranteed. |
| `nullopt_t -> nullable<T>` | Optional-empty sentinel may construct empty nullable implicitly. | `$x = null; // empty optional route` | `::scpp::nullable<T> x = ::scpp::nullopt_t();` | Keeps sentinel entry points flexible while preserving wrapper semantics. |
| `nullptr_t -> shared_p<T> / unique_p<T> / weak_p<T>` | Pointer-empty sentinel may construct empty handle wrappers implicitly. | `$x = null; // pointer-empty route` | `::scpp::shared_p<T> x = ::scpp::nullptr_t();` | Useful when the generator wants pointer-flavored empty semantics. |
| `T -> value_p<T>` | Inline value construction is explicit. | `$point = buildPoint();` | `auto point = ::scpp::value_p<Point>(buildPoint());` | Inline storage is opt-in, not implicit. |
| `T& / value_p<T>& -> ref_p<T> via ref` | Borrow creation is explicit through helper. | `$tmp = $obj; // borrowed lowering path` | `auto r = ::scpp::ref(obj);` | The helper enforces the approved borrowing/adaptation surface. |

## 5. Coercions

### Description
Coercions are not general casts. They describe context-driven lowering, currently for conditions and text rendering.

### 5.1 Condition coercion

#### Description
Condition lowering is intentionally narrow: only semantic `bool_t` is accepted, and then bridged through `native_value`.

| Rule / Directive | Meaning | PHP Example | Expected C++ Generated Code | Explanation |
|---|---|---|---|---|
| `condition.allowed_inputs = [bool_t]` | Only `bool_t` may enter a condition directly. | `if ($flag) {}` | `if (flag.native_value()) { ... }` | No other type gets PHP-style truthiness for free. Frontends must lower to `bool_t` first. |
| `condition.bridge = native_value` | The semantic boolean is bridged to native C++ condition syntax. | `while ($flag) {}` | `while (flag.native_value()) { ... }` | This keeps control flow legal in C++ without weakening the semantic type system. |

### 5.2 Text coercion

#### Description
Text contexts route through one result type, one helper family, and explicit null rendering. This prevents ad hoc concatenation or stream-based coercion from leaking into the model.

| Rule / Directive | Meaning | PHP Example | Expected C++ Generated Code | Explanation |
|---|---|---|---|---|
| `result_type = string_t` | Every text coercion ends as `string_t`. | `echo $x;` | `auto s = ::scpp::to_string(x);` | There is one canonical textual result type. |
| `dispatch_helper = to_string` | Non-identity text conversions route through helper dispatch. | `echo $n;` | `::scpp::string_t s = ::scpp::to_string(n);` | Keeps formatting policy centralized. |
| `null_t / nullopt_t / nullptr_t render as empty string` | Configured null-like sentinels print as empty text. | `echo null;` | `::scpp::print(::scpp::string_t(""));` | The config makes these three sentinels text-equivalent. |
| `string_t is identity in text context` | Existing text stays text. | `echo $s;` | `::scpp::print(s);` | No redundant helper call is required for string identity. |
| `bool_t / int_t / float_t use to_string` | Scalar wrappers have helper-driven text conversion. | `echo 42;` | `::scpp::print(::scpp::to_string(::scpp::int_t(42)));` | Avoids depending on native iostream formatting rules. |
| `nullable<T> / value_p<T> / ref_p<T> / shared_p<T> / unique_p<T> / weak_p<T> use to_string` | Wrapper text conversion is helper-defined, not implicit. | `echo $obj;` | `::scpp::print(::scpp::to_string(obj));` | Important because wrapper text policy can change independently of storage representation. |

## 6. Subtyping

### Description
Subtyping is wrapper-specific. The source of truth is C++ pointer convertibility, but only selected wrappers inherit that capability.

| Rule / Directive | Meaning | PHP Example | Expected C++ Generated Code | Explanation |
|---|---|---|---|---|
| `object_subtype_source = cpp_pointer_convertibility` | Runtime polymorphism follows C++ pointer convertibility rules. | `interface I {} class A implements I {}` | `static_assert(std::is_convertible_v<A*, I*>);` | The config avoids inventing a parallel subtype lattice. |
| `shared_p<U> -> shared_p<T> implicit when U* convertible to T*` | Shared handles are covariant. | `$x = new A(); $i = $x; // as interface/base` | `::scpp::shared_p<I> i = x;` | This is the core rule behind interface dispatch through shared ownership. |
| `weak_p<U> -> weak_p<T> implicit when U* convertible to T*` | Weak observers are covariant too. | `// weak observer upcast` | `::scpp::weak_p<I> i = w;` | Keeps weak handles aligned with shared subtyping. |
| `ref_p<U> -> ref_p<T> implicit when U* convertible to T*` | Borrowed references are covariant. | `function useBase(Base $x) {} useBase($derived);` | `void useBase(::scpp::ref_p<Base> x);` | Useful for non-owning interface/base dispatch. |
| `unique_p<U> -> unique_p<T> forbidden` | Unique ownership upcast is not standardized here. | `// disallowed by config` | `// generator error` | Blocked because ownership transfer and deleter policy were intentionally left unstabilized. |
| `nullable<U> -> nullable<T> forbidden` | Generic inner subtyping for nullable is disabled. | `// disallowed generic optional upcast` | `// generator error` | This avoids hidden composition rules inside optionality. |
| `value_p<U> -> value_p<T> forbidden` | Inline value wrapper is not polymorphic. | `// disallowed inline polymorphic wrapper conversion` | `// generator error` | Inline storage should not pretend to be a virtual object handle. |

## 7. Enabled Operator Families

### Description
Only these operator families are enabled. Everything else is forbidden by default or by an explicit forbidden-operation group.

| Rule / Directive | Meaning | PHP Example | Expected C++ Generated Code | Explanation |
|---|---|---|---|---|
| `bool_logical` | Boolean logic and equality on `bool_t` are enabled. | `$a && $b;` | `auto x = a && b;` | Covers `!`, `&&`, `||`, `==`, `!=` on semantic booleans. |
| `int_arithmetic` | Unary and binary arithmetic plus comparisons on `int_t` are enabled. | `$a + $b;` | `auto x = a + b;` | Covers arithmetic and relational operations for integers. |
| `float_arithmetic` | Unary and binary arithmetic plus comparisons on `float_t` are enabled. | `$a / $b;` | `auto x = a / b;` | Floating arithmetic mirrors integer structure. |
| `mixed_numeric` | Mixed `int_t` + `float_t` arithmetic/comparison promotes to `float_t`. | `$a + $b; // int + float` | `auto x = a + b; // result float_t` | Promotion is explicit in config rather than inferred ad hoc. |
| `string_ops` | Only equality/inequality on `string_t` are enabled. | `$a == $b;` | `auto same = (a == b);` | Notably, string concatenation is not an operator family here. |
| `pointer_null_comparisons` | Handle wrappers compare against the configured null-equivalence group and shared handles compare with same-family peers. | `$obj == null;` | `auto empty = (obj == ::scpp::null_t());` | This is how null checks stay legal without opening general pointer arithmetic or cross-family comparisons. |
| `nullable_ops` | Nullable values compare with null-equivalent sentinels and same-type nullable peers. | `$maybe == null;` | `auto empty = (maybe == ::scpp::null_t());` | Makes presence tests legal while keeping nullable arithmetic forbidden. |

## 8. Forbidden Operation Groups

### Description
These are explicitly banned even if the underlying native C++ representation might support something similar. This section is important because it defines what the language refuses to mean.

| Rule / Directive | Meaning | PHP Example | Expected C++ Generated Code | Explanation |
|---|---|---|---|---|
| `bool_arithmetic` | Booleans are not numbers. | `$a + $b; // bools` | `// compile-time error` | Prevents accidental promotion of flags into arithmetic. |
| `pointer_cross_family_comparisons` | Different ownership families must not be compared directly. | `$shared == $unique;` | `// compile-time error` | Ownership semantics differ; direct equality would be misleading. |
| `vector_arithmetic` | Vectors do not gain arithmetic operators. | `$a + $b; // arrays/vectors` | `// compile-time error` | Avoids inventing element-wise semantics by accident. |
| `string_arithmetic` | Strings do not gain arithmetic operators. | `$a + $b; // strings` | `// compile-time error` | Important because PHP string-plus-number behavior is already a source of ambiguity. |
| `nullable_arithmetic` | Optional values do not participate in arithmetic directly. | `$a + $b; // nullable numbers` | `// compile-time error` | Forces explicit unwrap/coercion instead of propagating ambiguous null arithmetic. |
| `pointer_arithmetic` | Ownership wrappers are not arithmetic operands. | `$obj + $obj;` | `// compile-time error` | Reinforces that handles are semantic wrappers, not address-like values. |
| `sentinel_arithmetic` | Sentinels never behave like numbers. | `null + null;` | `// compile-time error` | Null-like values stay in the sentinel domain. |

## 9. Composition Constraints

### Description
This section controls which wrappers may nest inside which wrappers. It is one of the highest-value parts of the config because it prevents semantically messy composite types from becoming legal by accident.

| Rule / Directive | Meaning | PHP Example | Expected C++ Generated Code | Explanation |
|---|---|---|---|---|
| `family_tags classify wrappers as ownership / inline_storage / reference / optionality` | Nesting rules reason in terms of family, not only concrete names. | `// type composition policy` | `// compile-time validation against family tags` | This makes the config extensible without duplicating every concrete combination. |
| `value_p<T> must not contain ownership-family wrappers` | Inline value wrapper cannot embed `shared_p`, `unique_p`, or `weak_p`. | `// disallowed: value of handle` | `// generator error for value_p<shared_p<T>>` | Prevents “inline wrapper around heap handle” layering that adds confusion without value. |
| `value_p<T> must not contain ref_p<U>` | Inline value wrapper cannot contain a borrow wrapper. | `// disallowed: value of ref` | `// generator error for value_p<ref_p<T>>` | Borrow semantics inside inline storage are intentionally not part of the model. |
| `ref_p<T> must not target ownership-family wrappers` | A borrow wrapper cannot wrap a handle wrapper. | `// disallowed: borrowed handle wrapper` | `// generator error for ref_p<shared_p<T>>` | Config prefers handle passthrough instead of double-wrapping ownership in borrowing syntax. |
| `ref_p<T> must not wrap ref_p<U>` | Borrow wrappers do not stack. | `// disallowed: ref of ref` | `// generator error for ref_p<ref_p<T>>` | Avoids meaningless alias-of-alias wrapper nests. |
| `nullable<T> must not contain unique_p<U>` | Optional unique ownership is forbidden. | `// disallowed: ?unique owner` | `// generator error for nullable<unique_p<T>>` | Reason given by config: redundant and confusing null layering. |
| `nullable<T> may contain ref_p<U> as a special allowed case` | Optional borrowed reference is allowed at runtime level. | `// runtime-level optional borrow` | `::scpp::nullable<::scpp::ref_p<T>> maybeRef;` | Config allows it, but explicitly marks it as not the primary PHP object-lowering model. |
| `ref(ref_p<T>) -> ref_p<T>` | Borrow helper collapses identity. | `$x = $y; // already borrowed form` | `auto x = ::scpp::ref(existingRef); // same ref_p<T>` | Prevents redundant wrapper creation. |
| `ref(value_p<T>&) -> ref_p<T>` | Borrow helper unwraps inline value to a borrow of its inner object. | `// borrow inline value` | `auto r = ::scpp::ref(inlineValue);` | This is the sanctioned bridge from inline storage to borrow semantics. |
| `ref(handle_like) -> same_type` | Borrow helper passes ownership handles through unchanged. | `// borrow shared handle` | `auto same = ::scpp::ref(sharedObj);` | This is the reason `ref_p<shared_p<T>>` is both unnecessary and forbidden. |
| `php_lowering_guidance.nullable_object_like = nullable<shared_p<T>>` | Nullable PHP objects should lower to nullable shared handles. | `function f(?MyClass $x) {}` | `void f(::scpp::nullable<::scpp::shared_p<MyClass>> x);` | This captures the project’s current main lowering model for PHP nullable object references. |
| `php_lowering_guidance.disfavored_forms = nullable<ref_p<T>>, nullable<unique_p<T>>` | These forms are not the preferred PHP lowering target. | `// avoid these shapes in frontend lowering` | `// generator should choose nullable<shared_p<T>> instead` | This is guidance, not just a raw type-theory statement. |

## 10. Assignment Rules

### Description
Assignments are explicit policy. This section says which source-target pairs are legal and what kind of state change they mean.

| Rule / Directive | Meaning | PHP Example | Expected C++ Generated Code | Explanation |
|---|---|---|---|---|
| `scalar wrappers assign by copy` | bool_t, int_t, float_t, string_t copy assign to same type. | `$a = $b;` | `a = b;` | These wrappers behave like normal value types for same-type assignment. |
| `vector_t<T> assigns by copy` | Vectors copy assign to same type. | `$a = $b; // vector-like value` | `a = b;` | Consistent with vector wrapper as a value-like container. |
| `nullable<T> <- T` | Present value may be assigned into nullable. | `$maybe = 7;` | `maybe = value;` | This is the assignment counterpart of `T -> nullable<T>` construction. |
| `nullable<T> <- nullable<T>` | Nullable copies from same nullable type. | `$a = $b; // both nullable` | `a = b;` | Standard copy semantics on optional wrapper. |
| `nullable<T> <- null_t / nullopt_t / nullptr_t` | Nullable may be reset to empty by any configured null-equivalent sentinel. | `$maybe = null;` | `maybe = ::scpp::null_t();` | Config explicitly treats these sentinels as reset sources. |
| `shared_p<T> <- shared_p<T>` | Shared handles copy assign. | `$a = $b; // object handles` | `a = b;` | Copies the shared ownership handle, not the object. |
| `shared_p<T> <- null_t / nullptr_t / nullopt_t` | Shared handle may reset to null via configured sentinels. | `$obj = null;` | `obj = ::scpp::null_t();` | Null-equivalent reset is deliberate and explicit. |
| `unique_p<T> <- unique_p<T>` | Unique handles assign by move, not copy. | `$a = $b; // exclusive owner transfer in lowered model` | `a = std::move(b);` | Reflects move-only ownership. |
| `unique_p<T> <- null_t / nullptr_t / nullopt_t` | Unique handle may reset to null. | `$obj = null;` | `obj = ::scpp::null_t();` | Reset is allowed even though copy is not. |
| `weak_p<T> <- weak_p<T>` | Weak handles copy assign. | `$a = $b; // observers` | `a = b;` | Observers are cheap copyable handles. |
| `weak_p<T> <- shared_p<T>` | Weak observer may be assigned from shared owner. | `$weak = $shared;` | `weak = shared;` | This mirrors the cast/helper downgrade path. |
| `weak_p<T> <- null_t / nullptr_t / nullopt_t` | Weak observer may reset to empty. | `$weak = null;` | `weak = ::scpp::null_t();` | Empty-observer reset is explicit. |
| `value_p<T> <- value_p<T> / T` | Inline value wrapper copies from same wrapper or copies into inner value. | `$box = $point;` | `box = point;` | Supports ordinary inline value replacement. |
| `ref_p<T> <- ref_p<T>` | Borrow wrappers copy assign by alias copy. | `$a = $b; // borrowed refs` | `a = b;` | Copying a borrow copies the alias, not the referent. |
| `shared_p<T> <- shared_p<U>, weak_p<T> <- weak_p<U>, ref_p<T> <- ref_p<U> when U* convertible to T*` | Assignment supports configured upcast/covariance for selected wrappers. | `$base = $derived;` | `base = derived;` | This is the assignment counterpart to the subtyping rules. |

## 11. Sentinel Semantics

### Description
Defines when distinct sentinel types should be treated as equivalent for specific semantic operations. The current config uses one equivalence group.

| Rule / Directive | Meaning | PHP Example | Expected C++ Generated Code | Explanation |
|---|---|---|---|---|
| `equivalence_group = [null_t, nullopt_t, nullptr_t] with policy comparison_equivalent` | The three configured sentinels compare as equivalent emptiness. | `$x == null;` | `x == ::scpp::null_t() // and equivalent sentinel forms` | This is what allows pointer-null and nullable-null comparisons to accept multiple sentinel spellings without inventing broader implicit conversions. |

## 12. Runtime Helpers Contract

### Description
Declares the stable helper names that generators/frontends are allowed to target directly. This is a cross-boundary contract, not an implementation detail list.

| Rule / Directive | Meaning | PHP Example | Expected C++ Generated Code | Explanation |
|---|---|---|---|---|
| `stable_helpers = create, shared, unique, weak, value, ref, cast, to_string` | These helper entry points are part of the public contract. | `$x = new A(); echo $x;` | `::scpp::create<A>(); ::scpp::to_string(x);` | Anything outside this list should not become a generator dependency without contract change. |
| `namespaces.core = scpp` | Core helpers live in `::scpp`. | `$x = 1;` | `::scpp::int_t x = ::scpp::int_t(1);` | This aligns type wrappers and helper entry points. |
| `namespaces.php = scpp::php` | PHP-specific runtime helpers, when needed, live under a separate namespace. | `// frontend/runtime glue` | `::scpp::php::...;` | Keeps core runtime and PHP-facing glue separable. |
| `generator_allowed_helpers matches stable_helpers` | Generators may only target the approved helper list directly. | `$x = (bool)$n;` | `::scpp::cast<::scpp::bool_t>(n);` | Important because contract stability matters more than today’s internal implementation structure. |
| `notes.separation_rule` | The contract lists shared knowledge helpers, not generator internals. | `// policy note` | `// no direct dependency on private helper names` | This is a governance rule: keep frontend/runtime coupling narrow and intentional. |

## 13. Scope Note

### Description
This review document is intentionally semantic and curated. It covers all major content areas present in `config.json`, but it does so by extracting the meaningful rules rather than reproducing every raw field mechanically.

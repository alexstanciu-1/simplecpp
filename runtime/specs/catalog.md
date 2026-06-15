# Simple C++ for PHP â€” Runtime Catalog
Doc Status: normative
Status: Draft / AI anchor
Scope: Public runtime surface only
Authority: Subordinate to `specs/spec_map.md`, `specs/dynamic_types.md`, `specs/array_semantics.md`, and `runtime/specs/spec.md`

## 1. Purpose

This document is the **short, grouped, versioned, enforceable** catalog of the public runtime/library surface used by **Simple C++ for PHP**.

It is intentionally:
- short per item
- broad in coverage
- normative for public API shape and return-state meaning
- not a dump of internal helpers

## 2. Interpretation Rules

- **MUST** = required public behavior
- **SHOULD** = preferred public behavior
- **MAY** = allowed but not required
- `value | false` means PHP-style falseable success/failure
- `value | null` means absence, not failure
- `result<T>` means structured success/error
- `result_or_false<T>` means success or PHP-style `false`
- `result_or_bool<T>` means success, `false`, or `true` where the API needs a boolean state in addition to a value
- This catalog describes the **public contract**, not internal implementation details

## 3. Stability Tags

- **Stable** â€” intended public surface for v1
- **Transitional** â€” allowed in v1 due to current generator/runtime boundaries
- **Internal** â€” not part of the public catalog even if visible in headers

---

# 4. Core Semantic Types

## 4.1 Scalar wrappers

### `bool_t` â€” Stable
Semantic boolean wrapper.

Contract:
- MUST represent runtime boolean values
- MUST not introduce uncontrolled implicit truthiness in generated logic; mixed_t may participate only through the approved condition helper when its runtime payload is bool/int/float
- SHOULD be explicitly bridged at native C++ condition boundaries

### `int_t` â€” Stable
Semantic integer wrapper.

Contract:
- MUST represent runtime integer values
- MUST preserve semantic isolation from raw native integer behavior

### `float_t` â€” Stable
Semantic floating-point wrapper.

Contract:
- MUST represent runtime floating-point values
- MUST preserve semantic isolation from raw native floating-point behavior

### `string_t` â€” Stable
Semantic string wrapper.

Contract:
- MUST represent runtime string values
- MUST preserve runtime-defined string behavior
- SHOULD be treated as UTF-8 aware where string helpers define code-point semantics

## 4.2 Sentinel/value-state wrappers

### `null_t` â€” Stable
Semantic null sentinel.

Contract:
- MUST represent absence / PHP `null`
- MUST remain distinct from `false`
- MUST remain distinct from `nullable<T>`

### `nullopt_t` â€” Stable
Semantic empty-optional sentinel.

Contract:
- MUST be used for optional-empty semantics
- MUST remain distinct from `null_t`

### `nullptr_t` / `null_ptr` â€” Stable
Semantic empty-pointer sentinel.

Contract:
- MUST represent pointer-like empty state
- MUST remain distinct from `null_t` and `nullopt_t`

## 4.3 Optionality and result wrappers

### `nullable<T>` â€” Stable
Optional value wrapper.

Public members:
- `has_value() -> bool_t`
- `value() -> T& / const T&`
- `require_value(context) -> T& / const T&`
- `reset()`

Contract:
- empty state means `null` / absence
- `value()` on empty MUST throw a runtime-shaped error
- MUST not silently collapse absence into `false`

### `result<T>` â€” Stable
Structured success/error wrapper.

Public members:
- `has_value()`
- `has_error()`
- `value()`
- `error()`
- `require_value()`
- `require_error()`

Contract:
- MUST represent `value` or `error`
- MUST not encode PHP `false` as its failure state

### `result_or_false<T>` â€” Stable
Falseable success wrapper.

Public members:
- `has_value()`
- `is_false()`
- `value()`
- `require_value()`
- `reset()`

Contract:
- MUST represent `value` or PHP-style `false`
- MUST be preferred for PHP APIs whose failure contract is `false`

### `result_or_bool<T>` â€” Stable
Value-or-bool wrapper.

Public members:
- `has_value()`
- `is_false()`
- `is_true()`
- `value()`
- `require_value()`
- `reset()`

Contract:
- MUST represent `value`, `false`, or `true`
- MUST be used only when the API genuinely needs a boolean state in addition to a value

## 4.4 Dynamic/container wrappers

### `mixed_t` â€” Stable
Primary dynamic runtime value.

Contract:
- MUST represent dynamic PHP-facing values
- MUST preserve distinction among scalar, null, and dynamic/container forms
- MUST not erase `null`/`false` distinctions

### `hash_t<T_VALUE, T_KEY = string_t>` â€” Stable
Runtime table / associative container.

Contract:
- MUST support keyed access under runtime-defined array/table semantics
- MUST follow `specs/array_semantics.md`
- `hash_t<mixed_t, mixed_t>` remains the dynamic PHP-array/storage specialization
- typed runtime maps default to `string_t` keys and may opt into an explicit supported `T_KEY`

### `vector_t<T>` â€” Stable
Runtime vector wrapper.

Contract:
- MUST support indexed sequence semantics
- SHOULD remain intentionally small in v1

### `dynamic_t` â€” Stable
Alias for shared dynamic object/table storage.

Definition:
- committed v1 default meaning: `dynamic_t<>`, backed by `shared_p<hash_t<mixed_t, mixed_t>>`
- runtime implementation note: headers currently expose `dynamic_t<T_VALUE, T_KEY> = shared_p<hash_t<T_VALUE, T_KEY>>`

Contract:
- MUST represent shared dynamic table/object-like storage
- MUST remain distinct in meaning from plain `hash_t<mixed_t, mixed_t>`
- non-default `dynamic_t<...>` instantiations are runtime-side generalization in the current phase, not a language-surface expansion

## 4.5 Ownership wrappers

### `shared_p<T>` â€” Stable
Shared-owning handle.

Contract:
- MUST model shared ownership
- public observers MAY include null checks, `get()`, `reset()`, and debug helpers

### `unique_p<T>` â€” Stable
Exclusive-owning handle.

Contract:
- MUST model exclusive ownership
- MUST preserve move-only ownership semantics

### `weak_p<T>` â€” Stable
Non-owning observational handle.

Contract:
- MUST not directly own the target
- MUST be observed through `lock()`-style behavior
- expired state MUST remain distinguishable

### `value_p<T>` â€” Transitional
Inline-storage wrapper.

Contract:
- MUST represent explicit non-heap inline storage
- MUST not become the silent default lowering for PHP objects in v1

---

# 5. PHP Core Helpers (`scpp::php`)

## 5.1 Strings

### `strlen(string_t) -> int_t` â€” Stable
Returns code-point length.

### `strlen(nullable<string_t>) -> int_t` â€” Transitional
Returns code-point length of present value.

Contract:
- null input MUST raise runtime error

### `strpos(haystack, needle[, offset]) -> result_or_false<int_t>` â€” Stable
Returns code-point position or `false`.

Contract:
- success => `int_t`
- not found => `false_sentinel`

### `strrpos(haystack, needle[, offset]) -> result_or_false<int_t>` â€” Stable
Returns last code-point position or `false`.

### `strtolower(value) -> string_t` â€” Stable
ASCII lowercase transform.

### `strtoupper(value) -> string_t` â€” Stable
ASCII uppercase transform.

### `lcfirst(value) -> string_t` â€” Stable
Lowercases first byte/character position under current ASCII-oriented behavior.

### `ucfirst(value) -> string_t` â€” Stable
Uppercases first byte/character position under current ASCII-oriented behavior.

### `str_starts_with(haystack, needle) -> bool_t` â€” Stable
Prefix test.

### `str_ends_with(haystack, needle) -> bool_t` â€” Stable
Suffix test.

### `ltrim(value[, mask]) -> string_t` â€” Stable
Left trim.

### `rtrim(value[, mask]) -> string_t` â€” Stable
Right trim.

### `trim(value[, mask]) -> string_t` â€” Stable
Two-sided trim.

### `substr(value, offset[, length]) -> string_t` â€” Stable
PHP-like substring under normalized offset/length rules.

### `substr_compare(main, part, offset[, length[, case_insensitive]]) -> int_t` â€” Stable
Substring compare.

### `substr_replace(subject, replacement, offset[, length]) -> string_t` â€” Stable
Substring replacement.

### `str_replace(search, replace, subject) -> string_t` â€” Stable
String replacement.

### `str_pad(input, pad_length[, pad_string[, pad_type]]) -> string_t` â€” Stable
String padding.

### `explode(separator, string[, limit]) -> vector_t<string_t>` â€” Stable
Returns typed split result.

Contract:
- separator MUST not be empty where PHP semantics require failure
- result shape is `vector_t<string_t>`

### `implode(separator, pieces) -> string_t` â€” Stable
Supported overloads:
- `hash_t<string_t>`
- `vector_t<string_t>`
- `mixed_t` when runtime kind is array-like

### `hex2bin(value) -> result_or_false<string_t>` â€” Stable
Returns decoded string or `false`.

### `bin2hex(value) -> string_t` â€” Stable
Hex encoding.

### `number_format(...) -> string_t` â€” Stable
Supported for:
- `int_t`
- `float_t`
- `string_t`
- `bool_t`
- `mixed_t`

Contract:
- invalid dynamic/string numeric input MAY raise runtime error if conversion is unsupported

### `microtime() -> string_t` â€” Stable
String form.

### `microtime(bool_t as_float) -> float_t` â€” Stable
Float form when requested.

### `to_string(...) -> string_t` â€” Stable
Supported for:
- `string_t`
- `int_t`
- `float_t`
- `bool_t`
- `null_t`
- `nullopt_t`
- `nullptr_t`
- `mixed_t`
- `nullable<T>`
- `result_or_false<T>`
- `result_or_bool<T>`
- `result<T>`

Contract:
- MUST return a runtime string representation
- SHOULD preserve visible value-state distinctions where meaningful

## 5.2 Output

### `echo(args...) -> void` â€” Stable
Writes arguments in sequence after stringification.

### `echo_eval(fns...) -> void` â€” Transitional
Evaluates deferred value producers and echoes them.

Contract:
- intended for lowered/runtime convenience, not user-facing API design

## 5.3 Identity / state helpers

### `identical(left, right) -> bool_t` â€” Stable
Strict identity helper used for lowered `===`.

Contract:
- MUST preserve strict/null-sensitive comparisons defined by runtime rules

### `not_identical(left, right) -> bool_t` â€” Stable
Strict non-identity helper used for lowered `!==`.

### `count(value) -> int_t` â€” Stable
Supported for:
- `vector_t<T>`
- `hash_t<T>`
- `mixed_t`

Contract:
- non-countable `mixed_t` handling MUST follow runtime helper policy

### `empty(value) -> bool_t` â€” Stable
Supported for:
- scalar wrappers
- sentinel wrappers
- `nullable<T>`
- `result*`
- ownership wrappers
- `vector_t<T>`
- `hash_t<T>`
- `mixed_t`

Contract:
- MUST follow lowered PHP `empty()` semantics for supported categories

### `empty(container, key) -> bool_t` â€” Stable
Supported for:
- `vector_t<T>`
- `hash_t<T>`
- `mixed_t`

Contract:
- invalid/missing/non-countable cases MUST follow non-throwing empty-policy behavior defined by runtime helpers

### `isset(args...) -> bool_t` â€” Stable
Variadic lowered `isset` helper.

Contract:
- MUST preserve null-sensitive semantics
- MUST be non-throwing for supported lookup/helper forms

### `isset(container, key) -> bool_t` â€” Stable
Supported for:
- `vector_t<T>`
- `hash_t<T>`
- `mixed_t`

### `unset(args...) -> void` â€” Stable
Variadic lowered `unset` helper.

Contract:
- supported wrappers reset/clear according to runtime rules
- unsupported custom types SHOULD fail at compile time rather than invent semantics

### `defined(name) -> bool_t` â€” Transitional
Runtime-defined symbol check.

Contract:
- current public usefulness is limited; keep behavior conservative

## 5.4 Process/memory helpers

### `memory_get_usage([bool_t real_usage]) -> int_t` â€” Stable
Process-level memory usage.

Contract:
- current behavior is benchmark/process oriented, not exact PHP engine parity

### `memory_get_peak_usage([bool_t real_usage]) -> int_t` â€” Stable
Process-level peak memory usage.

### `debug_use_count(shared_p<T> | weak_p<T>) -> long` â€” Transitional
Debug lifetime helper.

Contract:
- debugging aid only
- MUST not be treated as stable business-logic API

### `weakref(shared_p<T>) -> weak_p<T>` â€” Stable
Creates weak observation handle.

### `weakref_get(weak_p<T>) -> shared_p<T>` â€” Stable
Locks weak handle.

Contract:
- expired weak references yield empty shared handle

---

# 6. JSON (`scpp::php`)

### `json_encode(mixed_t) -> string_t` â€” Stable
Encodes runtime dynamic value to JSON string.

Contract:
- public input surface is `mixed_t`
- exact escaping/shape follows current runtime JSON implementation

### `json_decode(string_t) -> dynamic` â€” Stable
Decodes JSON string to runtime dynamic value.

Contract:
- source-facing result MUST be expressed as dynamic value
- arrays/objects decode into runtime dynamic/container forms

---

# 7. Filesystem (`scpp::php`)

### `is_file(path) -> bool_t` â€” Stable
File test.

### `is_dir(path) -> bool_t` â€” Stable
Directory test.

### `is_link(path) -> bool_t` â€” Stable
Symlink test.

### `file_exists(path) -> bool_t` â€” Stable
Existence test.

### `file_get_contents(path) -> result_or_false<string_t>` â€” Stable
Returns file contents or `false`.

### `file_put_contents(path, data) -> result_or_false<int_t>` â€” Stable
Returns bytes written or `false`.

### `mkdir(path) -> bool_t` â€” Stable
Creates directory.

### `scandir(path) -> result_or_false<hash_t<mixed_t>>` â€” Stable
Returns directory listing or `false`.

### `filesize(path) -> result_or_false<int_t>` â€” Stable
Returns size or `false`.

### `filemtime(path) -> result_or_false<int_t>` â€” Stable
Returns mtime or `false`.

### `touch(path) -> bool_t` â€” Stable
Touch/create timestamp update.

### `rmdir(path) -> bool_t` â€” Stable
Directory removal.

### `unlink(path) -> bool_t` â€” Stable
File removal.

### `copy(source, dest) -> bool_t` â€” Stable
File copy.

### `rename(source, dest) -> bool_t` â€” Stable
Rename/move.

### `realpath(path) -> result_or_false<string_t>` â€” Stable
Returns resolved path or `false`.

### `dirname(path) -> string_t` â€” Stable
Directory component.

### `basename(path) -> string_t` â€” Stable
Base name component.

### `set_cli_args(argc, argv) -> void` â€” Stable
Seeds the runtime CLI context for later helper calls.

### `cli_argc() -> int_t` â€” Stable
Returns the current CLI argument count.

### `cli_argv() -> mixed_t` â€” Stable
Builds the PHP-style CLI argument array from the seeded runtime CLI context.

### `cli_args() -> mixed_t` â€” Stable
Alias of `cli_argv()`.

### `shell_exec(command) -> result_or_false<string_t>` â€” Stable
Returns command stdout as `string_t` or `false`.

---

# 8. Resources / stdio (`scpp::php`)

## 8.1 Resource types

### `resource_handle_t` â€” Stable
Shared handle to `php_resource_t`.

### `nullable_resource_handle_t` â€” Stable
Nullable resource handle.

### `falseable_resource_handle_t` â€” Stable
Falseable resource handle.

### `php_resource_t` â€” Stable
Base runtime resource.

### `file_resource_t` â€” Stable
File-stream resource.

Public fields:
- `path`
- `mode`
- `readable`
- `writable`
- `append`

Contract:
- closed/open state MUST remain explicit
- invalid/closed handle use MUST raise runtime-shaped errors in resource-requiring helpers

## 8.2 stdio/file APIs

### `parse_fopen_mode(mode) -> fopen_mode_info` â€” Transitional
Mode parser.

Contract:
- helper-level API; public documentation may mention supported mode set but SHOULD not encourage direct user use

### `fopen(path, mode) -> falseable_resource_handle_t` â€” Stable
Returns file resource or `false`.

### `fseek(resource, offset[, whence]) -> nullable<int_t>` â€” Stable
Returns `0` on success, `null` on failure.

Contract:
- this is intentionally nullable, not falseable, in current v1

### `ftell(resource) -> result_or_false<int_t>` â€” Stable
Returns position or `false`.

### `fgets(resource[, length]) -> result_or_false<string_t>` â€” Stable
Returns line/string or `false`.

### `fread(resource, length) -> result_or_false<string_t>` â€” Stable
Returns bytes/string or `false`.

### `fwrite(resource, data) -> result_or_false<int_t>` â€” Stable
Returns bytes written or `false`.

### `fputs(resource, data) -> result_or_false<int_t>` â€” Stable
Alias/forwarder to `fwrite`.

### `rewind(resource) -> bool_t` â€” Stable
Rewind stream.

### `fflush(resource) -> bool_t` â€” Stable
Flush stream.

### `feof(resource) -> bool_t` â€” Stable
EOF test.

### `fclose(resource) -> bool_t` â€” Stable
Close stream.

Contract:
- subsequent closed-handle use MUST fail through runtime resource checks

---

# 9. MySQL / mysqli (`scpp::php` and `scpp` runtime classes)

## 9.1 `mysqli` â€” Stable
Connection wrapper.

Public fields:
- `connect_errno : int_t`
- `connect_error : string_t`
- `errno_code : int_t`
- `error : string_t`
- `insert_id : int_t`
- `affected_rows : int_t`

Constructor:
- `mysqli(host, username, password, database = "", port = 3306, socket = "")`

Methods:
- `query(sql) -> result_or_bool<shared_p<mysqli_result>>`
- `prepare(sql) -> result_or_false<shared_p<mysqli_stmt>>`
- `close() -> void`
- `set_charset(charset) -> bool_t`
- `begin_transaction() -> bool_t`
- `commit() -> bool_t`
- `rollback() -> bool_t`

Contract:
- connection and runtime status fields MUST reflect latest operation status
- `query()` is value-or-bool, not plain falseable, by design
- `prepare()` is falseable

## 9.2 `mysqli_result` â€” Stable
Query result wrapper.

Public fields:
- `num_rows : int_t`

Methods:
- `fetch_assoc() -> dynamic_t`
- `fetch_row() -> dynamic_t`

Contract:
- row fetch helpers MUST return runtime dynamic row forms
- end-of-result semantics MUST match current runtime implementation and companion docs/tests

## 9.3 `mysqli_stmt` â€” Stable
Prepared-statement wrapper.

Public fields:
- `errno_code : int_t`
- `error : string_t`
- `insert_id : int_t`
- `affected_rows : int_t`

Methods:
- `bind_param(types, args...) -> bool_t`
- `execute() -> bool_t`
- `get_result() -> result_or_false<shared_p<mysqli_result>>`
- `close() -> void`

Contract:
- `bind_param()` type-string arity MUST match bound argument count
- supported bound arg categories currently include `int_t`, `float_t`, `string_t`, and `mixed_t`
- unsupported bound arg categories MUST fail clearly rather than silently coercing

---

# 10. Return-State Rules

## 10.1 Distinctions are normative

The public runtime surface MUST preserve these distinctions:

- `value`
- `null`
- `false`
- `error`

They are not interchangeable.

## 10.2 Selection rules

- Use `nullable<T>` when the meaning is **absence**
- Use `result_or_false<T>` when the meaning is **PHP-style failure**
- Use `result_or_bool<T>` only when the API needs **value / false / true**
- Use `result<T>` when the meaning is **structured error**

## 10.3 AI/codegen rule

Generated and example code SHOULD:
- check `false` explicitly where relevant
- keep `null` separate from `false`
- avoid truthiness shortcuts when the state matters
- prefer the most explicit return-state handling form
- use `php::take(...)` when code should extract wrapper payloads into existing locals while preserving explicit branch semantics

### 10.4 `php::take(...)`

- `php::take($value, $source)` is the unified extraction helper for `nullable<T>` and `result_or_false<T>` wrappers
- `php::take($value, $error, $source)` is the unified extraction helper for `result<T>` wrappers
- `php::take($value, $bool, $source)` is the unified extraction helper for `result_or_bool<T>` wrappers
- `result_or_bool<T>` returns `true` from `take(...)` for both wrapped-value and bool-true states so mysqli-style success-without-result APIs stay representable
- outputs unrelated to the active wrapper branch remain unchanged
- `take(...)` is runtime-owned, evaluates the source expression exactly once, and preserves the declared output slot type

### 10.5 Wrapper iterable delegation

- approved wrappers carrying iterable payloads may delegate the runtime iterable-by-value surface through guarded unwrap
- this enables PHP `foreach ($wrapper as $value)` when the wrapper is in a success / value state and its carried payload is iterable
- sentinel / empty / error wrapper states fail at runtime on unwrap rather than silently producing zero iterations

---

# 11. Exclusions

This catalog intentionally excludes:
- internal helper functions
- private class members
- code-generation internals
- non-public support plumbing
- future planned APIs not present in the current public runtime surface

---

# 12. Change Policy

Any public API addition or behavior change SHOULD update:
- this catalog
- relevant subsystem specs
- tests covering the user-visible contract

Any removal or signature change to a **Stable** item MUST be treated as spec-sensitive.

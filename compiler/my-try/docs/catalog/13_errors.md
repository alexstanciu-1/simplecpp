# 13. Exceptions and cleanup
Doc Status: planning

> **Important: LLVM is deferred by default. Work on the direct LLVM backend only when the user explicitly requests it. The v0.2 default scope is Frontend + C++ S2S.**

[Catalog and workflow](README.md) · [Source merge ledger](source_merge.json)

Requires calls, control flow and lifetime. Discuss throw/catch before finally and exit-path cleanup.

Order is a discussion sequence, not a claim that every row is a prerequisite. Split combined examples before implementation.

## Progress

Edit these rows as work proceeds. Imported source support is recorded below, independently of this progress.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [ERR-THROW-001](#err-throw-001) | pending-discussion | `throw new Exception("x");` | unverified | unverified | deferred | — |
| [ERR-TRY-001](#err-try-001) | pending-discussion | `try { f(); } catch (Exception $e) { }` | unverified | unverified | deferred | — |
| [ERR-FINALLY-001](#err-finally-001) | pending-discussion | `try { return 1; } finally { g(); }` | unverified | unverified | deferred | — |
| [NOTE-047](#note-047) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
## ERR-THROW-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:309](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
throw new Exception("x");
```

**Existing C++ lowering / result**

```cpp
throw ::scpp::php::make_thrown(create<Exception>(string_t("x")));
```

**Category:** Error handling

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** thrown expression lowers to `shared_p<T>` where `T` derives from runtime `Throwable` / `Exception`

**Normalized pattern:** `throw <expr>`

**General rule:** `throw` lowers to native C++ `throw` of a runtime `::scpp::php::thrown_object` wrapper. The wrapped payload is a `shared_p<T>` throwable object, which keeps the current object-handle runtime model and avoids process abort/exit paths in normal error flow.

**Diagnostics:** Emit a generator error or compile-time failure for non-throwable expressions or unsupported expression forms.

**Notes:** Throw used as an expression is still unsupported in the current pass.


## ERR-TRY-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:310](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
try { f(); } catch (Exception $e) { }
```

**Existing C++ lowering / result**

```cpp
try { f(); } catch (const ::scpp::php::thrown_object& __t) { if (auto __caught = ::scpp::php::catch_as<Exception>(__t)) { auto e = __caught; } else { throw; } }
```

**Category:** Error handling

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** each catch type is a named class that derives from runtime `Throwable` / `Exception` and the catch variable is a simple local name

**Normalized pattern:** `try / catch`

**General rule:** `try` / `catch` lowers to native C++ `try` plus a runtime wrapper catch. Each typed PHP catch arm is implemented through `::scpp::php::catch_as<T>(...)`, so handled exceptions stay in-process and unmatched exceptions continue propagating.

**Diagnostics:** Emit a generator error for unsupported catch type forms such as non-class catches or unsupported union catches.

**Notes:** Catch variables materialize as local `shared_p<T>` handles inside the handler body.


## ERR-FINALLY-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:311](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
try { return 1; } finally { g(); }
```

**Existing C++ lowering / result**

```cpp
{ std::exception_ptr __pending; std::optional<int_t> __ret; bool __has_ret = false; try { __ret = int_t(1); __has_ret = true; } catch (...) { __pending = std::current_exception(); } { g(); } if (__pending) std::rethrow_exception(__pending); if (__has_ret) return *__ret; }
```

**Category:** Error handling

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** `return` may leave the protected `try` / `catch` region, but `break` / `continue` may not, and the `finally` body itself still must not use `return` / `break` / `continue`

**Normalized pattern:** `try / finally`

**General rule:** `finally` lowers without `goto`: the generator stores any escaping exception in `std::exception_ptr`, rewrites protected-region `return` statements into a pending-return flag/value, runs the finally block exactly once, then rethrows the pending exception and finally performs the delayed return. In the delayed-return path, protected `for (...)` loops are rewritten into explicit init + `while` form so the post-iteration clause does not run after the pending return is set. If finally throws, that new exception escapes and replaces the older pending exception.

**Diagnostics:** Emit a generator error when `break` / `continue` would leave the protected region, when break/continue depths are not the simple unit form, or when the finally body itself uses `return` / `break` / `continue`. Nested loop-local `break` / `continue` and switch-local `break` remain allowed when they stay inside the protected region.

**Notes:** Nested `try` / `catch` / `finally` uses one pending-exception slot per lowered region and reuses the surrounding function return type for delayed-return storage.


## NOTE-047

**Source:** [generators/php/specs/rules.md:1072](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 18. Error handling policy
>
> For unsupported or invalid cases:
> - stop generation immediately
> - throw an error
> - include file / line / position if available

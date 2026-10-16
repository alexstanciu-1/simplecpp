# 13. Exceptions and cleanup
Doc Status: planning

> **Important: LLVM is deferred by default. Work on the direct LLVM backend only when the user explicitly requests it. The v0.2 default scope is Frontend + C++ S2S.**

[Catalog and workflow](README.md) · [Source merge ledger](source_merge.json)

Requires calls, control flow and lifetime. Bounded throw/catch and exceptional cleanup
are implemented below; finally and its transfer interactions remain to be discussed.

Order is a discussion sequence, not a claim that every row is a prerequisite. Split combined examples before implementation.

## Structured error value debt from chapter 05

**RSL-ERROR-01 — deferred by agreement, 2026-10-03.** Chapter 05 implements Result
success construction, exact wrapper copying and explicit legacy sentinel construction.
Structured failure construction (`error -> result<T>`) waits here for its prerequisite.

Chapter 05's take slice (2026-10-04) now registers the source-facing `error` as a
runtime-owned nominal value backed by `scpp::error_t`, with default/copy semantics,
readonly `error::$no_error`, condition truthiness and logical negation. Runtime
failures reject no-error values and expose readonly error inspection. This is the
minimal prerequisite for a nonnullable take error output, not full error authoring.

### Structured error factory slice — 2026-10-16

**RSL-ERROR-01 is implemented within the agreed factory/getter/failure-transport bounds.**
Core metadata registers `error::make(string message, int line = 0, string file = "")`
and the value-returning `get_message()`, `get_line()`, `get_file()` methods. The existing
runtime nominal identity remains the owner; no new type, AST or member-call path was
introduced. The static factory delegates to `error_t` construction and metadata supplies
ordinary scalar defaults. Empty messages still produce real errors; default/no-error
values retain empty details and false condition truthiness.

Shared `Result_Conversions` selects a distinct `result_error` operation by canonical
error identity and structured Result role, retaining the existing payload admission.
C++ lowering consumes that decision and uses the checked runtime constructor.
No-error rejection remains value-dependent and preserves the old Result on rejected
assignment. Source getters return snapshots and keep the existing stable mutable
inline receiver restriction.

Proof: [error_values.php](../../tests/error_values.php) produced 17 generated programs
executed without PCH, 13 preparation rejections, no-op stability and incremental
rejection/repair/fresh agreement. Cases cover defaults/details/copying, empty-message
presence, getter snapshots, argument order, initialization/assignment/argument/return
failure transport, take clearing/preservation, existing payload families and no-error
runtime rejection. A native fixture harness catches the exception around an emitted
assignment to verify old-value preservation; this does not enable source catch syntax.
[results.php](../../tests/results.php) additionally proves renamed Result-role admission
and rejection of an unrelated source type named `error`.

Evidence: `/tmp/scpp-ch13-errors-01/summary.json` (17 generated executions) and
`/tmp/scpp-ch13-regressions-01/summary.json` (seven focused PHP suites). Final
preparation-only rejection checks, including initialized `result<error>` rejection,
passed under `/tmp/scpp-ch13-errors-final-host/logs/`. The shared runner used CLI fallback because
FPM socket creation was unavailable. No native compiler rebuild was performed.

Non-goals remain `new error(...)`, general runtime constructor metadata, broader
receiver lifetimes, new Result payload families, `result<error>`, bare error sentinel
exposure, new error operators and thrown exceptions. An `error` is an in-band value;
it is not a Throwable. [The authoritative contract](../../../../specs/error_values_and_take.md)
owns its semantics. **The bounded exception slice below follows this work.** Finally remains a separate
discussion of its interactions with returns and other exits.

## Bounded Exception transport and cleanup — 2026-10-16

ERR-THROW-001 and ERR-TRY-001 are implemented within the agreed single-type bounds.
Core metadata owns the opaque shared `Exception`, static `make(string)` factory and
instance `get_message()`. Named throw/try/catch syntax edges feed shared preparation;
canonical admission rejects unrelated spelling matches and in-band errors. The catch
binding uses ordinary local storage facts in its own lifetime region. Explicit throws
have no normal fallthrough; try completion conservatively includes the handler path.

C++ consumes the prepared operand and binding types, transports the same shared object
through `make_thrown`/`catch_as`, and relies on RAII for reverse exceptional cleanup.
Absent throws fail explicitly. The generated executable reports uncaught standard or
language exceptions at its outer boundary after local unwinding, returning 1. Runtime
guards bypass source catch. Existing function-try native probes deliberately remove
only that marked executable boundary through the shared test artifact transport.

[exceptions.php](../../tests/exceptions.php) owns focused construction/message,
canonical alias, single evaluation, callee propagation, nested rethrow, handler scope,
completion, reverse cleanup, outer survivors, partial construction, handler/loop exits,
object identity/release, absent throws and uncaught reporting proofs. Pointer identity
and final release use a native harness around the emitted callable; no source handle
comparison or weak-handle extension is implied. Incremental invalid throw/type/syntax
edits reject and repair to fresh output. Validation passed: 17 generated programs without PCH and 22 rejection cases,
including no-op and rejection/repair/fresh agreement, in
`/tmp/scpp-ch13-exceptions-04/summary.json`. Eleven focused PHP regression suites
passed in `/tmp/scpp-ch13-exception-regressions-01/logs/`; 19 of 21 existing generated
error/destructor probes passed there. The two single-file function-try probes exposed
a transport adaptation gap, repaired and proved in
`/tmp/scpp-ch13-exception-final-01/summary.json`, alongside both artifact transport
modes and a final host exception-suite pass. Thus all 21 selected regression programs
passed across the original and repair runs. CLI fallback was used after FPM socket
creation failed. Final host exception/AST checks passed after consolidation in
`/tmp/scpp-ch13-exception-review-01/logs/`. No native compiler rebuild or full regression
run was performed.

[The normative contract](../../../../specs/exceptions_v0_2.md) owns semantics.
Non-goals: finally, multiple/union catches, subclasses and source-to-runtime inheritance,
`new Exception`, arbitrary runtime/std exception catches, throw expressions, bare
rethrow, chaining, runtime-handle ownership extensions, static effect analysis and LLVM.
Next: discuss finally and its interaction with returns and other transfers.

## Progress

Edit these rows as work proceeds. Imported source support is recorded below, independently of this progress.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| RSL-ERROR-01 | implemented-bounded | `error::make("bad", 12, "input.phs")`, getters and `error -> result<T>` | proved | proved | deferred | 17 generated programs, 13 preparation rejections and incremental repair; `tests/error_values.php`; general runtime `new` remains excluded |
| [ERR-THROW-001](#err-throw-001) | implemented-bounded | `throw Exception::make("x");` | proved | proved | deferred | Single canonical shared Exception; identity, absence and unwind proofs in `tests/exceptions.php`; runtime `new` excluded |
| [ERR-TRY-001](#err-try-001) | implemented-bounded | `try { f(); } catch (Exception $e) { }` | proved | proved | deferred | One handler, nested/callee propagation, handler scope, RAII cleanup and outer reporting; finally excluded |
| [ERR-FINALLY-001](#err-finally-001) | pending-discussion | `try { return 1; } finally { g(); }` | unverified | unverified | deferred | — |
| [NOTE-047](#note-047) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
## ERR-THROW-001

**v0.2 decision / target C++:** Implemented within the [bounded exception slice](#bounded-exception-transport-and-cleanup--2026-10-16). Imported examples below retain their historical scope; runtime `new` and general inheritance are not enabled.

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

**v0.2 decision / target C++:** Implemented within the [bounded exception slice](#bounded-exception-transport-and-cleanup--2026-10-16). Imported examples below retain their historical scope; runtime `new` and general inheritance are not enabled.

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

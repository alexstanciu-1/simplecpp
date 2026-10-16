# Bounded exceptions — v0.2
Doc Status: normative

This contract covers the strict frontend and C++ S2S single-type exception slice.
The in-band [error value](error_values_and_take.md) remains a separate type.

## Construction and transport

Core runtime metadata registers `Exception` as an opaque shared class handle.
`Exception::make(string message): Exception` creates a present handle; the instance
method `get_message(): string` returns its message by value. An empty message is valid.
Default storage is absent. Ordinary shared copying retains the same object.
The existing stable-receiver rules apply to instance calls.

`throw expression;` evaluates its operand once. Shared preparation requires exactly
the registered canonical `Exception` identity, including ordinary aliases. Source
shadowing cannot turn an unrelated class named `Exception` into a throwable. Scalars,
in-band errors and other objects are rejected before C++ generation. An absent
Exception is a value-dependent runtime error (`absent_exception`), not a language
exception with a null payload.

A successful throw transports the same shared object. It never copies the payload.
It has no normal fallthrough, including in a function with a non-void return type.
Calls are not subject to static exception-effect analysis.

## Handler and lifetime

```php
try {
    throw Exception::make("failed");
} catch (Exception $e) {
    $message = $e->get_message();
    echo $message;
}
```

Each try has exactly one required typed catch with a simple local variable. Its type
must be the registered Exception. The handler binding is local to the handler and
can shadow an outer local; it is unavailable after the handler. Protected-block
locals are unavailable in the handler. Nested try/catch is supported. A handler can
propagate the same object outward with `throw $e;`; it does not catch its own throws.

Completion conservatively combines the protected and handler paths. A handler that
can fall through prevents an enclosing non-void function from being classified as
always returning/throwing without a later return. No analysis infers that a handler
is unreachable because a protected body contains no explicit throw.

Native C++ unwinding destroys initialized automatic storage in reverse lifetime
order. Enclosing survivors remain live. Shared exception transport keeps the thrown
object live through matching and handler execution. For failed construction, completed
members/bases unwind but the incomplete object's destructor does not run. Existing
[nonthrowing destructor semantics](destructors_v0_2.md#nonthrowing-boundary) apply:
an exception escaping a destructor terminates the process.

## Runtime boundary

C++ lowering catches the runtime `thrown_object` transport, checks the prepared
payload type and binds a shared handle. An unmatched transport propagates outward.
Source catch does not intercept standard C++ exceptions or runtime guard failures.

The generated executable encloses entry execution in an outer `std::exception`
handler. Uncaught language exceptions and runtime failures unwind to that boundary,
which uses `scpp::print_runtime_exception` and returns exit status 1. Ordinary source
returns retain their exit status. This does not introduce a recovery policy for
non-standard foreign exceptions, fatal signals or destructor termination.

## Excluded from this slice

`finally`, multiple catch arms, union catches, source exception subclasses and
source-to-runtime inheritance, general runtime constructor metadata (`new Exception`),
throw expressions, bare rethrow, exception chaining and static exception-effect
analysis remain deferred. Runtime-handle identity operators and ownership transformations
such as `weak(Exception)` are not added by this slice. LLVM remains deferred.

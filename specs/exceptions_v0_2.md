# Bounded exceptions — v0.2
Doc Status: normative

This contract covers the strict frontend and C++ S2S bounded exception hierarchy.
It also defines the bounded scalar/void-return finally extension.
The in-band [error value](error_values_and_take.md) remains a separate type.

## Construction and transport

Core runtime metadata registers `Exception` as an opaque shared class handle.
`Exception::make(string message): Exception` creates a present handle; the instance
method `get_message(): string` returns its message by value. Empty messages and embedded
zero bytes are preserved.
Default storage is absent. Ordinary shared copying retains the same object.
The existing stable-receiver rules apply to instance calls.

Source shared classes may extend the canonical registered `Exception`, directly or
through source ancestors. Aliases preserve that identity. This is a bounded
exception-root relationship, not permission to inherit arbitrary runtime types.
Source classes use their ordinary constructors, fields, visibility, inheritance
and virtual destruction. A descendant can widen to the root shared handle without
copying its payload.

The root instance method `get_message()` is available when ordinary source member
lookup finds no matching declaration, including on `$this`. Runtime static factory
inheritance and `parent` qualification of the runtime root are not added. A source
method named `getMessage` (including the portability framework's getter) remains
an ordinary source method; it does not override native diagnostic dispatch. The
root message of a source-created exception is `"Exception"`. A source declaration
named `get_message` hides the root method through normal source lookup, rather than
overriding the native root's implementation. Root-typed access still uses the root
contract. Framework detail/code/cause fields have ordinary source-class semantics;
this slice adds no language-level chaining protocol.

`throw expression;` evaluates its operand once. Shared preparation requires the
registered canonical `Exception` or a shared source descendant, including aliases. Source
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

Each try requires a catch, a finally, or both in that order. At most one typed catch
is supported, with a simple local variable typed as the registered Exception or a
shared source descendant. A base handler accepts a thrown descendant. A narrower
handler accepts the object only when its dynamic type matches that class or a
descendant; unmatched language exceptions propagate. The handler binding is local to the handler and
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

## Finally and delayed exits — v0.2

`try { ... } finally { ... }` and `try { ... } catch (Exception $e) { ... } finally { ... }`
run finally once after normal completion or an exit from the protected try/catch.
An empty finally is present syntax and follows the same rules.

- A protected return evaluates its expression and performs the ordinary prepared
  return conversion once, before cleanup/finally. The saved value is independent of
  later mutation of its source local. Only `int`, `bool`, `float`, `string` and `void`
  returns may cross finally in this slice; existing entrypoint return forms also work.
  Void forwarding evaluates its expression before becoming a pending return.
- If return evaluation or conversion throws, that exception follows the ordinary
  source-handler rules. Finally still runs; no successful return has been saved.
- An exception escaping either the try body or its catch handler is retained until
  finally finishes. Normal completion of finally rethrows the same exception object.
  Runtime guard failures also run finally and propagate; they do not become catchable
  by the source Exception handler.
- An exception escaping finally replaces a pending return or exception. The same
  try's catch does not catch exceptions from its finally. An enclosing handler may
  catch a language exception from finally. A failure handled wholly inside finally
  does not replace the original pending exit.
- Nested regions finish from inner to outer. An inner saved return resumes through
  all enclosing finally regions before leaving the callable.

Protected-block locals clean up before finally starts. Catch-local storage, including
its binding, ends before finally; the pending exception transport may retain the
underlying thrown object. Finally is a sibling lexical region and cannot see locals
from try/catch. Enclosing locals remain alive. Finally-local cleanup precedes replay
of the pending exit. Cleanup remains reverse-order RAII with the existing nonthrowing
[destructor boundary](destructors_v0_2.md#nonthrowing-boundary); fatal termination is
not a recoverable exception exit and does not promise execution of remaining finally
blocks.

Break/continue whose target lies outside a finally-protected region are rejected
during preparation. Break/continue in loops or switches wholly inside that region
remain supported. The same rule applies inside the finally body: its own local
loops/switches can transfer internally, but transfers cannot leave finally. Returns
from finally are rejected, including returns nested in its statements. A separately
invoked source closure has its own return context and is unaffected by those lexical
restrictions.

Reference returns and record/container/wrapper/owning-handle returns across finally
remain deferred. Such callables may still use finally where their returns do not
cross it. Preparation conservatively composes protected and finalizer completion:
a non-fallthrough finally suppresses pending normal exits; otherwise the protected
completion resumes. No interprocedural exception-effect analysis is implied.

The C++ backend uses one saved exception and optional pending scalar/void return per
region. An immediately invoked implementation lambda exits protected control flow
without running later statements or loop updates. Exception capture encloses the
entire try/catch operation, and finally executes outside that capture. This is not a
throwing destructor or an extension of source closure semantics. The backend consumes
prepared completion to mark impossible fallthrough with a termination invariant;
ordinary source exits do not use that path.

## Runtime boundary

Ordinary and prelowered C++ preserve the same payload identity and catch matching.
A template-free runtime payload base permits source inheritance. Native ownership
and typed catch operations remain behind compiled specialization boundaries in
prelowered output; application units and their transitive headers remain
free of template tokens. Prelowered finally retains an inline native exception
state whose size/alignment is measured by the existing layout machinery. Its
pending scalar return uses scalar storage plus an explicit pending flag; this
does not admit additional source return types.

C++ lowering catches the runtime `thrown_object` transport, checks the prepared
payload type and binds a shared handle. An unmatched transport propagates outward.
Source catch does not intercept standard C++ exceptions or runtime guard failures.

The generated executable encloses entry execution in an outer `std::exception`
handler. Uncaught language exceptions and runtime failures unwind to that boundary,
which uses `scpp::print_runtime_exception` and returns exit status 1. Ordinary source
returns retain their exit status. This does not introduce a recovery policy for
non-standard foreign exceptions, fatal signals or destructor termination.

## Excluded from this slice

Crossing break/continue, returns from finally, non-scalar/non-void returns through
finally, multiple catch arms, union catches, arbitrary source-to-runtime
inheritance, direct runtime construction (`new Exception`),
throw expressions, bare rethrow, exception chaining and static exception-effect
analysis remain deferred. Runtime-handle identity operators and ownership transformations
such as `weak(Exception)` are not added by this slice. LLVM remains deferred.

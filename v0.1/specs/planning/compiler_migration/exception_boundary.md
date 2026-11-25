# Exception portability boundary preflight
Doc Status: planning

The next validating compiler slices cannot honestly preserve their failure paths
by copying PHP exception syntax directly to selected v0.1.76. This preflight
establishes target facts before adding converter rules. It does not mark another
production file ready or claim a completed exception adapter.

## Verified target behavior

All probes used clean selected revision
`8cc4d8ff7eb395c6cc69219f25bef960323bce45`, strict mode and STAN, with Clang 18.

- `results/exception-preflight-01`: direct `InvalidArgumentException` construction
  and catch reach native compilation, which fails because the class is absent.
- `exception-preflight-02`: a namespaced subclass of root `Exception` fails because
  the base is emitted as an incomplete name in that namespace.
- `exception-preflight-03`: a global subclass resolves the base, but native
  `Exception` lacks a PHP `__construct` method and inherited `getMessage` method.
- `exception-preflight-04`: an explicit message field/accessor avoids those method
  gaps, but the subclass needs a default-constructible base under current lowering.
- `exception-preflight-05`: a small global framework hierarchy with defaulted
  constructors and explicit message storage/access succeeds. Both the specific
  subtype catch and framework-base catch produce the expected messages.

Each evidence directory retains exact PHS and build logs. No generated C++ was
patched, no STAN bypass was used, and no selected-target source was modified.

## Compiler demand and next owner

A read-only source scan found widespread LogicException, RuntimeException,
InvalidArgumentException, Exception, OutOfBoundsException and RangeException usage,
plus Throwable catches. JSON-specific catches also exist. Source_Error extends
RuntimeException and initializes its message through the parent constructor.
This is a shared framework concern, not a reason to rewrite failure categories
independently in each compiler component.

The next implementation should introduce a deliberate native framework owner and
an explicit type-spelling policy for the supported root PHP exception classes.
Fixed root-name translation is local syntax; it must not turn into general symbol
resolution. Authored PHP should continue using its actual PHP exception behavior.
The native counterpart must keep category relationships, messages and catch order.
Compiler call sites should retain their logic rather than acquiring ad hoc errors.

Before production use, prove narrow versus base catches, unmatched propagation,
rethrow identity, actual compiler error messages, and the required constructor
forms. Code/cause arguments and JSON failures must be inventoried rather than
silently dropped. Define how native framework sources join generated projects
without compromising one-to-one user-file conversion or incremental ownership.
The known parent-constructor and namespaced-base limitations need explicit rules.

The passing probe only demonstrates caught messages. It does not establish native
uncaught diagnostics, PHP traces, arbitrary Throwable compatibility, cause chains,
all runtime-originated errors, or a safe general inheritance conversion.

No broad compiler refactor or target-version change has been made. The migration
goal remains active; this is verified input to the next runtime/converter slice,
not a declared global blocker. Ten production files remain ready.

Implementation follow-up: the [handled-exception slice](../../portability/compiler_exception_slice.md)
now provides the bounded framework and assembly path, with explicit remaining limits.
This preflight remains the historical input to that work.

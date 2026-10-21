# Exception hierarchy bootstrap boundary
Doc Status: planning

Initial audit at `68e75704`, 2026-10-21. The user subsequently authorized the
bounded redesign. It is implemented and verified in this change; current semantics
belong to `specs/exceptions_v0_2.md`, and measured evidence belongs to
[the bootstrap checkpoint](storage_bootstrap_2026_10_21.md#exception-hierarchy-and-prelowered-transport-retry--2026-10-21).
The audit and agreed scope below preserve the design rationale.

## Verified boundary

The runtime already has polymorphic exception payloads, shared-object throw
transport and dynamic subtype catches in
`runtime/include/lang/php/php_exceptions.hpp`. It does not need a second exception
identity or copied exception payload.

The v0.2 relationship owner only accepts source bases. Exception preparation
accepts exactly the registered Exception identity. The C++ emitter explicitly
rejects throw, catch and finally in prelowered mode. These are separate barriers:
admitting the relationship alone cannot complete this request.

Four focused probes at `/tmp/exception-v02-design-audit/` reproduce:

| Probe | Mode | Current diagnostic |
| --- | --- | --- |
| `class Child extends Exception {}` | ordinary | Relationship target must be a source class, struct or interface |
| Throw factory-created Exception | concrete | throw operation requires concrete transport |
| Catch registered Exception | concrete | catch operation requires concrete transport |
| Scalar return through finally | concrete | finally operation requires concrete transport |

The probes use the shared test runner with CLI fallback after FPM socket binding
was denied. All four expected-rejection checks pass. They are boundary checks,
not bootstrap measurements.

Directly including the current native Exception header in a generated source
class exposes templates (`shared_p`, `make_thrown`, `catch_as`) to application
headers. This violates the enforced transitive template-free contract. An opaque
handle wrapper cannot serve as a substitute for its pointee's C++ base class.

## Agreed bounded redesign

1. Give runtime exception payload inheritance a template-free public interface,
   keeping native shared ownership, factories and typed transport behind compiled
   boundaries. Preserve the same object and polymorphic destruction. Review
   source message behavior explicitly: the portability class stores `detail` and
   exposes `getMessage`, while native reporting uses `message_string`.
2. Extend prepared relationships with an explicit admitted runtime exception-root
   edge, separate from source-base identity. Observe and propagate exception
   ancestry through the existing scheduler. Admit only shared source classes and
   the canonical registered Exception root, including aliases; reject unrelated
   runtime bases and source types merely named Exception.
3. Use that ancestry in throw/catch admission and source-to-Exception conversions.
   Define the root member boundary explicitly rather than pretending runtime
   members have collected source declarations. Retain ordinary source inheritance
   and access/override rules for the framework subclasses.
4. Add exception operation bridges to the existing specialization request stream.
   Throw transports the original shared payload; typed catch preserves dynamic
   identity and propagates unmatched/foreign exceptions. Application code must
   not instantiate native transport templates. Keep finally's existing pending
   exception and scalar/void return semantics while replacing its exposed
   `std::optional<T>` transport with template-free generated storage/control.
5. Prove ordinary/concrete behavior and transitive header purity, then rerun the
   existing timed PHP-hosted bootstrap and stop at its next blocker.

This touches the runtime payload ABI, semantic relationships/conversions and C++
exception specialization. It is wider than a local relaxation of throw/catch
admission and was explicitly approved under the repository's cross-owner redesign rule.

## Validation and risks

Required proofs: source root and grandchildren; base/derived catches; unmatched
and foreign propagation; same-object mutation/rethrow and destruction; absent
throw; finally normal, return and replacement-throw paths; canonical aliases and
shadow rejection; incremental ancestry removal/repair; ordinary and prelowered
execution; full application-header template audit. Runtime header refactoring
also needs existing factory/transport compatibility checks for v0.1 consumers.

Main risks are loss of shared identity at the compiled bridge, inconsistent native
versus source message dispatch, stale ancestry consumers and templates leaking
through source-base headers. These are semantic/ABI risks, not performance tuning.

The smaller alternative is an ordinary-only hierarchy slice, leaving concrete
transport explicitly unsupported. That does not fulfill the requested prelowering
integration. General runtime-class inheritance is a much larger alternative and
is not recommended for this bootstrap slice.

Non-goals: Storage_Cursor widening, arbitrary runtime-class inheritance, multiple
or union catches, general object casts/weak handles, broader finally return types,
LLVM, and repairs to later bootstrap failures. The converter already emits one
framework-base catch with ordered kind-mask dispatch, so repeated source catches
are not a prerequisite.

The implemented boundary uses one template-free common payload base; the legacy
runtime Exception factory and typed catch APIs remain usable. The original audit
made no implementation changes. After approval, the measured bootstrap advanced
past exception relationships and stopped in later semantic preparation at 47.97 s,
289.2 MiB RSS and 270 MiB PHP heap. Storage_Cursor widening remains excluded.

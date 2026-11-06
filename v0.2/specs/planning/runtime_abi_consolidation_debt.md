# Runtime ABI declaration and binding consolidation

Doc Status: planning
Date: 2026-11-06

Deferred after the compiler/runtime metadata audit. The current compiler binding catalog
and runtime declaration templates independently describe the same six free-function
helpers: int64 division/remainder, float64 division, integer/byte-span output and checked
entry conversion. Native integration proofs check current agreement. This duplication has
no application preparation-time cost and does not justify a broader ABI redesign now.

The eventual owner should be a runtime ABI declaration catalog shared by public-header
generation and compiler binding generation. Compiler strategies would continue adapting
source values to that ABI. Do not make runtime declarations depend on compiler-specific
lowering strategies or interpret binding metadata during application compilation.

Current semantic bindings are insufficient to generate complete native declarations.
For example, echo's string operand becomes `(const char* data, size_t length)`, with
borrowing for the call's duration. A truthful catalog would need parameter/result types,
arity and applicable ABI/lifetime contracts, not merely a semantic operand identity.

Revisit when additions or changes to runtime helpers make manual alignment recurring work.
Start with the six free functions. Keep runtime implementation bodies, class APIs,
constructors/members and the entry target assertion outside that first slice. Preserve
public include closures, signatures, diagnostics and generated application behavior.
Verify declaration/binding consistency, isolated public-header compilation, native behavior
and incremental recovery across all three flavors. Native tests validate the current
configuration; they do not prove every possible metadata configuration.

Until then, maintain both descriptions together and retain their native integration proofs.
See [audit](compiler_metadata_audit.md) and [binding implementation](../../compiler/docs/runtime_bindings.md).

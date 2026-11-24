# Integrating a helper or library in v0.2

Doc Status: planning

This draft applies the repository's builtin intake procedure to the v0.2 metadata, runtime publication and compiler. Start with one useful operation and its smallest real caller. For a library, also establish its module, external dependencies and archive boundary once; individual operations can reference that decision. [Builtin intake procedure](../../../../specs/builtin_intake_procedure.md)

## Proposed review sequence

| Step | What to decide or do | Evidence for review |
| --- | --- | --- |
| 1. Agree on the source contract | Record the supported flavor names and call forms, parameter types, defaults, conversions, value or reference behavior, return type, failure and boundary cases. Use the existing intake procedure to resolve differences from reference behavior. | Link the owning spec and give a source example with its expected result. Include a case that distinguishes plausible but incorrect behavior. Freeze open semantic choices before implementation. |
| 2. Choose the native boundary | Identify an existing provider or the smallest needed addition. Record the native signature, borrowed inputs, mutable slots, owning or aliasing results, and which part belongs in a compiled implementation or a public inline body. Identify the public header and required module. | Show the intended C++ call and what the caller must include. Reuse a published form when it already implements the contract. |
| 3. Publish the operation | Express the flavor mapping, native target, parameter/result adaptation and declaration provider in the owning runtime catalog. Use the API generator to produce the runtime surface and compiler binding from the same publication. Implement private work in the runtime. | Generate the selected publication and inspect the descriptor and declaration together. For an optional library, verify a consuming app links its declared module and an app without that dependency has the expected availability. |
| 4. Compile a source caller | Preparation resolves the typed operation. Lowering handles the operands at that site, including evaluation count, order, snapshots and lifetime. Request the provider used by the emitted unit. | Inspect the generated call and compile and run it through the normal application path. Test the contract's boundary case and relevant rejected inputs. For a mutable argument or returned alias, write through it and observe the original slot. |
| 5. Check exposure and cost | Compile the public header in isolation. Inspect the runtime headers actually reached by the minimal caller and check that an unrelated caller avoids the added provider. | Record the tests and results. For an existing implementation pattern, link the representative measurement. For a new pattern or a claimed improvement, run the smallest comparison that can settle the question and add the finding to the know-how note. |

Source contracts own meaning. Metadata describes how a published operation can be called. The compiler owns how an individual expression is evaluated. The runtime owns its representation, inline bodies and private dependencies. For the first operation of an optional library, also check a clean build/link with its module enabled and the effect on a minimal core-only build. [Public header boundary](../../runtime_public_headers.md) · [Module workflow](../../../compiler/docs/modules.md)

## Worked example — strpos

- **Contract.** PHP accepts two strings and an optional integer offset. A match at byte zero returns a present integer zero; a miss returns false. The JS and C++ forms return a signed integer with −1 for a miss. Test both cases and the specified offset policy for each exposed form.
- **Binding.** The catalog selects the find provider and flavor-specific adaptation. The PHP caller uses the narrow strpos adapter and its result carrier. The JS and C++ callers can use the integer search provider directly. The search implementation stays in the compiled runtime. [Catalog](../../../runtime/metadata/runtime.json) · [Published boundary](../../runtime_public_headers.md#first-match-byte-search)
- **Proof.** Compile a minimal caller for each flavor and inspect the actual include closure. For PHP, exercise both an immediate false check and returning the carrier to another caller. The existing namespace test checks the header closure; the search fixtures and ABI probe supply behavior and code-generation evidence. [Header check](../../../compiler/tests/app_namespace_native.py) · [Search fixtures](../../../compiler/tests/strlen.php) · [ABI and LLVM probe](../../../tests/strpos_benchmark/README.md)

## What to enforce first

Use existing generator validation for resolvable targets, types and provider dependencies. Use compiled fixtures for semantics and include closure. Keep workload timings as review evidence. Identical machine code settled the strlen spelling question, while a smaller stack frame had mixed CPU effects across the pilots. For timing claims, record the baseline, flags, workload and spread. A timing threshold needs a reproducible signal before it becomes a build gate. [Lowering know-how](lowering_know_how.md)

For the next integration, include the contract link and distinguishing case, native signature and provider, metadata entry, minimal source fixture and header check, and either the existing pattern evidence or the new comparison in the review description. Use that trial to identify any repeated manual check worth automating.

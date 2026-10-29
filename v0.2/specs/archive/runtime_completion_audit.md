# Runtime/generator completion audit

Doc Status: historical

Archived: 2026-10-29. Retained as a dated record; statements about pending work,
validation counts and next steps describe the original milestone, not current status.
Date: 2026-10-28

Objective: metadata-driven operators, checked casts and overloads for numerics,
bool/null, string and generated vectors inside v0.2, with a template-free public
API, explicit construction, optional native interoperability and compiler-owned
source semantics. This audit retains all six slices of runtime_type_operations.md.

## Requirement-by-requirement evidence

| Requirement | Current authoritative evidence |
| --- | --- |
| One native generator, reviewed reuse, no legacy build dependency | tools/api_generator runtime prepare/emit pipeline, independent tool JSON reader/value model, family provenance sections; fresh source-only build and compiler dependency inventory |
| Metadata owns identities, defaults, policy and publication | runtime.json/publication.json; preparation validates supported bindings, resolves ranges/types, dependencies, mappings and aliases before emission |
| Domain-maximum integer rules and explicit exceptions | integer_preparation enumerates type pairs and native ranges, including signed/uint64 rejection; integer_consumer and integer_full_publication execute all widths |
| All planned integer operator forms, checked casts and mutation | integer_all_types covers arithmetic/bitwise, comparisons, shifts, unary, compound and prefix/postfix; signedness extremes and failed write-back are checked; private scalar_semantics owns checks |
| Bool/null semantics and names | value_preparation/value_consumer check bool_t, configured aliases, null distinction, explicit casts and actual short-circuit side effects |
| Float arithmetic and scalar casts | floating_preparation/consumer and full publication check exact-range eligibility, explicit wide casts, IEEE comparison behavior, zero division, finite/range checks and truncation |
| Owned strings and agreed text defaults | string_preparation/consumer and sequence_semantics cover byte length, embedded NUL, copy/move/self operations, ordering, pointers, integer bounds, finite decimal parsing/formatting, locale independence and true/false words |
| Generated vectors and element dependencies | vector_preparation/consumer and vector_publication cover both defaults, additional element families, value reads, explicit set, checked indices, copy/move/reallocation and partial explicit-cast failures |
| Natural C++ conveniences without implicit constructors | runtime_demo, integer/native bridge proofs and vector consumers; exact native int/C-string adapters are selected, unsupported fundamental types fail compile-time concepts/deleted overload checks |
| Template-free public API and include closure | runtime_public_boundary compiles each of seven headers independently and scans declarations; actual generated include graph contains only those generated headers, no private/std headers |
| One public operator surface and private execution | generated numeric/string/vector headers, no public numeric_add/cast helper duplicates; compiled implementations use private scalar/sequence algorithms and standard storage |
| Physical pre-lowering and honest compiler boundary | prepared.json contains resolved conversion/operation facts, source mappings, mutation categories, string/vector methods and explicit cast dependencies; source admission/sequencing/import remain explicitly excluded |
| Selective and deterministic publication | runtime_generation, native_operator_publication, string_publication, vector_publication; reordered request tests and rejected absent/missing dependencies; generation validates before writing |
| Freshness and clean regeneration | generator --check; source-only staged export contains no runtime/generated; normal CMake builds regenerate all artifacts |
| Plain C++ composition deliverable | examples/runtime_demo.cpp combines all families, local float_t alias, short names and optional numeric bridge; CTest checks output Ada: 21; ratio=7 |
| Build requirements | CMakePresets.json: Ninja/Clang/libc++, jobs=8; CMake enforces Ninja/Clang and mold; fresh configure/build and ELF linker evidence |
| No generated or compilation products in Git | v0.2/.gitignore, git ls-files and source-only staged export; generated runtime remains locally usable |
| Exact API/cast and remaining-decision documentation | runtime_api_summary.md and integer/values/floating/string/vector contracts; exclusions distinguish missing future work from implemented behavior |
| Periodic cleanup and slice commits | retired integer-only generator/files and generated scalars.cpp references removed from active owners; common string/vector lifecycle emission; no obsolete parallel implementation or required archive |
| Follow-on hash/mixed/dynamic review requested after vectors | hash_mixed_dynamic_port_review.md identifies protected custom table cores and separate payload families; no unreviewed code import or table-core modification |

## Scope safeguards and limitations

This completes a runtime/API-generator slice, not the source compiler. my-try-cpp
integration, source evaluation sequencing, carriers, arbitrary user types, nested
vectors, public iteration, cross-target ABI packaging and a sealed compiler import
protocol are not claimed. They were excluded or recorded as later decisions in the
accepted plan. Runtime signed overflow remains within native C++'s defined domain;
only specified boundaries/checks are checked. Integer/float range and string parsing
failure behaviors are documented, not described as universal arithmetic safety.

The default prepares eight concrete types, forty scalar/text conversion records,
151 numeric operators (91 core, 60 optional numeric bridge), eight string operators
and four vector comparisons. Deleted rejection declarations are not implementations.
Vector native member adapters do not create new element algorithms. Larger and
smaller publications are independently compiled rather than assuming the default
represents the whole supported registry.

Generated strings/vectors use private pointer ownership, with its documented extra
allocation cost. No throughput or memory-equivalence claim is made. ASan+UBSan
consumer proofs use Clang/libstdc++; the installed libc++ exception-library mismatch
and traced-environment LSan limitation remain explicitly recorded. No libc++ ASan
or leak-sanitizer success is claimed.

The hash follow-on is a reviewed proposal, not an implemented port. It must preserve
Swiss-style core algorithms and route both tagged families through hash-owned
storage without collapsing dynamic values into mixed containers.

## Final verification record

Result: the original runtime/API-generator objective is achieved. Every row above
has implementation and directly scoped proof; no required slice remains open.

A source-only export of staged v0.2 files (no generated or build artifacts) was
configured and built with both presets. All 21 tests passed in Release and all 21
passed with UBSan/recovery disabled. Both compiler dependency inventories contain
no original-repository inputs. ELF .comment for the demo confirms Clang 18 and mold
2.40.4. The generator's final --check succeeds; the demo prints Ada: 21; ratio=7.

The string/default-vector/alternate-vector ASan+UBSan ownership proofs from the
preceding slices apply unchanged; this final slice adds a composition example and
documentation, not runtime behavior. Their known LSan/libc++ ASan limitations remain
as stated above. No unsupported sanitizer or performance claim is added.

Cleanup consolidated the README and removed stale string/vector pending statements.
The final source-only snapshot also verifies that generated code is not needed in
Git. The next implementation work, if requested, is the separately scoped hash port;
its initial review is complete but it has not been implemented.

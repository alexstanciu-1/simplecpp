# Boxed-result validation

Doc Status: supporting
Date: 2026-10-29
Contract: [boxed_results.md](boxed_results.md).

## Evidence

- Full Release CTest: 49/49 passed, including unchanged frozen generator artifact
  contracts, existing boxed families and scalar/typed carrier consumers.
- Focused libc++ UBSan: consumer, freshness, public boundary and publication (4/4).
- Generated consumer passed Clang ASan+UBSan with libstdc++; leak detection was
  disabled, so this is not a leak-sanitizer proof. The test qualifies scpp::error_t
  to avoid the system error_t typedef exposed by that standard-library configuration.
- A standalone client compiled and ran with only copied generated public headers and
  the three required static libraries. No private headers or old runtime were used
  by the client. The final qualified-error test also passed a Release rerun.
- `git diff --check` passed. No metadata, frozen fixtures, private runtime algorithms,
  carrier templates or carrier state-machine changes were required.

`tests/boxed_results/consumer.cpp` keeps assertions active in Release. It proves
successful default-null, explicit null and false payloads; failure/error extraction;
guarded destination preservation; recovery; rejection of the no-error sentinel;
mixed COW and dynamic alias visibility across carrier copy/extraction; independent
binding replacement; moved-from successful-null defaults; self-assignment; and
allocation failures while copying both a payload result and an owned error result.

The public boundary proof requires a standalone template-free include closure and
rejects native construction, cross-family payload/carrier conversion, direct
condition/delegation, mutable extraction references and unrequested nullable_dynamic.
Publication tests cover single-family results, absent-family dependencies, implicit
and explicit extraction policies, duplicate/header conflicts, deterministic ordering
and rejection of other boxed carrier families or nested/unsupported payloads.

## Cost and limits

Default result_mixed/result_dynamic success and default-null extraction allocate
nothing, as checked by the consumer. Explicit payload/error storage and materialized
carrier copies retain the existing wrapper allocation. Mixed and dynamic payload
copies retain their own COW/shared semantics; this is not an independent-container
copy guarantee for dynamic results. No throughput claim is made.

This is the checked-result prerequisite for JSON. The tests use guarded extraction,
not a newly published take helper. Parser/writer reuse, key normalization, traversal
adapters, JSON metadata and source compiler integration remain separate work.

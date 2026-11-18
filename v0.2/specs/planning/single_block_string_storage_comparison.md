# String storage experiment — deferred

Doc Status: planning

Decision: 2026-11-14. Keep the current production string and prioritize language
features and the v0.3 bootstrap. No experimental implementation was adopted.

## Minimum retained findings

Two isolated single-block prototypes were compared with production, native C++ and
Rust on dev002, using Clang/GCC, with/without LTO, and cpp/php/js source flavors.
The second prototype removed construction temporaries, shared a private append
routine and tried one-pass replacement with geometric growth.

- The production object remains 24 bytes with 22 inline payload bytes on the tested
  target. The prototypes preserved that layout and deep-copy value semantics.
- A fresh long string used one allocation instead of two and requested 16 fewer
  heap bytes. Allocator overhead and process RSS were not measured.
- Heap copies improved, and the revised repeated-runtime-append workload improved
  on GCC. This was not a universal improvement: Clang LTO short copying, dense
  replacement and some compatibility helpers remained slower than production.
- Fewer allocations did not guarantee lower peak memory. Replacement contraction
  could retain extra capacity; std::string-returning helpers could add a copy.
- The second experiment passed 60 full-byte fixture profiles and 14 sanitizer
  proofs covering runtime behavior, overflow and partial replacement failures.
  Public headers and generated source stayed equivalent across representations.
- Both prototypes failed the execution-speed adoption gate. Native std::string
  was a useful baseline, not a universal winner; its tested object was larger
  (32 bytes) and its inline capacity smaller (15 bytes).

At the user's request, experiment sources, detailed reports, raw measurements,
builds, temporary toolchains and transfer files were removed. These are historical
findings, not retained reproducible benchmark evidence or a production change.

## If optimization is resumed later

Start a fresh isolated comparison. Investigate a small inline-copy path with a
separate heap slow path, and replacement construction that separates growth from
chunk writes. Recheck value semantics, inline storage, self-append, allocation
failure, overflow and public match-count preservation. Measure execution time,
allocation count and peak live memory against current native baselines; do not
adopt based on allocation savings alone.

Feature priority now moves to [the bootstrap implementation plan](v03_bootstrap_implementation_plan.md),
particularly section 3, “Object relationships — a small AST family.”

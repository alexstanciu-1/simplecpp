# Explicit runtime argument views — 2026-10-25
Doc Status: planning

User approved the five audited runtime-call owners. PHP-only locals adapt values to
the container's declared element type: preparation_owner in CPP_Generator,
runtime_function_definition in both Runtime_Callables registration paths,
required canonical_type_use inside the existing CPP_Initializer_Buffer null guard,
and declaration_node in Callable_Instances. Existing conversion machinery owns
upcasting/checking. No runtime, binding, converter, semantic or prelowering changes.
No container copies or reordered operations.

The production-snippet proof tests/portability/runtime_argument_views.py passes PHP,
v0.1 native, v0.2 ordinary native and v0.2 concrete/prelowered native. It checks
shared mutation visibility through every view, function registration order and
key/value ordering with an absent key skipped. Fixtures provide minimal type
families around the extracted production statements; this is not a native build of
the whole compiler. First fixture conversion rejected top-level named functions;
using supported static fixture methods corrected the harness without source changes.

Host callable_instances, runtime_payload_calls and incremental_preparation pass.
Final bootstrap: 1:49.77 wall, 625588 KiB RSS (610.93 MiB), 592.5 MiB PHP heap.
Whole runner 114.38 seconds includes conversion/orchestration. Preparation fails;
16 diagnostic categories remain. First is now data construction without a known
concrete target. Runtime-call matching remains later; this slice establishes the
five audited source boundaries, not elimination of the whole category.

The preceding foreach_literal_audit identifies bare foreach literals as candidates
for the next construction failure and proves typed locals in preparation/emission.
Stopped before those fixes or any further semantic changes. Native compiler and
second-generation acceptance remain outstanding. revision.txt records the parent
revision and source_hashes.json identifies the tested source snapshot.

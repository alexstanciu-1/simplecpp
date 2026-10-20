# Implementation-group assembly extraction — 2026-10-16
Doc Status: supporting

The C++ backend now assembles source-owned implementation groups explicitly.
`cpp_implementation_group` in `structures.php` contains identity, output path,
assembled body text, include requirements, and forward declarations. It is transient
backend data, not a retained semantic owner, cache entry, or shared-library group.

`CPP_Implementation_Groups` owns the existing source membership policy and ordered
assembly of dispatch definitions, static storage, signatures, constructor initializers,
and bodies. It consumes existing prepared owners and rendered fragments; it does not
perform preparation or acknowledge source changes. Empty implementation groups are
omitted exactly as before. Trait methods retain the consumer type's source placement.

`CPP_Artifacts::implementation()` materializes the group's preamble and text using
the existing described-artifact protocol. `CPP_Generator` still renders fragments,
assembles declaration headers, sequences implementation assembly, and emits the
unchanged entry/helper artifacts. Include insertion order, body order, paths, and
artifact identities remain unchanged. No sorting, regrouping, splitting, scheduling,
Ninja/Mold integration, or new cache policy is introduced.

## Focused proof

Before editing baseline `aa4683e3`, captured outputs from `project_cpp.php`,
`inheritance.php`, `abstract_dispatch.php`, `traits.php`, and `property_defaults.php`.
Repeated the same five suites after extraction through the shared 12-slot runner.
All five suites pass and all 190 emitted files match byte-for-byte (identical names
and SHA-256 sets). Project fixtures compare encoded artifact bundles, including
identity/path/kind/include metadata; the other suites also export generated C++
using their existing fixture formats. This covers source/module placement, header
requirements, dispatch, trait consumers, static storage, and constructors/defaults.
Execution manifests contain output-root paths and are excluded from byte comparison.

[Per-file hashes and counts](implementation_group_assembly_20261016.json) preserve
both the baseline and candidate's common output fingerprint. To reproduce, invoke
each of the five suites as `php compiler/my-try/tests/SUITE.php OUTPUT_DIRECTORY`
through the shared runner in separate baseline/candidate checkouts, then compare
the emitted file sets excluding `executions.json`. Output directories must exist.
The checks used PHP CLI fallback because sandbox socket creation prevented PHP-FPM
startup. No C++ compiler invocation, native compiler rebuild, performance benchmark,
or compiler incremental/MT investigation was performed for this extraction.

Automatic unit splitting and multi-file classes remain
[CH15 debt](../catalog/15_blocking_debt.md#ch15-cpp-group-split-01--automatic-implementation-unit-splitting).

# Namespaces, imports and multi-file compilation — 2026-10-17
Doc Status: supporting

Review only; no repository files changed. Current checkout HEAD 8471657979ff4e2c621dc79fa8a10a8643a39558, with pre-existing working-tree changes retained. PHP-hosted compiler, Clang 18.1.3. Batches 15–17 use the shared 12-task runner in CLI mode.

## Method

run-multi.py accepts a mapping from fixture names to source-file maps. probe-multi.php explicitly selects main.phs as the entry, calls sync(), prepare(), and cpp(), and records successful preparation separately in prepared.txt. Encoded artifacts are materialized without flattening translation units. Every emitted translation unit is compiled to an object; successful fixtures are linked against the repository fixture runtime object. No generated program is executed. This checks both Clang compilation and linker diagnostics, unlike the preceding syntax-only batches. The compiler itself was not rebuilt natively.

## New finding: cyclic generated type headers fail for mutually referring container/callable payloads

main.phs:

    $a = new A();
    return 0;

a.phs:

    class A { vector<B> $bs; }

b.phs:

    class B { vector<A> $as; }

Preparation and emission succeed. Clang rejects entry.cpp with use of undeclared identifier 'record_A' (or the encoded qualified name in the namespace variant).

The emitted A header includes B's header before declaring A, and B's header includes A's header before declaring B. #pragma once breaks the inclusion recursion, but neither supplies the missing forward declaration. The vector element is a shared handle to the other class; this is not a recursive inline object layout.

This is an accepted-source/backend-generation defect, not evidence that the source program is language-invalid. Making preparation reject a valid relationship would need an explicit temporary support restriction, not a claim that source namespace semantics forbid it.

Evidence:
- batch16/mutual_containers: namespaced, separate source files.
- batch17/mutual_vector_min: minimal unqualified separate-file example above.
- batch17/mutual_hash_min: replace vector with hash.
- batch17/three_cycle_vector: A -> B -> C -> A.
- batch17/mutual_vector_single_file: both declarations and entry in one source file, same failure.
- batch17/mutual_callable: A has function<B()> $f, B has function<A()> $f, same header-cycle failure.

Treat these as one header-dependency family rather than six independent defects. Each directory contains original .phs inputs, prepared.txt, artifacts.json, cpp/ and result.json. Diagnostics and commands are preserved in batch logs/.

Successful controls (compile AND link):
- Mutual direct class handles: class A { B $b; }, class B { A $a; }.
- Self-referential vector: class A { vector<A> $as; }.
- Acyclic vector dependency: class A { vector<B> $bs; }, class B {}.
- One container edge and one direct handle edge: class A { vector<B> $bs; }, class B { A $a; }.

Ownership: 05_backend/cpp/types.php:75–105 requests complete definitions for callable signatures and template arguments. The source nominal mapping records includes for complete requirements (around line 132). 05_backend/cpp/artifacts.php:45 emits includes before its declaration section and suppresses a forward declaration when the full header is included. The declarations required to break these accepted cycles are therefore unavailable at the point of use.

The conservative template-payload policy is already documented in docs/planning/incremental_cpp_artifacts.md around lines 257–260. This review supplies a concrete compilation consequence; it does not authorize changing incomplete-type assumptions for every runtime template. No fix or generated-header workaround was applied.

## Existing preparation gap reconfirmed across files

main.phs: return 0;
a.phs: namespace Lib; class C {}
b.phs: namespace Lib; class C {}

Preparation succeeds, then emission fails with Duplicate C++ artifact identity or path. Evidence: batch15/duplicate_type. This is the previously reported duplicate-class gap in cross-file form; it does NOT reach Clang and is not a new independent finding.

## Import/name-resolution checks

The sampled invalid programs rejected before emission:
- Missing imports, including unused imports in non-entry files.
- Wrong-kind imports; function aliases used as types.
- Different imports bound to the same alias, grouped-import conflicts.
- Alias collisions with namespace declarations in the same body, another body, or another file.
- Import environments leaking into another file/body or aliases treated as re-exported definitions.
- Missing global/parent-namespace fallback and namespace aliases not supported by plain use.
- Duplicate functions/constants, cross-file constant cycles, inline record cycles and recursive reference-return cycles.
- Cross-file signature/access/interface mismatches, unknown property types and final-base extension.
- Executable statements in non-entry files or multiple executable namespace bodies.

Selected valid controls compile and link:
- Same leaf names in different namespaces and same source basenames in different directories.
- Qualified-name/underscore and escape-like spelling combinations.
- Same alias targeting different functions in different files of one namespace.
- Repeated idempotent imports, a plain import exposing both type/function identities, constant aliases and runtime aliases.
- Cross-file recursion, inheritance, traits, static storage, struct and enum return types, closure factories and forward constants.

Unicode/emoji identifiers were rejected during parsing and are not evidence of semantic validation. A constant-based parameter default and a source static return-type probe hit existing preparation support limits and were not counted as name-resolution defects. Compilation/linking success does not prove execution semantics; no incremental edit/recovery audit was performed.

## Reproduce

    MY_TRY_PHP_EXECUTOR=cli python3 specs/planning/results/compiler_diagnostic_review_2026_10_18/run-multi.py cases15.json rerun15
    MY_TRY_PHP_EXECUTOR=cli python3 specs/planning/results/compiler_diagnostic_review_2026_10_18/run-multi.py cases16.json rerun16
    MY_TRY_PHP_EXECUTOR=cli python3 specs/planning/results/compiler_diagnostic_review_2026_10_18/run-multi.py cases17.json rerun17

Use fresh output directories. All logs include exact compiler/linker command lines and exit codes.

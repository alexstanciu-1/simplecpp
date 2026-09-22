# Portable PHP implementation profile
Doc Status: planning

The [compiler rewrite reset](../planning/compiler_migration/rewrite_reset.md) preserves
these capability proofs. Active rewrite readiness is now fourteen input/tokenization files;
see [manifest reading](project_manifest_reading.md).

For writing code, start with the [authoring guide](authoring_guide.md) and the
project-local [portable-PHP skill](../../.agents/skills/simple-cpp-portable-php/SKILL.md).
Use the [validation workflow](validation_workflow.md) for the ready-set PHP loop
and explicitly selected native proofs.

Collection adapters and bounded typed callbacks now have [PHP/native proofs](collection_helpers.md)
on the prior candidate `08c8206a`; selected unreleased target is now `9b4b33f3` (#240). [Process/lock candidates](os_helpers.md)
pass host lifecycle tests and the prepared native facade comparison on `2f0d667f`,
which fixes the target alias-signature bug.

Current checkpoints: [converter foundation](first_slice.md),
[update context](compiler_context_slice.md), [token vocabulary](compiler_token_slice.md),
[step contracts](compiler_step_slice.md), [path spelling](compiler_path_slice.md),
[string-byte correction](compiler_string_bytes_slice.md), and
[parser nodes](compiler_nodes_slice.md), and
[role views](compiler_role_views_slice.md), and
[expression cursors](compiler_cursor_slice.md), and
[snapshot/tree storage](compiler_storage_slice.md), and
[decimal range checking](compiler_decimal_slice.md), and
[guarded configuration/exceptions](compiler_exception_slice.md), and
[fixed tool-service requests](compiler_tool_contracts_slice.md), and
[fixed source read tasks](compiler_read_tasks_slice.md), and
[discovery records](compiler_discovery_records_slice.md), and
[source snapshot owner/indexes](compiler_source_set_slice.md), and
[source-scan result records](compiler_scan_result_slice.md), and
[source-scan reconciliation](compiler_scan_join_slice.md), and
[snapshot acceptance](compiler_snapshot_join_slice.md), and
[fixed source-read selection](compiler_read_selection_slice.md), and
[token storage](compiler_token_store_slice.md), and
[token acceptance](compiler_token_join_slice.md), and
[token selection](compiler_token_selection_slice.md), and
[completed file frontends](compiler_frontend_record_slice.md), and
[frontend storage and acceptance](compiler_frontend_join_slice.md), and
[binary syntax](compiler_binary_syntax_slice.md), and
[parser selection](compiler_parser_selection_slice.md), and
[logical syntax comparison](compiler_syntax_comparer_slice.md), and
[quoted-byte decoding](compiler_byte_literals_slice.md), and
[semantic type-reference records](compiler_type_references_slice.md), and
[native project identity/roots](compiler_native_project_slice.md), and
[manifest snapshot export](compiler_manifest_record_slice.md), and
[source directory scanning](compiler_source_scanner_slice.md), and
[runtime-preparation symbol spelling](compiler_preparation_symbols_slice.md). 39 production files passed before the rewrite reset; current active readiness is fourteen input/tokenization files, and the
concept below includes future work. `compiler/portability.json` is the source set.

Catalog for the next design discussions: [strict features, libraries and PHP
conversion proposals](catalog/README.md).

Scalar value records and explicit local aliases: [contract and native-aware guidance](value_records.md).

Current adoption assessment: [consolidation and debt](debt.md).
Start with its [current constraint summary](debt.md#current-constraint-consolidation)
for coder guidelines, converter errors, open contracts, target work and tooling debt.

[Container method returns and nonpublic scalar fields](container_returns.md) are now supported,
with the selected target limitations recorded explicitly.

[Map probes and iteration](map_iteration.md) now cover keyed `isset` and by-value
`foreach`; generated PHS uses native type syntax.

[Recursive container annotations](container_annotations.md) now support nested vectors and typed maps.

Explicit [method signatures](method_signatures.md) now support scalar/named boundaries and void returns.
The [collection snapshot proof](collection_snapshots.md) covers explicit changed-row
copies, shared unchanged rows and independent scalar-vector fields.

Latest tool checkpoint: [restricted traits and incremental declaration/token cache](traits_and_incremental_index.md).

Current string authoring: [UTF-8 text helpers and explicit bytes](utf8_text_contract.md).
Managed `strlen` now counts code points; byte-sensitive code must use `string_byte_len`.

Authoring-first review: [compiler shape audit](../planning/compiler_migration/authoring_shape_audit.md),
separating source rewrites, unresolved contracts, parser gaps and target work.

## Purpose and boundary

Author the Simple C++ compiler in ordinary executable PHP, using explicit metadata
and controlled library contracts to support later automated or semi-automated
conversion to PHP++. PHP is the implementation source of truth; generated PHP++
is derived output. Generated files are not independently maintained.

This profile describes the compiler's implementation language. It does not define
the PHP++ language accepted by the compiler and does not promise general PHP
compatibility. Existing Simple C++ semantic specs remain authoritative for the
conversion target; proposed target extensions must be decided in their owners.

The immediate benefit is a fast PHP development/test loop and more reliable AI
PHP authoring while native compile times are expensive. Native conversion proofs
remain necessary, but are not required after every PHP edit.

## Agreed conceptual model

Portable PHP is executable PHP written with Simple C++ intent. Annotations and a
small PHP support library express that intent. Conversion preserves the intended
algorithm using PHP++ representations; tests validate the required behavior.
Exact PHP runtime compatibility is not a goal. Ordinary PHP operations remain
useful approximations where sufficient; the PHP library may be slow or sequential.

The converter is a separate tool written in PHP. It uses `token_get_all()` and
its own very simple structural AST. It does not depend on the compiler frontend,
infer whole-program types or perform semantic analysis. A later agreed extension
indexes declaration locations and expands direct same-namespace traits; ordinary
expression/type resolution remains excluded. Conversion uses locally observed syntax, explicit annotations and recognizable support-library
operations. If these are insufficient, improve the source annotation or library
surface instead of introducing semantic resolution into the converter.

For example, the agreed local annotation direction is:

```php
$count /** uint32 */ = 10;
```

which conveys enough local intent to produce:

```text
$count uint32 = 10;
```

PHP still executes with its own integer representation. The annotation does not
emulate unsigned arithmetic in PHP. Boundary behavior is tested on the target
where necessary. The complete annotation grammar, other metadata forms and library
spellings remain to be specified; the first slice implements this annotation; broader metadata remains open.

Local validation checks supported structure and required metadata, not whether a
value returned from another file really satisfies an annotation. PHP tests, PHP++
compilation/STAN and native tests supply that evidence. Every portable file uses one central function policy and its tool-maintained import
block. Bare calls and explicitly selected implementations use that policy; authored
code cannot rebind names or bypass framework-owned functions through global calls.
Unsupported syntax or missing required information
receives a source diagnostic rather than a guessed conversion.

Semi-automated conversion means an actionable migration report and correction of
the PHP source or conversion rule followed by regeneration. Generated PHP++ is not
maintained through permanent manual patches.

## Agreed authoring discipline: structures and arrays

Prefer defined structures with named, explicitly typed fields as much as possible,
instead of ad-hoc array records. Express mutable collections and snapshot changes
through defined owners and visible operations.

Ordinary PHP arrays belong in non-hot parts of the code where usage has two phases:
set up the data, then only read it. This is not the default representation for
mutable algorithm state or performance-critical paths. A read-only container does
not itself make referenced objects immutable; the author must make intended sharing
and mutation clear.

Preserve the behavior the **algorithm** relies on, rather than fragile incidental
PHP copying or aliasing behavior. Unit tests should prove intended changes,
unchanged state, and shared or independent identities where those matter.

These are code-writing, review and testing responsibilities. The converter has no
say in these design choices: it does not classify hot paths, enforce read-only
phases, infer ownership/copying intent, or impose this discipline. It translates
the explicitly authored supported types and operations. No new container syntax,
runtime facility, or automatic class-to-value-struct conversion is implied here.

### References: be careful; migrate gradually toward indexed records

By-reference parameters are parked as a **be careful** concern, not banned and not
currently queued for a general Simple C++ reference implementation. Review actual
mutation and aliasing when adapting the owning algorithm, with behavioral tests.
The production audit found 25 reference parameters in 23 methods across 15 files:
19 array parameters and six integer parameters. Most inspected arguments are local
variables; three calls pass object fields. No nested array-element reference
arguments were found at the inspected sites.

The agreed optimization direction is to organize compiler data as records in owned
storage and access those records by index. Migrate gradually as the relevant owners
are adapted; do not start a wholesale storage rewrite now. Keep record selection
and updates visible, and preserve the algorithm's required identity and snapshot
behavior. Existing logical IDs must not silently become physical storage offsets.

This is an authoring and compiler-storage direction, not converter inference or
enforcement, and does not add work to the target-feature batch by itself.

## PHP support library and native-only tests

The framework includes libraries missing from PHP, not only compatibility wrappers.
All namespaces are lowercase: `scpp` owns target-specific facilities and libraries;
`scpp\compat` owns PHP-like functions requiring different target behavior. Ordinary
PHP builtins remain in use when sufficient. The same tool-maintained function imports apply to every file; the current global
script and single-namespace slices implement this policy.

The small PHP library makes target-oriented operations executable during PHP
algorithm development and gives the converter explicit locally recognizable forms.
It need not reproduce the complete Simple C++ runtime or its performance.

For example, a PHP worker API can execute work sequentially while its PHP++ form
uses threads. Fixed inputs, private outputs and explicit joins support both forms.
Tests that cannot meaningfully run in PHP, including actual multithreading tests,
run after porting. We do not build PHP thread emulation to make those tests pass.
The same separation applies to target-specific suspension, synchronization and
lifetime behavior. PHP tests cover the algorithm where PHP is a useful approximation;
native tests cover the capabilities only the target provides.

## File-to-file and incremental conversion

Preserve source organization: one authored PHP file maps to one PHP++ file in a
separate output tree, retaining relative directories (normally `.php` to `.phs`).
Do not reorganize the compiler during conversion. Direct trait methods are the
agreed exception to declaration-local output: they expand into consuming classes,
while trait files retain corresponding outputs without native trait declarations.
Explicit library support artifacts have their own owners.

Ordinary source edits should require conversion only of changed files. A changed
callee does not require re-converting unchanged callers because the converter
does not resolve ordinary call bindings or types. A changed trait does invalidate
its direct consumers through the declaration index. PHP++ analysis and native compilation still
own their real dependency invalidation; conversion reuse does not imply native
object reuse.

Keep incremental bookkeeping small: record source/output correspondence and input
fingerprints. Converter rules, configuration and annotation/library mapping versions
also affect output; invalidate the relevant conversions when those inputs change.
Distinguish mapping changes from PHP-only library implementation changes. Track
removed/renamed source files so their owned generated outputs do not remain stale.
Never delete unrelated files from the output tree.

Compare final output before publication and preserve unchanged bytes/timestamps.
Report conversion failures without treating stale output as current. The first
implementation must prove no-op conversion, a one-file edit, removal and rule-change
invalidation; it does not need a semantic dependency graph or an incremental AST
engine. Exact manifest and publication details are not yet selected.

## Ownership

- `specs/portability/`: the portable profile and per-feature conversion contracts.
- `tools/php_portability/`: validation, conversion and executable support needed by
  portable PHP, with internal placement decided when concrete owners exist.
- `tests/portability/`: PHP/native behavioral witnesses and rejection fixtures.
- `compiler/`: a consumer of the portable profile, organized by prototype processes.
- `.agents/skills/simple-cpp-portable-php/`: authoring guidance derived from these contracts and checks.

Keep local syntax/annotation checks and conversion consistent inside the small
portability tool. Its AST is independent of the compiler AST; it must not grow a
second semantic compiler or require compiler analysis to convert a file.

## Scope and explicit alternatives

Build the framework incrementally from actual compiler needs. GUI/WebView libraries
are not required for this effort. Dynamic PHP behavior is discouraged; unsupported
dynamic constructs remain rejected rather than motivating symbol resolution.

An explicit `if (SCPP_NATIVE)` native-comment / PHP-fallback escape hatch is an
accepted design direction when ordinary shared code or library adapters are
impractical. The converter selects the PHP++ payload structurally; PHP executes the
fallback. See [the proposed contract](catalog/conversion.md#8-incremental-scope-and-native-escape-hatch).
It is not implemented yet. Keep native source embedded in the authored PHP and test
the two paths as appropriate; do not maintain manual patches to generated output.

## First decisions, in order

1. Representation: typed sequences/maps, structured records, shared objects,
   assignment/copy behavior, mutation and parameter passing.
2. Metadata: native PHP types, generic/local types, doc comments versus attributes,
   validation of contradictions, and source diagnostics.
3. Expressions and control flow: numeric behavior, null/false/error distinctions,
   evaluation order and supported PHP constructs.
4. Libraries and composition: approved calls, imports/autoloading, IO and errors.
5. Lifetimes, tasks and workers: explicit resources, observable scheduling rules
   and what each execution environment can actually prove.

For every decision record PHP spelling/approximation, locally available conversion
facts, intended PHP++ operations, known differences and the tests needed. Distinguish
shared algorithm tests from native-only tests; do not require exact PHP semantics.

## First vertical proof

Select one small real compiler component after deciding its representation needs.
A source-location component or tokenizer is a candidate, not a committed choice.
Run its PHP tests, validate portability, convert it, compile/run PHP++, and compare
results plus observable mutation/identity where relevant. Include a rejected
nonportable use and useful source attribution. Native build failures are blockers,
not evidence of a successful port.

## Non-goals for the initial slice

General PHP conversion, importing the whole prototype, LLVM/native backend work,
full self-hosting, arbitrary shared-state threads, and converting the existing
product for backward compatibility. Symbol resolution and whole-program inference
are excluded from the converter, not deferred converter features.

[Source discovery](source_discovery.md) supplies canonical path policy and fresh
directory selection, with measured native correction cycles.

[Verified source reads](verified_source_reads.md) adds checked snapshot ingestion
and owned byte buffers, with 40 PHP/native outcomes and native cycle accounting.

[Tokenizer](tokenizer.md) adds 304 PHP/native outcomes, reusing 41 scans from the
existing tokenizer unit and preserving byte-based compact token rows.

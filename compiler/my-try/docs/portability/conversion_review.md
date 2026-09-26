# Model conversion review
Doc Status: planning

## Current status

The full compiler now builds and runs natively on the PR #244 candidate plus
committed toolchain fixes, with normal STAN enabled. All 142 PHP/native comparisons
passed: 48 valid programs also compiled and executed, and 94 invalid inputs matched
PHP rejection messages and recovered. Repeated compilation and retained output
identity passed. See [adaptations and limits](native_adaptations.md) and
[validation evidence](../../../../specs/planning/compiler_migration/results/my-try-native-success-01/README.md).

The verified target pin is unchanged. STAN still reports 159 advisory errors and
33 warnings, with zero build-blocking diagnostics. This proves the tested compiler
slice, not exhaustive language support or a release-ready packaged CLI.

| Area | Status / remaining work |
| --- | --- |
| Static Model fields | Declaration and literal/self access have focused PHP/native proof; see [static fields](../../../../specs/portability/static_properties.md). Preserve shared roots and reset behavior. |
| Required fields | Omitted initializers are supported; assign before reading/publication. See [required fields](../../../../specs/portability/required_fields.md). STAN may require constructor assignment rather than a separate populate method. |
| Nullable parameters/reset | Explicit nullable scalar/named method parameters and null defaults are supported; see [nullable parameters](../../../../specs/portability/nullable_parameters.md). file.tokens now initializes/resets to null. The PHP initialization/nullability audit is complete; native repeated-compilation/recovery checks now pass for the tested slice. |
| Collection bindings | Explicit Storage<T>/Keyed_Storage<T> declarations and annotated construction now convert. PHP identity and PHS spelling tests pass; native compiler parity now passes for the 142 tested cases. |
| Native object identity | Start with automatic shared_p<T> elements. Reads alias records; replacement/removal preserve previously retrieved handles. Ownership tags do not implement weak references or cycle reclamation. |
| Remaining PHP boundaries | Review object-identity maps, nullsafe access, policy-map initialization, payload narrowing and host APIs individually. Historical checker failures are not a complete current support matrix. |

## Next conversion steps

For nested cross-file collection operations, an explicit local receiver is now
native-proved: `$children /** Storage<ast_node> */ = $body->children;` then
`$children->append($node)`. The same pattern works for Keyed_Storage and preserves
membership and record aliases. Direct property access failed the matching C++
comparison. See [the two-file proof](../../../../specs/planning/compiler_migration/results/nested-storage-locals-01/README.md).
The [source adaptation checkpoint](../../../../specs/planning/compiler_migration/results/typed-storage-access-01/README.md)
applies this at affected receiver boundaries. The full native validation now passes with these receiver boundaries.
Continue using explicit source boundaries before adding generator or STAN inference.

Next, review the documented adaptations and advisory analysis diagnostics, then
integrate the accepted candidate toolchain through the normal release/pin process.
Keep host reporting and process execution outside the converted compiler core.

Preserve shared graph identity, first-use external-target order, source/AST purity,
old results after worker reuse, and independent output operands. Native short-circuit
and byte-helper rules need explicit review during adaptation. No new compiler
features, rollback machinery or inline-layout redesign is part of this work.

The latest collection migration passed PHP lint for 36 files, 19 LLVM fixture
executions, 28 call executions and sample exit 9. Preparation reuse/shared-target
identity also passed. These are PHP compiler regressions, not native compiler proof.


## Conversion-only inspection after initialization audit

The [latest 26-file probe](../../../../specs/planning/compiler_migration/results/my-try-conversion-01/README.md)
converted two record files and recorded the first rejection in each of the other
24 files. The full conversion failed without publishing output. Saved partial PHS
was inspected only; no native compile was requested or performed. This is a historical
diagnostic checkpoint, superseding earlier source-hash evidence for these
files without claiming exhaustive feature coverage.


## First source-adaptation checkpoint

[LLVM formatting adaptations](../../../../specs/planning/compiler_migration/results/my-try-conversion-02/README.md)
bring diagnostic acceptance to 5/26 files. Names use existing byte helpers; text
writing and function emission avoid raw array joins/interpolated strings. Host PHP
checks passed and all 19 LLVM fixture outputs remained byte-identical. This pass
performed no native compilation; accepted syntax still needs dependency/binding
and native verification. The framework loads from host boot, outside converted input.


## Temporary Storage projection

Use `php compiler/my-try/tools/conversion_probe.php NEW_DIRECTORY --fake-storage` from the repository
root to inspect other blockers while native bindings are developed. It alters only
copied type tokens/annotations and marks all partial output DO NOT BUILD; it does
not implement or validate Storage semantics. Never compile or publish its generated
PHS. Real compiler code continues to execute against the actual PHP collections.

The [first projected checkpoint](../../../../specs/planning/compiler_migration/results/my-try-fake-storage-01/README.md)
accepts 13/26 files after independent tokenizer, policy-map and struct-preparation
adaptations. PHP behavior checks passed, including all byte values and unchanged
LLVM text. Projected acceptance is distinct from real-binding conversion readiness.


## Earlier real binding conversion checkpoint (before payload casts)

The real (unmodified) 29-file input now has 27 structurally accepted files and two
rejections, both `instanceof`: `03_parse/syntax.php` and `05_backend/llvm/prepare.php`.
The count includes two explicitly omitted host classes and three traits validated
and expanded into their consumers. It is not a complete generated compiler.

Storage construction now states its element type explicitly, for example
`new Storage /** Storage<token> */()`. The converter supports the corresponding
fields, locals, constructor/method parameters and returns. Filesystem loading uses
shared fs helpers; byte spelling, decimal limits and LLVM joining use small named
helpers. Compiler execution is silent; Host_Report/main own presentation and native
sample execution. `@scpp-no-export` marks the two host workers.

Run the current diagnostic without fake Storage:

```sh
php compiler/my-try/tools/conversion_probe.php NEW_DIRECTORY
php tools/php_portability/convert.php NEW_DIRECTORY/source NEW_DIRECTORY/complete --stats
```

The second command currently rejects and publishes no complete output. Partial
output is only for inspection. The current target pin is unchanged; no native
compilation was performed. See the saved real-binding checkpoint in
`specs/planning/compiler_migration/results/my-try-real-storage-01` at repository root.

### Decisions recorded at that checkpoint

* AST: `ast_node.specialization` is `?node_structure`. Consumers access concrete
  fields through it. Replacing the instanceof predicate alone does not establish
  safe typed access. Keep the graph and add a proved checked narrowing operation
  (recommended), or redesign the payload representation. The latter touches AST
  records, parser factories, analyzers and LLVM consumers and needs confirmation
  under AGENTS.md before a broad refactor.
* Identity indexes now use explicit `hash<Value, shared<Key>>` bindings, with
  SplObjectStorage only as the executable PHP carrier. Existing native hash supports
  shared identity keys; no new native container was needed. See
  [object hashes](../../../../specs/portability/object_hashes.md) for publication/copy
  discipline and the deliberately bounded supported operations. PHP/conversion and
  native type mapping pass; native execution remains untested.
* Native review after those choices still includes explicit cross-file local types,
  nullable extraction, enum-name access and safe dependent guards. Conversion alone
  cannot prove these. No inline-layout work is included in this pass.

Follow-up binding audit: Storage interface signatures are deliberately still
rejected, matching the unproved generic-interface boundary. Concrete class/trait
methods and constructor signatures are covered by the focused Storage proof.


## Current: complete PHP-to-PHS conversion

All 29 implementation files now convert together through the normal atomic
converter. An immediate second conversion reuses all 29 outputs. Host_Report and
Native_Runner are explicitly omitted; the three traits are expanded into their
consumers. This is the full implementation conversion, not a fake-Storage projection.

The published output is `compiler/my-try/build/portability-01/phpp` from repository
root. Its sibling source/ directory and source_hashes.json identify the exact input.
Saved evidence is in specs/planning/compiler_migration/results/my-try-complete-01.

AST payload consumers now use typed Syntax_Nodes accessors backed by checked object
casts. Native instanceof parsing/lowering was repaired; interface records remain
shared and the graph is unchanged. Object-key indexes bind to native hash. The
remaining host integer-limit spelling uses the existing qualified PHP_INT_MAX
runtime constant through string concatenation.

Validation: PHP lint, model/AST/storage/tokenizer/LLVM-text behavior, the 19 LLVM
fixtures, converter regressions, and focused Storage/hash/cast proofs. Native C++
was generated and inspected for the small cast fixture only. No native compilation
or native execution was performed.

Next stage: combine the Storage source-binding delivery with these native cast
changes, then verify target typing, nullable extraction, cross-file collection
metadata, enum-name access, exception transport and dependent guards. The target
pin has not been changed. Successful PHS conversion does not prove a native build.

# Model conversion review
Doc Status: historical

Archived 2026-09-29; superseded by the [active documentation](../README.md).

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


## Superseded conversion/retry ledger — 2026-09-29 to 2026-09-30

Historical snapshot of the former portability status page; its blockers and suggested
STAN commands are superseded by [current status](../portability/conversion_review.md).

## Recorded checkpoints

- Earlier full native builds passed bounded PHP/native comparisons on a PR #244
  candidate plus overlays. Those counts describe earlier source shapes, including
  AST designs that have since been replaced. They do not certify the current tree.
- The retained-C++ checkpoint exposed missing Storage/snapshot tooling; integration
  restored those capabilities. Bounded inference fixes reduced the subsequent
  normal STAN build findings to 17, involving delegated/staged initialization.
  No full compiler execution was reached in that recorded attempt.
- The specialized-AST migration preserved the proposal and added bounded native
  proofs for accessor covariance, shared-owner access, trait fields and typed lazy
  iterators. It did not establish whole-compiler native parity.
- Subsequent appended-token, collection and preparation cleanups have focused PHP
  evidence. There is no current whole-compiler native certification in these docs.

Historical details, toolchain identities and logs:
[build checkpoints](../archive/native_adaptations.md),
[migration audit](../archive/specialized_ast_migration_audit.md),
[earlier conversion review](../archive/conversion_review_checkpoints.md).
Temporary `/tmp` evidence paths in those records may no longer exist; retain their
source/toolchain qualifications when interpreting them.

## Latest attempted whole-compiler check — 2026-09-29

Source/toolchain checkout: `bfd129588c5a06d0740c7b3b220174fad57a062e`.
The normal `tools/native_validate.py` workflow stopped at conversion:
`02_tokenize/buffer.php:25: unsupported syntax: +=` (`$row->offset += $offset`).
STAN, C++ compilation, native compiler execution and parity were not reached.
This is the first reported blocker, not an exhaustive list. Compound assignment
also occurs in token cleanup; review the converter capability rather than assuming
that earlier native checkpoints cover the appended-token implementation.

Host validation: all 31 PHP test files passed. The full runner stopped at its source
style gate (14 layout differences and three missing purpose comments). A separate
continuation of the remaining checks passed lint for 103 PHP files, 19 LLVM, 28 call
and 73 C++ S2S program executions, plus sample exit 9. The full suite remains failed
on style. Evidence: `/tmp/my-try-all-php-20260929-bfd12958/summary.json` and
`/tmp/my-try-full-remaining-20260929-bfd12958/summary.json`.

Attempt logs: `/tmp/my-try-native-20260929-bfd12958/logs/`; the runner retained
source hashes and candidate toolchain hashes. No source workaround, STAN bypass or
verified-target update was made.

### Converter follow-up — additive assignments

The converter now preserves `+=`/`-=` without duplicating receiver/index evaluation.
Focused PHP conversion tests and the ordinary-integer native proof pass with normal
STAN (`/tmp/my-try-additive-ordinary-20260929/`). The compact-field variant exposed
missing runtime overloads for `uint32 += int` and `uint32 -= int`; see the failed native proof at
`/tmp/my-try-additive-proof-20260929/`. The user chose a `uint32` offset, not wider runtime overloads. Token_Buffer now
checks the full range before narrowing the delta; focused token tests pass. The
updated native fixture uses `uint32` deltas and preserves single receiver/index
evaluation. Its normal STAN-enabled build and PHP/native execution passed at
`/tmp/my-try-additive-uint32-20260929/`. No runtime behavior changed.

Whole-compiler retry (`logs-2` in the attempt directory above) passes the additive
syntax and now stops at `03_parse/parser.php:513`, `isset($function->body)`: the
converter supports keyed probes, not required-property initialization probes.
That probe is now replaced by explicit `has_parsed_body()` / `set_parsed_body()`
construction state; failed replacements retain the established body. Focused parsing,
AST and preparation-recovery tests pass. Retry `logs-3` advances to
`03_parse/structures/abstractions.php:103`: `abstract public function` is rejected
because the converter expected visibility before the abstract modifier. That order
restriction is now removed: both forms normalize to the same declaration and focused
inheritance/method-signature conversion tests pass. Retry `logs-4` advances to
`04_analyze/collect/structures.php:136`, `parent::__construct($collection)`:
`expected literal type name`. The converter now preserves literal parent method/constructor calls. Retry
`logs-5` reaches `04_analyze/prepare/worker.php:129`, another
`isset($function->body)` requiring the existing parsed-body query. Both remaining
preparation probes now use `has_parsed_body()`. On 2026-09-30, conversion of all 66
compiler/driver files succeeds in `/tmp/my-try-conversion-20260930-body-state`
against `d352b76e` plus this two-caller change. Focused preparation-recovery and
parse/collection tests pass.
The isolated parent-call proof in `/tmp/my-try-parent-proof-isolated-20260929`
exposes a separate generator defect: constructor extraction rejects the IR object
payload because its guard expects an array, leaving `Base::__construct(...)`
in the C++ body. The approved generator fix now accepts the expression object.
The same proof passes conversion, normal STAN, native build and PHP/native execution
(`10:2`) in `/tmp/my-try-parent-proof-fixed-20260929`, against `cf4d4d9d` plus
the extractor fix. No runtime changes were needed.
Whole-compiler native compilation/execution remain unreached. The 2026-09-30
STAN attempt (`logs-6`) reports 68 initialization checks, six unresolved calls,
one override mismatch and one LLVM enum diagnostic. The user authorized bypassing
STAN for native investigation. After the parser constructor/scope/type cleanups,
conversion passes and the previous 27 parser visibility errors disappear.
The next scope blocker in `Compiler::cpp()` is also resolved: declare
`$output /** cpp_module */;` before `try`, then assign inside it. Focused
incremental-C++ and recovery tests pass. Conversion and C++ generation now succeed;
clang reported actual compilation errors, beginning with five field/method naming
collisions. All five backing fields now have distinct names; a reflection scan of
loaded compiler classes finds no remaining field/method collisions, and AST,
structure-access, parse/collection and incremental-preparation tests pass.
The next no-STAN clang attempt is recorded in
`/tmp/my-try-native-20260930-member-names.stderr` and matching stdout. Remaining
diagnostics included type-name hiding; the three reported collisions are now
renamed (preparation_work_owner, lookup_scope and return_statement). Four focused
tests and conversion pass. The retry in
`/tmp/my-try-native-20260930-type-names.stdout` confirms these errors are gone.
Remaining diagnostics concern nullable/derived-type conversions, covariant return
emission, differing ternary branch types and a local split across generated
finally-control-flow blocks. No native compiler executable
or execution results are available yet.

## Current authoring contracts

Use the [portable PHP guide](../../../../specs/portability/authoring_guide.md) and
skill, not workarounds copied from an old checkpoint. Important supported boundaries:

- [Storage/Keyed_Storage](../../../../specs/portability/storage_collections.md): explicit
  element types and shared membership; bind nested receivers to typed locals.
- [Weak fields](../../../../specs/portability/weak_fields.md): explicit supported
  bindings implement native weak references; documentary tags alone do not.
- [Nullable extraction](../../../../specs/portability/nullable_parameters.md): distinguish
  required extraction from class narrowing. Do not weaken required fields or add
  dummy defaults to satisfy an incomplete analysis.
- Object-key dependency maps preserve identity. Value vectors/hashes must not be
  treated as collection aliases merely because PHP objects are shared.

## Next explicit native pass

Use the existing `tools/native_validate.py` workflow and a fresh evidence directory.
Record the exact source commit, target/toolchain and overlays. Convert, build with
normal STAN enabled, execute the compiler and compare its outputs/recovery with PHP;
then compile/run the generated cases. Report blockers separately from advisory
findings. Do not infer that old diagnostic counts still apply without rerunning.

[Review debt](../portability/REVIEW.md) tracks open ownership/analysis questions. Do not update the
verified target pin or claim full native success from a focused fixture.

## Shared-object conversion checkpoint

The generic shared-object boundary policy now handles required/nullable upcasts.
The focused runtime proof passes clang and GCC; the no-STAN compiler retry in
`/tmp/my-try-native-shared-upcasts.stdout` no longer reports the nullable/derived
failures in scope lookup, iterators or maintenance edges. The compiler still fails
on generated finally scope, container/type assignments,
ternary branch types and covariant method-return emission. No executable exists
from this attempt; this does not claim whole-compiler native success.

The remaining source_record module/type collision is fixed by renaming the field
to module_reference. Module-discovery and file-scan tests pass; the retry in
`/tmp/my-try-native-module-reference.stdout` confirms its cast error is gone.

The finally local-scope defect is fixed in legacy generation: delayed-return
guards now enclose continuation suffixes, keeping earlier locals visible.
`/tmp/my-try-native-finally-scopes.stdout` confirms the Parser_Run::block error
is gone. Native/PHP finally behavior matches in `/tmp/finally-scopes-proof-03`;
existing return/loop cases and new local/catch/exception cases pass with STAN
disabled. The compiler still fails on independent assignment, ternary and
covariant-return issues.

The local-type audit fixes call_node's reused Storage local, three CPP/LLVM
output-local name pairs in the native driver, and a test's unrelated work-record
local. AST, structure-access and preparation-work tests pass. Conversion passes;
`/tmp/my-try-native-local-types.stdout` confirms the Storage assignment failure
is gone. The parser's array-count assignment is now validated before narrowing into the
integer-literal field. Four invalid-extent regressions and focused AST/collection
tests pass. `/tmp/my-try-native-array-count.stdout` confirms that error is gone;
remaining emitted failures are ternary branch types and covariant returns.

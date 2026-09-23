# Simple C++ compiler rewrite
Doc Status: supporting

This is the single active home for the stage-by-stage convertible-PHP rewrite.
The registered component proofs cover **248 production files** in PHP and native
PHP++. Coverage includes input preparation, tokenizer/parser, shared source/provider
symbols, name resolution, type-model foundations, template checking, concrete
instance preparation, records, and callable signature selection/publication/reuse.

Core type-snapshot assembly and accepted-family association checks are proved.
Local-type preparation and signature-derived parameter prefixes are also proved.
Construction lookup and association debug output are proved. Complete debug-owner
serializers and coordinator integration remain. Complete prepared-package
consumption remains unfinished. No active compiler CLI or complete compilation
pipeline exists yet.

`src/` preserves the prototype's numbered stage layout. `src-runtime-preparation/`
stays PHP as-is for now, outside this conversion scope. `tests/` holds registered stage outcome proofs.
Do not populate these folders by copying the whole old implementation back.
Bring reusable code in deliberately, applying the portable-PHP and strict skills.

The converter, PHP runtime framework and reusable capability tests remain at
`tools/php_portability/` and `tests/portability/` from the repository root.
`portability.json` lists the proved source files. `tools/portability_target.json`
pins tested, unreleased #240 candidate `9b4b33f35f053b487e018c94d6a4a7888d77c64a`.
Other retained toolchain configuration is historical provider/tooling input, not
proof that the rewritten compiler can run it already.

Run framework validation from the repository root:

```sh
python3 tools/php_portability/validate.py --results /tmp/scpp-rewrite-check-NEW
```

It reports framework results separately from compiler readiness. Add `--native compiler --target-checkout TARGET` for the registered stage native proof.
See [manifest reading](../specs/portability/project_manifest_reading.md) for the API and scope.
Native capability proofs such as `--native records --target-checkout TARGET` remain
available. No empty compiler build is treated as a successful compilation.

## Preserved reference

The previous adopted compiler, its tools, tests, examples and documentation are
frozen under [reference/pre-rewrite](reference/pre-rewrite/). This is source reference,
not a second maintained implementation. Its internal relative launch paths are
historical; replay it from Git branch `v0.2/pre-rewrite-reference` at commit
`623402d05e066bb5bef12c1439472a0a7f376b10` in an isolated checkout when necessary.
The external prototype checkout is unchanged.

The old 39-file cumulative proof remains historical evidence and reusable test
material, not coverage of this new source tree. See the
[reset record](../specs/planning/compiler_migration/rewrite_reset.md) and
[current methodology](../specs/planning/compiler_migration/README.md#current-methodology-stage-by-stage-rewrite).

Root repository specs and working rules remain authoritative. Preserve meaningful
language/protocol results while allowing better internals; complete migration before
adding compiler functionality. Unions and deeper layout tuning are deferred unless
needed by a selected component.

See [source discovery](../specs/portability/source_discovery.md) for path policy,
selection outcomes and native iteration counts.

[Verified source reads](../specs/portability/verified_source_reads.md) supplies owned
source bytes with explicit version-check limits and PHP/native boundary differences.

[Tokenizer](../specs/portability/tokenizer.md) records reused unit cases, compact rows
and the explicit lexical-failure contract.

[Parser foundation](../specs/portability/parser_foundation.md) owns compact syntax
storage and scoped angle matching; it does not imply full parsing support.

Portable functions use the [global facade](../specs/portability/global_functions.md)
without imports. [Expression parsing](../specs/portability/expression_parser.md) is
implemented. [File parsing](../specs/portability/file_parser.md) adds statements and
declarations. Project parser planning/join/reuse now has its own proof; a general
scheduler and later semantic stages remain pending.

[Syntax access/comparison](../specs/portability/syntax_access.md) now supplies value role views, lazy struct-member traversal and logical subtree comparison.

[Project parser](../specs/portability/parser_project.md) adds current-path membership, byte-stable syntax reuse, atomic segmented joining and failed-update diagnostics.

[Source declaration collection](../specs/portability/declaration_collection.md) adds compact facts, stable source symbol IDs and atomic candidate updates (97 PHP/native outcomes plus nine host purity assertions).

[Source entry preparation](../specs/portability/entry_preparation.md) proves manifest selection and supporting-file execution policy (44 PHP/native outcomes plus nine host purity checks). The scalar catalog now supplies its authoritative return-type binding.

[Type representations](../specs/portability/type_representations.md) adds 98 PHP/native outcomes for all prototype value shapes, passing/result modes and context/lineage records, plus retained-constructor and host purity checks.

[Type lifetimes](../specs/portability/type_lifetimes.md) adds 154 PHP/native outcomes for operation composition and capability/binding validation, with retained-prototype and host purity checks.

[Scalar catalog and entry binding](../specs/portability/scalar_catalog.md) adds 116 PHP/native outcomes, the retained catalog-parser oracle and eight host purity checks.

[Declaration lookup](../specs/portability/name_lookup.md) adds 40 PHP/native outcomes and 15 retained numeric constructor cases.

[Lexical/body resolution](../specs/portability/lexical_resolution.md) adds 320 PHP/native outcomes, 23 host invariants and five retained generic-permission checks.

[Project resolution](../specs/portability/resolution_project.md) adds 182 PHP/native outcomes and 25 host snapshot/acceptance checks.

[Canonical type storage](../specs/portability/type_store.md) adds 133 PHP/native outcomes, retained store facts and 20 host invariant checks. Aggregate lifecycle composition adds 36 PHP/native outcomes and eight host checks. Normalized structural definitions are now proved; annotation preparation remains incomplete.

Native record layout contracts add 18 PHP/native outcomes and 441 retained-contract
comparisons. Resource-aware definition validation now adds 39 PHP/native checks and 90 retained allocation-effect cases. Record/array materialization adds 27 PHP/native checks. Next: concrete annotation and provider/storage dependencies.

Definition_View adds 14 PHP/native checks for provider/source precedence and accepted
identity. Instance contexts, typed arguments and exact integer literals add 35 PHP/native
outcomes and 200 host range cases. Instance allocation now adds 23 PHP/native outcomes and 60 retained allocator calls.
Symbolic terms and permission-result containers add 40 PHP/native checks and 529
retained symbolic comparisons. Provider integration precedes symbolic declaration
interpretation and the template-checking worker/joins, then registry publication
and instance bindings.
Annotation resolution remains incomplete.

Provider declaration references/signatures add 35 PHP/native checks and three PHP
carrier checks. Generic-family contracts/source exposures add 51 PHP/native checks
and 33 retained-validator cases. Both first native builds passed without native
corrective cycles. See the [approved provider integration plan](../specs/planning/compiler_migration/provider_declaration_integration.md)
and [adaptation/timing record](../specs/planning/compiler_migration/php_adaptation_record.md).
Normalized record catalog storage and family import acceptance now add 30 PHP/native
checks, with scalar/catalog and source-resolution native regressions passing.
Prepared-package ingestion/composition and shared symbol integration remain pending.

Prepared callable ABI transport and semantic compatibility add 66 PHP/native checks
and retained-validator agreement for 32 compatibility cases and slot mappings.
Storage-family contracts now add 53 PHP/native checks, including exact descriptor
ownership validation. Complete prepared-package consumption remains pending.


Package physical measurements now add 106 PHP/native outcomes and retained importer
acceptance comparisons (`results/package-measurements-01`). All six storage kinds,
alignment and integer width/signedness are validated without granting type ownership.
Next: language exposure and exact accepted native/source owner binding, then complete
package acceptance; those remain incomplete.


Ordinary package type exposure adds 38 PHP/native outcomes with retained importer
agreement (`results/type-exposure-01`): exact scalar/void catalog identity, explicit
opaque/span permissions and deferred record materialization. Native/source imports
cannot use this path. Next: exact accepted native imports and source export ownership,
then whole-package composition/acceptance.


Accepted native type imports add 40 PHP/native outcomes and retained-importer
comparisons (`results/native-type-import-01`). Exact definition identity, measured
storage and semantic copy/assignment permission are checked separately from C++
traits. Next: source-export ownership and complete package type publication/retention;
package target/checksum/lease acceptance remains incomplete.


Source-export project/backend provenance adds 279 PHP/native outcomes and retained
acceptance comparisons (`results/export-provenance-01`). Explicit project identity,
byte-preserving lexical roots and required target/revision keys are retained without
filesystem access or claims of verified backend support. Next: tagged export type
identity and accepted layout/dependency provenance, before source-export binding.


Typed export identity encoding adds 61 PHP/native outcomes and 215 exact retained
key comparisons (`results/export-identity-01`). Tagged keys preserve argument order,
declared constant types, nested structure and source flags without heterogeneous
parts arrays. Next: accepted layout/dependency provenance and source-export ownership;
source/provider identity projection itself remains pending.


Accepted layout/dependency records add 34 PHP/native outcomes, 17 retained layout
comparisons and six PHP carrier rejections (`results/layout-contracts-01`). Copied
container membership retains exact shared lineage and definition provenance. Native
measurement/join acceptance remain unimplemented. Next: source export capability
and task records, then compiler-side source payload binding and package composition.


Physical ABI and source-export records add 219 PHP/native outcomes, including 204
retained capability/semantic comparisons (`results/source-export-contracts-01`).
Source-only complete plans, explicit unavailable states and separate import/implementation
associations are preserved. Next: compiler-side source payload binding and package
type composition; source export production/join/linkage remain unfinished.


Package type composition adds 38 PHP/native outcomes and 38 retained acceptance
comparisons (`results/package-type-map-01`). Ordinary catalog exposure, accepted
native imports and exact source payload definitions now compose through one private
map, with conflict/unknown-binding rejection before publication. Source payloads
retain their existing definition and measured layout; no adapter definition is
substituted. Next: accepted package contracts and exact retained-binding comparison;
receipt validation, source export production/join and complete package acceptance
remain unfinished.


Runtime package/project records add 160 PHP/native outcomes and retained lifecycle
enumeration comparisons (`results/runtime-package-01`). Queries preserve exact
shared type/catalog identity, copied container membership and stable
destroy/copy/move/assign/default order. Rebound native/source owners are excluded
from duplicate lifecycle enumeration. Constructor calls do not authorize artifacts
or receipts. Next: explicit semantic contract comparison for package/type reuse;
`matches`, retained-type canonicalization, lease ownership and diagnostic projection
are not yet migrated.


Explicit callable comparison and retention add 925 PHP/native outcomes and original
prototype equality comparisons (`results/callable-contracts-01`). Nested reference
identity, ordered semantic parameters, result/effects, physical ABI and exposure
flags all participate. Equal rebuilt contracts retain old object identity; changed
contracts and new coverage keep their new objects and order. Next: type-definition
and binding/project comparison before package reuse; complete package acceptance
and integration of callable retention in the adapter remain pending.


Lifecycle equality adds 842 PHP/native and original-prototype comparisons
(`results/lifecycle-contracts-01`). All five permission categories and exact
imported/source operation plans participate; source member order matters, while
role-map insertion order does not. Local type/body IDs are comparable only within
the same accepted lineage. Next: complete definition/resource/layout/storage
comparison, then type and package reuse. This helper alone does not authorize reuse.


Full definition comparison adds 1,513 PHP/native matrix outcomes plus seven focused
retained-prototype contract checks (`results/definition-contracts-01`). Resource
paths, native layouts, record fields and typed-storage dependencies now participate
alongside names, representations and lifecycle permissions. Empty resource wrappers
normalize to no obligations; storage map insertion order is ignored while ordered
paths/fields retain meaning. Next: integrate exact type retention, then package
binding/project-context comparison; complete package acceptance remains unfinished.


Runtime type retention adds 172 PHP/native outcomes, including 169 comparisons
with the actual retained equality/retention implementation (`results/type-retention-01`).
Unchanged bindings and complete contracts retain old object identity; changed
contracts under unchanged bindings require a fresh type context. Missing/changed
old bindings keep current objects. Source payload bindings require exact export
definition identity, and late failure leaves both input maps untouched. Next:
package binding/project-context comparison and adapter acceptance integration;
the retention helper assumes the caller has established the same package context.


Package-context matching adds 33 PHP/native outcomes (`results/package-context-01`).
Current selection is an explicit record; directory/manifest bytes, base catalog,
binding maps and project receipt/export membership must agree. Rebuilt ordinary
bindings may match, but accepted native/source owners retain exact object identity.
This deliberately makes reconstructed accepted owners a cache miss instead of
returning an old package carrying stale owner associations. Artifact/receipt
validation must still precede the query. Next: package metadata/artifact acceptance
and source receipt validation; complete adapter integration remains unfinished.

Concrete preparation queue: 31 PHP/native scheduling assertions and a host payload-release proof; indexed fact wakeups, fixed kind batches and request identity pass. Full coordinator integration remains incomplete.

Instantiation policy: 22 PHP/native cases agree with the preserved prototype; explicit file input and bounded integer validation are proved. Host default-data discovery remains a session integration task.

Checked body flow adds builder protocol proofs and 48 PHP/native graph cases with retained-prototype agreement. Concrete body expressions/statements, lifetime analysis and end-to-end stage orchestration remain incomplete.

Checked storage places add 47 PHP/native cases with retained-prototype agreement, fixed projection membership and ordered index-cursor access. Expression/place checking and lifetime consumers remain incomplete.

Exact operation contracts and selection add 294 PHP/native cases with retained-prototype agreement. Operand/result canonical identities and declared capabilities are preserved; expression execution and lowering remain separate.

Typed value/call/statement records add 1,009 PHP/native outcomes, including 1,000 retained statement combinations and all byte values. Completed body ownership and evaluation order remain separate.

Retained checked-body queries add 23 PHP/native scenarios for canonical dependencies, operands, argument ranges and projected storage. Captured signature parameter IDs remove mutable-store dependence. Body checking/evaluation execution and the debug serializer remain incomplete.

Streaming expression order adds 24 PHP/native traces with retained-generator agreement and a 4,096-level iterative proof. Lifetime/lowering consumers still need migration to the explicit iterator.

Conversion selection adds 196 PHP/native cases and 12 selection invariants. Identity, permitted widening and purpose-specific provider lookups agree with the prototype; applying conversions in the body worker remains unfinished.

Quoted-byte decoding reuses the preserved implementation and 8,593-case frozen corpus, now all proved in PHP/native on the active target. Body-worker integration remains separate.

Typed body-output ownership adds 33 PHP/native checks and 24 host comparisons with the retained access selector (`results/body-output-01`). Reserved arguments/scopes, pending locations, stable IDs and completed handoff are explicit; first native build passed without corrections. Next: wire the body worker and traversal handlers to the proved output owner. Full body checking remains unfinished.

Body-worker input acceptance and dependency retention add 23 PHP/native scenarios plus four retained closure cases (`results/body-context-01`). Exact source/name/signature/local/template associations, signature sharing and iterative implicit element retention pass; first native build passed without correction. Next: actual statement/expression traversal using Body_Context and Body_Output. Complete body checking remains unfinished.

Source Body_Worker and its five handlers now pass 58 PHP/native scenarios (`results/body-worker-01`): literals, calls, nested arguments, operations/conversions, local/field/array writes, record borrowing/returns, scopes and branches/loops. Checked rows are consumed by Expression_Order. The first C++ build passed after one STAN correction cycle; expanded cases have a separate final native verification. Next: method/template/storage and byte/echo provider integration cases, then body selection/reuse/join. Full analysis and lifetime checking remain unfinished.

Concrete source method/template call integration adds 14 PHP/native scenarios (`results/body-calls-01`) without production changes. Real application/member workers and joins prepare receivers, repeated calls, value/type arguments and nested concrete contexts; both callers and concrete callees are checked. Mutable/const receivers, receiver-plus-arguments, `$this` forwarding and arity errors pass. Ready count stays 226. Remaining body coverage includes storage-element expressions, successful byte/echo metadata bindings and managed lifecycle paths; body selection/reuse/join is still unmigrated.

Project body selection, reuse and complete batch publication add 23 PHP/native scenarios (`results/body-project-01`). Shared validity tracks exact source/local/type/signature dependencies and captured parameter IDs; reversed workers publish in current signature order, malformed batches fail without changing prior bodies, and source failures expose no partial set. Concrete method/template bodies participate. First native build passed without corrections. Readiness is 231 files. Remaining body integration covers storage/byte/echo/managed lifecycle cases and debug export; full session/Step coordination and lifetime analysis remain unfinished.

Metadata-driven literal/echo body checking adds 20 PHP/native scenarios
(`results/body-language-01`): contextual/default constructors, direct span arguments,
empty/binary/UTF-8 bytes, exact target/argument order, retained provider dependencies,
and absent binding diagnostics. Provider functions are not linked or executed by
this compiler proof. No production changes; readiness stays 231 files. Remaining
body integration includes storage elements and managed lifecycle paths, plus debug
export and whole-stage coordination. Lifetime analysis remains unmigrated.

Typed-storage body integration: 23 PHP/native cases pass (`results/body-storage-01`). No production changes; readiness stays 231 files. See `specs/portability/compiler_body_storage_slice.md` for scope and remaining obligations.

Managed-value body integration: 23 PHP/native cases pass (`results/body-managed-01`). No production changes; readiness stays 231 files. See `specs/portability/compiler_body_managed_slice.md` for scope and remaining obligations.

Definite-initialization flow begins lifetime-stage migration: 16 PHP/native and retained-solver comparisons pass (`results/local-flow-01`). Typed ordered facts and exact checked-body ownership preserve scope/intersection behavior. Ready count is 233 files. Cleanup, consumption and allocation/ownership analysis remain unfinished.

Lifetime record contracts add 1,105 PHP/native and retained-constructor comparisons (`results/lifetime-records-01`). Stable end/subject names survive the enum-to-tag adaptation. Readiness is 234 files. Full lifetime worker, result ownership, allocation analysis and stage integration remain unfinished.

Lifetime consumption and cleanup planning adds 29 PHP/native scenarios (`results/lifetime-plan-01`). The explicit intermediate plan preserves scope/value/cleanup contracts without claiming resource ownership acceptance. Readiness is 239 files. Allocation/ownership flow, full analyzed-body publication and stage integration remain incomplete.

Resource-state algebra adds 336 PHP/native, prototype and independent-oracle outcomes, plus exhaustive associativity/identity checks (`results/resource-states-01`). Readiness is 240 files. Resource location/effect/alias modeling and full allocation/ownership analysis remain unfinished.

Resource locations and canonical endpoints add 491 PHP/native outcomes, including 477 direct preserved-helper comparisons and real nested record/parameter discovery (`results/resource-locations-01`). Readiness is 242 files. Resource effects/aliasing and allocation/ownership flow remain unfinished.

Ownership flow/summary records add 340 PHP/native retained-constructor outcomes and snapshot/contract assertions (`results/ownership-records-01`). Readiness is 244 files / 109 registered proofs. Effect/alias consumers, allocation flow and complete ownership acceptance remain unfinished.

Bound resource-call effects and alias checks add 449 PHP/native preserved-trait comparisons, with seven independent outcomes (`results/resource-calls-01`). Readiness is 245 files / 110 registered proofs. Runtime effect binding, allocation flow and complete ownership acceptance remain unfinished.

Runtime allocation effects add 896 PHP/native preserved-trait comparisons with independently specified outcomes (`results/allocation-calls-01`). Readiness is 246 files / 111 registered proofs. Checked operand binding, allocation flow and complete ownership acceptance remain unfinished.

Checked ownership operand binding adds nine real-body PHP/native scenarios and attributed failure checks (`results/resource-bindings-01`). Readiness is 247 files / 112 registered proofs. Allocation traversal, fixed-point flow and complete ownership acceptance remain unfinished.

Checked resource expression traversal adds 25 PHP/native scenarios for runtime effects, borrows and construction/destruction fields (`results/allocation-traversal-01`). Readiness is 248 files / 113 registered proofs. Statement/scope integration, fixed-point flow and ownership acceptance remain unfinished.

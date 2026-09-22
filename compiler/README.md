# Simple C++ compiler rewrite
Doc Status: supporting

This is the single active home for the stage-by-stage convertible-PHP rewrite.
Input preparation, tokenization, parser grammar, structural queries and project parser
reuse, source declarations, entry selection, representations, lifecycle contracts scalar catalog/entry binding project name resolution and canonical type storage cover **122 production files**
with PHP/native proofs. Next is prepared-package consumption and shared provider symbol integration. No active compiler
CLI or complete compilation pipeline exists yet.

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

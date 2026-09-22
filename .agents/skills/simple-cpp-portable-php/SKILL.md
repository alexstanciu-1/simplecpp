---
name: simple-cpp-portable-php
description: Write, review, or adapt executable PHP for this repository's PHP-to-PHP++ portability converter and scpp framework. Use for portable compiler implementation code and its PHP/native behavioral proofs, not direct PHS authoring or arbitrary PHP application conversion.
---

# Simple C++ portable PHP
Doc Status: supporting

Author executable `.php`; PHP++ is generated. Adapt unsuitable PHP deliberately.
The converter is structural: it does not resolve symbols or infer whole-program
types. Direct same-namespace trait indexing is the agreed narrow exception.

Read the [authoring guide](../../../specs/portability/authoring_guide.md) before
editing. Follow its feature links as needed. The [function policy](../../../tools/php_portability/function_map.php)
owns bindings and arities; the [constraint summary](../../../specs/portability/debt.md#current-constraint-consolidation)
distinguishes authoring rules, converter gaps and target limitations. Do not treat
an old slice's limits or a feature-catalog proposal as the current support matrix.

- Keep PHP syntax and explicit supported type comments; generated PHS uses native
  type syntax. Use lowercase namespaces and no function imports. The shared bootstrap
  supplies global helpers: plain names for non-PHP facilities, q_ names for existing
  PHP functions (`q_count`, `q_strlen`, `q_is_bool`). Use the fixed function map;
  do not call internal scpp/scpp\compat functions or invent unsupported q_ helpers.
  Avoid C++ keyword identifiers such as operator/template in local names.
  See [global convention](../../../specs/portability/global_functions.md).
- Prefer named typed records and explicit vector/map intent. Ordinary arrays are
  for non-hot setup/read-only data. The writer and behavioral tests own discipline;
  the converter does not infer hotness, ownership or PHP copy-on-write intent.
- Ordinary classes share identity; explicitly marked scalar value records become
  inline structs. Read the [record/alias contract](../../../specs/portability/value_records.md)
  before using `@scpp-struct` or either `&ref` form. Use explicit owner copy operations
  for independent values and snapshots; borrow only stable locals, never container
  elements. PHP object sharing must not accidentally define native value behavior.
- Choose dense vectors versus sparse/keyed hashes by access pattern. Keep stable IDs,
  storage positions and missing sentinels distinct. Normalize compact numeric tags to
  the comparison domain explicitly (for example `(int)` before int-tag `===`); PHP
  success does not prove native fixed-width strict comparisons. Use narrow fields only with known
  bounds; PHP integer annotations do not enforce native ranges. Measure layout and
  memory natively. PHP readonly alone does not establish native immutability.
- Distinguish UTF-8 code-point operations from byte operations for source spans,
  filesystem spelling and binary data. Use explicit JSON schemas at boundaries.
- Keep null, false and present empty/zero values distinct. Check the guide for the
  particular declaration, wrapper, callback or exception form being authored;
  related native language support does not establish converter support.
- On the current target, use nested conditions or early returns when RHS safety,
  errors or side effects depend on `&&`/`||` skipping evaluation, even for boolean
  operands. Independent safe comparisons may stay compound. This is source
  adaptation, not a converter rejection; preserve the algorithm's evaluation order.
- For compiler rewrites, read the prototype stage and load the
  [Simple C++ strict skill](../simple-cpp-php-strict/SKILL.md). Follow the
  [stage methodology](../../../specs/planning/compiler_migration/README.md#current-methodology-stage-by-stage-rewrite):
  manifest, paths/discovery, verified reads, tokenizer, parser, then semantic stages.
  Keep `src-runtime-preparation` PHP as-is for now; it is outside conversion scope.
  Reuse suitable existing unit cases; adapt their harness and add portability proofs.
  Keep one implementation; reuse tools and suitable code without preserving incidental
  internals. Define stage inputs/outputs, rejection behavior and ownership first.
- Iterate in PHP with frequent cheap checks/conversion. Prove native behavior for new
  capabilities/representations and completed components, not every small edit. Compare
  meaningful results and clean/incremental agreement; require exact bytes only where
  the contract does. Defer unions/deeper layout tuning unless concretely needed.
- Consolidate once per component, record major changes/reasons and measured effort,
  and extend conversion only for demonstrated needs. Report cross-owner decisions
  and target defects without silently redesigning contracts or fixing generated C++.

Use the [validation workflow](../../../specs/portability/validation_workflow.md).
`python3 tools/php_portability/validate.py --results FRESH` runs the ready-set fast
loop. Add `--native compiler --target-checkout TARGET` for the cumulative native
proof; a PHP-only pass is not native evidence. After the
[rewrite reset](../../../specs/planning/compiler_migration/rewrite_reset.md), the active
ready set grows only with registered stage outcome proofs in `compiler/tests/`.
Old 39-file coverage is historical. Record elapsed time by activity and command in
each stage evidence, including failed attempts, to guide workflow optimization.
Record the first passing PHP-behavior checkpoint (command and source revision/hash):
separate authoring/PHP debugging up to that point from conversion/native stabilization
after it, then regression verification and consolidation. Keep the original checkpoint
if native fixes require PHP rechecks. See the validation workflow for timing boundaries.
Count native attempts to first pass, corrective cycles, and final verification builds
separately; keep checker-only fixes separate. Give framework wrapper types distinctive
names, avoiding case-only variants of target runtime types. The immutable target lives in
[portability_target.json](../../../compiler/tools/portability_target.json), not in
this skill. Host loading belongs outside the converted source tree; assemble native
framework support separately and consume output only after successful conversion.

Prove independent expected values, errors, effects and relevant identity before
adding a file to `compiler/portability.json`. Use affected retained compiler tests.
Actual concurrency/lifetime behavior needs native evidence. Consolidate the owning
contract, focused examples/rejections and saved evidence; keep file counts distinct
from whole-compiler completion. This skill adds no authorization to resume a paused
goal or introduce compiler functionality during migration.

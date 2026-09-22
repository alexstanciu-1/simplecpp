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
  type syntax. Use lowercase namespaces and synchronized uniform imports. `scpp`
  owns target-specific facilities; `scpp\compat` owns adapted PHP-like operations.
  Do not invent `compat_*` source calls or bypass managed bindings.
- Prefer named typed records and explicit vector/map intent. Ordinary arrays are
  for non-hot setup/read-only data. The writer and behavioral tests own discipline;
  the converter does not infer hotness, ownership or PHP copy-on-write intent.
- Ordinary classes share identity; explicitly marked scalar value records become
  inline structs. Read the [record/alias contract](../../../specs/portability/value_records.md)
  before using `@scpp-struct` or either `&ref` form. Use explicit owner copy operations
  for independent values and snapshots; borrow only stable locals, never container
  elements. PHP object sharing must not accidentally define native value behavior.
- Choose dense vectors versus sparse/keyed hashes by access pattern. Keep stable IDs,
  storage positions and missing sentinels distinct. Use narrow fields only with known
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
- Select dependency-coherent components, preserve algorithm behavior, and extend
  conversion only for demonstrated needs. Report required cross-owner decisions
  and target defects without silently redesigning contracts or fixing generated C++.

Use the [validation workflow](../../../specs/portability/validation_workflow.md).
`python3 tools/php_portability/validate.py --results FRESH` runs the ready-set fast
loop. Add `--native compiler --target-checkout TARGET` for the cumulative native
proof; a PHP-only pass is not native evidence. The immutable target lives in
[portability_target.json](../../../compiler/tools/portability_target.json), not in
this skill. Host loading belongs outside the converted source tree; assemble native
framework support separately and consume output only after successful conversion.

Prove independent expected values, errors, effects and relevant identity before
adding a file to `compiler/portability.json`. Use affected retained compiler tests.
Actual concurrency/lifetime behavior needs native evidence. Consolidate the owning
contract, focused examples/rejections and saved evidence; keep file counts distinct
from whole-compiler completion. This skill adds no authorization to resume a paused
goal or introduce compiler functionality during migration.

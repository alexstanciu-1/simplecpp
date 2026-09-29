# Portability status and evidence
Doc Status: supporting

PHP behavior, successful PHP-to-PHS conversion, native compiler execution, and
execution of generated programs are distinct claims. None implies the others.
Native compiler validation remains explicitly opt-in under [AGENTS](../AGENTS.md).

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
because the converter currently expects visibility before the abstract modifier.
Whole-compiler STAN/build/execution remain unreached.

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

[Review debt](REVIEW.md) tracks open ownership/analysis questions. Do not update the
verified target pin or claim full native success from a focused fixture.

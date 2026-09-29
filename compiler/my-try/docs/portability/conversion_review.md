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

# Managed struct fields validation
Doc Status: planning

Scope: string fields, ordinary class fields using existing shared handles, and
recursive vector/hash/fixed-array compositions of permitted struct field types.
`mixed`, `dynamic`, nullable fields, explicit ownership-wrapper fields, and managed
union payloads remain excluded. Numeric field eligibility is unchanged.

## Implementation

- STAN and generation share `Analysis/StructFieldTypePolicy.php`; each supplies
  its existing declaration-kind metadata. No general expression inference added.
- Typed hash literal emission is reusable at known expected-type boundaries,
  including nested container and keyed struct initialization.
- Generic JSS struct fields emit canonical PHS typed-property syntax.
- The declaration-kind catalog includes JSS summaries and invalidates cached
  catalog data when its JSS implementation inputs change.
- The normative compact-layout contract, generator rules, both language skills,
  and Unreleased notes describe the extension.

## Validation

- `php tests/tools/test_scpp_managed_struct_fields.php`: PASS with Clang.
- Same suite with `SCPP_CXX=g++`: PASS with GCC.
- Each run builds and executes strict PHP++, legacy PHP++, and JSS projects with
  declarations across three files. Checks cover empty/default-initialized fields,
  fixed-array/string defaults, copied strings/containers, nested struct copying,
  shared class identity through direct/vector/hash fields, and function
  parameter/return boundaries. PHP++ also checks keyed struct initialization
  containing class construction and nested typed literals.
- PHP++ negative cases reject direct/nested mixed, dynamic, direct string union
  payloads, and structs carrying strings/objects inside unions. Both STAN and
  generator-only paths are checked.
- `test_scpp_stan_strict_discipline.php`: PASS.
- `test_scpp_build_options.php`: PASS.
- `test_scpp_compact_layout_acceptance.php`: PASS (existing compact record 32 bytes).
- `test_scpp_project_unit_scoped_packs.php`: PASS.
- `test_scpp_jss_frontend_first_slice.php`: PASS.
- `test_scpp_jss_samples.php`: PASS (135 cases).
- `test_scpp_build_planner_state.php`: PASS.
- Both updated skills pass `quick_validate.py`; `git diff --check` passes.
- MSVC 14.44 compiled an isolated native probe using actual `string_t`,
  `shared_p`, and `vector_t` fields. This is a compile-only Windows check, not a
  full generated-project Windows run. The earlier native probe also passed
  address/undefined sanitizers on Linux with leak detection disabled because
  LeakSanitizer cannot run under this environment's tracing; explicit pointee
  destruction assertions passed.

Logs are retained under `/tmp/scpp-managed-*.log` for this session.

## Remaining boundaries

Cross-file declaration-kind metadata does not supply full field schemas for all
literal-assignment paths. Construct an explicitly typed container local before
assigning it to a container field declared in another file. This limitation is
also recorded in `specs/compact_layout_types.md`.

JSS object literals use its canonical `hash<T>` target syntax; JSS struct locals
use declarations and field assignment rather than JavaScript-style object
literals as struct constructors.

This focused validation does not clear the independent release blockers recorded
in `release_candidate_validation_2026_09_09.md`. No branch merge, release, commit,
or publication is part of this change.

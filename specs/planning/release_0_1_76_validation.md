# Release 0.1.76 validation
Doc Status: planning

Date: 2026-09-19. Release source: `release/0.1.76` from `develop` at
`4cf03fd55b50c3303ab90c9d58a2b285a04b3ded`.

The only production change since v0.1.75 is the struct-construction fix (#229).
Release preparation changes VERSION.txt, CHANGELOG.md, the two repo-local Agent
Skills, and this validation record. No additional production changes are included.

## Validation carried forward from the fix

- `php tests/tools/test_scpp_struct_construction.php`: passed, including strict
  and legacy lowering, source diagnostics, STAN typed/inferred value use, and
  native execution for same-file/cross-file/imported construction, independent
  struct copies, and shared class identity.
- `php tests/tools/test_scpp_managed_struct_fields.php`: passed for strict,
  legacy, and JSS, including copy semantics and exclusions.
- `php tests/tools/test_scpp_stan_strict_discipline.php`: passed.
- `php tests/tools/test_scpp_build_options.php`: passed.
- PHP syntax checks for changed implementation/tests and `git diff --check`: passed.
- Hosted [main CI for the fix](https://github.com/alexstanciu-1/simplecpp/actions/runs/35425074450): passed.

## Release review

- Both `.agents/skills/*/SKILL.md` files now describe no-argument struct value
  construction and preserve the ordinary class shared-ownership distinction.
- Both skills passed the skill-creator `quick_validate.py` validator.
- VERSION.txt and CHANGELOG.md agree on 0.1.76. CLI version reporting reads the
  nearest release tag, so it remains 0.1.75 until v0.1.76 is tagged on main.
- The release body is extracted from the checked-in 0.1.76 changelog section.
- Release-PR CI must pass before merge and publication.

## Scope and limitations

STAN's existing advisory imported-alias/ambiguous-short-name limitations remain;
the regression permits those specific advisories while retaining the checked
native build and requiring clean analysis for the direct typed/inferred case.
No broader name-resolution change or custom struct constructor support is added.
The unrelated deferred defects recorded for v0.1.75 are not changed by this patch.

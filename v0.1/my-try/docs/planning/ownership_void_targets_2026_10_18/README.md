# Non-void shared and unique ownership targets
Doc Status: supporting

2026-10-18: DR-02 admission repaired under the explicit user decision.
Type_Registry::validate_application checks the resolved ownership family and
no-value target before interning. It resolves the target/family once and reuses
those facts for existing composition checks. No new generic capability, retained
structure, runtime change, backend check or copyability constraint was introduced.
The normative rule is in specs/type_use_modifiers.md, Non-void owning targets.
Weak admission is unchanged; the bare weak<void> control still compiles and runs.
This is not a claim that new weak<void> operations are enabled.

The registry regression failed before the fix. It now checks canonical and renamed
no-value targets, ordinary and renamed ownership definitions, exposed aliases,
repeated invalid attempts without application publication, unchanged weak interning,
and an unrelated same-spelled ordinary template that gains no ownership restriction.
Existing ownership model checks retain noncopyable target and composition coverage.

Source checks reject shared/unique void in locals, fields, parameters, returns and
nested vector applications during preparation: ten cases. Two same-instance invalid
edit/retry/repair sequences restore exact baseline artifacts. Three valid generated
controls compile, link and execute without PCH: shared<Struct> and unique<Struct>
return 9; weak<void> returns 0.

```sh
PYTHONDONTWRITEBYTECODE=1 MY_TRY_PHP_EXECUTOR=cli python3 compiler/my-try/tests/records.py --suite ownership_targets --no-pch --results FRESH_DIRECTORY
```

The shared 12-slot runner also passed ownership_model.php, ownership.php and
type_catalog.php. These are PHP semantic/emission regressions, not a rebuild of
all existing ownership programs. CLI was selected following the prior sandbox
FPM socket failure. No native compiler rebuild or broad regression suite was run.

[Focused summary](focused/summary.json), [input hashes](provenance.json), command
journals and nonempty diagnostic streams are retained. Paths inside logs are the
original execution locations; rerun the command above into a fresh directory.
Source-location improvements remain DR-08. Other diagnostic issues and pre-existing
deferred work were not changed.

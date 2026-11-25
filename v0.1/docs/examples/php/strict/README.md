# Strict PHP Examples
Doc Status: supporting

Purpose: provide authoritative examples for the strict PHP library profile.

This folder should be the first stop for agents writing new code against the strict profile.

The strict profile is defined normatively in:

- `specs/php/library_profiles.md`

Current sample shape:

- `project_samples/`
  - small checked-in strict-profile projects
  - explicit `runtime.languages.php.profile = "strict"`
- plain PHP-like names for general helpers and family-prefixed names for subsystem helpers, such as `fs_get`, `fs_scan`, `strlen`, `io_open`
  - checked expected-output runner at `project_samples/tests/run_examples.sh`

Strict examples are authoritative for agents when:

- the project profile is `strict`
- a reusable non-legacy runtime family exists
- there is a strict/legacy choice for the same capability

Legacy examples do not override this folder.

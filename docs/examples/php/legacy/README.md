# Legacy PHP Examples
Doc Status: supporting

Purpose: provide compatibility-oriented examples for the legacy PHP library profile.

This folder is for:

- PHP-legacy builtin/library names such as `scandir`, `file_get_contents`, `strlen`
- migration guidance from PHP-shaped authoring
- projects that explicitly select the legacy profile

Current validated suite:

- `project/`
  - one shared checked-in example project
  - explicit `runtime.languages.php.profile = "legacy"`
  - lightweight expected-output validation runner

Use this folder when the active project profile is `legacy`.

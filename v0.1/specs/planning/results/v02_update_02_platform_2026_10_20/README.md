# Latest _02 platform/alias merge — 2026-10-20
Doc Status: planning

Integration base: `a718b9d5`. Fetched `_02` branch `v0.2/develop` at
`842276aaf6ae324a9b14ef9ccb85d48383c91bca`, adding these complete commits:

- `2f4f1bd1`: qualified runtime aliases and platform contracts.
- `842276aa`: V-D09/V-D12 dev002 evidence and closeout.

No cherry-picking, squashing, rebasing or modifications to `_02`. The single
metadata conflict was resolved by retaining the concrete vector append registration
and both incoming microtime registrations/bindings. Incoming ordinary test fixtures
explicitly opt out of the new prelowering default; source semantics are unchanged
by the resolution.

## Focused validation

`tests/platform_alias.py` passes preparation, metadata rejection/recovery, seven
compiled programs (with the fixture's sanitizers) and ordinary production cold/warm
builds. Raw results: `/tmp/v02-update-platform-20261020`.

Seven shared preparation regressions pass: callables, observed lookup, preparation
recovery, member owners, methods, static members and qualified static members.
Raw results: `/tmp/v02-update-platform-regressions-20261020`.

A separate default-prelowering O0 program exercises both `php::microtime` forms,
string observation, numeric comparisons and `fs_is_windows`. It uses normal
publication and native compilation with strict template-free validation, then checks
output and warm reuse. The exact source, expected output and build counts are in
`concrete.json`; raw results: `/tmp/v02-update-platform-concrete-20261020`.

These checks execute generated programs using the PHP-hosted compiler. No fresh
native compiler rebuild, full accounting rerun or expanded universal template
coverage is claimed for this small follow-up merge. Nothing is pushed by this step.

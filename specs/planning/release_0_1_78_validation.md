# Release 0.1.78 validation
Doc Status: planning

Date: 2026-09-22. Follow-up for #235/#236; inherits all #231–#233 changes from v0.1.77.

## Immutable candidate

`a1a1babd07082d9abf7ac885b2328c99368ad4cf` on `feature/qualified-construction-235` (PR #238).
The combined commands below all ran on this same clean candidate. No migration
workspace implementation was changed. The native runtime sources are unchanged
from the already built v0.1.77 tree; those native binaries were rerun together
with fresh strict/PHS fixture builds on the candidate.

## Combined local results

| Command | Result | Seconds |
| --- | --- | --- |
| `php tests/tools/test_scpp_construction_references.php` | PASS | 0.2 |
| `php tests/tools/test_scpp_inheritance_references.php` | PASS | 0.2 |
| `php tests/tools/test_scpp_collection_typing.php` | PASS | 1.0 |
| `php tests/tools/test_scpp_runtime_type_declarations.php` | PASS | 0.2 |
| `php tests/tools/test_scpp_strict_runtime_catalog.php` | PASS | 0.2 |
| `php tests/tools/test_scpp_stan_strict_discipline.php` | PASS | 4.6 |
| `php tests/tools/test_scpp_managed_struct_fields.php` | PASS | 66.2 |
| `php tests/tools/test_scpp_build_options.php` | PASS | 89.8 |
| `python3 tests/tools/test_scpp_collections.py` | PASS | 47.1 |
| `python3 tests/tools/test_scpp_process.py` | PASS | 44.8 |
| `python3 tests/tools/test_scpp_file_locks.py` | PASS | 24.5 |
| `python3 tests/tools/test_scpp_runtime_alias_signatures.py` | PASS | 20.9 |
| `python3 tests/tools/test_scpp_nested_vector_fields.py` | PASS | 20.6 |
| `python3 tests/tools/test_scpp_qualified_inheritance.py` | PASS | 20.2 |
| `python3 tests/tools/test_scpp_qualified_construction.py` | PASS | 20.0 |
| `python3 tests/tools/test_scpp_host_paths.py` | PASS | 19.1 |
| `python3 tests/tools/test_scpp_snapshots.py` | PASS | 22.4 |
| `ctest --test-dir /tmp/scpp-file-lock-build -R snapshot\|file_locks\|collection\|process\|php_filesystem --output-on-failure` | PASS | 0.8 |

The native CTest selection contains seven tests. PHP syntax checks and
`git diff --check` also passed. The constructor native fixture requires normal
STAN, no compile-error findings and expected output `7:7:11:13`; only existing
basename ambiguity and short-vs-qualified return advisories are allowed.

## Release preparation and sign-off

- Next version: 0.1.78. v0.1.77 remains immutable.
- CHANGELOG.md owns the release body; publication must use that committed text.
- Strict PHP++ skill review adds explicit absolute construction guidance for
  method/type collisions. JSS skill reviewed: its existing PHS-versus-JSS
  coverage boundary remains current; no new JSS syntax or frontend coverage.
- Integration PR #238 CI passed on Linux, macOS, Windows and Android:
  https://github.com/alexstanciu-1/simplecpp/actions/runs/35689751000
- Integration merge `e56480bb` has the same tree as the tested candidate.
- Both repository skills passed the skill-creator quick validator; the release
  diff passes whitespace checks.
- Release PR CI must pass before merge/publication.
- Migration-owner handoff: https://github.com/alexstanciu-1/simplecpp/issues/236#issuecomment-5771523741
- **Pending:** returned downstream portable-PHP/native proof for the immutable
  candidate. No final release sign-off, tag or publication until it is recorded.
- Release bookkeeping may change docs/version/skills only. Confirm the final
  production tree matches the candidate; rerun affected gates after any code
  changes or conflict resolution.

## Scope and limitations

The fix shares absolute-reference classification between validation and rendering;
it does not disable relative-name validation or add whole-program inference.
Unqualified method/type hiding, qualified return-type lookup and existing STAN
namespace advisories are outside this slice. Linux-only process/lock/snapshot
scope, caller-polled deadlines, snapshot whole-second timestamp limitations and
missing Windows host-helper execution evidence remain as in v0.1.77.

Downstream whole-compiler migration completion is not claimed.

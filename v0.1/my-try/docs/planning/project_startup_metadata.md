# Project composition and immutable startup metadata
Doc Status: planning

Completed 2026-10-21 on dev002 with default template prelowering. V-P01 is
implemented and verified; V-D10 is reconciled against the existing immutable
startup contract and verified. **47/64 original issues resolved (73.4375%),
17 open: +3.125 percentage points.** This is one capability repair and one
intentional-contract reconciliation, not two implementation fixes.

## Changes and ownership

`main.php --project PROJECT_DIRECTORY|PRISM_JSON` now composes the normalized
transitive project graph, selects the root entry, installs the configured runtime
modules and passes explicit project source inventories to the compiler. Nested
projects have disjoint file ownership; generated/VCS files remain excluded.
The existing directory CLI is preserved. See the
[project entry guide](../architecture/project_compilation.md) for usage and limits.

Project configuration, dependency graphs, inventory and public-export policies
reuse `bin/project_services.php`. Its policy enforcement was extracted into one
shared function, preserving report/warn/fail behavior and unconstrained empty
export lists. No second manifest parser, semantic resolver or metadata registry
was added. `Project_Evidence` reads settled collected occurrences and selected
source declarations, including private callable/member collections through their
original file provenance. Runtime identities and unused lookup candidates do not
become project source edges. The host adapts the evidence to existing policy helpers.

Compiler module configuration owns explicit source membership. Invalid selections
are rejected before replacing the session; identical selections preserve retained
identities. Directory discovery and selected-file observation share reconciliation.
Source notifications cannot add files outside an explicit selection. Source edits,
provider deletion/recovery and configuration changes use the existing preparation,
publication and native-cache owners.

V-D10 adds no hot reload. The runtime metadata specification requires validation
before installation and immutable startup records. Disk changes apply to a new
compiler session. Source updates do not reread provider files. Syntax resets reuse
the decoded snapshot with fresh semantic identities; stale callable bindings reject.
The original finding and its unsupported live selective-preservation scenario remain
historical evidence in the issue ledger.

## Dev002 acceptance

Run: `python3 compiler/my-try/tests/project_compilation.py --results FRESH`.
All **25 tasks / 55 commands** passed in `20261021-project-final` using 12 continuously
refilled runner slots. PHP-FPM was attempted; the runner used CLI fallback because
the available CLI/FPM versions differ. Native programs used `/usr/bin/clang++`,
default template prelowering and the existing `Native_CPP_Build` consumer.

| Proof | Result |
| --- | --- |
| Transitive diamond, directory/config dependency spellings, nested roots, deduplication | One shared provider; native output `41:42` |
| Cold/warm native cache | Cold: 7 objects, 1 link; warm: 0 objects, 0 links, 7 reused |
| Provider edit | `51:52`; 1 object rebuilt, 6 reused |
| Provider removal/recovery | Compile failure blocks stale native publication; repair prints `61:62` |
| Removed project dependency | Obsolete owned artifacts removed; remaining executable prints `61` |
| Project policies | 15 report/warn/fail cases: allowed/private/unknown exports, empty allowlist, undeclared dependency |
| Qualified declaration identities | Function, constant and class exports resolve across projects with identical relative filenames; each private-export variant rejects its actual target |
| Invalid inputs | Cycle, missing project, self-dependency, source-symbol collision and missing entry reject with their intended diagnostics |
| Optional provider | Manifest-selected regex compiles/links/runs `regex_quote`; disabling it rejects the call |
| Existing single-directory CLI | Unchanged `--s2s` entry publishes, builds and prints `42` |
| Metadata snapshot | Edited disk constraints and then invalid JSON do not affect the live source update; emitted initial/edited programs print `1` / `2` |
| New metadata session | Fresh process sees the added vector-payload `hashable` constraint and rejects the nested-vector source |
| Identity retirement | Live replacement rejects without changing metadata; reset creates fresh callable identities and stale bindings reject |

Supplementary proofs passed:

- `project_source_selection.php`, `module_discovery.php`, `module_sync.php`:
  selected/nested ownership, ignored files, invalid-config retention, stable source
  identity and notification boundaries. The first new-fixture attempt omitted
  reselecting the entry after configuration reset; its corrected rerun passes.
- Existing `test_scpp_project_module_public_api_validation.php` and
  `test_scpp_project_module_dependency_validation.php`: shared-policy refactor
  preserves the original report/fail/unavailable-evidence behavior.
- Existing `runtime_catalog.php`: private validation, decoded-input lifetime,
  identity reset and stale binding checks.
- Production `tools/build_cpp.php` consumes the final project publication, reuses
  all native objects without relinking, and its returned executable prints `61`.

## Evidence and limits

[Acceptance archive](project-startup-acceptance.tar.gz), SHA-256:
`ec757703e89099e88a7b442103d91dfba4e46fb10f198c8d629c96a6ed1a571e`.
It contains command journals/stdout/stderr, final task summary, source audit,
original-policy regressions and the historical source-overload rejection.
[Source delta](project_startup_source_delta.json) extends the previous scalar-debug
snapshot; the archive contains the complete manifest. **5,367 inputs matched** the
local candidate with no mismatches. The remote archive checkout was
`/home/alex-ai/curl/20261020-work`; test outputs were outside that source tree.

The first integration attempt lacked the existing host debug includes in the remote
fixture checkout; syncing those dependencies resolved that setup failure. Two
fixtures initially lacked their output parent directory; the corrected fixtures
pass. The qualified-name probe also confirmed the existing source-function overload
restriction (`S2S source function overloads are deferred`); the final identity test
uses distinct supported namespaces. No overload implementation was added.

This does not certify full `scpp build`/Ninja/packaging parity, arbitrary native
library projects, JSS/legacy projects, live metadata replacement, a checked
run/current-build reader, or a native rebuild of the compiler. Unprepared deferred
bodies have no fabricated dependency evidence. Existing project-module naming and
assignment policies were reused, not redesigned. Other runtime and architecture
issues remain open. No runtime implementation changes or full matrix rerun.

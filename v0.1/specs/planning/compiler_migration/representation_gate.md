# Native representation prerequisite results
Doc Status: planning

Date: 2026-09-21. Outcome: **passed on the selected v0.1.76 PHP++ target**.
The original older pin fails the richer-field prerequisites, as retained below.
No compiler representation or portability-converter behavior changed in these checks.

## Selected implementation target: v0.1.76

The user selected v0.1.76. GitHub's annotated tag and peeled commit were verified
using `git ls-remote`; the isolated checkout is exactly
`8cc4d8ff7eb395c6cc69219f25bef960323bce45` (tag object
`1add67ac96259f73c4be95323d13ade7aae55dd1`).
[Target configuration](../../../compiler/tools/portability_target.json) records
this selection separately from the older LLVM/provider dependency.

**All nine native build/run probes passed with STAN enabled**, using strict projects,
Clang 18 and explicit project-local runtime builds:

- Original eight probes: scalar copy, vector/hash of inline records, string fields,
  vector/hash of strings, integer-keyed hash, and class-handle sharing/rebinding.
- Added v0.1.76 probe: inferred and explicitly typed `new Struct()` produce values;
  copying and mutating the inferred struct preserves the original string.

The [v0.1.76 result](results/representations-v0.1.76/summary.json) records all commands,
fixture hashes, timings and clean-checkout verification. The earlier
[v0.1.75 comparison](results/representations-v0.1.75/summary.json) passed the original
eight probes before the user selected v0.1.76. The new constructor probe was added
for v0.1.76; it was not run as part of the v0.1.75 comparison.

Release notes are preserved from each verified checkout. The browser could not
retrieve the release pages; direct GitHub tag verification and checked-in changelogs
provided the release provenance. v0.1.75 added the richer fields; v0.1.76 fixed
no-argument struct value construction. Existing provider configuration and adopted
source fingerprints remain unchanged.

This closes the bounded representation prerequisite for the selected release.
Cross-file layouts, broader nested shapes, lifetime/aliasing cases and real compiler
components still require focused proofs as they are adapted; nine probes are not
evidence of complete compiler portability.

## Historical original pin and evidence

The configured target is
`/home/alexv/__AI/simple_cpp_compiler/vendor/simple_cpp/bin/scpp.php`, revision
`fc20d73d040c4e69758bcec0b1caf40c26755f72`, from `compiler/tools/toolchain.json`.
The checkout remained clean at the same revision after execution. Tests used
strict projects, Clang 18 and explicit project-local runtime builds.

[Probe sources and contracts](../../../tests/portability/native_representation/README.md)
and the [completed result](results/representations-02/summary.json) retain exact
commands, configurations, source hashes, timings and diagnostics. Fixtures are
hand-authored PHP++ capability witnesses, not converter output.

| Struct field | Build/run result | Observable evidence |
| --- | --- | --- |
| `uint32` | Passed | Original 7; copied record changed to 9 |
| `vector<element_row>` with inline uint32 record | Passed | Original element 7; copied container element changed to 9 |
| `hash<element_row>` with inline uint32 record | Passed | Original element 7; copied container element changed to 9 |
| `string` | Rejected by generator | `unsupported first-slice field type string` |
| `vector<string>` | Rejected by generator | `unsupported first-slice field type vector<string>` |
| `hash<string>` | Rejected by generator | `unsupported first-slice field type hash<string>` |
| `hash<string, int>` | Rejected by generator | `unsupported first-slice field type hash<string, int>` |
| Ordinary class `Shared_Value` | Rejected by generator | `unsupported first-slice field type Shared_Value` |

Both normal builds and `--no-stan` retries were attempted for rejected cases. These
are generator capability failures, not merely legacy STAN diagnostics. Copy/sharing
behavior for rejected field types remains unverified because execution is unreachable.
The initial `representations-01` attempt omitted runtime building and its controls
stopped at missing runtime artifacts; it remains setup evidence only. The corrected
run built the runtime and proved all three controls.

## Why the target matters

The pinned revision's own compact-layout spec describes the older, restricted
field set. This repository's current compact-layout spec describes strings, ordinary
class handles and recursively composed containers, but that newer support must not
be attributed to the older pinned checkout. Documentation is not a native proof.

The existing PHP compiler contains direct strings and source-buffer references;
portability must preserve those concepts. Do not replace them with path IDs, buffer
IDs or wrapper classes just to pass this old target. Likewise, passing a container
of fixed-layout records does not imply that arbitrary `vector<T>`/`hash<T, K>`
fields work, and does not prove complete compiler portability.

## Next migration work

Use the v0.1.76 implementation target for dependency-ordered portability slices.
Keep the adopted compiler's LLVM/runtime-provider dependency pinned for baseline
comparison: it serves a different role. `compiler/tools/toolchain.json` and the
preparation configuration retain the original dependency; the separate
`compiler/tools/portability_target.json` owns the selected implementation target.
The probe runner accepts its checkout through `--target-checkout`, verifies the
exact configured commit, and never falls back to a globally installed CLI.

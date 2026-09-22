# Baseline validation plan
Doc Status: planning

## Status and inspected environment

Fresh isolated baseline execution is recorded under `results/`. The first run
completed with 107/110 compiler fixtures passing and all six preparation suites
passing. Its three compiler failures came from the baseline harness relocating an
include path to an absolute path where those tests require config-relative paths.
A corrected full run completed in `results/baseline-02`: 109/110 compiler fixtures
passed, with one 180-second timeout. That fixture passed alone in 79.696 seconds
using the original runner and unchanged timeout. All six preparation suites and
post-test integrity checks passed. Earlier foundations-tracker results remain
historical evidence, separate from these fresh migration results.

### Fresh results (2026-09-21)

| Gate | Corrected run outcome | Seconds |
| --- | --- | ---: |
| B01 Compiler suite | 109/110; `integration/provider_family_runtime_types.php` timed out at ten jobs | 240.327 |
| B01 focused retry | Timed-out fixture passed alone, original 180-second limit | 79.696 |
| B02 Provider package | Passed, 170 checks | 53.182 |
| B03 Provider families | Passed, 54 checks | 42.102 |
| B04 Lifecycle investigation | Passed, O0/O1/full/ThinLTO | 18.632 |
| B05 Source operation bridge | Passed, O0/O1/full/ThinLTO | 13.656 |
| B06 Specializations | Passed, native comparison/allocation/reuse/rejection proofs | 79.674 |
| B07 Sequence aliasing | Passed, 12 cases each at O0/O1/sanitized | 3.414 |
| B08 Integrity | Passed before/after both runs and after focused retry | — |

The corrected [summary](results/baseline-02/summary.json) deliberately retains
`passed: false`: a focused retry does not erase the default-concurrency failure.
The [retry record](results/baseline-02/B01-retry.json) supplements that result.
All 110 fixtures have passing evidence across the corrected run and retry, but
there is not yet a completely green ten-job run. Load sensitivity is a hypothesis,
not an established root cause. Carry this timeout into relocation comparison;
measure a consistent lower concurrency if a repeatable whole-suite gate is needed.

The initial [summary](results/baseline-01/summary.json) and logs retain the three
harness-induced failures; changing only the runtime include path from absolute to
config-relative resolved all three in the corrected run. Neither prototype code
nor assertions were edited. Both runs used the pinned external runtime, not this
repository's newer runtime. B04 and B05 remain native/hand-authored LLVM experiments,
as their reports state; their passes do not imply production compiler integration.

| Input | Inspected value |
| --- | --- |
| Compiler source | `75e9b0f7c3f6420255b3b126429afbfa0e13ccb1`, clean |
| Configured Simple C++ dependency | `/home/alexv/__AI/simple_cpp_compiler/vendor/simple_cpp`, clean at `fc20d73d040c4e69758bcec0b1caf40c26755f72` |
| Historical toolchain selector | `tools/toolchain.json`, branch hint `codex/compiler-vector-runtime`; verified_commit matches actual dependency HEAD |
| Native backend | `tools/backend.json`: clang, mold, target auto, compile_jobs 20 |
| Provider preparation | clang++-18, C++23, vendored runtime include root and local provider include root |
| Available PHP | 8.5.7; POSIX, JSON and tokenizer extensions present |
| Available native tools | clang/clang++ 18.1.3, ld.lld-18 18.1.3, mold 2.40.4 |
| Python | 3.12.3 |

Do not switch to the current product runtime before measuring the original baseline.
That would combine a migration with a dependency upgrade and obscure attribution.
The current checkout's portability sample passes do not prove this compiler baseline.

## Isolation and evidence protocol

- Use an isolated snapshot of the pinned tracked source, preserving directory
  relationships. The original is read-only; some proof runners otherwise write to
  `prototype/generated-runtime/proofs/` by default.
- Resolve the original configured runtime/toolchain to explicit pinned locations in
  the isolated copy, recording the configuration-only diff. Relative sibling paths
  will not automatically work after copying to a scratch directory.
- Use a distinct workspace for each preparation proof. Preserve failed artifacts
  and full stdout/stderr/exit status, not just a final pass/fail count.
- Record compiler/runtime revisions, source/config hashes, tool versions, OS/target,
  commands, fixture counts and elapsed times. Keep raw evidence in a durable migration
  results directory before discarding scratch work. Timing is contextual, not a new
  performance target.
- Run suite groups sequentially to avoid multiplying their own internal concurrency.
  Begin with the runner defaults unless host limits require a documented adjustment.
- Expected negative diagnostics are part of passing tests. Unexpected failures are
  recorded as baseline failures; do not fix them in the original checkout.

## Required baseline matrix

Commands below run from the root of the isolated source snapshot, not the original.
Substitute distinct absolute workspace paths where shown.

| Gate | Command / check | What must be preserved |
| --- | --- | --- |
| B01 Compiler suite | `python3 prototype/tests/run.py --jobs 10 --timeout 180` | 105 registered PHP + 5 Python fixtures; runner lints prototype PHP and checks registration. Native execution, workers/joins, purity, snapshots, provider integration, diagnostics and existing increment proofs |
| B02 Provider package | `php prototype/src-runtime-preparation/tests/run.php` | Standalone generation/reuse, metadata/ABI, validation and ordinary/full/ThinLTO execution |
| B03 Provider families | `php prototype/src-runtime-preparation/tests/families/run.php <fresh-workspace>` | Runtime-only family preparation, exact argument identities, lifecycle and reuse |
| B04 Whole lifecycle investigation | `python3 prototype/src-runtime-preparation/tests/lifecycle/run.py --workspace <workspace>` | Native/LLVM shell lifecycle evidence; keep its investigation-only interpretation |
| B05 Source operation bridge | `python3 prototype/src-runtime-preparation/tests/source_operations/run.py --workspace <workspace>` | Compiler-owned lifecycle/native-template boundary and expected link failures; not blanket production support |
| B06 Specializations | `python3 prototype/src-runtime-preparation/tests/specializations/run.py --workspace <workspace>` | Bounded source/native-family proof, native comparison, replacement/reuse; keep run's defaults recorded |
| B07 Sequence aliasing | `python3 prototype/src-runtime-preparation/tests/sequence_aliasing/run.py` | Spare-capacity/forced-growth append at O0/O1/ASan+UBSan; recorded sanitizer limitations retained |
| B08 Source/provenance integrity | Compare inventory hashes and original Git state after execution | No original-source modifications; only recorded config adaptations in snapshot |

The main suite includes CLI/single-file execution, lowering, source/native exports,
owned results, generic permissions and family compositions. Use that registered
coverage before inventing a redundant new full suite. Successful stdout alone is
not the acceptance criterion for lifetime/reuse/purity witnesses.

## Later gates, not prerequisites for the first read-only inventory

- **Relocated-PHP equivalence:** repeat the same baseline after full adoption with
  only relocation/config changes. Attribute any differences before portability edits.
- **Port target probes:** the source working rules require focused proofs of struct
  fields containing string, vectors, hashes and class handles, including snapshot
  copy/reference behavior. Perform against the explicitly selected migration toolchain
  before native adaptation; do not silently change data models to bypass failures.
- **Component dual execution:** add PHP++ harnesses around converted production
  components; retain the original assertions even when their PHP harness is host-only.
- **Whole migrated compiler:** compile the generated PHP++ implementation and exercise
  the existing compiler/preparation capabilities and applicable baseline assertions.
  No production execution may depend on the old compiler checkout at completion.
- **Performance archives:** preserve benchmark fixtures and reports now. Replay the
  documented scalability workload when assessing performance changes; it is not a
  prerequisite to declaring a read-only inventory complete.
- **Historical original PHP++ tools:** preserve them; `tools/check_compiler.py` targets
  the old root `src/`, not the active PHP prototype. Do not count it as proof that
  the new full compiler implementation is already portable.

## Completion records

For each gate record `not_run`, `passed`, `failed`, or `blocked`, plus command,
inputs, log paths, exit status and limitations. `results/baseline-01` preserves the
initial setup failure, exact driver and configuration. Its post-test integrity
check passed: original compiler/runtime Git state stayed clean, original compiler
hashes matched, and snapshot changes were limited to the two recorded configs.
Successful adoption/migration must not be inferred from baseline execution alone.

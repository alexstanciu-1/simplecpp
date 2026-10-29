# Git exclusion inventory

Doc Status: historical

Archived: 2026-10-29. Retained as a dated record; statements about pending work,
validation counts and next steps describe the original milestone, not current status.

Date: 2026-10-29. Scope: whole repository, with v0.2 as the active source of truth.
This is an inventory and recommendation, not authorization to delete legacy
sources, remove evidence or rewrite history. No ignore rules were changed.

## Measurements

Before adding this report: 59,243 tracked paths, approximately 467.81 MiB of
current on-disk tracked-file contents. These are not Git compressed-object sizes.
The working tree was clean. Git reports 116.95 MiB loose objects and 140.81 MiB
packed objects; the whole checkout occupies about 973 MiB including ignored files.

| Tracked area | Files | MiB | Interpretation |
| --- | ---: | ---: | --- |
| compiler/my-try/docs/planning/ | 900 | 270.92 | Planning text mixed with large evidence archives and repeated checkpoints |
| tools/compile_latency/results/ | 17,239 | 54.55 | Experimental output trees, generated code, LLVM IR and run logs |
| specs/planning/compiler_migration/results/ | 17,742 | 40.15 | Historical migration evidence; local ignore explicitly retains logs |
| specs/planning/results/ | 9,223 | 28.53 | Profiles, diagnostic runs and other curated evidence |
| runtime/public/ | 415 | 0.54 | Legacy public surface; includes generated headers |
| compiler/my-try-cpp/samples/ | 392 | 3.44 | Mixed authored samples, generated compiler corpus and measurement records |
| v0.2/runtime/generated/ | 0 | 0 | Correctly untracked, including compiler publication |
| v0.2/build/ | 0 | 0 | Correctly untracked |

The first four rows total about 394.15 MiB, roughly 84% of tracked content.
This does NOT mean every file in those directories is disposable. Preserve
handwritten plans and selected reproducible proof summaries.

## Recommended exclusion inventory

| Category | Paths/examples | Recommendation |
| --- | --- | --- |
| Generated v0.2 APIs and plans | v0.2/runtime/generated/, including compiler/ and prepared.json/inventory.json | Keep current exclusion. Regenerate from runtime metadata, consumer publications and generator sources. |
| Native build products | build/, out/, cmake-build-*/, objects, libraries, executables, CMake/Ninja/CTest state | Keep out of Git. v0.2 already covers its build directories and in-source Ninja/CTest state. |
| Dependency publication snapshots | compiler/my-try-cpp/dependencies/ | Already ignored in legacy compiler. Adopt an explicit local dependency-output rule if v0.2 introduces this directory. |
| Test scratch outputs | tests/.runtime/, tests/.run-tests/, native output folders, disposable generated fixtures | Exclude; untrack existing scratch output separately. Preserve authored inputs and intentional expected-output fixtures. |
| Python caches | __pycache__/, *.pyc, *.pyo | Directory rule exists; add suffix coverage if wanted. One tracked .pyc remains in a reference fixture. |
| Raw experiment output | tools/compile_latency/results/ generated trees, *.ll, run logs, stdout/stderr captures | Stop adding raw runs. Archive historical runs externally, retain scripts, inputs and compact result summaries. Do not blanket-ignore all .ll/.stdout files repository-wide: some may be intentional tests. |
| Repeated evidence archives/checkpoints | compiler/my-try/docs/planning/language_verification_evidence/, evidence.tar.gz, evidence.json.gz | Review for external archival; keep a small manifest with revision, command, toolchain, result and artifact hash/location. |
| Large review result trees | specs/planning/results/, specs/planning/compiler_migration/results/ | Separate durable summaries from raw runs. Existing retained-log policy must be consciously changed, not silently overridden. |
| Generated legacy headers/corpora | runtime/public/scpp/integers.hpp; compiler/my-try-cpp/samples/my-try/ | Exclude from future v0.2 imports. Before legacy untracking, verify regeneration and consumer requirements. Samples README documents its refresh command. |
| Profiling and coverage output | *.profraw, *.profdata, *.gcda, *.gcno, coverage output directories | Add scoped rules when adopting these tools; current root rule *.prof does not cover these suffixes. Preserve selected report summaries. |
| Machine-local configuration | CMakeUserPresets.json, .env, local credential/config files, editor session state | Add explicit rules for local presets/secrets; keep shareable CMakePresets.json and safe configuration examples. This was a path inventory, not a secret-content audit. |
| OS/editor scratch | .DS_Store, Thumbs.db, swap and backup files | Reasonable preventative exclusions; no tracked instances found by the targeted scan. |

## Already tracked despite ignore rules

`git ls-files -ci --exclude-standard` finds **1,811 paths**. Grouping:

- 1,683 under tools/compile_latency/ (primarily ignored output/log categories).
- 118 under tests/.runtime/ (generated C++ scratch outputs).
- Three shell launch scripts under build/operator_matrix_server_runs/.
- Two extension development configurations under tools/vscode_phs_extension/extension/.vscode/.
- Two distributed PHP AST binaries: ext/8.4-deb_php_ast.so and ext/php_ast.dll.
- One sandbox source, one tests/php path, and one cached Python bytecode file under
  tests/portability/reference/pre-rewrite/compiler_context/__pycache__/.

Do not mechanically remove this whole list. Relocate useful build scripts to an
owned tools directory. Shared extension launch/tasks configuration can be deliberate
source. Distributed binaries require a replacement acquisition/build workflow
before removal. Reference fixtures need their purpose checked.

## Keep in Git

- Authored C++/PHP/PHS, handwritten headers, tests and examples.
- Runtime semantic metadata and each consumer's publication requests.
- Generator/pre-lowering implementation and build/validation scripts.
- Shareable CMakeLists.txt and CMakePresets.json.
- Owning specs, migration plans and compact curated validation summaries.
- Intentional golden fixtures, even when their contents resemble generated output,
  provided their testing role is documented.
- Reproducibility lockfiles when relevant; the root's blanket composer.lock ignore
  should be reviewed rather than copied into v0.2 policy.

Do not ignore every JSON, C++ header, directory named generated, or archive by
extension alone. Metadata and fixtures need clear ownership-based rules.

## Suggested cleanup order

1. Keep v0.2's existing generated/build exclusions. Add narrow preventive rules
   for local presets, coverage/profile output and cache suffixes.
2. Review and untrack obvious scratch outputs and bytecode while leaving local
   copies intact. Git ignore rules do not remove tracked files.
3. Establish an evidence archive location and manifest format, then migrate raw
   legacy run trees in reviewed batches. Preserve summaries and proof provenance.
4. Treat old source/reference trees as the separately planned legacy archive;
   excluding their contents is not a substitute for that migration decision.
5. Consider history compaction only as a separate coordinated action. Ordinary
   untracking reduces future checkout content but does not remove historical blobs
   or automatically shrink existing clones. The tracked reference Git bundle at
   compiler/reference/pre-rewrite/reference/history/scpp_compiler_3.bundle (2.51 MiB)
   belongs in this archive review, not automatic scratch deletion.

## Reproduction and limits

Inventory used git ls-files, git ls-files -ci --exclude-standard, git check-ignore,
working-tree file sizes, directory sizes and git count-objects -vH. Representative
headers, ignore files and sample/tool documentation were inspected to distinguish
outputs from sources. No build/test run was needed for this read-only inventory.
No file removal, untracking, ignore-rule edit, remote archival or history rewrite
was performed. Directory totals classify candidates; they are not a file-by-file
proof that each item can be regenerated.

## Safe cleanup applied — 2026-10-29

Follow-up approval applied only the disposable-output cleanup. Untracked 118
generated files in tests/.runtime/, one volatile constants test-results JSON,
and one Python bytecode cache. The test runner identifies .runtime as its state
root and test-results JSON as volatile execution state. All 120 local copies were
retained and SHA-256 verified unchanged after untracking.

Ignore rules now additionally cover CMakeUserPresets.json, sanitizer test scratch
roots, Python bytecode/test/tool caches and OS metadata. v0.2 also excludes raw
profile/coverage output, prospective compiler dependency publications and token
caches. Existing generated API/build exclusions remain in force. Targeted checks
verified both excluded outputs and retained metadata, tests and shared presets.

Historical evidence and archives remain tracked pending a separate archival
workflow. Legacy public headers, generated benchmark corpora, distributed binaries,
launch scripts, intentional fixtures and shared extension configuration were not
removed. No history rewrite was performed. Earlier measurements above describe
the pre-cleanup snapshot, not the resulting tracked file count.

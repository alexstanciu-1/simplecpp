# Complete compiler adoption
Doc Status: planning

Date: 2026-09-21. This checkpoint relocates the existing compiler; it does not
complete the portability migration or add compiler functionality.

## Preservation and development ownership

All 2,968 inventoried tracked files from source revision
`75e9b0f7c3f6420255b3b126429afbfa0e13ccb1` are preserved here. Active compiler
implementation is `compiler/src/`, `compiler/src-runtime-preparation/`, their
bootstrap code and `compiler/tool_process/`. This repository is the sole ongoing
compiler development home. The original checkout remains unchanged reference.

The [adoption inventory](adoption_inventory.json) records every source/destination,
original SHA-256 and adopted SHA-256, including files whose paths/content needed
relocation. All original refs and reachable Git history are in
`compiler/reference/history/scpp_compiler_3.bundle`. A fresh mirror clone and
`git fsck --full` passed, and restored HEAD matches the pinned revision. The bundle
checksum and verification workspace are recorded in the inventory. Original
untracked/ignored caches and generated binaries are not source history.

Two proposed destinations were deliberately adjusted: original AGENTS.md became
`compiler/reference/source-repository/working_rules.md`, and the original prototype
README became `prototype_readme.md` beside it. Both are preserved verbatim. The
active compiler README explains this repository's ownership and authority.
Historical PHP++ remains a preserved reference, not a second maintained compiler.

## Relocation-only differences

The [relocation patch](relocation.patch) is relative to the original tracked files.
Only two production PHP files changed: backend configuration lookup moves one level
closer, and CLI help/error text uses the adopted paths. No algorithms, contracts,
test assertions, fixture membership or test timeouts changed.

Other differences are test/benchmark root calculations, preparation proof output
paths, two dependency config paths, documentation links/command paths/status labels,
and the new compiler entry guide/ignore policy. All active Markdown file links were
checked, including links to the same external dependency. Historical documents and
benchmark reports remain evidence of the prototype, not newly executed performance
claims or authority over the repository's language specs.

The pinned runtime dependency remains external at
`fc20d73d040c4e69758bcec0b1caf40c26755f72`. Adoption does not upgrade it to the runtime
in this repository. The original compiler checkout is not a production dependency.

## Validation

Evidence is in [adoption-01](results/adoption-01/). The compiler suite produced
109/110 passes at ten jobs and a 180-second per-fixture timeout: the same
`integration/provider_family_runtime_types.php` timeout seen in baseline-02.
The same fixture passed alone in 79.132 seconds with the original 180-second limit
(baseline isolated retry: 79.696 seconds). All 110 compiler fixtures therefore have
passing evidence across the adopted suite and retry. The default-concurrency failure
is retained, not relabeled as a pass by a focused retry.

All six preparation gates passed: B02 170 checks, B03 54 checks, lifecycle and source
operations at O0/O1/full/ThinLTO, specializations, and sequence aliasing at
O0/O1/sanitized. [Preparation evidence](results/adoption-01/preparation.json) records
the commands, timings and before/after adopted hashes. Original compiler/runtime
Git state remained clean at the pinned revisions, all original file hashes matched,
and adopted inputs remained unchanged during execution and the focused retry.

This establishes relocation equivalence at the recorded coverage, including the
existing concurrency timeout. It does not establish a fully green ten-job run,
PHP++ portability, new language support, or production integration for experimental
hand-authored LLVM proofs. Broader scalability benchmarking is outside this slice.

A single `64kb`, one-trial scalability smoke check failed in the adopted tree and
then failed identically in the preserved baseline-02 snapshot. Unchanged
`benchmarks/scalability/measure.php:73` reads `callable_binding::$symbol_id`, which
is no longer present, and passes null to `Lowered_Set::for_symbol()`. Both reports
and logs are preserved as `benchmark-smoke.*` and `benchmark-original-smoke.*`.
This is pre-existing benchmark measurement debt, not a successful performance run
or a relocation regression. Repair the measurement contract before using these
benchmarks for migration performance conclusions; no repair was mixed into adoption.

## Next phase

Run the agreed native target representation probes before changing implementation
representations. Then select dependency-ordered portability slices, extending the
separate PHP converter and support library only as needed to preserve behavior.
Keep all adopted code, including lowering, LLVM emission and runtime preparation.
Complete the whole migration before adding compiler features or a new backend.

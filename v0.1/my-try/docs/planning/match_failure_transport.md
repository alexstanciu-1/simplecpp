# VD08-A01 default-less match transport
Doc Status: planning

## Closeout — 2026-10-22

VD08-A01 is implemented and verified on dev002 with default prelowering. The
existing compiled-operation owner now publishes a no-input, non-returning
`match_failure` bridge. It throws the same native `std::runtime_error` with
`Unhandled match expression`; it does not manufacture a source `Exception`.
Ordinary emission is unchanged. Match preparation, selector snapshots, first-match
label order and lazy arm evaluation remain in their existing owners.

This closes V-D08's bounded compatibility issue after the
[compatibility audit](enum_compatibility_closeout.md): two original payload gaps
were repaired, six original extension probes are unsupported in pinned v0.1 too,
and the additional default-less match regression now executes. Overall closure is
**59/64 (92.1875%)**, with V-R08 partial and four other original issues open.
This is issue-count progress, not entire-language coverage.

No runtime implementation changes, full matrix rerun, native compiler rebuild or
exhaustiveness analysis. Reflection, string backing, enum methods/interfaces,
broader constants, wrapper operators/conditions and indexed/general selection
remain explicit separate domains; this closeout does not admit them.

## Verification

Source base `c11c73fa7` plus the six-file overlay recorded in
`/home/alex-ai/curl/20261020-work/vd08-a01-manifest.json`. Host dev002, shared
12-slot runner; CLI fallback because CLI/FPM versions differ. Commands:

```sh
python3 compiler/my-try/tests/match_prelower.py \
  --results /home/alex-ai/curl/20261022-vd08-a01-01
python3 compiler/my-try/tests/enum_compatibility_audit.py \
  --baseline /home/alex-ai/curl/vd08-v01-baseline \
  --cases match,numeric-match,match-default \
  --results /home/alex-ai/curl/20261022-vd08-a01-audit
```

- 20 source executions: enum/integer matched and unmatched paths, multiple and
  duplicate labels, defaults before/after labels and default-only selection,
  selector-once, selector mutation and argument snapshots, enum return, nesting,
  source-catch bypass, unchanged failed-assignment destination, destructor order,
  nested finally, matched return/finally and replacement failure from finally.
- All unmatched paths exit 1 with the existing message; source catches do not
  intercept. Cleanup observer prints `21F9`; failed assignment retains `42`.
- Retained API publication adds/removes default, retires/reintroduces helper demand,
  edits enum values, rejects an unknown label, withdraws ready output, restores
  identical artifacts/manifests and agrees with a fresh compiler.
- All 21 focused publications run cold/warm with application templates forbidden;
  warm builds compile nothing and do not link.
- Three pinned-v0.1/current comparison pairs execute with expected output. The
  existing audit retained publication also passes, as do enum-model, enum and
  property-default PHP suites. Four additional current publications pass cold/warm.
  Baseline revision remains `75de94c71e37060c564b6ade19850b7686bd457a`.

Historical 48-pair audit results are unchanged evidence. Only the two current
match expectations move from generation failure to execution success; source
hashes remain unchanged. No claim of a new full 48-pair run is made.

Evidence archive: `/home/alex-ai/curl/vd08-a01-evidence.tar.gz`, SHA-256
`03e2b3c61e984d0637b7f1895fe360b48badca4c0e8c7cb4676453dd9a1fe240`.
All six overlaid source/test hashes were verified against the executed workspace.

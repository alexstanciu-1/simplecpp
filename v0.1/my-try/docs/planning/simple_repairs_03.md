# Simple repairs 03: JSON build metadata and catalog reconciliation
Doc Status: planning

Authorized and completed 2026-10-19: V-R01 followed by V-D02. Both bounded repairs
passed verification on dev002. All 13 issues originally classified as simple fixes
are now repaired across fixture_repairs_01, fixture_repairs_02 and this batch.
Other language gaps, intentional deferrals and V-R11 remain open.

## R01: production JSON linkage

The JSON module now selects `modules/json/json.cpp` alongside `document.cpp` in
`runtime/metadata/modules/json.json`. No runtime implementation or compiler code
changed. The existing publication fixture accepts an optional module argument,
retaining its original default; the new `json_production.py` test uses that fixture
and the production `Native_CPP_Build` driver.

The strict source fixture executes `json_decode`, `json_decode_mixed`, both
`json_encode` overloads and document parse/root/member/kind/size/at/int calls.
Exact output is `{"n":7}|{"n":9}|array:17`.

| Check | Result |
| --- | --- |
| Private catalog without value provider | Expected production link failure names missing decoder symbols; original missing-provider failure remains evidence. |
| Reload catalog, republish with repaired requirements, reuse failed-build cache | Exactly one provider object compiled, three reused, no PCH rebuild, one link; exact output passed. |
| Fresh cache | Four objects compiled, one PCH built, one link; exact output passed. |
| Warm cache | Four objects reused, no compilation/PCH/link work; exact output passed. |
| Catalog hidden after publication | Both successful build paths consume published requirements without reopening catalogs. |
| Existing runtime_module_build.py | Passed unchanged provider/default-module behavior, body/provider/definition/library invalidation checks. |

No provider source is injected manually into the build invocation. The publication
selects dependencies. This proof uses default dependency scanning; it does not
claim automatic refresh of optional runtime-snapshot caches. Metadata changes require
catalog reload and republication, and runtime-snapshot mode retains its documented
fresh-cache boundary. V-R11 CMake aggregate link ordering was not changed.

## D02: catalog reconciliation

All 141 sections tagged V-D02 in the frozen section ledger now state current scope,
link their contracts/owners, and identify the stable evidence section ID. Earlier
checkpoint or decision prose is explicitly historical; imported v0.1 examples remain
intact. Duplicate pending labels below implemented expression checkpoints were removed
from the visible prose and remain in the archived original documents.

The scope notes retain each probe's limitations. In particular they preserve wider
operator/lifting gaps, restricted unions, string indexing, inherited-property
redeclaration, reference iteration/mutation policy, callable/reference escape limits,
provider gaps and intentional strict/legacy distinctions. They do not turn a bounded
positive into whole-feature support. `division_by_zero` is preserved.

The scalar operator guide now routes field/indexed/eager operations to their later
owners instead of declaring all of them deferred. Catalog navigation explains how
to read current scope versus historical progress. Five scope notes incorporate the
completed fixture and JSON linkage repairs without rewriting the original audit.

[Per-section reconciliation](simple_repairs_03_documentation.json) maps all 141
changes to contracts, checks and scoped evidence. The dev002 documentation check
verified unique coverage, unchanged heading inventory and fenced code examples,
balanced historical blocks, and introduced contract/evidence file links across the
11 affected chapter files. Existing reviewed semantic evidence was sufficient; no
new language execution claims or additional feature probes were needed.

[Original chapter contents](simple_repairs_03_docs_before.json.gz) preserve the
pre-reconciliation wording in full. Original section/check ledgers, audit totals,
failed probes and historical evidence archives were not rewritten.

## Reproduction and provenance

Run from the repository root on dev002 with the PHP 8.5.11 launcher directory
`/home/alex-ai/language-verification/20261018/verification/modern-bin` on PATH:

```sh
python3 compiler/my-try/tests/json_production.py --results FRESH_JSON_RESULTS
python3 compiler/my-try/tests/runtime_module_build.py --results FRESH_MODULE_RESULTS
```

Snapshot: `/home/alex-ai/simple-repairs/20261019-01`. Documentation checks ran in
its isolated `verification/docs-review` tree. Clang 18.1.3 and Python 3.12.3 were used.
All executable verification used the shared runner on dev002; none ran on WSL.
There was no native compiler rebuild or broad matrix rerun.

[Results](simple_repairs_03_results.json) contain observations, toolchain versions,
evidence hash and documentation transfer hash. [Evidence](simple_repairs_03_evidence.json.gz)
contains command logs, expected failure, outputs, manifests, cache observations and
the documentation checker. [Tested sources](simple_repairs_03_source.json) records
6,184 verified file hashes; [baseline](simple_repairs_03_baseline.json) records the
starting worktree. All 423 protected compiler/runtime implementation files were
unchanged. Documentation review files are separate from that source snapshot.

No remaining blocker was exposed within this authorized scope. No follow-on work
or additional implementation goal is authorized by this closeout.

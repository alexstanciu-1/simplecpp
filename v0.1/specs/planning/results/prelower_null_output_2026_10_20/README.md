# Concrete null output through the existing output bridge
Doc Status: supporting

2026-10-20. Completes the null-output gap without rejecting the established workload.

## Implementation and ownership

The internal null sentinel now has a physical `scpp::null_t` binding in the existing
C++ binding registry. It still has no source type name or storable capabilities.
Intrinsic registration resolves its canonical identity through the supplied catalog,
not a second registry or global lookup during candidate configuration.

Null literals record their type/header dependency through the ordinary type owner.
`CPP_Declarations::output` no longer excludes null from generic runtime-operation
transport. The same operation request, fragment replay, helper assembly and native
builder used by other output operands now handle it. The app passes a const pointer
to the existing native sentinel; middleware invokes the original `echo_one` runtime
implementation. No output coercion is reimplemented and no operand is discarded.

The runtime's concrete `null_t` header needed none of `scpp/detail.hpp`; removing that
unused include leaves the class and literal unchanged while making its app-facing
header template-free. No template-family engine branch or new runtime algorithm.

## Evidence

- Fresh O0/no-debug/no-PCH application with nulls interleaved with side-effecting echo
  arguments prints exactly `[p1:p2]2`.
- Recorded native commands cover both published app units, including entry.cpp.
  Fresh preprocessing and full Clang traces report zero template declarations and
  zero instantiations in both units. See [coverage](coverage.json) and [audit](audit.json).
- Warm native build compiles zero objects, reuses eight and does not relink.
- Existing output, indexing and compound O0 suites pass. Direct/concrete output bytes,
  side-effect order, failure prefixes, nested references and boundary counters match.
  Both output runs report seven zero allocation/lifecycle counters. See
  [counter results](output-counters.json); primitive sentinel copies are not tracked
  payload copies, and both sides use the same pointer/reference transport at this boundary.
- Output-demand checks now require a null-sentinel operation request. Existing demand
  retirement/restoration, mode switching and fragment replay pass with no additional
  concrete stored type required for null.

The runtime inventory proof was updated to include the existing concrete string key
in the demanded `hash<vector<int>,string>` closure (four types rather than its stale
expectation of three). It additionally checks that null's physical binding remains
non-opaque/non-storable and creates no specialization demand.

Reproduce using the shared runner:

```sh
python3 compiler/my-try/tests/prelower_null_output.py --results FRESH_CLOSURE_RESULTS
python3 compiler/my-try/tests/prelower_header_boundaries.py --results FRESH_BOUNDARY_RESULTS
python3 compiler/my-try/tests/prelower_contracts.py --results FRESH_CONTRACT_RESULTS
python3 compiler/my-try/tests/runtime_inventory.py --results FRESH_INVENTORY_RESULTS
```

Local evidence: `/tmp/prelower-null-output-closure-20261020`,
`/tmp/prelower-null-output-o0-20261020`, `/tmp/prelower-null-output-contracts-20261020`
and `/tmp/prelower-null-runtime-inventory-final-20261020`. FPM sockets are unavailable in
this sandbox; PHP used the runner's CLI fallback.

No full accounting regeneration, compiler-native rebuild, O2 or performance benchmark
is claimed. This is complete closure for the new fixture and focused runtime parity,
not universal runtime coverage. Other strict enforcement/public configuration work
remains in the solidification TODO.

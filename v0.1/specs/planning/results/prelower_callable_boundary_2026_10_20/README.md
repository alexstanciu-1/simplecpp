# Concrete callable boundary: explicit rejection
Doc Status: supporting

2026-10-20. First bounded implementation slice of the
[strict prelowering contract](../../cpp_template_prelowering_decision_2026_10_19.md#agreed-solidification-contract--2026-10-20).

## Change and ownership

Concrete type emission could fall through to ordinary `std::function` spelling for
structural callable locals, fields and signatures. Nested template callable storage
already rejected through `CPP_Opaque_Representation::require_native_type`.
`CPP_Types` now invokes that existing check when concrete mode reaches the callable
representation branch. Both paths report that concrete callback transport is required.
Shared semantic preparation remains unchanged: the source is valid in ordinary mode.
No new metadata, registry, retained facts, family dispatch or runtime algorithm.

## Proof

The new shared-runner fixture failed before the fix with
`Callable fallback/partial output: local`. After the fix, all five shapes (local,
parameter, result, field, nested container) pass:

- Ordinary mode still emits its callable representation.
- Concrete mode explicitly rejects instead of emitting the template representation.
- Failure leaves no partial `Model::$cpp_files` output.
- Switching back restores exactly the ordinary artifacts.
- Replacing the source with supported vector storage repairs concrete emission;
  subsequent no-op replay preserves identical artifacts.

Reproduce with:

```sh
python3 compiler/my-try/tests/prelower_contracts.py --results FRESH_DIRECTORY
python3 compiler/my-try/tests/prelower_header_boundaries.py --results ANOTHER_FRESH_DIRECTORY
```

Local red/green evidence: `/tmp/prelower-contract-red-20261020` and
`/tmp/prelower-contract-green-20261020`. The runner used its CLI fallback because
FPM socket creation is unavailable in the sandbox.

The existing O0/no-PCH header-boundary suite also passed all three groups: output,
indexing and compound. It compares direct/concrete execution, allocation/copy
counters, evaluation order, nested references and warm native reuse. Results are in
`/tmp/prelower-contract-boundaries-20261020`. These native checks build generated
applications; they do not rebuild the compiler itself.

## Limits

This closes one confirmed fallback; it does not implement callable transport,
complete all strict coverage checks or switch the public CLI to the general path.
The failure proof checks in-memory publication, not on-disk manifest recovery.
Full accounting was not rerun. No O2, performance benchmark or native compiler
rebuild is part of this slice.

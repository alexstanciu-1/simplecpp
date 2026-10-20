# Remaining inspected runtime interface escapes
Doc Status: supporting

2026-10-20. Bounded continuation of the strict prelowering audit.

## Findings and changes

The inspected native-only paths still exposed implementation headers or template
signatures: boxed extraction (`required_cast`), runtime condition truthiness,
non-keyed value probes without a prepared presence operation and enum
compiler helpers. Each now rejects at its existing backend owner in concrete mode.
Prepared semantic operations still select the path; source admission is unchanged.

Integer conditions retain their existing native-value expression before the runtime
condition rejection. Boolean identity, prepared presence and keyed probes keep their
existing paths. Enum declarations are not blanket-rejected; the check applies to
compiler-helper requests whose generated signatures still use ordinary representations.
No metadata, registry, runtime algorithm or new semantic pass was added.

## Validation

The expanded fixture failed before the fix with
`Template fallback/partial output: boxed_extract`. After the fix, all 21 boundary
cases and ten closure-command coverage tests pass. Every boundary case verifies
ordinary emission, explicit concrete rejection, no partial output, non-ready failed
publication, mode recovery, source repair and exact manifest replay. The repair
program explicitly exercises boolean and integer conditions and match with a default.

```sh
python3 compiler/my-try/tests/prelower_contracts.py --results FRESH_DIRECTORY
python3 compiler/my-try/tests/prelower_header_boundaries.py --results ANOTHER_FRESH_DIRECTORY
```

Local contract evidence: `/tmp/prelower-runtime-boundaries-red-20261020` and
`/tmp/prelower-runtime-boundaries-final2-20261020`. O0 boundary results are recorded
under `/tmp/prelower-runtime-boundaries-o0-final-20261020`. The shared runner uses CLI
fallback because sandbox FPM sockets are unavailable.

An initial null-output rejection failed the established output fixture, which emits
null during option switching. That rejection was removed rather than weakening the
proof. Null output still needs concrete transport before the public strict-mode
switch; its existing broad headers are a documented remaining exposure.

The final O0/no-PCH suite passes output, indexing and compound groups, including
direct/concrete behavior, counter parity, nested references, evaluation order and
warm native reuse. It builds generated applications, not the compiler itself.

No full accounting regeneration, native compiler rebuild, O2 or benchmark is claimed.
Provider metadata and transitive headers still need a complete enforcement strategy;
this list of checked operations is not a universal template-free guarantee. The public
CLI/API switch remains pending. Broader runtime support is not implemented by rejection.

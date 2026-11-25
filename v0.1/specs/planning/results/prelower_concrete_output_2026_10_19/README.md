# Concrete output transport
Doc Status: planning

Date: 2026-10-19. Base `560e2a0c` plus this slice.

The existing runtime-operation request/fragment machinery now transports output
operands by const address. Native implementations call the ordinary `echo_one` helper;
formatting, exceptions and semantic output admission remain with existing owners.
Ordinary syntax emission retains one full expression per operand. A const reference
cast borrows lvalues and temporary values without creating a copy or ending a
temporary owner's lifetime before printing. Null has no stored representation and
continues directly through the ordinary runtime helper.

## Validation

`runtime_template_demand.py --output --alternate-storage` passes in 63.00 seconds.
Both ordinary and concrete builds pass O0, O2 and ASan/UBSan (leak detection disabled),
without PCH. A fixed-capacity stream buffer checks exact bytes, scalar passthrough,
reference-return operands, temporary integers, single ordered evaluation, and partial
output followed by division failure. Later operands remain unevaluated. Both modes
report `output 0 0 0 0 0 0 0` and the inherited zero recipe-boundary counters in all
configurations. Primitive numeric copies are not instrumented tracked-payload copies;
the transport borrows existing storage. This is a bounded workload, not a claim that
formatting arbitrary values never allocates.

Source-demand removal retires output helper requests. Restoration, unchanged fragment
replay, option switching and warm zero-compile/zero-link builds pass. The existing
`echo.php` preparation/emission/recovery suite also passes; its generated individual
case executables were not run in this checkpoint. No native compiler rebuild occurred.
All commands use the shared 12-slot runner with CLI fallback after FPM socket denial.
[Compact results](summary.json). Local logs: `/tmp/prelower-output-4`,
`/tmp/prelower-output-echo`, `/tmp/accounting-output-1`.

## Harness correction and failed attempts

The first output run exposed an off-by-one option index, inherited from the earlier
alternate-binding fixture. That earlier alternate run had actually used the default
binding. Both option indexes are corrected, and the driver now independently asserts
the alternate header and output counter marker. The successful final run truly uses
`fixture::Handle` and verifies mutually referring source classes. Intermediate runs
caught the null-sentinel mapping assumption and a missing explicit runtime-error
include in the C++ harness; these were corrected before the final proof.

## Accounting and limits

Accounting publishes successfully and passes its previous concrete integer output
failure. Native compilation now stops at container interfaces: e.g. a hash physically
stores `Concrete<FixedAsset>` while its `index_store` adapter accepts the ordinary
`shared_p<record_FixedAsset>`. Read and append adapters show the same mismatch for
other source classes. This requires consistent physical mapping through existing
source storage and operation bindings, not new indexing semantics or application
workarounds. No accounting source changes were made.

Output for arbitrary nullable/Result types containing concrete payloads still needs
runtime interface coverage; unwrapping only the outer object does not establish that
its formatter accepts every substituted inner type. Whole-accounting execution and
compile-time comparisons remain pending.

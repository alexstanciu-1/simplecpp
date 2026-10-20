# Consistent source storage in template arguments
Doc Status: planning

Date: 2026-10-19. Base `3ea16d2a` plus this slice.

Nested implementation arguments previously forced nominal source handles and value
modifiers into concrete owners independently of ordinary representation selection.
Consequently a container could store an opaque source handle while its prepared
read/write adapter accepted or returned the ordinary native shared handle.

`CPP_Types::implementation_argument` now delegates stored/payload selection to the
same representation query used by ordinary declarations and signatures. There is no
new registry, family dispatch or conversion. Source layouts, semantic identities,
capabilities, preparation and runtime indexing algorithms remain with their owners.
This preserves native source-class shared wrappers consistently; it does not finish
prelowering those wrappers. The full-template-elimination objective remains open.

## Focused proof

`prelower_substitution.py --source-arguments` passes in 87.36 seconds, together with
the source-header-cycle check. The fixture now uses production mappings instead of
forcing opaque storage in its C++ harness. Generated source functions perform append,
indexed store and indexed read on `vector<MyClass>`. Native checks establish exact
pointee identity and the expected shared count after reading. Existing nested field
mutation, value-copy isolation and reference-identity checks remain enabled.

Direct and concrete O0/O2 and ASan/UBSan runs match all allocation and tracked-payload
counters. The shared scenario reports five allocations/96 bytes in both modes and no
tracked payload copies; its setup/container work is included. Reference-only phases
allocate/copy nothing. This proves no additional measured boundary cost, not that
all container work is allocation-free. Primitive and handle copies are not tracked
payload constructors; identity/reference counts separately check handle behavior.

Request order/replay, unchanged fragments, body edits, layout edits and restoration
pass. Warm builds compile/link nothing. The metadata/output proof with the corrected
alternate binding also passes (68.71 seconds), with all measured output and recipe
boundary counters zero. No native compiler rebuild was performed. Shared 12-slot
runner, CLI fallback after FPM socket denial; sanitizer leak detection disabled.
The initial source-argument run was invalidated by extending the fixture during the
run and failed its artifact-stability check. The final run used the settled fixture.

## Accounting checkpoint

The unchanged accounting app now publishes, compiles and runs with concrete type
substitution enabled at O0, without PCH. It reports:

```
accounting workload: invoice/payment/trial-balance checks passed
CHECKS passed=998 failed=0
ACCOUNTING_WORKLOAD_PASS
```

The cold native build compiles 22 units, builds three layout batches and links once.
This is correctness evidence, not a controlled compilation-time comparison. Local
logs: `/tmp/accounting-source-handles-1`, `/tmp/prelower-handles-2`, and
`/tmp/prelower-handles-recipes`. [Compact evidence](summary.json).

## Remaining coverage

There are no currently observed accounting compile/run failures. A reliable total
of remaining full-prelowering gaps is not established. A limited literal scan of
app artifacts (excluding specialization files) finds zero `vector_t<`, `hash_t<`,
`int_t<` or `value_t<` spellings, but 1,374 `shared_p<` occurrences in 48 files. These
are spelling occurrences, not unique types or instantiations. Included runtime
headers and other template helpers were not audited by this scan.

Source ownership wrappers remain explicit template exposure. Wrapper formatting
with concrete inner payloads, other runtime interfaces and unsupported callback ABI
transport remain broader coverage items. Passing accounting does not close them.

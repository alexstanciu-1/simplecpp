# Checked shifts and restricted compact layout
Doc Status: supporting

Completed 2026-10-20 on dev002: **V-L01 and V-L02 close**, advancing the frozen
original inventory from **35/64 (54.6875%) to 37/64 (57.8125%)**, a gain of
**3.125 percentage points**. Supplementary finding **BS-R01 also closes**; it is
outside the original denominator. No batch implementation blocker remains.

## Implemented contracts and owners

BS-R01 follows the approved [checked-count contract](../../../../specs/compact_layout_types.md#71-checked-integer-shift-counts):
`<<`, `>>`, `<<=` and `>>=` throw `ValueError` for negative counts or counts at
least the declared left representation's bit width. Canonical integer payloads
use width 64; narrow C++ promotion does not widen the range. The shared
`operators/arithmetic/integer_shift.hpp` validator serves the handwritten
`scpp/generated/operator_detail.hpp` primitives. The public operator generator
removes unconditional `noexcept` from scalar compound shifts. Existing operand
admission and valid-count behavior remain unchanged.

Source checks preserve left-to-right snapshots and checked extraction. A failed
left carrier extraction prevents RHS evaluation; boxed kind checks happen after
both authored operands execute. Invalid-count checking follows successful payload
admission. Failed compound computation performs no write-back, including when the
RHS has already mutated the destination: that authored mutation survives rather
than being rolled back to the captured left value.

V-L01 adds a distinct `union_type` declaration kind using existing inline nominal
storage, record work and field facts. `Union_Preparation` owns restricted shape
and recursive payload eligibility: bool, all fixed integer spellings,
fixed-backed enums and eligible nested structs, including final structs. It rejects managed payloads,
containers, wrappers, nested unions, methods, constants, inheritance, interfaces,
traits, nonpublic/static fields and union field defaults. No hidden tag or managed
union lifetime is introduced.

A default-constructed union has no active member. Whole-member assignment
snapshots/converts its RHS, then explicitly constructs the selected member. The
same property-write owner handles direct storage and retained shared receivers.
Native proofs cover independent copies, nested struct payloads, member changes,
failed RHS preservation and shared-receiver writes. Inactive-member reads are not
promised; nested access requires an already active payload. These are untagged
payload unions, not primary-type normalized PHP union signatures.

V-L02 installs `layout_sizeof`, `layout_alignof`, `layout_offsetof` and
`layout_field_sizeof` as ordinary compiler-provided callable identities.
`Layout_Query_Preparation` shares observed lexical type lookup, resolves the
optional field, and retains `prepared_layout_query`; there are no runtime
arguments or provider bindings. The backend observes target C++ layout with
complete header dependencies and canonical `int` results. Bare type names,
`Type::class`, imported types, source-function shadowing and producer edits are
covered. No host-side padding assumption, source constant-call evaluation or
external ABI promise is added.

## Verification and evidence

All execution was on `alex-ai@dev002.travelfuse.ro`, in
`/home/alex-ai/compact-shifts/20261020-01`, from base `95e48aeb` plus the recorded
candidate overlay. The shared runner used 12 slots and PHP CLI fallback because
CLI/FPM versions differ. No full operator matrix or native compiler rebuild ran.

| Accepted checkpoint | Proof |
| --- | --- |
| `shifts-05` | Operator regeneration matches except its timestamp; eight native programs, including seven source-generated programs; eight integer representations, two boxed families, four carriers and both bool sentinel states; runtime object and programs instrumented with UBSan |
| `compact-07` | Seven generated programs; all four queries against independent native observations; union lifetime/copy/write controls; 49 rejected source cases; imported/cross-file layouts and one retained error/retry/repair/no-op/fresh comparison cycle |
| `compact-07` production | Cold/warm native builds execute `7:1`; warm build compiles zero units and performs zero links |
| `regressions-04` | 29 PHP checks and 175 native programs covering records, property defaults, data construction, property mutation, enums and boxed/operator behavior |
| `enum-baseline-01` | Separate pre-goal compiler comparison proves the stale enum harness expectation described below |

The compact source programs use ASan/UBSan with leak detection disabled, matching
the existing generated-program fixture policy. Regression programs retain their
own runner flags. Cross-file union headers include their payload definitions;
translation units compile independently without PCH. The original v0.1 union
project probe remains provenance; these checks verify its restricted storage and
layout behavior through the v0.2 API, not project-CLI parity (V-P01).

The [results](compact_layout_results.json), [audit](compact_layout_audit.json) and
[compressed raw evidence](compact_layout_evidence.json.gz) record **751 expected
command outcomes and 193 native executions**: 192 acceptance executions plus one
baseline comparison. All **6,329 candidate file hashes** matched locally and on
dev002. The initial snapshot's **228 non-test compiler PHP files** were also
verified against Git `95e48aeb` for the baseline comparison. The
[initial](compact_layout_source_initial.json) and
[tested final](compact_layout_source_final.json) manifests preserve the tested
inputs; all manifest hashes still match the closed implementation.

Intermediate failures remain in the archive: a test observer initially used
unescaped generated function names, and two regression attempts retained the old
enum SIGABRT expectation. Removing the runtime object did not change that outcome;
the baseline comparison established the actual cause. No compiler fix was made
for the enum observation.

## Separate finding and retained boundaries

**CL-H01 — open harness debt:** the existing `tests/enums.py` expects SIGABRT
(`-6`) for `enum_unhandled`. Both the pre-goal and candidate generated entrypoints
catch the exception, print `Unhandled match expression` and exit 1. The new combined
harness checks exit 1 plus that diagnostic. The original runner remains unchanged;
this is a separate fixture repair, not a new language regression or an additional
closure in the frozen 64.

The earlier BS-R01 left-shift UBSan failures in `boxed_selection` remain historical
baseline evidence. `boxed_selection_shift_boundary.py` is a pre-repair reproduction,
not the current acceptance runner; `checked_shifts.py` owns the new contract.

Still separate: managed/nested union payloads, active-member flow analysis,
packing/external ABI guarantees, general reflection, inherited/polymorphic or
nonpublic offset layouts, computed/static field selection, primary-type normalized
PHP union parameters, broader fixed-width shift admission, boxed compounds and
wider updates. The catalog's former CS161 reference to V-L01 for normalized union
parameters was a routing error; closing restricted payload unions does not close
that different language surface.


## Implementation commits

- `c026e072`: shared checked shift-count contract, runtime repair and focused proofs.
- `e173100a`: restricted union preparation/storage, layout queries and focused proofs.

The closeout/evidence commit is separate. Unrelated Chapter 15 working-tree edits
and the pre-existing test bytecode cache were left untouched.

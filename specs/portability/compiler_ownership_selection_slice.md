# Ownership request selection
Doc Status: supporting

Ownership_Selection reads a fixed Body_Set and optional Type_Store and produces
Ownership_Request records. It selects owning structural definitions, source-body
identities and lifecycle prerequisites. Ownership_Queue owns missing-dependency
checks, readiness, execution and accepted result publication. Selection does not
execute workers, mutate type definitions, or replace the runtime-preparation tool.

Type enumeration uses existing ordered Type_Store accessors rather than adding a
second structural catalog. Compound type/symbol keys replace nested member maps;
ordered Ownership_Child records preserve field ordinals and reverse destruction.
The selector retains source-operation member roles, including copy fallback during
movement and no automatic field work for custom assignment. Those last paths still
need broader integration proofs; implementation is not evidence of coverage.

Body dependencies are selected from checked calls, value construction, return mode
and write mode. Raw allocation owners stay on their existing direct analysis path;
only static aggregate resource paths participate in these requests. The queue
receives canonical lifecycle keys and deduplicated dependency keys.

## Current proof status

Eight PHP/native cases pass: no owning body, borrowed aggregate, local construction, raw
allocation exclusion, direct record return, borrowed source call, owned source-call
return and repeated construction dependency deduplication. Additional assertions
cover two-field destruction order, unavailable copy/assignment exclusion, exact body
identity, body-only selection without a type store and incremental queue reuse.
Owned-return cases assert the exact nested result path and empty state, not just
PHP/native output agreement. The first native build passed without corrective cycles. Evidence is saved in
`specs/planning/compiler_migration/results/ownership-selection-01`.

Positive custom lifecycle-body selection and concrete copy/move/assignment contracts
remain integration obligations. The current storage fixture intentionally does not
grant these operations. Do not weaken its lifetime policy to make a test pass.

## Integration correction

Body checking represents zero-initialized record returns as RETURN_STORE. Allocation
analysis previously required direct/copy/move construction for every resource-owning
return, rejecting the already prepared facts of VALUE_RECORD_DEFAULT. Inspection
found the same mismatch in the preserved prototype. Allocation_Pass now transfers
prepared facts for precisely RETURN_STORE + VALUE_RECORD_DEFAULT and consumes that
temporary before cleanup. Arbitrary resource stores remain rejected. This is an
allocation-analysis correction; it does not grant source copy/move permissions or
change body checking's representation. Existing allocation-pass (8 cases) and
allocation-flow (15 cases) PHP regressions pass after the correction. The passing focused
native selection proof includes the corrected dependency source.

Run `python3 compiler/tests/ownership_selection/run.py --results FRESH --target-checkout TARGET`.
First PHP readiness is `/tmp/ownership-selection-php-03`; subsequent expanded proof
is `/tmp/ownership-selection-php-10`. Preserve both timing boundaries when consolidating.
The early harness needed two dependency-loading corrections; expansion needed one
checker-only key-expression correction and one unsupported move-fixture correction.
The discovered return integration mismatch required one production correction.

## Custom lifecycle integration in progress

`compiler/tests/ownership_lifecycle_selection/run.py` now supplies ten PHP-passing
cases: constructor, destructor, copy constructor, copy assignment and all four
combined, each with accepted checked bodies and with those bodies deliberately
omitted. The fixture parses actual methods, materializes their record contracts,
creates concrete receiver instances, resolves signatures/locals and checks bodies.
Selection must retain each role's exact concrete callable, include its body dependency,
and reject missing checked source bodies. Complete selected queues also run in PHP.
The first native proof is running at `/tmp/ownership-lifecycle-selection-native-01`.
No production changes were needed for these additional cases. Broader source
copy/move/assignment expression dependency coverage remains separate.

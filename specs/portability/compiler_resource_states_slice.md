# Resource-state relations
Doc Status: supporting

Resource_States preserves the prototype four-bit relation model: one two-bit output
set for empty input and one for owned input. Composition, required-state acceptance
and deterministic-input queries keep their original meaning, including missing and
ambiguous outputs. File-scope RESOURCE_* constants replace unsupported class constants.

The converter's supported token set has no bitwise/shift operators. The portable
implementation expresses the two finite lanes using modulo/division and explicit
set union, with no temporary arrays or table allocation. This is a local algorithm
adaptation, not a converter extension. Relation values are explicitly restricted to
0..15 and required masks to 0..3; out-of-domain integers now fail at this internal
boundary instead of accidentally depending on the prototype's bit truncation.
Consumers must supply validated relation values. No deeper performance claim is made.

All 336 valid-domain outcomes agree in PHP/native with the original helper and an
independent edge-set relational oracle (256 compositions, 64 required-mask queries,
16 determinism queries). The independent oracle follows the preserved resource
contract test's edge-set method. Tests also cover 4,096 associativity combinations,
both identity directions for all 16 relations, and six invalid-domain rejections.
These prove the algebra, not whole-body allocation safety. Resource locations,
aliasing, effects, fixed-point flow and ownership acceptance remain to migrate.

Run `python3 compiler/tests/resource_states/run.py --results FRESH --target-checkout TARGET`.
Evidence: `specs/planning/compiler_migration/results/resource-states-01`.

# Body language bindings
Doc Status: supporting

The real body worker consumes metadata-indexed literal and echo operations from
Type_Resolution. Contextual literals select by result type; context-free literals
require a default provider. A byte-span parameter receives decoded bytes directly.
Echo selects the binding for the produced canonical type. No provider-name inference
or native function execution occurs in this compiler-checking proof.

20 PHP/native scenarios cover exact empty/binary/UTF-8 payloads, distinct contextual
and default providers, direct spans, multiple echo operands, nested provider calls,
exact argument IDs and call targets, captured provider identities, missing bindings
and unsupported Unicode escapes. Existing expression-order consumers read the
resulting body, and input type-store sizes remain unchanged.

Run `python3 compiler/tests/body_language/run.py --results FRESH --target-checkout TARGET`.
Evidence: `specs/planning/compiler_migration/results/body-language-01`.
No production change was required. Lowering, provider execution, storage-element
integration and managed lifetime analysis remain separate obligations.

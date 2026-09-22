# Struct-member traversal decision
Doc Status: planning

Status: typed-cursor recommendation accepted by the user on 2026-09-22.
PHP implementation and all ten consumer adaptations completed on 2026-09-22.
Focused PHP and 39 retained fixtures pass; native query-dependency proof remains
outstanding. The whole-file ready count remains 36.

Before adaptation, Syntax_Access::struct_members was a PHP generator with ten
production consumers.
Eight use foreach, one materializes through iterator_to_array, and one counts through
iterator_count. No caller in the audited compiler uses send(), throw(), generator
return values, or arbitrary rewind. The input syntax snapshot is read-only by contract.

Implemented contract: a typed Struct_Member_Cursor captures tree/declaration/requested
kind, with advance(): bool and current(): int. Construction captures only; the first
advance validates the struct roles, preserving deferred validation. Subsequent
advances follow the sibling list and skip other member kinds. The cursor retains
constant-size traversal state and the shared syntax tree. It does not build a member
list unless the existing consumer already requires one.

Affected consumers:
- resolve_types/record_join.php: materialized field IDs.
- resolve_types/source_lifecycle.php: method iteration.
- resolve_types/main_prepare_concrete.php: selected members.
- resolve_types/records.php: field iteration.
- check_templates/body.php and terms.php: field iteration.
- resolve_symbols/handlers/names.php: member lookup.
- collect_symbols/collect.php: method collection.
- collect_symbols/utilities/declaration_syntax.php: field collection.
- collect_symbols/join.php: member count.

This changes a shared structural-query return contract and callers across semantic
owners. The user explicitly authorized this scope on 2026-09-22 under AGENTS.md's
cross-owner-refactor rule.
The alternative offered is an eagerly built typed vector, which is simpler to consume
but allocates membership and changes validation timing. Neither option includes
general PHP generator conversion or new compiler functionality.

Implementation validation must cover empty/mixed member lists, ordering, wrapper
unwrapping, deferred validation, early termination, repeated advance, current-value
contract, unchanged tree identity, materialized/count consumers and affected semantic
regressions, followed by a PHP/native query proof. Full parser readiness is a separate
claim and remains blocked in part by the source-diagnostic dependency.

## Implementation checkpoint

`parse\Struct_Member_Cursor` owns forward-only traversal. Construction captures
inputs; the first advance calls the unchanged structural-role validators. Current
is available only after a successful advance; repeated current reads are stable.
Exhaustion or traversal failure is terminal, and current then throws LogicException.
Advancing from a result follows its next-sibling link only when requested. Input
syntax remains read-only and shared. No general generator conversion was added.

Eight consumers now use advance/current loops, one explicitly materializes the same
ordered vector, and one counts. The focused registered parser fixture compares with
the original generator algorithm and checks empty/mixed members, template wrappers,
filtering/order, deferred invalid-name/declaration errors, early stopping before a
poisoned tail, repeated current/exhaustion and shared tree identity/purity.

All 39 selected parser, semantic, template and backend-preparation fixtures passed
through the existing isolated run_fixture harness. The initial direct invocation
failed from an incorrect cwd; its failure and the corrected run are retained.
Changed PHP lint and diff whitespace checks pass.

The cursor file alone passes the portability checker. With its actual Syntax_Access
and Metaprogramming_Syntax owners, the checker rejects existing `??` syntax in the
trait. This is the next local adaptation dependency, not a new v0.1 request. No
native build was attempted with fabricated validators or partial production files.
The cursor is not added to the ready manifest before its real dependency proof.

Evidence: [struct-member-cursor-01](results/struct-member-cursor-01/summary.json).
Next: adapt the structural query/trait owners, retaining their errors and shape
validation, then establish the complete PHP/native query proof.

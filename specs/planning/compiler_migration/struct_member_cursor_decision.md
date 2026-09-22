# Struct-member traversal decision
Doc Status: planning

Status: typed-cursor recommendation accepted by the user on 2026-09-22.
Implementation and behavioral proofs remain outstanding.

Syntax_Access::struct_members is a PHP generator with ten production consumers.
Eight use foreach, one materializes through iterator_to_array, and one counts through
iterator_count. No caller in the audited compiler uses send(), throw(), generator
return values, or arbitrary rewind. The input syntax snapshot is read-only by contract.

Proposed contract: a typed Struct_Member_Cursor captures tree/declaration/requested
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

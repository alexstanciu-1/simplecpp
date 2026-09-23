# Checked resource expression traversal
Doc Status: supporting

Allocation_Traversal connects the checked Expression_Order cursor to resource
contracts. Runtime call arguments are removed from pending borrows before metadata
effects; source-call summaries retain the full active borrow set during checking.
Allocation-backed reads require owned storage and local element borrows remain
active until consumed. Explicit maps preserve value-ID membership without relying
on PHP array_diff_key or generators.

Resource_Field_State replaces constructed-value nested arrays with a named private
field-state owner. Constructor summaries start in empty destination storage;
copy/move source contracts apply before constructing the destination. Unconsumed
field facts are returned to the statement owner for transfer or destruction. Temporary
destruction checks the complete selected summary. Definitions, signature metadata
and preselected dependencies remain authoritative; no recursive body inference or
converter resolution is introduced.

Pass owners are constructor-injected because uninitialized named object fields are
not currently convertible. The outer allocation pass must assemble one consistent
observation context for contracts/traversal. Solver instances carry no observations;
validation instances do. Attributed failures propagate from binding, runtime effects
or common contracts through the existing frontend diagnostic projection.

25 PHP/native cases use real checked bodies and explicit expected outcomes. They
cover balanced acquisition/release, empty count/release, double acquisition,
transfer overlap, indexed reads, nested record borrows, current-call consumption,
pinned-borrow invalidation, supplied source-call summaries, constructed expression
facts, construction requirements, temporary destruction and copy/move source effects.
Lifecycle summaries in helper cases are supplied fixtures, not inferred callee
contracts. This is not a retained full-worker differential proof or a complete
allocation-safety result. Statement/scope traversal, fixed-point merging, ownership
summary inference and final acceptance remain separate dependencies.

The first native build passed without corrective cycles. Earlier checker/PHP fixture
corrections and expanded coverage are preserved in the evidence; first PHP readiness
remains the initial nine-case checkpoint.

Run `python3 compiler/tests/allocation_traversal/run.py --results FRESH --target-checkout TARGET`.
Evidence: `specs/planning/compiler_migration/results/allocation-traversal-01`.

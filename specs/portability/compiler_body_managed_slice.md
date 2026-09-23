# Managed-value body integration
Doc Status: supporting

Twenty-three PHP/native scenarios exercise authoritative lifecycle permissions through
real signature/local/body checking: explicit/implicit default construction, copies
from owned locals and const borrows, assignment, owned provider results, direct
return construction, move/copy return selection, call-scoped const borrowing, and
rejection when copying/default/assignment/expiring construction is unavailable.
Exact statement write/return modes and borrowed source selections are checked.
Provider metadata defines five opaque managed categories; tests neither run their
native operations nor claim cleanup scheduling or source lifecycle-body integration.
No production adaptation was needed. Lifetime analysis remains responsible for
consumption, cleanup ordering and allocation ownership. Storage and managed body
plan coverage is now present; debug export and complete stage coordination remain.

Run `python3 compiler/tests/body_managed/run.py --results FRESH --target-checkout TARGET`.
Evidence: `specs/planning/compiler_migration/results/body-managed-01`.

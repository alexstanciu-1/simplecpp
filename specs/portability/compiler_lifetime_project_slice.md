# Lifetime project selection and publication
Doc Status: supporting

Lifetime_Set retains ordered immutable analyzed bodies and ownership results.
Lifetime_Inputs snapshots accepted ownership membership and owns keyed body lookup.
Lifetime_Selection selects on full rebuild, exact checked-body change, or accepted
ownership-result identity change. Equal summary meaning alone does not authorize
reuse of a result tied to different accepted input provenance.

Lifetime_Join validates exact selected identities and one result per selected task,
then verifies every current body and ownership input before constructing a new set.
Unselected results must be reusable from the previous set. Removed bodies disappear;
worker completion order does not change publication order. No partial set is exposed
and prior sets are not mutated. Shared Step/session orchestration and debug exports
remain separate; this slice does not introduce a generic phase interface.

The first eleven PHP cases pass: reuse, full rebuild, reversed result order, changed
body, changed ownership, removed work, duplicate tasks/results, incomplete results,
unexpected results and stale results. A twelfth case checks that deliberately omitting
work after an ownership change fails at publication. Expanded PHP validation passes all twelve cases.
No native proof yet. First PHP checkpoint: `/tmp/lifetime-project-php-01`; the delegate
owner correction in Lifetime_Worker also passes `/tmp/lifetime-project-php-02`.

Run `python3 compiler/tests/lifetime_project/run.py --results FRESH --target-checkout TARGET`.

Commit review checkpoint, 2026-10-19: the existing runner passed 12 PHP
outcomes, portability checking, conversion and unchanged-conversion reuse in
`/tmp/develop-lifetime-project-20261019`. No target checkout was supplied, so this
checkpoint does not establish native execution or whole-compiler integration.

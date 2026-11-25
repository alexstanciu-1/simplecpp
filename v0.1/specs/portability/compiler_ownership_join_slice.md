# Ownership batch acceptance
Doc Status: supporting

Ownership_Join validates an entire fixed task batch before publishing accepted
results. Exact task identity, allocation-body provenance, parameter and resource-path
membership, const-source rules and complete owned-result fields are checked. Immutable
Ownership_Summary construction already owns endpoint and state-domain validation.
Previous results remain unchanged on rejection. Reuse retains only a semantically
equal previous summary handle; the current task and allocation provenance survive.

Ownership_Contracts replaces prototype PHP object equality with explicit typed
membership and transition comparison. Parameter/field insertion order and canonical
alias ordering are not semantic differences. All four transition facts, parameter
and field membership, alias exclusions and owned-result states participate. This
avoids depending on PHP array order or object comparison in the native result.

The focused proof passes 15 PHP/native acceptance/rejection cases and 18 symmetric
comparison assertions. It covers reordered batches, equal-summary retention, changed
contracts, a real checked-body worker result, const/move sources, malformed fields,
incomplete/duplicate/foreign/stale results and unchanged previous identities after
failure. First native build passed without corrective cycles.

Request selection and scheduling, complete lifetime publication and whole-stage
coordination remain outside this checkpoint. This is a concrete typed join; it does
not introduce a generic callable interface or change the runtime-preparation tool.

Run `python3 compiler/tests/ownership_join/run.py --results FRESH --target-checkout TARGET`.
Evidence: `specs/planning/compiler_migration/results/ownership-join-01`.

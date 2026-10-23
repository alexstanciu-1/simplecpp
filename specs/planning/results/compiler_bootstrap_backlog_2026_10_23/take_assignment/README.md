# Conditional take assignment checkpoint
Doc Status: supporting

Reuse: existing prepared_take operation distinguishes payload success from boolean
sentinels; existing expression preparation owns evaluation order, and structured
completion already consumes pending local identities. local_condition_paths extends
only invocation-local context, never retained expression/type facts. Ordinary
expression dispatch discards incidental child correlations. Direct take, ! and lazy
&&/|| publish outcome paths; if, loop and conditional-expression branches consume them.
No saved-boolean correlation, arbitrary boolean algebra, constant folding, out-parameter
protocol or generic nullable payload expansion is introduced.

/tmp/take-assignment-03 passes 23 tasks: two native programs, 20 negative cases and
both-mode retained guard edit/reject/revert/reset. Ordinary 23.92 s, concrete 25.18 s.
The fixture privately opts the existing shared json_pull_reader provider into
result_payload to execute success/failure and true-sentinel paths. Production metadata
is unchanged. Native execution tests real wrapper states and shared identity.
Initial fixtures incorrectly used true instead of true_sentinel, then configured the
retained runtime catalog after reset. Both setup issues were corrected.

/tmp/take-local-regression-01 passes all 67 local-assignment tasks. The existing take
preparation suite passes in /tmp/take-assignment-retained-01. Production condition-path
composition passes PHP, legacy native (21.31 s) and ordinary/concrete v0.2
(23.96 s / 24.12 s) in /tmp/take-assignment-portable-01. This is a component proof,
not execution of the compiler natively or a full-suite claim.

Bootstrap /tmp/compiler-bootstrap-take-assignment-01: 75.10 s wall time /
339,992 KiB RSS (332.0 MiB) / 310 MiB PHP allocation. Preparation fails first on
required file-field initialization. Aggregate diagnostic texts are unchanged from
the prior local-assignment checkpoint; this does not measure individual repaired
owners. No fatal/OOM, emission or native compiler rebuild. The isolated production
scpp_portability_lock_empty factory still reproduces the out read-before-assignment
diagnostic (/tmp/take-empty-lock-probe-01). Its empty required-handle contract belongs
to the pending runtime ownership/binding audit, not conditional take assignment.

Required field migrations, runtime API empty-handle contracts, generic nullable take
and remaining backlog items are not closed here.

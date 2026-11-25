# LOCAL-01 structured definite assignment checkpoint
Doc Status: supporting

Reuse audit: compiler_local01_completion_integration_2026_10_23.md maps the existing
owners. Invocation-local pending storage identities extend statement completion's
existing exit categories. No retained cache, extra AST traversal or CFG is introduced.
Direct writes do not read their target; RHS preparation must succeed first. Conditional
expressions and lazy operands preserve alternative paths. Loop/selection/exception
owners compose path facts; do-while condition follows body/continue preparation.
Catch/finally entry is conservative, without effect analysis. Runtime catch/iteration
bindings are initialized at entry. Struct defaults/lifetimes are unchanged.

/tmp/local-assignment-03 passes 65 tasks: four native executions, 60 rejections and
both-mode retained edit/reject/revert/reset. The first fixture attempt used an unsupported
runtime Exception constructor and assumed constant-folded while(true) reachability;
replaced with existing Exception::make and unconditional for syntax. No new constant
folding was introduced merely for that fixture.

/tmp/local-assignment-portable-02 passes PHP, legacy native (22.22 s), ordinary v0.2
(24.76 s) and concrete v0.2 (24.83 s). It extracts production completion methods and
statement_completion, with minimal invocation/storage scaffolding. It checks distinct
storage identity, independent vector snapshots, joins, transfer/finally facts and
unreachable reads. First legacy fixture attempt needed explicit vector annotations on
array literals assigned to properties; the production implementation was unchanged.

/tmp/local-assignment-control-01 passes 10 of 12 PHP suites. incremental_cpp's prototype
default spelling assertion and specialization_dispatch's unprepared-expression call
fail identically on archived baseline 8299abaee. Saved diagnostics establish this;
they remain regression debt. All 137 emitted control-flow programs subsequently
compile and execute under /tmp/local-assignment-control-native-01. The encompassing
control_flow.py command is not claimed green.

Initial bootstrap /tmp/compiler-bootstrap-local-assignment-01: 73.95 s wall time /
339,744 KiB RSS (331.8 MiB) / 310 MiB PHP allocation. First diagnostic remains required
file-field initialization. Added aggregate diagnostic: Required local may be read
before assignment: out. No fatal/OOM or emission/native compiler rebuild.

Inspection finds facade take outputs plus an empty file-lock factory. The take output
lookup was corrected to write-only after that checkpoint; success-conditioned flow
facts remain open. LOCAL-01 is therefore in progress, not complete. The measured
revision predates that lookup correction; the final checkpoint is recorded below. This is not a native compiler rebuild or closure of
remaining runtime API/cleanup presence guarantees, converter initialization, class
self retention, nullable wrappers, tasks, cursor metadata or CH15 registry debt.

After the write-only take lookup correction, /tmp/local-assignment-04 rechecked all
65 existing tasks successfully. Its two new json_node output fixtures initially
failed because the fixture loaded the compiler module without the json module.
/tmp/local-assignment-take-01 loads json and passes both native modes (20.17 s ordinary,
23.19 s concrete), proving that an unused required output can be bound without a read.
This does not prove conditional use after a successful take; that is the next slice.

Final bootstrap /tmp/compiler-bootstrap-local-assignment-02: 74.09 s wall time /
339,964 KiB RSS (332.0 MiB) / 310 MiB PHP allocation. Preparation still stops first
on required file-field initialization. Against shared_identity, the only added
aggregate diagnostic is "Required local may be read before assignment: out"; none
are removed. Facade take success-flow and empty-handle contracts remain open. No
fatal/OOM, emission or native compiler rebuild. The earlier rejection point prevents
interpreting these numbers as a full-bootstrap performance improvement.

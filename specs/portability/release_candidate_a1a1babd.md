# Release candidate a1a1babd downstream proof
Doc Status: supporting

Tested exactly `a1a1babd07082d9abf7ac885b2328c99368ad4cf` on Linux x86-64,
PHP 8.5.7 and clang++ 18.1.3, strict profile with normal STAN. The candidate
checkout remained clean. The test pin was temporarily set to this revision and
restored byte-for-byte; this checkpoint does not adopt a target or expand the
36-file ready manifest.

The [returned #236 handoff](https://github.com/alexstanciu-1/simplecpp/issues/236#issuecomment-5771710352)
contains the revision, commands, results and blocker. Evidence is under
`specs/planning/compiler_migration/results/release-a1a1babd-01/`.

## Results

All ten established native suites pass: cumulative compiler (36 ready files and
21 retained fixtures), collections/adapters, process/lock facades, method signatures,
container returns, map iteration, nested containers, snapshot ownership, UTF-8 and
traits/incremental conversion. The fast suite, four supplementary rejection/oracle
checks and 39 retained query fixtures also pass. Structural-query oracle coverage
is 27,560 result/error comparisons.

The deep PHP OS lifecycle test initially failed with EPERM writing a local Unix
socket pair under the execution sandbox. The unchanged test passes when run with
the required permissions. Initial and retry results are both retained; this is
independent of the passing native facade test. The original aggregate run therefore
records two failures, while the final assessment has one remaining behavioral blocker.

## Constructor proof and newly exposed runtime blocker

The pending six-production-file query/cursor project now builds and links. It
executes template unwrapping, filtered cursor traversal and the same-named
function_parts method/record construction. This verifies that #235's construction
rejection is cleared for the real migration component.

The project then fails at a field-role query: a guarded optional traversal executes
when its left-hand condition is false. An independent converted PHP/native witness
uses a false integer comparison followed by a boolean-returning method that prints
`rhs-called`. PHP prints only `done`; native prints `rhs-called` then `done`.

Generated C++ combines bool_t operands with overloaded operator&&, then casts the
result to native bool. The overload requires eager argument evaluation. The cast
does not restore PHP/source short-circuiting. No target patch, compiler source
workaround or altered expected output was used. This is a newly exposed runtime
blocker; these runs do not classify it as a regression introduced by this candidate.

The pending query component remains outside the ready manifest. Do not equate its
successful compilation with a passed PHP/native behavioral proof. The v0.1 owner
received the diagnostic and owns target triage; this workspace owns the rerun.

## Reproduction and provenance

`runner.py` records the orchestration, temporary pin selection/restoration and every
command/exit. `query-runner.py` replays the existing complete query dependency files
and independently expected output. `scpp-a1a1babd-shortcircuit.py` records the small
behavioral diagnostic. The retained-fixture runner uses the existing compiler test
harness and isolated fixture directories.

The only repository test-code change is an optional full candidate hash argument
for collections.py; exact revision and cleanliness checks remain enabled. No
migration framework, production compiler implementation or target code changed.
The saved summary includes implementation hashes, candidate selection, host, test
commands and durations. Build caches, binaries and disposable filesystem symlinks
are excluded from durable evidence; fixture sources and creation scripts remain.

This downstream Linux proof does not add Windows/macOS/Android execution coverage,
implement the new filesystem PHP adapters, or finish whole-compiler migration.
Release notes, skill review, release-tree reconciliation and publication remain
with the v0.1 owner.

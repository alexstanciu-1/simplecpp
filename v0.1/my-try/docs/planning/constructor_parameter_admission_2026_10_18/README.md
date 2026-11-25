# Constructor parameter concrete-value admission
Doc Status: supporting

2026-10-18: DR-04 semantic admission repaired; DR-08 source-location work remains open.
Ordinary constructor parameters now use Declaration_Preparation::parameter_contract,
including the same concrete-value validation and semantic dependency registration as
ordinary by-value function inputs. Constructor fixed-by-value restrictions remain;
property-backed inputs retain their existing copy-admission path. No new model or
backend validation was introduced.

The focused regression failed before the change because an abstract constructor
parameter passed preparation. The final tests reject 12 unused/direct/inherited
constructor signatures across class/struct consumers, abstract struct inputs,
value<AbstractClass> inputs and explicitly abstract structs without abstract methods.
Two same-instance concrete-to-abstract edit/retry/repair sequences use unused
constructors, so abstract allocation rejection cannot mask signature invalidation.
Four generated control programs compile, link and execute (exit 9), covering concrete
inline inputs and abstract shared handles through inherited constructors.

Commands from repository root:

```sh
PYTHONDONTWRITEBYTECODE=1 MY_TRY_PHP_EXECUTOR=cli python3 compiler/my-try/tests/records.py --suite constructor_parameters --no-pch --results FRESH_DIRECTORY
```

The existing constructors.php and abstract_dispatch.php suites also passed through
the shared 12-slot CommandRunner/run_tasks pool (PHP preparation/emission checks).
Their generated programs were not rebuilt in this regression pass. CLI execution
was selected after the preceding review established sandbox FPM socket unavailability.
The focused suite built its generated programs without a PCH. This is not a native
compiler rebuild or a broad regression proof.

A first post-fix run exposed a missing cpp_probe.php import in the new test harness;
that import was corrected before the successful recorded focused run. No production
change was needed for that harness error. Source locations remain message-only under
DR-08; do not label this a completed structured-diagnostic repair.

[Focused results](focused/summary.json), [source fingerprints](provenance.json),
focused command logs, pre-fix rejection evidence and regression logs are preserved
here. Original temporary paths in command journals are provenance. Executables,
objects and generated program bundles are omitted and reproducible through the test.
Other diagnostic issues and pre-existing deferred work are untouched.

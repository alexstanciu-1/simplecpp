# Incremental compiler test backlog

Doc Status: planning

## Goal and completion criteria

Exercise the dependency and recovery boundaries of the retained PHP-flavor numeric
frontend. Run the outstanding cases on dev002 in one goal. Use one live session per
edit sequence, an untouched control where applicable, and independent expected
native results. Check prepared bodies, emitted units, written files and Ninja edges
separately. Compare published C++ with a clean retained session and execute both
builds and production. Failed requests must preserve the last published bundle.
Finish with the default suite at 24 jobs. Fix reproducible defects with an
unambiguous expected result. Record other findings with a reproducer, impact and
the decision needed. Keep timing and stress work on demand.

## Existing tests to reuse

| Boundary | Evidence in compiler/tests |
|---|---|
| Byte changes, parse failures across files, retry, deletion, duplicate declaration and surviving definition | translated_retained_frontend.py |
| Body or return type changes, conversions, precision, overflow, transitive caller reuse | translated_retained_frontend.py and retained_parameter_incremental.py |
| Arity, multiple parameters, named slots, converted defaults, rename and rebinding | retained_parameter_incremental.py |
| Retarget, withdraw and restore a watched symbol, pending work through missing-call repair | Both retained test modules |
| Bundle locks, rollback during installation, retry, obsolete unit removal and restart | translated_retained_frontend.py |
| Production flavors, constants/globals, repeated edits, ownership and cleanup | retention/equivalence.py and compiler/docs/incremental_equivalence.md |
| Build inputs, source add/remove/content changes, module selection, link order, Ninja restat and toolchain cache invalidation | CTest compiler_native_source_inventory, compiler_native_module_graph, compiler_native_ninja_publication and compiler_native_toolchain_qualification |

## Test sequences for this goal

| Batch | Edits and required observations |
|---|---|
| A. Shared callee | Two direct callers omit a default, a third supplies it, and an outer caller calls a direct caller. Change the default and then its spelling while retaining its converted value. Only direct consumers of the changed value should rebuild. Edit a caller and the callee together to replace an omitted default with an explicit argument, then edit the default again. |
| B. Float identity | Change a default from positive zero to negative zero, then to an equivalent negative-zero spelling. Verify dependency precision, emitted sign, native integer results and fresh equivalence. |
| C. File ownership | Introduce a duplicate in a different source file; repair by withdrawing the original. Preserve the stable symbol, remove the obsolete owned file, install the replacement, and ensure the next default edit reaches its caller. |
| D. Superseded publication | Block publication after a default edit, change the input again before retry, then release the lock. Publish only the newest result and settle to zero work. |
| E. Entry target | Switch main between two omitted-default calls. Verify ownership of definition.cpp, withdrawal of the obsolete unit, exact rebuilds, old and new default watches, and actual executable exit status. |

## Production incremental coverage

Use the running production compiler for these sequences. These tests can proceed
independently of extending the native retained example. Audit existing assertions
first and add only missing observations. The four production batches were verified separately on dev002; see the production results below.

| Priority | Edit in one live session | Evidence to reuse and remaining question |
|---|---|---|
| 1. Instance properties and class layout | Change a field type, remove it with a reader still present, then repair. Include a constructor, reader, writer and an unrelated type with the same field name. | classes_native.py and required_fields_native.py already compare resident and fresh builds. Identify exactly which bodies reprepare, which type headers change and which objects rebuild through those headers. Check failure preserves publication. |
| 2. Methods and dispatch | Edit an override body, then change its contract, remove the override and restore it. Keep a same-named method on an unrelated class. | virtual_methods_native.py already checks selected native rebuilds and idle-unit reuse. Map the existing assertions before adding cases for the selected implementation, base-typed caller and unrelated receiver. |
| 3. Constants | Change a leaf constant, retarget a dependent expression to a different leaf, then edit each leaf. Include a same-named constant on an unrelated type. | computed_constants_native.py already covers chains, cycles, alias retargeting and repair. Verify exactly which consumers keep or drop the dependency, including class/struct-qualified reads where supported. |
| 4. Construction and member lookup | Change a field initializer, constructor default or member visibility; exercise inherited lookup, direct construction and an unrelated consumer. | Reuse named_construction_native.py, struct_properties_native.py and interface_inheritance_native.py. Separate semantic invalidation from rebuilding via a changed header. |

For every uncovered edit, record prepared body identities where observable, emitted
sources, changed header and source bytes, persistent Ninja edges, and behavior
against a fresh compilation. Record instrumentation gaps explicitly. An aggregate
reuse counter cannot establish that a particular body did zero work.

## Follow-on coverage triggers

| When supported in the retained service | First incremental test |
|---|---|
| Module imports and visibility | Move a declaration across modules, remove and restore the import, and keep a private same-name declaration in a control module. |
| Constants and globals | Change a constant used in a body or default; separately change a global's type and initializer. Observe direct consumers and unchanged siblings. |
| Classes, interfaces and methods | Change the selected override, then remove it to exercise fallback. Track affected dispatch sites and an unrelated class. |
| Callable values, closures and references | Edit capture mode, callable signature or reference mode while retaining an unchanged enclosing caller; verify diagnostics and repair. |
| Additional flavors | Reuse the same edit sequences at the shared preparation boundary and add flavor-specific binding/diagnostic cases. |
| Retained project driver integration | Change selected sources, flags, toolchain and a dependency's link input while the service stays alive. Compare the actual project build with a clean build. |

The existing production fixtures and project-driver tests are the starting points
for these follow-ons. Keep extended retention cycles, leak checks and timing
matrices on demand. Record any new coverage request against its owning layer.

## Execution record on dev002

Each row is a separate CTest unit in the default suite. Times are isolated runs.

| Completed | Seconds | Observation |
|---|---:|---|
| A. Shared callee | 2.76 | Both default consumers rebuilt. Their outer caller reused its object. After the peer switched to an explicit argument it stopped rebuilding on default edits. |
| B. Float identity | 2.29 | The omitted argument changed sign in emitted C++; changing only its equivalent decimal spelling left caller files and objects untouched. |
| C. File ownership | 2.41 | The duplicate failed before publication. Repair withdrew the old unit and installed the surviving definition. A later default edit rebuilt its direct consumer. |
| D. Superseded publication | 2.20 | The blocked edit left the bundle intact. The successful retry published 17 without publishing 13. |
| E. Entry target | 2.67 | The role change replaced the entry target unit and withdrew its old file. Changing the active default rebuilt main.o alone. Actual process exit codes matched production. |

**Finding repaired in the test harness.** An edit affecting only main.o rebuilt
that object without relinking the test observer, which links only function
objects. The Ninja assertion now models the observer's actual inputs. The test
also links and executes the program with its real entry point.

No compiler defect or unresolved result was observed in these five sequences.

Final verification on dev002: 452/452 default tests passed with 24 jobs in
27.50 seconds, including the missing-call repair regression and all five batches.

## Production execution plan

Run four resident PHP-flavor edit sequences, one per production batch above, on
dev002. Compare each revision with fresh diagnostics and generated bytes. Run a
persistent native build with compiler depfiles and independent expected output.
Measure emitted units, changed headers, changed C++ units and rebuilt objects.
Use same-named controls on different types. Preserve the last bundle after failed
edits and require zero work on a settled retry. Run the existing broader flavor
checks through the default suite. Keep clear local repairs with regression cases;
record ambiguous or structural findings here for review.

## Production results on dev002

Each new PHP-flavor CTest unit drives one production compiler process across
all edits, compares every successful bundle with a fresh process, verifies
diagnostics and preserved C++/headers on failure, executes each successful
revision against independent expected output, and executes the final fresh build.
All four settled retries reported zero prepared bodies, emissions, changed files
and rebuilt objects. Same-named controls remained unmodified throughout the edits.

| Batch | Isolated time | Preparation and native observations |
|---|---:|---|
| Class property | 0.97 s | Changing Node.x from int to byte prepared Node's constructor, makeNode, readX, writeX and main. Only the reader's C++ and one header changed. The five corresponding objects rebuilt. Removing x diagnosed the reader and writer; repair matched fresh. |
| Methods | 1.07 s | Editing Child::value's body prepared that method and main. Only child.o rebuilt. An incompatible override failed. Removing the override restored base dispatch; child.o and main.o rebuilt, while the base-typed reader and unrelated type retained their objects. |
| Constants | 1.08 s | After switching readLimit from Left::LIMIT to Right::LIMIT, editing Left prepared zero bodies and changed zero files or objects. Editing Right prepared and rebuilt only the reader. Removing and restoring the constant recovered cleanly. The transitive namespace-constant fixture also passed both old- and new-leaf edits (1.66 s). |
| Construction and inherited lookup | 1.10 s | An inherited constructor default edit prepared Base's constructor and both construction sites. Only the base and omitted-argument objects rebuilt. An initializer edit changed one header and zero C++ units; base, child and both construction sites rebuilt. Protected visibility produced the expected error, and repair succeeded. |

The standard report exposes aggregate preparation counts and emitted source
paths. For the body names above, a separate dev002 run traced the successful
function-body preparation loop in a temporary copy. The test assertions ran
again against that copy. The production compiler and its reporting API are
unchanged. The default tests use the uninstrumented compiler and real Ninja
depfiles.

### Open findings for design review

- **Override body-only edits.** The concrete call in main re-prepares although
  its generated C++ and native object remain unchanged. The base-typed reader
  stays cached. Before narrowing the dependency, establish which receiver and
  declaration changes need to invalidate concrete dispatch. On dev002 the
  measured difference was 2.60 ms at 128 trivial concrete callers and 11.20 ms
  at 512, relative to base-typed callers. [Timing report and samples](benchmarks/override_preparation/README.md).
- **Explicit inherited-constructor arguments.** Changing a default also
  re-prepares the site supplying that argument explicitly. Its generated C++
  and native object remain unchanged. Review how inherited constructor lookup
  and per-argument default dependencies can be distinguished safely.

Both findings affect preparation cost. No incorrect execution, stale output,
or failed repair was observed. No production change was required for correctness.
The integrated default suite passed 456/456 with 24 jobs in 30.66 s.

# Concrete iteration through the existing runtime protocol
Doc Status: planning

The adapter supplies concrete range, iterator, sentinel and entry representations.
It delegates every operation to the existing native value-iteration protocol and
reuses ordinary foreach source lifetime, prepared bindings and control flow. Native
state is stored inline; existing layout probes/cache resolve size and alignment.
Requests retain the existing prepared iteration facts in the fragment operation
inventory. No semantic pass, registry, family-specific engine implementation or
parked `_02` work was added. `_02` was untouched.

## Proof

```bash
python3 compiler/my-try/tests/prelower_substitution.py --iteration --results FRESH
```

Passed in 190.30 seconds on the aa6eb108 base plus this slice. PHP executor used CLI
fallback because the sandbox could not bind FPM sockets. This proves generated
native programs, not a native rebuild of the compiler. Debug disabled, no PCH.

Vector, hash, nested vector and metadata-registered custom `tray<T>` loops execute
through both backends. The fixture checks source evaluation once, key order,
empty-loop binding retention, continue/break/return, payload-copy exceptions and
cleanup. Inherited reference, initializer and conversion proofs remain active.

O0, ASan/UBSan with disabled optional elision, and controlled O2
(`-fno-assume-sane-operator-new -fno-elide-constructors`) match all counters.
The iteration workload has 25 allocations / 331 bytes / 25 frees on both paths.
The isolated tracked-payload loops have zero allocations, three copies, zero moves
and three destructions, including early return, in all configurations.

**Stock O2 differs:** ordinary 24 allocations / 323 bytes versus concrete 25 / 331.
The extra 8-byte allocation is an unresolved optimization difference, not established
optimized allocation parity. No new claim about full runtime performance is made.

Demand removal drops iteration requests. Request-order replay, unchanged semantic
preparation, option switching, body edits, layout edits and restoration pass through
the existing harness. Three layout batches suffice; no parallel layout cache exists.
The summary retains native build/cache counters and the iteration workload results.

Focused ordinary iteration and custom runtime-protocol PHP regressions passed.
The changed production backend files pass the portability checker; that is not a
native portability proof.

## Accounting and limits

Unmodified accounting emits 285/285 prepared fragments, publishes its C++ and
reaches Clang. Its first native failure is missing concrete integer increment
support in calendar code (`year++`, `month++`). That prelowering gap is not bypassed
with an application workaround. Other native failures may follow; no complete
accounting executable or compilation-time comparison is claimed.

The adapter currently requires a substituted source representation and preserves
the existing non-mutating runtime iteration contract. Unsubstituted source adapters,
mutation guards and broad runtime metadata coverage are outside this slice.

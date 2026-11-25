# Positional constructor transport
Doc Status: supporting

Date: 2026-10-19. Branch: `v0.2/prelower_templates`. Base: `a98e1fbe`.

Selected source constructors now reuse the existing prepared object construction,
prepared call, ordinary argument sequencer and native allocation selector. The
method worker is renamed `CPP_Source_Calls`: methods and constructors share native
argument value categories and transport. A constructor has no receiver operand;
its resulting native owner is constructed directly in final facade storage by the
existing result callback. No family-specific engine code, new AST or registry was
introduced. Current semantic admission remains fixed by-value constructor inputs.

## Proof

```bash
python3 compiler/my-try/tests/prelower_expressions.py --constructors --results FRESH_DIRECTORY
```

The fixture extends the existing source/method workload with:

- ordered positional arguments with independently checked effects and values;
- complete and partial defaults;
- shared, unique and inline class construction;
- struct construction with explicit/default property-backed parameters;
- a native source-value parameter copied and used inside the constructor;
- injected failures during snapshot copying, native parameter copying, and
  constructor-body copying after field initialization;
- existing cached replay, demand removal, remove/restore, failure recovery,
  configuration switching, fresh equivalence and native layout/object reuse.

Identical test-only tracked fields count native payload lifecycle. Constructor
bodies, allocation helpers and cleanup are emitted by the compiler. No runtime
construction behavior is substituted.

| Scenario | Allocations | Bytes | Frees | Constructions | Copies | Moves | Destructions |
|---|---:|---:|---:|---:|---:|---:|---:|
| Expanded source workload | 31 | 2496 | 31 | 14 | 15 | 4 | 32 |
| Constructor argument-copy failure | 0 | 0 | 0 | 1 | 0 | 0 | 1 |
| Constructor parameter-copy failure | 1 | 256 | 1 | 1 | 1 | 0 | 2 |
| Constructor body-copy failure | 1 | 256 | 1 | 2 | 2 | 0 | 4 |

All O0/O2 and ASan/UBSan runs passed with identical direct/ABI counters.
Warm builds compiled and linked nothing. A method-body edit reused the layout
and rebuilt one support unit; a layout edit rebuilt the probe and seven units.
See [counters](summary.json) and [cache results](source-cache-summary.json).

The required native allocation before constructor parameter/body failure is freed.
Live-payload assertions check complete cleanup. Direct and ABI counts are compared
within O0, O2 and ASan/UBSan configurations. Sanitizer runs disable optional elision;
the existing ownership-inspection factory then adds one move/destruction equally.
LeakSanitizer is disabled. These are generated-program native proofs, not a native
compiler rebuild or a new compilation-speed benchmark.

Raw final run: `/tmp/prelower-constructors-final`. Focused PHP constructor,
constructor-parameter, method, method-parameter, method-return, ownership,
initialization and destructor suites passed in `/tmp/prelower-constructor-checks`,
along with opaque emission/replay and scoped conversion of five backend files.
The shared runner used CLI fallback because FPM sockets were unavailable.

An initial fixture attempted constructor reference parameters; shared preparation
correctly rejected these as outside the current constructor contract. The fixture
was corrected without changing semantics. A development run preceded the final
fixture's struct cases; the final run covers both representations consistently.

## Remaining scope

Named construction, static/provider calls, property places and broader reference
transport remain follow-ups. The [native-place proposal](../../prelower_native_places_2026_10_19.md)
records the field-mutation contract needed next. Constructors with private access
from native source methods continue using the ordinary native path; this fixture
does not establish new private/inherited-constructor boundary coverage.

[Owning guide](../../../../compiler/my-try/docs/architecture/cpp_template_prelowering.md)

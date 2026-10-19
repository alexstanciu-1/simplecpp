# Demand-driven source payload checkpoint
Doc Status: supporting

Date: 2026-10-19. Branch: `v0.2/prelower_templates`. Base: `5737d992`.

The runtime ABI path now consumes source-defined payloads through the existing
class emitter, canonical types, capabilities, operation bindings and layout cache.
There is one native definition per source type. Fields, method signatures/bodies,
static storage and dispatch use ordinary native bindings. Native implementations
are assembled into `support/units/`; application functions stay in `units/` and do
not acquire native payload headers merely by sharing a source file.

Application representations are demand-driven. Implicit class handles, explicit
ownership applications and the existing `value<T>` modifier request the same
generic opaque storage/lifecycle mechanism. No family or source-type names were
added to the engine. An unused unique/weak/value form is not generated simply
because shared storage is used. Native method signatures do not request facades.
Existing source declaration emission is retained; this is not dead-code elimination.

Default construction without a selected constructor call consumes the settled
construction facts through the existing operation request and native allocation
helper. A callback constructs the original native result in final owner storage.
No source AST rewrite, second semantic registry, retained worker or alternate
layout cache was introduced.

The source deletion/restoration proof exposed transient type IDs in initialization
factory keys. Generated initializer helper names now use resolved native bindings
instead, preserving output identity when source declarations are recreated.

## Proof

```bash
python3 compiler/my-try/tests/prelower_expressions.py --source-types --results FRESH_DIRECTORY
```

The compiler emits the application, source class/struct definitions, default
construction, native methods/destructors, container operations and source factory
functions. The fixture inserts identical over-aligned tracked members into both
emitted payload definitions solely for allocation/copy/lifetime measurement.
Native inspection code consumes generated factory results to assert identity;
it does not substitute construction or ownership implementations.

Coverage:

- implicit shared source-class storage, explicit unique ownership and value storage;
- source structs containing runtime containers, and nested containers of source handles;
- shared aliases designate the same payload, with zero payload copies;
- unique movement preserves the pointee address and empties the source handle;
- value copies designate independent payloads and mutations do not affect the original;
- unique representations remain noncopyable, checked using native traits;
- destructor bodies call native methods, and all tracked payloads are destroyed;
- an injected value-copy failure unwinds without leaking tracked payloads;
- unused class/weak forms do not request facades, and removing unique usage removes its request;
- application units do not include source payload headers; payload headers do not depend on facade layouts;
- no-op fragment identity, cached replay after edits, removal/restoration, configuration
  switching without repreparation, fresh output equality and emission failure recovery;
- warm native builds reuse objects; method-body edits reuse layout probes and compile
  one native support unit; field-layout edits rebuild the layout probe through existing dependencies.

Matched direct/ABI counters at O0 and O2:

| Scenario | Allocations | Bytes | Frees | Constructions | Copies | Moves | Destructions |
|---|---:|---:|---:|---:|---:|---:|---:|
| Generated source workload | 22 | 928 | 22 | 4 | 9 | 2 | 15 |
| Injected copy failure | 0 | 0 | 0 | 1 | 0 | 0 | 1 |
| Ownership identity inspection | 2 | 320 | 2 | 3 | 1 | 0 | 4 |

ASan/UBSan at `-O1 -g0 -fno-elide-constructors` preserves direct/ABI parity.
The identity inspection has one additional move and destruction in both modes,
because its source factory returns a named value and optional NRVO is disabled.
Required native allocations/copies/moves remain; the tested boundary adds none.
LeakSanitizer is disabled; tracked live-payload assertions are enabled.

See [counters](summary.json) and [native cache results](source-cache-summary.json).
Raw final run: `/tmp/prelower-source-final2`. Scope/error checks also ran through
the shared runner in `/tmp/prelower-source-checks`. The initializer regression is
`/tmp/prelower-source-initializers-regression`. Scoped portability conversion of
the ten touched backend files, existing ownership/destructor/method/initialization
PHP suites and accounting-recipe replay passed in `/tmp/prelower-source-validation`.
The runner used CLI fallback because FPM sockets were unavailable.

## Remaining boundary work

Application-to-native method/property/static-property access, selected constructors
and named construction, weak derivation/lock, and native methods calling application
functions still need transport and reject explicitly. Tests verify those diagnostics
and recovery. Recursive payload dependency graphs, callbacks and full inheritance
operation coverage remain follow-up proofs. This does not establish whole-accounting
support or a compile-time speed ratio, and the compiler was not rebuilt natively.

[Owning guide](../../../../compiler/my-try/docs/architecture/cpp_template_prelowering.md)

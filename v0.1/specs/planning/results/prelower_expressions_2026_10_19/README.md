# Ordinary runtime ABI expression checkpoint
Doc Status: supporting

Date: 2026-10-19. Branch: `v0.2/prelower_templates`. Base: `c75b467a`.
This proves a bounded compiler-generated expression path, not full template
coverage, whole-accounting performance or a native rebuild of the compiler.

## Implementation

Enable both `Compiler::$cpp_lowering->prelower_templates` and
`runtime_template_abi` through the API. The ordinary backend reads the existing
AST/prepared facts; no analysis or AST transformation was added. The existing CLI
flag still selects the accounting recipe baseline. The opt-in ABI path remains
bounded while initializer-buffer, broader call and iteration transports are developed.

Runtime integer wrappers and applied runtime templates use native objects in
layout-probed opaque storage. Literal construction uses registered integer width
and signedness. Operation bridges reuse selected callable/type bindings and the
ordinary native conversion/operator renderers. `cpp_runtime_operation` requests
survive cached-fragment reuse; their names use stable semantic identities and a
SHA-256 digest, not engine entry positions. The engine remains family-independent.

Both ordinary writes and bridges now share `CPP_Index_Write::resolve_path`.
Prepared keys and RHS run before path probing/descent; copied index reads remain
copied reads. Runtime helpers own actual storage and mutation. No native element
is reinterpreted as a facade object. Whole source-function reference parameters
continue to designate the same owning facade.

## Proof

Run:

```bash
python3 compiler/my-try/tests/prelower_expressions.py --results FRESH_DIRECTORY
```

The fixture compiles authored source through direct and runtime ABI modes. It
uses the existing test provider metadata with a tracked payload and instrumentation;
application operations are emitted by the compiler, not manually written bridges.
A provider hook catches an intentionally throwing copy from a generated source
function so cleanup can be counted without introducing source exception semantics.

Coverage includes:

- default and empty initialization, append/store, integer literals/casts/comparisons;
- vector/hash and provider-template operations through the same bridge worker;
- nested copied reads, including an observing call consuming a copied container read;
- nested writes whose RHS replaces/reallocates the root via a source reference parameter;
- provider observations with direct/opaque owned results and tracked nested payloads;
- a failed payload copy and automatic destruction during unwinding;
- no-op emission with identical artifact bytes and identical retained fragment objects;
- entry-body edit while the work-function fragment is reused, removal of all requests,
  restoration, unsupported-call failure/recovery, configuration switching without
  semantic version changes, and fresh-versus-incremental output equality;
- warm native builds performing zero compilations and zero links.

Exact direct/opaque counter parity at O0, O2 and ASan/UBSan with
`-fno-elide-constructors` (optional elision disabled):

| Scenario | Allocations | Bytes | Frees | Payload constructions | Copies | Moves | Destructions |
|---|---:|---:|---:|---:|---:|---:|---:|
| Ordinary expressions, each mode | 27 | 857 | 27 | 1 | 9 | 0 | 9 |
| Throwing copy, each mode | 0 | 0 | 0 | 1 | 0 | 0 | 1 |

The throwing copy fails before the instrumentation increments its copy count.
Required source/runtime copies remain; the measured boundary adds none.
See [raw counter summary](summary.json). This is a scoped behavioral result, not
proof for every operation/type combination.

Raw final run: `/tmp/prelower-expressions-final` (183.70 seconds including all
builds/checks; not a compilation benchmark). Follow-up emission/portability checks:
`/tmp/prelower-expression-portability`; twelve changed PHP source files pass scoped
conversion, and the accounting-recipe and runtime-ABI replay fixtures pass.
The shared runner used CLI fallback because FPM socket creation was unavailable.

Regressions: `/tmp/prelower-expressions-layout-regression` passes the existing
layout/native-cache proof, including dependency edits, corrupt-header repair,
failed-layout recovery and sanitizer counters. `/tmp/prelower-expressions-index-regression`
passes indexed-property preparation, family, failure and lifetime checks through
the shared path lowering. These checks compile generated programs, not the compiler.

## Remaining boundaries

Nonempty data construction requires a general initializer-buffer transport preserving
allocation/evaluation order. Iteration, mutation expressions, indexed compound
updates, broader runtime argument/default/receiver/reference-result transport and
remaining wrapper/property/callback operations are not established by this slice.
Generated source-type template arguments still require layout bootstrap work.
Unsupported transports are not implemented by copying or boxing native elements.
No new compile-time speed ratio is claimed.

[Owning guide](../../../../compiler/my-try/docs/architecture/cpp_template_prelowering.md)
· [Agreed contract](../../prelower_expression_integration_2026_10_19.md)

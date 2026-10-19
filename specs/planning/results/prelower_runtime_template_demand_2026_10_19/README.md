# Recipe-driven native template demand
Doc Status: planning

Date: 2026-10-19. Base `9f4b8137` plus this slice.

The general runtime path selects native template applications using the installed
C++ recipe, without testing integer family or source-level template application.
Nested argument substitution uses that same fact. The historical measured recipe
mode retains its old selection in its own worker; it is not a fallback from the
general runtime path. No semantic identity, capability or AST changes are introduced.

A selected concrete type already represents the complete stored type. Returning it
before reapplying storage prevents double wrapping shared/value representations.
The fixture includes a runtime nominal type with a closed native template and shared
storage to exercise that distinction.

## Proofs

`runtime_template_demand.py` passed in 34.99 seconds. Both ordinary and concrete
source emission compile/run at O0/O2 and ASan/UBSan, without PCH. Four concrete types
are demanded: int, closed, duo<closed,int>, shared_closed. The unused metadata type
produces no concrete artifact. Native nested arguments use the concrete closed type.

Prepared source functions copy values, return a nested reference and assign through
it. Native checks establish exact reference identity, nested values, shared pointee
identity and expected reference count. The shared payload is allocated before the
measured boundary; its copy must not allocate or clone the pointee. Both modes report
`recipe-boundary 0 0 0 0 0 0 0` in every build. Primitive numeric copies are not
instrumented payload copies; this proof does not measure transient reference-count
instructions or establish all runtime-family interfaces.

Removing source demand removes all four headers. Restoration, unchanged fragment
replay and ordinary/concrete option switching preserve expected output. Replay does
not create semantic types or replace unchanged fragments. Warm native builds compile
and link nothing. The artifact closure is asserted before any explicit test engine
requests, so direct fixture requests cannot mask failed automatic selection.

The broader mutation/substitution proof also passed (190.13 seconds), including its
O0/O2/sanitizer/controlled allocator, demand removal and body/layout cache proofs.
Its stock O2 allocation delta remains zero. The structured recipe native suite
passes after sharing its fixture metadata. Metadata/type-catalog checks and the
focused portability checker pass. No native compiler rebuild was performed.

All commands use the shared 12-slot runner with PHP CLI fallback. Sanitizer leak
detection is disabled. Local evidence is under `/tmp/prelower-recipe-selection/`:
`source3`, `mutations`, `recipe-native`, `check-final`. `source1` was the initial
inline-only proof; `source2` caught a fixture string syntax error before compilation.
[Compact evidence](summary.json).

## Remaining integration

Implicit shared storage still spells its runtime wrapper directly in the renderer;
consume the installed binding while preserving incomplete source-payload requirements.
Structural callables retain their existing representation owner. Runtime operations,
trait interoperability and accounting output remain separate coverage work. This
proof does not establish a template-free preprocessed application or complete runtime
metadata for every internal helper.

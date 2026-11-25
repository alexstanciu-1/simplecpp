# General per-type publication experiment
Doc Status: planning

Date: 2026-09-20

## Outcome

General dependency-driven type publication removes the two-file historical stall,
while the coordinated 28-file edit remains a meaningful slow exception.

| Coherent historical edit | Prior native screen | Per-type native result | Assumed total | Native rebuild |
| --- | ---: | ---: | ---: | --- |
| `41392c03`, two source files | 194.008 s | **3.955 s median** | **5.455 s** | 7 objects + link |
| `9103637e`, 28 source files | 193.489 s | **32.680 s screen** | **34.180 s** | 78 objects + link |

The two-file trials are 3.955/3.990/3.695 seconds. The compound trial stopped after
one successful screen above twenty seconds; it is not a median. Neither edit
rebuilds a PCH under the new policy. Both pass before/after smoke and literal
LLVM/native exit42 checks, with the full application linked. These witnesses do
not exhaustively validate every changed compiler feature.

The source states match the previous coherent reconstruction, except the same
unchanged instrumented main harness. The generator/runtime remain current,
Clang uses debug `-O0 -g1`, mold links, Ninja has twelve jobs and cache launchers
are disabled. Timings include native scheduling and linking; reconstruction and
generation costs are excluded. The frontend remains an assumed 1.5 seconds.
Original compiler source is read-only, and scratch sources are restored.

## Contract and implementation

No aggregate application-type header, grouped type inventory or project PCH is
reachable in the final candidate. The shared PCH contains runtime headers only.
Each of the 485 current project classes/structs has one header preserving its
exact definition from the existing expanded policy. Callable declarations carry
local forward declarations; implementation units include derived individual type
headers. Existing callable implementation groups remain, with each unit receiving
the dependencies needed by that group's contents.

The transformation belongs to `tools/compile_latency/minimal_type_layout.py` and
writes a separate mirror, object tree and `minimal-main` executable. It applies
to the entire observed type inventory, not a three-class allowlist. The original
expanded policy remains available as the comparison input. This is experimental
output reshaping, not production semantic/S2S integration.

The bounded structural extractor distinguishes shared-handle forward declarations
from complete-definition requirements. Referenced callable signatures supply
inferred return types. Accessed-field dependencies cover nested member chains,
and inline adapters retain their callable declarations and complete-type needs.
It rejects observed unsupported ownership/definition shapes and complete-type
cycles. Per-state manifests record definition hashes and dependency sets.

This is conservative metadata, not a resolved AST or a proof that every remaining
include is necessary. Signature parameters and repeated field names can overstate
complete-type requirements. A future resolved generator can narrow those edges.
The pinned layout has 1052 active implementation units, a median of one directly
included complete type and a maximum of 25; transitive includes are additional.

## What the failed setup builds taught

The failed setup logs are retained and are not accepted timings:

- `build-1.log`: free-function declarations were supplied implicitly by the old
  PCH. A general symbol-to-declaration-owner map now supplies their headers.
- `build-2-native.log`: inline adapter headers lost declarations they call.
  Their callable dependencies and inline complete-type needs are now retained.
- `build-3.log`: nested access such as `state->diagnostics->error_count` needs an
  intermediate definition absent from lexical type-name scanning. The extractor
  now closes accessed-field dependencies over known receiver types.

The initial inventory of 486 also contained one stale private header from an
older replay. Only names present in the current original generated source can
enter publication; the pinned inventory is 485. Native full build subsequently
passes in `build-4.log`. A separate wrong-working-directory Ninja invocation is
retained in `build-2.log`; it did not compile anything.

## Remaining compound work

Of 78 rebuilt objects in `9103637e`, 71 have changed generated C++ inputs and seven
have unchanged C++ inputs but changed dependencies. Changed C++ text may include
include-list or diagnostic changes as well as body changes; this is not a proof
that all 71 rebuilds are unavoidable. Likewise, the other seven are not proven
unnecessary: changed declarations/types can legitimately require recompilation.

The compiler jobs total 350.059 seconds of elapsed job time, with all twelve
slots used at peak. Dividing observed work by twelve gives 29.172 seconds, close
to the 32.680-second native wait. This holds observed job durations fixed; it is
not CPU-time measurement or a hardware-independent lower bound. It suggests that
scheduling alone is unlikely to deliver ten seconds for this edit. Further gains
need less compilation work or faster compilation, not another global-PCH fix.

The two-file edit recompiles only seven changed C++ inputs. Its previous
588-object/PCH rebuild was largely removed by the general publication rule.

## Validation and next coverage

The full pinned build, smoke checks, existing value-copy, shared-carrier,
composition identity/output and adapter witnesses pass. Final checks pass: generation is idempotent, the native build is a no-op, all
original types are represented, policy hashes remained stable throughout the
replays, and external/scratch source hashes are unchanged.

The two-file case meets the preferred target. The user subsequently accepted the approximately 30-second compound result
(2026-09-20); its frequency in normal saves has not been established.
Do not carry the earlier nine-code-case or three-code-case coverage totals over
to this new policy without rerunning them.

Next rerun the frozen runnable history samples under this policy, then inspect
the 71 changed compound C++ inputs to separate body changes, signature changes,
include-list changes and diagnostic metadata. Preserve the other historical
exclusions and the full twelve-source `7d58f273` gap. Do not promise that every
coordinated multi-file edit will fit ten seconds.

Evidence: [archived policy, graphs, dependency manifests, proofs and timings](../../tools/compile_latency/results/2026-09-20/minimal-types/).


## Coverage follow-up complete

The twelve runnable frozen code cases have now been rerun under this policy;
all pass, with native medians 1.593–4.815 s and no PCH rebuilds. The compound input
audit and isolated compiler traces are also complete. See
[s2s_compile_latency_minimal_coverage.md](s2s_compile_latency_minimal_coverage.md)
for the complete update and next required-runtime experiment.

# Non-owning call-target prototypes

Both prototypes use Simple C++-generated records, owner/view adapters, and weak observers. One stores a C++ variant of concrete weak handles with distinct source and constructor tags; this models a native sum type. The other stores one generated weak interface view. Its targets implement one method returning the role, signature identity and selected operand identity. IDs stand in for declarations, native symbols, private implementations, local bindings and owner factories. The production AST still uses its existing storage.

## Measured cost

Linux x86-64, Ubuntu Clang 18.1.3, C++23, libc++, -O2 -g0. The benchmark uses the prebuilt runtime archive. Run `python3 -B run.py` from this directory after building v0.2. Medians below cover 41 samples after a warmup. Timing excludes compiler/tool startup, native compilation and linking.

| Measurement | Tagged concrete weak handle | Weak interface |
| --- | ---: | ---: |
| Call slot size / alignment (bytes) | 24 / 8 | 16 / 8 |
| Allocation for 32,768 pre-sized slots | 1 × 786,432 B | 1 × 524,288 B |
| Owner setup (640 owners plus one backing array) | 641 allocations, 38,912 B | 641 allocations, 44,032 B |
| Allocations during binding, replacement and reads | 0 | 0 |
| Fresh binding, 32,768 sites | 659.412 µs | 308.891 µs |
| Edited body binding, 64 sites | 2.751 µs | 0.900 µs |
| Read selected targets, fresh sites | 348.831 µs | 449.825 µs |
| Read selected targets, edited sites | 0.660 µs | 0.890 µs |

Each variant gets the same seeded mix of the five roles in 512 modeled bodies. The timed fresh pass starts with empty slots; preparing one edited body overwrites its 64 live slots with different owners. Binding starts after lookup and dirty-body selection. Reads lock the observer and obtain the same three consumer fields. The benchmark checks every final binding against an independent expected role, signature and operand, and verifies identical checksums across variants. The allocation counters cover normal scalar and array C++ allocation; these fixtures require only ordinary alignment.

The interface adds 8 bytes to each modeled target payload, for 5,120 bytes across this pool. It saves 8 bytes per site, or 262,144 bytes in this fixture. Existing polymorphic owners, multiple interfaces and different target-to-call ratios can change the balance.

## Lifetime and incremental checks

- Replacing the source head updates an already-bound call. A specialized call reads the updated authored signature and retains its distinct implementation.
- A stable record entry sees a replacement construction head. A runtime binding can be shadowed by a source binding and restored.
- Retiring the method instance expires its call observer while a sibling remains usable. Retiring the local binding expires the old call even after a replacement is allocated. Explicit rebinding and reset work for both layouts.

As a separate reference, the production PHP compiler prepared a Python constructor call and a PHP caller containing the four other target roles. Full semantic preparation, excluding frontend and emission, took 0.4158 ms fresh (5 bodies) and 0.1966 ms after a caller-only edit (1 prepared, 4 reused), median of 41 samples. This is a smaller real project. Neither native prototype was installed in that pipeline.

## Decision

Prefer the weak interface for a translated pilot. It is source-expressible and saves slot space at zero per-call allocation in this model. Before choosing production storage, exercise the actual signature and lowering consumers with their real owner types, and measure their relative frequency. The native variant was faster at consuming resolved targets here. Keep invalidation watches and specialization retirement with their existing owners.

## Pilot with production consumers

Run `python3 -B consumer_pilot.py --counts` in this directory. The driver copies the PHP compiler into two temporary directories, applies the weak-interface overlay to one copy, and uses identical clocks around actual incremental preparation and C++ emission. Xdebug counts are collected in a separate run. The checkout's compiler files are untouched.

Each real owner supplies a signature for validation and an operand for lowering. A source entry follows its current declaration, a runtime descriptor supplies its own signature and native binding, a specialized method separates the authored signature from its private implementation, a lexical binding supplies a callable signature and invocation storage, and a class entry supplies its constructor signature and record. An implicitly constructible class may have no authored constructor. The calls keep PHP weak references directly to their selected owners. The first pilot resolved the target separately at each consumer query. Hoisting the resolved operand within the emitter's invocation path reduced weak reads from 15 to 6 for the four calls in the edited worker, and operand queries from 7 to 4.

The fixture has 16 PHP workers and a Python entry using call-shaped construction. Baseline and pilot agreed on prepared/reused bodies, emitted source sets, diagnostics and generated C++ file contents across 13 states. Successful incremental output matched a fresh build at each state. Checks included source/runtime shadowing and restoration, invalid source, runtime, local, specialized and constructor calls, retirement of a replaced local and the last specialized instance, and reintroduction of demand. Both repaired bundles compiled and ran with exit status 16.

| Phase, median ms (interquartile range) | Baseline PHP | Weak interface in PHP |
| --- | ---: | ---: |
| Fresh preparation, 20 bodies | 2.392 (2.265–2.673) | 2.372 (2.262–2.490) |
| Fresh lowering, 20 sources | 1.737 (1.633–1.984) | 1.674 (1.600–1.813) |
| Caller edit preparation, 1 body prepared / 19 reused | 0.350 (0.315–0.425) | 0.342 (0.317–0.399) |
| Caller edit lowering, 1 source | 0.390 (0.361–0.458) | 0.383 (0.349–0.435) |

Each cell covers 88 samples from eight warmed PHP processes per implementation, ordered in alternating ABBA blocks. Measurements exclude frontend work, publishing files and native compilation. The overlapping ranges give no clear throughput advantage. In the final weak variant, fresh preparation made 115 signature queries; lowering made 100 weak-target reads, 66 operand projections and 17 signature queries. The edited worker made 7, then 6/4/1 respectively. These are PHP host measurements. The native size and allocation results above belong to the earlier representation benchmark.

**Compatibility decision.** Both copies passed the incremental-preparation and Python-frontend tests. The unmodified functions test passed on baseline and stopped on the weak copy at its pre-emission purity snapshot: PHP rejects serialization of a WeakReference. Byte-identical C++ output alone cannot verify that emission leaves preparation facts unchanged. Keep production storage pending a purity snapshot that represents weak edges by target identity and detects rebinding, expiry and mutation during lowering. The pilot retains this regression as an explicit finding.

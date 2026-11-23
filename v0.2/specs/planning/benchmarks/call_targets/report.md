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

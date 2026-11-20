# Typed hash construction on dev002

Doc Status: supporting

A private field-wise write improved throughput for new entries with either key type
and for replacement with short string keys. Integer-key replacement regressed in
every pair. Keep ordinary production lowering while testing whether the hash
provider can share one lookup and preserve its existing replacement path.

| Key | Workload | Ordinary ns/write | Direct ns/write | Paired throughput change | Middle 50% of changes |
| --- | --- | ---: | ---: | ---: | ---: |
| int | Insert | 48.06 | 46.07 | +3.9% | +3.5% to +4.8% |
| int | Replace | 11.95 | 13.16 | −9.0% | −10.8% to −8.3% |
| string | Insert | 103.79 | 96.80 | +7.1% | +6.4% to +7.7% |
| string | Replace | 42.84 | 38.04 | +12.5% | +11.6% to +13.0% |

Each pair ran on dev002 CPU 7 using Clang 18.1.3. One warm-up pair preceded
16 measured pairs with alternating order. Each sample timed 12 batches of
8,192 writes of a two-int64 record, in the same permuted key order. New tables
grew normally. Replacement tables were populated immediately before timing.
Key construction, validation of both fields of every entry, and destruction
were outside the timer. String keys were prebuilt inline decimal strings.
The percentage is the median of ordinary time divided by direct time minus one.

The cpp, php and js providers emitted byte-identical concrete table and record
headers for each key type. Each distinct native specialization was measured
using the same driver. The ordinary form builds a local and calls set. The
isolated helper updates fields on a hit, constructs in the slot when both the
table and index have room, and delegates growth to ordinary set. Timing builds
omit the correctness probe's counters. The results cover the complete helper,
including its customer-visible key copies and lookup paths. They do not isolate
an instruction-level cause for the replacement difference.

## Native build costs

| Key and mode | Compile wall ms | Link wall ms | Compile + link ms | Frontend trace ms | Backend trace ms |
| --- | ---: | ---: | ---: | ---: | ---: |
| int ordinary | 932.5 | 51.3 | 983.8 | 901.8 | 60.0 |
| int direct | 940.3 | 51.2 | 991.9 | 904.3 | 64.0 |
| string ordinary | 968.8 | 51.8 | 1020.3 | 922.2 | 79.5 |
| string direct | 956.8 | 51.8 | 1008.6 | 913.8 | 83.7 |

Native build wall columns are medians of five serial recompiles and links after
warm-up. Compile + link uses the sum from each run. The last two columns are
Clang time-trace totals from one separate compile per variant. Flags were
C++23, -O2, -g0, with the default standard library and linker, one job, no PCH,
LTO or compiler cache, warm filesystem, and two reused runtime objects. This
measures building the benchmark TU and executable. PHP preparation and project
orchestration are outside these timings.

## Reproduction

Run keyed_bench.py prepare NEW_DIR locally with the compiler publication
available. On the benchmark host, run taskset -c 7 python3 -B keyed_bench.py
bench . from the prepared directory. The feasibility probe also checks allocation
failure and retry, removal, reinsertion, index widening and boxed-family ownership.

[Raw pairs and build traces](keyed.dev002.json) · [Benchmark runner](keyed_bench.py) · [Feasibility probe](keyed_probe.py)

## One-lookup storage variant

Using ordinary whole-record assignment on a hit did not recover integer-key replacement speed. The earlier helper already made a single existence lookup on a hit. This variant also avoids a repeated existence lookup on misses that need growth. Storage shares its replacement assignment, insertion preparation and index publication between ordinary writes and field-wise writes. A new entry constructs the two fields in place after capacity and index checks succeed.

| Key and operation | Ordinary ns/write | One-lookup ns/write | Paired throughput change | Middle 50% | Earlier helper vs its baseline |
| --- | ---: | ---: | ---: | ---: | ---: |
| Integer insert | 48.12 | 50.45 | −4.7% | −5.3% to −4.3% | +3.9% |
| Integer replace | 11.97 | 13.19 | −9.2% | −9.8% to −7.7% | −9.0% |
| Short string insert | 103.63 | 98.10 | +5.6% | +5.0% to +5.9% | +7.1% |
| Short string replace | 42.78 | 36.73 | +15.9% | +14.6% to +17.3% | +12.5% |

All 16 pairs agreed on the direction in each workload. Each helper was paired with ordinary set in its own run. The ordinary headers matched the earlier run by SHA-256. The same pinned CPU, Clang build, driver, compilation flags and timing protocol were used. All three flavors produced identical headers for each specialization. The instrumented native probe passed replacement, growth, removal, reinsertion, index widening, allocation failure and retry. The timed binaries verified every stored field.

| Key and mode | Compile wall ms | Link wall ms | Compile + link ms | Frontend trace ms | Backend trace ms |
| --- | ---: | ---: | ---: | ---: | ---: |
| Integer ordinary | 921.1 | 50.4 | 971.5 | 889.3 | 59.8 |
| Integer one-lookup | 926.8 | 50.7 | 977.4 | 885.9 | 61.9 |
| String ordinary | 951.8 | 50.8 | 1003.4 | 912.1 | 79.2 |
| String one-lookup | 958.9 | 51.2 | 1010.9 | 914.4 | 81.0 |

Retain ordinary production hash writes. Both experiments favor short string keys, but the measurements cover one inline key distribution and a two-integer record. Before considering a key-specific capability, inspect optimized Clang code for avoidable key copies or moves in ordinary set and test a representative hash workload. The timings alone cannot attribute the difference to a specific copy or instruction.

Reproduce with `keyed_probe.py --single-lookup` and `keyed_bench.py prepare-single NEW_DIR`; use the same pinned bench command as above. [Raw one-lookup pairs and traces](keyed_single.dev002.json)

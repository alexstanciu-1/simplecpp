# On-demand construction in a vector slot

Doc Status: supporting

A requested pass on the settled tokenizer body identified the existing three field writes
and the final append. On dev002, discovery cost about 3 µs. The selected binary ran the
presized operator corpus about three times as fast on Clang. GCC showed no gain on this
corpus. The comparison includes the explicit spare-capacity fast path as well as direct
construction. The [earlier by-value fast-path control](../byte_access/slot.md) separates those effects.

## What the pass proved

The candidate is an ordinary local value record declared in the same lexical block.
Initialization covers every field once in layout order, followed immediately by the
block’s final statement appending that local. This excludes later reads and intervening
effects without searching the rest of the function for aliases. The trial admits public
int fields, local integer reads and identity-converted subtraction. It excludes
constructors, field defaults, bases, interfaces and other payloads. A second two-field
record with different names used the same proof. An observable read after the append
gave byte-identical ordinary and requested output.

The plan references settled nodes and belongs to one retained file revision. The
experiment checks that the file and provider are current at selection. An unrelated
source edit retained the plan across a real incremental compile. Edits to the initializer
and to the record layout in a separate source retired it. Restoring either recovered
eligibility in cpp/php/js. The provider-off test uses an explicit experimental flag.
A production capability would need metadata publication and dependency tracking.

The experimental emitter consumes the existing scalar expression lowering. Values are
passed to a concrete helper at the original append point. With spare capacity the helper
constructs the aggregate in the slot and then increments count. On growth it delegates
to the existing by-value append. Allocation failure during initial storage creation or
growth leaves the previous size and contents intact and later append succeeds. Both
paths ran on Clang and GCC. Native checks also compare all 11 scanner fixtures with
the PHP oracle with zero and previous-count hints. The second record verifies both
fields on reserved and growing vectors.

## Compiler cost

Microseconds on dev002 CPU 7, PHP 8.5 with Xdebug and CLI OPcache off. The ordinary
body preparation has no destination-discovery call. Discovery measures the requested
pass alone. The two emission columns measure complete bundles from the same prepared
program, alternating order over 12 warm batches. Selected emission reuses the plan
and includes generating the concrete helper header. Both emission controls use the
same experimental emitter. Parsing, file I/O and native compilation are excluded.

| Flavor | Ordinary body preparation | On-demand proof | Ordinary emission | Emission with plan |
|---|---:|---:|---:|---:|
| cpp | 563.9 | 2.94 | 655.3 | 662.2 |
| php | 503.4 | 3.04 | 647.8 | 650.1 |
| js | 564.3 | 3.01 | 670.1 | 674.8 |

## Native cost

Dev002 CPU 7, C++23, -O2, separate source units, no LTO. Ratios are median paired
throughput of selected versus ordinary on the presized 8,192-byte operator corpus,
8 alternating pairs of 9 batches of 128 scans. Compile times are medians of four
alternating pairs compiling the scanner unit, in milliseconds. The few-millisecond
differences warrant a larger compile-time study before adopting a permanent helper.

| Compiler | Flavor | Operator throughput ratio | Ordinary compile ms | Selected compile ms |
|---|---|---:|---:|---:|
| clang++ | cpp | 3.010× | 294.8 | 295.0 |
| clang++ | php | 3.014× | 290.2 | 294.6 |
| clang++ | js | 3.017× | 293.9 | 297.9 |
| g++ | cpp | 0.959× | 243.5 | 246.1 |
| g++ | php | 0.992× | 236.5 | 238.5 |
| g++ | js | 0.993× | 239.0 | 240.6 |

## Integration decision

Retain on-demand discovery and evaluate integration on Clang across all three flavors. The [representative-source comparison](source_files.md) measured a 37.2% aggregate
throughput gain on Clang and an 8.0% loss on GCC. A production version would have the provider generate the concrete helper,
and the build would select an immutable plan for each emission. The trial uses a
private copy of the emitter and an in-memory header substitution. It implements no
public slot references, persistent cache or scheduler.

## Reproduce

Use a fresh work directory. The oracle directory is emitted by the existing
tokenizer fixture driver. Run the following from the checkout root.

```sh
python3 -B v0.2/compiler/tests/tokenizer_scanner_native.py /tmp/token-oracle
d=v0.2/specs/planning/benchmarks/tokenizer/destination
taskset -c 7 python3 "$d/run.py" v0.2/compiler /tmp/token-destination
taskset -c 7 python3 "$d/native.py" /tmp/token-destination v0.2/runtime /tmp/token-oracle
```

[Raw measurements](results.dev002.json) · [Plan recognition](plan.php) · [Incremental checks](incremental.php)

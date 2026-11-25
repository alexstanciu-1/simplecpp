# 65,536-record thread scaling

Run 1 benchmark on WSL2 (Ryzen 5 PRO 4650G) and dev002 (Ryzen 9 5950X). Both ran the same statically linked Linux executable, SHA-256 `456e269edf5ff170c2dfca346025cd370c52e9959f85459ad67dd7b6fbc23320`. Each batch updates 8 MiB of stock and 512 KiB of adjustments in place. A single coordinator resets the input immediately before timing, then verifies all updated fields, adjustments and per-slice subtotals after timing. This measures a recently initialized batch. Cache state, CPU frequency and other machine activity were uncontrolled.

From this directory, with the repository's Clang runtime archive and layout facts built:

```sh
python3 build.py /tmp/mt-65536-build
python3 run.py /tmp/mt-65536-build/scaling > timings.csv 2> host.txt
```

Each mask and worker count gets two independent processes in opposite sweep orders. Each process cycles through all six mode orders, with six warm-up rounds, 24 ordinary rounds and 12 profiled rounds. Each table cell below is the median wall time in microseconds of 48 ordinary batches. The direct baseline calls the compiled kernel sequentially on the same number of equal slices. Fresh includes creation and joining of all workers. Reuse includes admission, dispatch, wake-up, completion and result collection. Pool construction and destruction happen outside timing.

| Host | Workers | Direct | Fresh | Reused |
|---|---:|---:|---:|---:|
| WSL2 | 1 | 1697 | 1927 | 1811 |
| WSL2 | 2 | 1657 | 1233 | 1163 |
| WSL2 | 4 | 1743 | 1352 | 1280 |
| WSL2 | 8 | 1682 | 1489 | 1196 |
| dev002 | 1 | 1106 | 1161 | 1147 |
| dev002 | 2 | 1093 | 830 | 986 |
| dev002 | 4 | 857 | 504 | 590 |
| dev002 | 8 | 889 | 400 | 456 |

The spread mask admits one logical CPU per physical core until exhausted. At eight workers WSL uses six reported cores plus two siblings, and dev002 uses eight distinct cores. The coordinator and workers share the mask and the scheduler chooses placement.

## Two-worker placement controls

| Host | Allowed CPUs / placement | Direct | Fresh | Reused |
|---|---|---:|---:|---:|
| WSL2 | One logical CPU | 1628 | 1964 | 1714 |
| WSL2 | Two reported siblings | 1664 | 1155 | 1144 |
| WSL2 | Four distinct cores available | 1605 | 1388 | 1144 |
| WSL2 | Two distinct cores, reused workers pinned individually | 1622 | 1187 | 1137 |
| dev002 | One logical CPU | 836 | 881 | 855 |
| dev002 | Two siblings | 838 | 746 | 783 |
| dev002 | Four distinct cores available | 1043 | 530 | 533 |
| dev002 | Two distinct cores, reused workers pinned individually | 838 | 549 | 547 |

Only reused workers receive individual affinity in the pinned control. Direct and fresh provide baselines in the same process. WSL guest affinity cannot establish Windows host placement.

## Timing evidence

Profile rows measure pre (call entry to first kernel start), span (first start to last finish), tail (last finish to returned results), cpu (summed per-thread kernel CPU time), and spread (first to last kernel start), all in microseconds. Each profiled batch has its own wall time. Medians of separately collected components can differ from the uninstrumented wall-time medians.

On WSL the two-worker fresh path had median pre/span/tail of 180/971/102 µs, versus 92/1005/81 µs with reuse. At eight fresh workers the start spread grew to 788 µs. On dev002 the two-worker reused path had 8/993/6 µs and a start spread of 511 µs. The pinned control had 6/538/6 µs and a start spread of 2 µs. The wide-mask control also had a 534 µs kernel span. Even direct timing varied across processes on dev002 (1340 and 851 µs medians for the ordinary two-core mask). Placement and effective worker overlap matter, and the data provide no separate measurement of bandwidth, cache misses or frequency.

[WSL samples](wsl.csv) · [dev002 samples](dev002.csv) · [WSL topology](wsl-host.txt) · [dev002 topology](dev002-host.txt)

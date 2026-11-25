"""Host CPython benchmark, not portable compiler source."""
import sys
import time

binary = len(sys.argv) > 2 and sys.argv[2] == "bytes"
backend = "python_bytes" if binary else "python"
for line in open(sys.argv[1], encoding="utf-8"):
    ident, op, s, t, needle, repl, expected = line.rstrip("\n").split("\t")
    if binary:
        s, t, needle, repl = (v.encode() for v in (s, t, needle, repl))
    if op == "replace":
        assert s.replace(needle, repl) == (expected.encode() if binary else expected)
        def batch(n):
            total = 0
            start = time.perf_counter_ns()
            for _ in range(n):
                total += len(s.replace(needle, repl))
            return time.perf_counter_ns() - start, total
    elif op == "compare":
        assert (s > t) - (s < t) == int(expected)
        def batch(n):
            total = 0
            start = time.perf_counter_ns()
            for _ in range(n):
                total += 1 + (s > t) - (s < t)
            return time.perf_counter_ns() - start, total
    else:
        def batch(n):
            total = 0
            start = time.perf_counter_ns()
            for _ in range(n):
                total += len(s)
            return time.perf_counter_ns() - start, total
    count = 1
    while batch(count)[0] < 20_000_000 and count < 2**26:
        count *= 2
    for trial in range(7):
        ns, checksum = batch(count)
        print(ident, backend, trial, count, ns, checksum, sep=",")

import json
import sys
import time

count, length, loops = map(int, sys.argv[1:])
wire = sys.stdin.read()
half = length // 2
word = 'a' * half + 'b' + 'z' * (length - half - 1)
assert json.loads(wire)['value'] == [word] * count

def run(k):
    total = 0
    start = time.perf_counter_ns()
    for _ in range(k):
        total += len(json.loads(wire)['value'])
    elapsed = time.perf_counter_ns() - start
    assert total == k * count
    return elapsed / k

run(max(1, loops // 8))
print(run(loops))

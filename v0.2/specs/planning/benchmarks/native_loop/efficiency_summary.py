"""Render the two parallel-efficiency measures from a single efficiency sweep."""
import argparse
import csv
from collections import defaultdict
from statistics import median
import sys


def report(rows, output):
    groups = defaultdict(list)
    for row in rows:
        if int(row["round"]) > 0:
            key = (row["compiler"], row["dispatch"], int(row["workers"]))
            groups[key].append(row)

    if not groups:
        raise ValueError("No measured efficiency rounds")
    print("| Compiler | Wait policy | Threads | Thread-only efficiency | Whole-pool efficiency |",
          file=output)
    print("|---|---|---:|---:|---:|", file=output)
    for (compiler, policy, workers), samples in sorted(groups.items()):
        serial = groups.get((compiler, policy, 1))
        if not serial:
            raise ValueError(f"Missing single-thread baseline for {compiler} {policy}")
        values = []
        for field in ("longest_kernel_us", "sample_wall_us"):
            baseline = median(float(row[field]) for row in serial)
            parallel = median(float(row[field]) for row in samples)
            values.append(baseline / (workers * parallel))
        label = {"pool": "sleeping", "active": "active-wait"}[policy]
        print(f"| {compiler} | {label} | {workers} | {values[0]:.1%} | {values[1]:.1%} |",
              file=output)


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("samples", type=argparse.FileType("r"))
    with parser.parse_args().samples as samples:
        report(csv.DictReader(samples), sys.stdout)

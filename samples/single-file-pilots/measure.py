"""Run inside the packaged remote directory on Linux."""
import argparse
import json
import os
from pathlib import Path
import platform
import random
import subprocess


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--cpu", type=int, required=True)
    parser.add_argument("--passes", type=int, default=20)
    parser.add_argument("--rounds", type=int, default=9)
    args = parser.parse_args()
    if min(args.passes, args.rounds) < 1:
        parser.error("passes and rounds must be positive")
    os.sched_setaffinity(0, {args.cpu})
    names = ["access-log", "stock-ledger", "route-table"]
    rows = []
    counts = {}
    for name in names:
        directory = Path(name).resolve()
        actual = subprocess.check_output([directory / "report"], cwd=directory)
        if actual != (directory / "expected.txt").read_bytes():
            raise ValueError(f"{name}: report mismatch")
        counts[name] = len((directory / "input.txt").read_bytes().splitlines())
    rnd = random.Random(8554)
    for mode in ["timing", "memory"]:
        rounds = args.rounds if mode == "timing" else 3
        passes = args.passes if mode == "timing" else 1
        for i in range(rounds):
            rnd.shuffle(names)
            for name in names:
                result = subprocess.run([Path(name) / mode, Path(name) / "input.txt", str(passes)],
                                        check=True, capture_output=True)
                cpu_ns, rss, allocs, requested = map(int, result.stderr.split())
                rows.append(dict(name=name, mode=mode, round=i, passes=passes, cpu_ns=cpu_ns,
                                 peak_rss_kib=rss, new_calls=allocs, new_bytes=requested))
    print(json.dumps(dict(host=platform.node(), cpu=args.cpu, rows_per_pass=counts, samples=rows), indent=2))


if __name__ == "__main__":
    main()

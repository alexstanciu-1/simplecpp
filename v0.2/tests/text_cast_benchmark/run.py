"""Build an identical external-call loop for the owned-string formatters and compare byte checksums."""
import argparse
import os
from pathlib import Path
import statistics
import subprocess

here = Path(__file__).resolve().parent
root = here.parents[1]
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('work', type=Path)
parser.add_argument('--rustc', default='rustc')
args = parser.parse_args()
work = args.work.resolve()
work.mkdir(parents=True, exist_ok=True)


def run(*cmd):
    return subprocess.check_output(cmd, cwd=work, text=True, env={**os.environ, 'XDEBUG_MODE': 'off'})


clang = ['clang++', '-std=c++23', '-stdlib=libc++', '-O2', '-g0']
run(*clang, '-c', here / 'driver.cpp', '-o', 'driver.o')
run(*clang, 'driver.o', here / 'native.cpp', '-o', 'cpp')
for name in ['scpp', 'mixed', 'dynamic']:
    (work / 'measure.inc').write_text(run('php', here / 'measure.php', name))
    run(*clang, '-I', work, '-I', root / 'runtime/generated/public', '-DSCPP_TEXT', 'driver.o',
        here / 'native.cpp', root / 'build/clang/libscpp_runtime.a', '-o', name)
run(args.rustc, '--crate-type=staticlib', '-C', 'opt-level=2', '-C', 'debuginfo=0',
    here / 'native.rs', '-o', 'librust.a')
run(*clang, 'driver.o', 'librust.a', '-ldl', '-lpthread', '-lm', '-o', 'rust')
names = ['scpp', 'cpp', 'rust', 'mixed', 'dynamic']
types = ['bool', 'byte', 'int64', 'float64']
times = {(name, t): [] for name in names for t in types}
checksums = {}
for i in range(8):
    for name in names[i % len(names):] + names[:i % len(names)]:
        rows = run(work / name).splitlines()
        assert len(rows) == len(types)
        for t, line in zip(types, rows):
            ns, checksum = line.split()
            assert checksums.setdefault(t, checksum) == checksum, (name, t)
            if i:
                times[name, t].append(float(ns))
print('checksums', checksums)
for (name, t), rows in times.items():
    print(name, t, statistics.median(rows), min(rows), max(rows), 'ns/value')

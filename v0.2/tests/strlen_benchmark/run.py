"""Build an identical external-call loop for the three byte length implementations and print ns/query."""
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


(work / 'measure.inc').write_text(run('php', here / 'measure.php'))
clang = ['clang++', '-std=c++23', '-stdlib=libc++', '-O2', '-g0']
run(*clang, '-c', here / 'driver.cpp', '-o', 'driver.o')
run(*clang, 'driver.o', here / 'native.cpp', '-o', 'cpp')
for name, define in [('scpp', 'SCPP_LENGTH'), ('scpp-size', 'SCPP_SIZE')]:
    run(*clang, '-I', work, '-I', root / 'runtime/generated/public', '-D' + define, 'driver.o',
        here / 'native.cpp', root / 'build/clang/libscpp_runtime.a', '-o', name)
run(args.rustc, '--crate-type=staticlib', '-C', 'opt-level=2', '-C', 'debuginfo=0',
    here / 'native.rs', '-o', 'librust.a')
run(*clang, 'driver.o', 'librust.a', '-ldl', '-lpthread', '-lm', '-o', 'rust')
names = ['scpp', 'scpp-size', 'cpp', 'rust']
times = {name: [] for name in names}
checksums = set()
for i in range(8):
    for name in names[i % len(names):] + names[:i % len(names)]:
        ns, checksum = run(work / name).split()
        checksums.add(checksum)
        if i:
            times[name].append(float(ns))
assert len(checksums) == 1
print('checksum', checksums.pop())
for name, rows in times.items():
    print(name, 'median', statistics.median(rows), 'min', min(rows), 'max', max(rows), 'ns/query', rows)

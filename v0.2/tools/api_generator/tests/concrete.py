"""General concrete instantiation, non-default/throwing elements and real runtime allocation failure cleanup."""
import os
from pathlib import Path
import subprocess
import tempfile
import argparse

ROOT = Path(__file__).resolve().parents[3]

def run(command, env=None):
    p = subprocess.run(list(map(str, command)), capture_output=True, text=True, env=env)
    assert p.returncode == 0, (command, p.stdout, p.stderr)
    return p.stdout

def prove(work, sanitize):
    source = work / 'probe.cpp'
    run(['php', ROOT/'tools/api_generator/tests/concrete.php', source])
    for compiler in ['clang++', 'g++']:
        flags = ['-std=c++23', '-O1', '-g0', '-Wall', '-Wextra', '-Werror', '-I'+str(ROOT/'runtime/src/sequence'), '-I'+str(ROOT/'runtime/src/hash')]
        if sanitize and compiler == 'clang++': flags += ['-fsanitize=address,undefined', '-fno-omit-frame-pointer']
        run([compiler, *flags, source, '-o', work/'probe'])
        run([work/'probe'])
        invalid = work / 'throwing-move.cpp'
        invalid.write_text(source.read_text().replace('Probe(Probe&& other) noexcept', 'Probe(Probe&& other) noexcept(false)'))
        rejected = subprocess.run([compiler, *flags, '-fsyntax-only', str(invalid)], capture_output=True, text=True)
        assert rejected.returncode != 0 and 'nothrow_move_constructible' in rejected.stderr, rejected.stderr
        run([compiler, *flags, '-I'+str(ROOT/'runtime/generated/public'), '-I'+str(ROOT/'runtime/src/numeric'),
             ROOT/'runtime/generated/src/runtime.cpp', ROOT/'tests/vector_failure.cpp', '-o', work/'failure'])
        run([work/'failure'])
        print(compiler + ': generic recipe, vector/hash aligned non-default elements, holes, copy failure prefixes and runtime allocation failures passed', flush=True)

if __name__ == '__main__':
    p = argparse.ArgumentParser(description=__doc__); p.add_argument('--sanitize', action='store_true'); args = p.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-concrete-') as directory: prove(Path(directory), args.sanitize)

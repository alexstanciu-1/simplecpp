#!/usr/bin/env python3
"""Structured runtime C++ type recipes: schema, demand, layout and native lifecycle."""
import argparse
from pathlib import Path
import sys
APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    args = parser.parse_args()
    out = args.results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
        def proof():
            folder = out / 'generated'
            folder.mkdir()
            runner.run('bindings', ['php', APP / 'tests/runtime_type_bindings.php', folder])
            base = ['clang++', '-std=c++23', '-g0', '-I' + str(ROOT / 'runtime/include'), '-I' + str(folder)]
            includes = []
            for index, source in enumerate(sorted((folder / 'specializations').glob('layout*.cpp'))):
                binary = out / ('layout-' + str(index))
                runner.run('layout-build-' + str(index), [*base, *includes, source, '-o', binary])
                header = out / ('layout-' + str(index) + '.hpp')
                header.write_bytes(runner.run('layout-run-' + str(index), [binary]))
                includes += ['-include', header]
            for label, flags in [('O0', ['-O0']), ('O2', ['-O2']),
                                 ('sanitized', ['-O1', '-fsanitize=address,undefined', '-fno-omit-frame-pointer'])]:
                binary = out / label
                runner.run(label + '-build', [*base, *includes, *flags,
                    folder / 'specializations/containers.cpp', folder / 'proof.cpp', '-o', binary])
                runner.run(label + '-run', ['env', 'ASAN_OPTIONS=detect_leaks=0', binary])
        run_tasks([Task('type-recipes', proof)])


if __name__ == '__main__':
    main()

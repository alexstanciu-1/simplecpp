#!/usr/bin/env python3
"""Structured runtime C++ type recipes: schema, demand, layout and native lifecycle."""
import argparse
import json
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
                    *sorted((folder / 'specializations').glob('containers_*.cpp')), folder / 'proof.cpp', '-o', binary])
                runner.run(label + '-run', ['env', 'ASAN_OPTIONS=detect_leaks=0', binary])
        def self_initialization():
            folder = out / 'self-initialization'
            (folder / 'source').mkdir(parents=True)
            (folder / 'source/main.phs').write_text(
                'class Node { public ?Node $next = null; } $node = new Node(); echo 42;')
            runner.run('self-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                                      ROOT / 'runtime/metadata/modules', 'compiler', 'concrete'])
            build = json.loads(runner.run('self-build', ['php8.5', APP / 'tools/build_cpp.php',
                                                       folder / 'published', folder / 'cache', '-O2']))
            assert runner.run('self-run', [build['executable']]) == b'42'
            includes = []
            for layout in sorted((folder / 'cache/artifacts').glob('*.hpp')):
                includes += ['-include', layout]
            headers = sorted((folder / 'published/specializations').glob('runtime_conversion_*.hpp'))
            assert headers
            for index, header in enumerate(headers):
                # Compile each bridge first: a unity unit can accidentally hide a header cycle.
                runner.run('self-header-' + str(index), ['clang++', '-std=c++23',
                    '-I' + str(ROOT / 'runtime/include'), '-I' + str(folder / 'published'),
                    *includes, '-x', 'c++', '-fsyntax-only', header])
        run_tasks([Task('type-recipes', proof), Task('self-initialization', self_initialization),
                   Task('partition-stability', lambda: runner.run(
            'partition-stability', ['php8.5', APP / 'tests/specialization_partitions.php', out / 'publication']))])


if __name__ == '__main__':
    main()

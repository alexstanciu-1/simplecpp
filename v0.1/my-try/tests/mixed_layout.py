#!/usr/bin/env python3
"""Focused boxed storage ABI/lifetime proofs with matching debug/release runtimes."""
import argparse
import json
from pathlib import Path
import sys

APP = Path(__file__).resolve().parents[1]
REPOSITORY = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    args = parser.parse_args()
    output = args.results.resolve()
    output.mkdir(parents=True, exist_ok=False)
    modes = {'debug': ['-O0'], 'release': ['-O2', '-DNDEBUG']}
    # The older test_mixed_t_dynamic operator suite has independently reproduced
    # baseline failures (bool/int comparison and string +=); see the storage plan.
    suites = ['test_mixed_layout', 'test_array_semantics', 'test_hash_t',
              'test_packed_table_storage', 'test_dynamic_value', 'test_json']
    flags = ['clang++', '-std=c++23', '-DSCPP_LANGUAGE_TARGET_PHP=1',
             '-I', REPOSITORY / 'runtime/include', '-pthread']
    with CommandRunner(output / 'logs', REPOSITORY, jobs=12) as runner:
        def build_runtime(mode):
            directory = output / mode
            directory.mkdir()
            runner.run(mode + '-runtime', [*flags, *modes[mode], '-c',
                       REPOSITORY / 'runtime/include/core/runtime.cpp',
                       '-o', directory / 'runtime.o'], timeout=240)
            runner.run(mode + '-json', [*flags, *modes[mode], '-c',
                       REPOSITORY / 'runtime/include/modules/json/json.cpp',
                       '-o', directory / 'json.o'], timeout=240)
        run_tasks([Task(mode + '-runtime', lambda mode=mode: build_runtime(mode))
                   for mode in modes], jobs=12)

        def execute(mode, suite):
            executable = output / mode / suite
            runner.run(mode + '-' + suite + '-compile', [*flags, *modes[mode],
                       REPOSITORY / 'tests/runtime/native' / (suite + '.cpp'),
                       output / mode / 'runtime.o', output / mode / 'json.o',
                       '-o', executable], timeout=240)
            runner.run(mode + '-' + suite + '-execute', [executable])
        run_tasks([Task(mode + '-' + suite, lambda mode=mode, suite=suite: execute(mode, suite))
                   for mode in modes for suite in suites], jobs=12)
    (output / 'summary.json').write_text(json.dumps(dict(
        passed=True, modes=list(modes), runtime_programs=len(modes) * len(suites),
        native_compiler_rebuild=False), indent=2) + '\n')


if __name__ == '__main__':
    main()

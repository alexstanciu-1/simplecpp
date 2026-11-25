#!/usr/bin/env python3
"""Tagged MySQL row adapters: offline proofs, with and without Connector/C."""
import argparse
import json
from pathlib import Path
import shlex
import subprocess
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
    package = next((name for name in ('libmariadb', 'mariadb', 'mysqlclient')
                    if subprocess.run(['pkg-config', '--exists', name]).returncode == 0), None)
    if package is None:
        raise RuntimeError('Connector/C development package required for the enabled-path proof')
    cflags = shlex.split(subprocess.check_output(['pkg-config', '--cflags', package], text=True))
    libs = shlex.split(subprocess.check_output(['pkg-config', '--libs', package], text=True))
    modes = {'debug': ['-O0'], 'release': ['-O2', '-DNDEBUG']}
    flags = ['clang++', '-std=c++23', '-DSCPP_LANGUAGE_TARGET_PHP=1',
             '-I', REPOSITORY / 'runtime/include', '-pthread']
    with CommandRunner(output / 'logs', REPOSITORY, jobs=12) as runner:
        def build_runtime(mode):
            directory = output / mode
            directory.mkdir()
            for name, source in (
                ('runtime', 'core/runtime.cpp'), ('json', 'modules/json/json.cpp'),
                ('wrapper', 'lang/php/php_mysqli.cpp')):
                runner.run(mode + '-' + name, [*flags, *modes[mode], '-c',
                           REPOSITORY / 'runtime/include' / source,
                           '-o', directory / (name + '.o')], timeout=240)
        run_tasks([Task(mode, lambda mode=mode: build_runtime(mode)) for mode in modes], jobs=12)

        def execute(mode, enabled):
            label = mode + ('-enabled' if enabled else '-disabled')
            directory = output / mode
            module = directory / (label + '.o')
            runner.run(label + '-module', [*flags, *modes[mode],
                       '-DSCPP_HAS_MYSQLI=' + str(int(enabled)), *(cflags if enabled else []),
                       '-c', REPOSITORY / 'runtime/include/modules/mysql/mysql_module.cpp',
                       '-o', module], timeout=240)
            executable = directory / label
            runner.run(label + '-compile', [*flags, *modes[mode],
                       REPOSITORY / 'tests/runtime/native/test_mysqli_fetch_exhaustion.cpp',
                       directory / 'runtime.o', directory / 'json.o', directory / 'wrapper.o',
                       module, *(libs if enabled else []), '-o', executable], timeout=240)
            runner.run(label + '-execute', [executable])
        run_tasks([Task(mode + '-' + str(enabled),
                       lambda mode=mode, enabled=enabled: execute(mode, enabled))
                   for mode in modes for enabled in (False, True)], jobs=12)
    (output / 'summary.json').write_text(json.dumps(dict(
        passed=True, connector_package=package, runtime_programs=4,
        live_database_test=False, native_compiler_rebuild=False), indent=2) + '\n')


if __name__ == '__main__':
    main()

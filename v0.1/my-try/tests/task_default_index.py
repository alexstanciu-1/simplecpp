"""Runtime-first default task index probe, run on dev002 through the shared task pool."""
import argparse
import json
import sys
from pathlib import Path

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--results', required=True, type=Path)
args = parser.parse_args()
out = args.results.resolve()
out.mkdir(parents=True, exist_ok=False)
with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
    cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True)
    cpp.flags.extend(['-std=c++23', '-fsanitize=address,undefined',
                      '-fno-sanitize-recover=undefined', '-DSCPP_HAS_TASKS=1'])
    cpp.link_flags.append(ROOT / 'runtime/include/modules/tasks/tasks.cpp')

    def check(name, source):
        cpp.compile(name + '-build', source, out / name)
        runner.run(name + '-run', ['env', 'ASAN_OPTIONS=detect_leaks=1', out / name], timeout=120)

    def disabled():
        disabled_cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True, runtime_object=cpp.link_flags[0])
        disabled_cpp.flags.extend(['-std=c++23', '-DSCPP_HAS_TASKS=0', '-fsanitize=address,undefined'])
        disabled_cpp.compile('disabled-build', APP / 'tests/task_default_index_disabled.cpp', out / 'disabled')
        runner.run('disabled-run', [out / 'disabled'])

    run_tasks([Task('default-index', lambda: check('default-index', APP / 'tests/task_default_index_runtime.cpp')),
               Task('disabled-index', disabled)])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'scope': 'runtime only'}, indent=2) + '\n')

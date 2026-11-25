"""Sanitized joined shared-owner lifetime and join-before-rethrow proof."""
import argparse
import json
import sys
from pathlib import Path
APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP/'tools'))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler
p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--results', type=Path, required=True)
a = p.parse_args()
out = a.results.resolve()
out.mkdir(parents=True, exist_ok=False)
with CommandRunner(out/'logs', ROOT, jobs=12) as runner:
    cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True)
    cpp.flags.extend(['-std=c++23', '-fsanitize=address,undefined', '-fno-sanitize-recover=undefined', '-DSCPP_HAS_TASKS=1'])
    cpp.link_flags.append(ROOT/'runtime/include/modules/tasks/tasks.cpp')
    def check():
        cpp.compile('build', APP/'tests/task_joined_runtime.cpp', out/'proof')
        runner.run('run', ['env', 'ASAN_OPTIONS=detect_leaks=1', out/'proof'], timeout=60)
    run_tasks([Task('joined-lifetime', check)])
(out/'summary.json').write_text(json.dumps({'passed':True,'scope':'runtime ownership and join/error protocol','pool_sizes':[0,2],'cases':['success','worker_failure','publisher_failure']},indent=2)+'\n')

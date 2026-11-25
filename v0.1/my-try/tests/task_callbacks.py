"""Owned value-callback prerequisite for tasks; default prelowering on dev002."""
import argparse
import json
import shutil
import sys
from pathlib import Path

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks

p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--results', type=Path, required=True)
a = p.parse_args()
out = a.results.resolve()
out.mkdir(parents=True, exist_ok=False)
metadata = out / 'metadata'
shutil.copytree(ROOT / 'runtime/metadata/modules', metadata)
module = {'version': 1, 'module': 'task_callbacks_probe', 'dependencies': ['core'], 'functions': [], 'cpp': []}
for name, arity, result in [('save', 2, 'int'), ('zero', 0, 'void'), ('pair', 2, 'void')]:
    signature = {'callable': {'parameters': [{'name': 'p' + str(i), 'type': 'int', 'passing': 'value'} for i in range(arity)], 'returns': result}}
    module['functions'].append({'id': 'probe.' + name, 'name': 'probe_' + name, 'parameters': [{'name': 'callback', 'type': signature, 'passing': 'value'}], 'returns': 'void'})
module['functions'].append({'id': 'probe.invoke', 'name': 'probe_invoke', 'parameters': [], 'returns': 'int'})
for fn in module['functions']:
    name = fn['name'].removeprefix('probe_')
    module['cpp'].append({'id': fn['id'], 'symbol': 'task_callbacks_probe::' + name, 'header': str(APP / 'tests/fixtures/task_callbacks/provider.hpp')})
(metadata / 'task_callbacks_probe.json').write_text(json.dumps(module, indent=2) + '\n')
cases = {
    'zero-void': ('$f function<void()> = function():void{echo "zero";};$g=$f;$g();probe_zero($f);', 'zerozero'),
    'pair-void': ('$f function<void(int,int)> = function(int $a,int $b):void{echo $a,":",$b;};$f(1,2);probe_pair($f);', '1:25:6'),
    'retained-worker': ('function install():void{$n=20;probe_save(function(int $a,int $b) use ($n):int{return $a+$b+$n;});$n=99;}install();echo probe_invoke();', '27'),
    'zero-value': ('function make(int $n):function<int()>{return function() use ($n):int{return $n;};}$f=make(42);$g=$f;$f=make(9);echo $g(),":",$f();', '42:9'),
}
with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
    def check(name, source, expected):
        folder = out / name
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(source)
        runner.run(name + '-publish', ['php', APP / 'tests/async_tasks_publish.php', folder, metadata, 'task_callbacks_probe'])
        assert json.loads((folder / 'published/.scpp-output.manifest').read_text())['application_templates'] == 'forbidden'
        for temperature in ['cold', 'warm']:
            build = json.loads(runner.run(name + '-' + temperature, ['php', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
            assert runner.run(name + '-' + temperature + '-run', [build['executable']]) == expected.encode()
            if temperature == 'warm':
                assert build['compiled'] == build['linked'] == 0
    tasks = [Task(name, lambda n=name, s=source, e=expected: check(n, s, e)) for name, (source, expected) in cases.items()]
    tasks.append(Task('contracts', lambda: runner.run('contracts', ['php', APP / 'tests/task_callback_contracts.php', metadata, out / 'contract-source'])))
    run_tasks(tasks)
(out / 'summary.json').write_text(json.dumps({'passed': True, 'cases': list(cases)}, indent=2) + '\n')

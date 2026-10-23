"""Capture transfer facts through callable erasure and provider transport; dev002."""
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
module = {'version':1, 'module':'task_transfer_probe', 'dependencies':['core'], 'functions':[], 'cpp':[]}
for name, result in [('allowed','bool'), ('execute','int'), ('joined_allowed','bool')]:
    module['functions'].append({'id':'probe.'+name,'name':'probe_'+name,'parameters':[{'name':'callback','type':{'callable':{'parameters':[],'returns':'int'}},'passing':'value','requires_transfer':name == 'execute'}],'returns':result})
    module['cpp'].append({'id':'probe.'+name,'symbol':'task_transfer_probe::'+name,'header':str(APP/'tests/fixtures/task_transfer/provider.hpp')})
module['functions'].append({'id':'probe.generic','name':'probe_generic','type_parameters':['R'],'parameters':[{'name':'callback','type':{'callable':{'parameters':[],'returns':{'parameter':'R'}}},'passing':'value','requires_transfer':True}],'returns':{'parameter':'R'}})
module['cpp'].append({'id':'probe.generic','symbol':'task_transfer_probe::generic','header':str(APP/'tests/fixtures/task_transfer/provider.hpp')})
module['functions'].append({'id':'probe.after','name':'probe_after','parameters':[{'name':'callback','type':{'callable':{'parameters':[],'returns':'int'}},'passing':'value','requires_transfer':True},{'name':'marker','type':'int','passing':'value'}],'returns':'int'})
module['cpp'].append({'id':'probe.after','symbol':'task_transfer_probe::after','header':str(APP/'tests/fixtures/task_transfer/provider.hpp')})
module['functions'].append({'id':'probe.joined','name':'probe_joined_execute','parameters':[{'name':'callback','type':{'callable':{'parameters':[],'returns':'int'}},'passing':'value','transfer_policy':'joined_shared'}],'returns':'int'})
module['cpp'].append({'id':'probe.joined','symbol':'task_transfer_probe::joined_execute','header':str(APP/'tests/fixtures/task_transfer/provider.hpp')})
(metadata/'task_transfer_probe.json').write_text(json.dumps(module,indent=2)+'\n')
cases = {
    'joined-execute': ('class Box{public int $n=7;}$b=new Box();echo probe_joined_execute(function() use ($b):int{return $b->n;});$f function<int()> = function() use ($b):int{return $b->n;};echo probe_joined_execute($f),probe_allowed($f),$f();', '777'),
    'reject-joined-empty': ('$f function<int()>;probe_joined_execute($f);', ''),
    'joined-permission': ('function pass(function<int()> $f):function<int()>{return $f;}class Box{public int $n=7;}$b=new Box();$f function<int()> = function() use ($b):int{return $b->n;};$g function<int()> = function() use ($f):int{return $f();};echo probe_joined_allowed(pass($g)),probe_allowed(pass($g)),$g();', '17'),
    'joined-intersection': ('class Box{public int $n=7;}$b=new Box();$f function<int()> = function() use ($b):int{return $b->n;};$empty function<int()>;$g function<int()> = function() use ($f,$empty):int{return $f();};echo probe_joined_allowed($g),probe_allowed($g),$g();', '7'),
    'generic': ('echo probe_generic(fn():string=>"owned");', 'owned'),
    'reject-order': ('function mark():int{echo 42;return 0;}class Box{public int $n=7;}$b=new Box();$f function<int()> = function() use ($b):int{return $b->n;};probe_after($f,mark());', '42'),
    'containers': ('$v vector<int> = [40,2];$h hash<int> = {"n":0};$n ?int=0;$f function<int()> = function() use ($v,$h,$n):int{return $v[0]+$v[1]+$h["n"]+($n??0);};echo probe_execute($f);', '42'),
    'owned': ('$n=42;$f function<int()> = function() use ($n):int{return $n;};$g=$f;$n=9;echo probe_allowed($g),probe_execute($g);', '142'),
    'shared': ('class Box{public int $n=7;}$b=new Box();$f function<int()> = function() use ($b):int{return $b->n;};echo probe_allowed($f),$f();', '7'),
    'nested': ('function pass(function<int()> $f):function<int()>{return $f;}$f function<int()> = fn():int=>42;$g function<int()> = function() use ($f):int{return $f();};class Box{public int $n=7;}$b=new Box();$f=function() use ($b):int{return $b->n;};echo probe_allowed(pass($g)),probe_execute(pass($g)),probe_allowed(pass($f));', '142'),
    'reject-transfer': ('class Box{public int $n=7;}$b=new Box();$f function<int()> = function() use ($b):int{return $b->n;};probe_execute($f);', ''),
}
for case, fixture in list(cases.items()):
    cases['ordinary-' + case] = fixture
with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
    def check(name, source, expected):
        folder = out / name
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(source)
        ordinary = name.startswith('ordinary-')
        scenario = name.removeprefix('ordinary-')
        runner.run(name + '-publish', ['php', APP / 'tests/async_tasks_publish.php', folder, metadata, 'task_transfer_probe', 'ordinary' if ordinary else 'concrete'])
        assert json.loads((folder / 'published/.scpp-output.manifest').read_text())['application_templates'] == ('allowed' if ordinary else 'forbidden')
        for temperature in ['cold', 'warm']:
            build = json.loads(runner.run(name + '-' + temperature, ['php', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
            run_name = name + '-' + temperature + '-run'
            assert runner.run(run_name, [build['executable']], expected=1 if scenario.startswith('reject-') else 0) == expected.encode()
            if scenario.startswith('reject-'):
                assert 'Task callback captures are not transferable' in (out / 'logs' / (run_name + '.stderr')).read_text()
            if temperature == 'warm':
                assert build['compiled'] == build['linked'] == 0
    tasks = [Task(name, lambda n=name, s=source, e=expected: check(n, s, e)) for name, (source, expected) in cases.items()]
    tasks.append(Task('contracts', lambda: runner.run('contracts', ['php', APP / 'tests/task_transfer_contracts.php', metadata, out / 'contract-source'])))
    run_tasks(tasks)
(out / 'summary.json').write_text(json.dumps({'passed': True, 'cases': list(cases)}, indent=2) + '\n')

"""S12 production async acceptance; default prelowering on dev002."""
import argparse
import json
import shutil
import sys
from pathlib import Path
APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
from legacy_compatibility import fixture_bytes
p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--results', type=Path, required=True)
a = p.parse_args()
out = a.results.resolve()
out.mkdir(parents=True, exist_ok=False)
metadata = out / 'metadata'
shutil.copytree(ROOT / 'runtime/metadata/modules', metadata)
module = {'version': 1, 'module': 'async_probe', 'dependencies': ['core'], 'functions': [], 'cpp': []}
for name, returns in [('mark', 'void'), ('same', 'bool')]:
    module['functions'].append({'id': 'async_probe.' + name, 'name': 'probe_' + name, 'parameters': [], 'returns': returns})
    module['cpp'].append({'id': 'async_probe.' + name, 'symbol': 'async_probe::' + name, 'header': str(APP / 'tests/fixtures/async/provider.hpp')})
(metadata / 'async_probe.json').write_text(json.dumps(module, indent=2) + '\n')
sanitizer = out / 'sanitizer-clang'
sanitizer.write_text('#!/bin/sh\nexec /usr/bin/clang++ -fsanitize=address,undefined -fno-sanitize-recover=undefined -fno-omit-frame-pointer "$@"\n')
sanitizer.chmod(0o755)
cases = {
    'original': ('async function compute_value():int{await async_sleep_ms(1);return 42;}echo await compute_value();$value int=await compute_value();echo $value;', '4242'),
    'nested': ('async function child(int $n):int{await async_sleep_ms(0);return $n;}async function parent():int{return await child(7);}function sync():int{return await parent();}echo sync(),":",async_wait(parent());', '7:7'),
    'void': ('async function child():void{await async_sleep_ms(0);echo "C";}async function parent():void{echo "A";await child();echo "B";}await parent();', 'ACB'),
    'owned-string': ('async function child(string $value):string{await async_sleep_ms(0);return $value;}echo await child("owned");', 'owned'),
    'expression-order': ('async function child(int $n):int{echo $n;await async_sleep_ms(0);return $n;}function pair(int $a,int $b):int{return $a*10+$b;}async function parent():int{return pair(await child(2),await child(3)) + await child(4);}echo ":",await parent();', ':23427'),
    'lazy-selection': ('async function child(int $n):int{echo $n;await async_sleep_ms(0);return $n;}async function parent():int{$a=true?await child(1):await child(9);$b ?int=2;$c=$b??await child(8);$d ?int=null;return $a+$c+($d??await child(3));}echo ":",await parent();', ':136'),
    'containers': ('async function child(int $n):int{await async_sleep_ms(0);return $n;}async function parent():int{$v vector<int> = [await child(4),await child(5)];$v[await child(0)]=await child(7);$v[0]+=await child(3);return $v[await child(0)]+$v[1];}echo await parent();', '15'),
    'match': ('async function child(int $n):int{echo $n;await async_sleep_ms(0);return $n;}async function parent():int{return match(await child(2)){1=>await child(8),2=>await child(3),default=>await child(9)};}echo ":",await parent();', ':233'),
    'logical-loops': ('async function truth(int $n):bool{echo $n;await async_sleep_ms(0);return $n<3;}async function parent():int{$a=false && await truth(9);$b=true || await truth(8);$n=0;while(await truth($n)){$n++;}return $n;}echo ":",await parent();', ':01233'),
    'snapshot': ('class Box {public int $n=1;}async function change(Box $b):int{await async_sleep_ms(0);$b->n=9;return 2;}function pair(int $a,int $b):int{return $a*10+$b;}async function parent():int{$b=new Box();return pair($b->n,await change($b));}echo await parent();', '12'),
    'unwind': ('class Guard {function __destruct(){echo "D";}}async function child():int{$g=new Guard();await async_sleep_ms(0);$z=0;return 1/$z;}async function parent():int{$g=new Guard();return await child();}echo await parent();', 'DD'),
    'conversions': ('async function child():int{await async_sleep_ms(0);return 4;}async function boxed():?int{return await child();}async function parent():int{$n ?int=await child();$f float=(float)await child();return ($n??0)+(await boxed()??0)+(int)$f;}echo await parent();', '12'),
    'scheduler': ('async function child():void{echo probe_same();await async_sleep_ms(0);echo probe_same();}async function parent():void{probe_mark();await child();echo probe_same();}await parent();', '111'),
    'explicit-wait': ('async function child():int{await async_sleep_ms(0);return 5;}async function parent():int{return async_wait(child());}echo await parent();', '5'),
}
original_case = next(case for case in json.loads((APP / 'tests/legacy_compatibility_strict.json').read_text())['cases'] if case.get('feature') == 'async')
original_source = fixture_bytes(ROOT / original_case['source'], original_case).decode()
cases['original'] = (original_source, original_case['expected']['stdout'])
cases['ordinary-original'] = cases['original']
with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
    def check(name, source, expected):
        folder = out / name
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(source)
        ordinary = name == 'ordinary-original'
        runner.run(name + '-publish', ['php', APP / 'tests/async_tasks_publish.php', folder, metadata, 'async_probe'] + (['ordinary'] if ordinary else []))
        manifest = json.loads((folder / 'published/.scpp-output.manifest').read_text())
        assert manifest['application_templates'] == ('allowed' if ordinary else 'forbidden')
        for temperature in ['cold', 'warm']:
            build = json.loads(runner.run(name + '-' + temperature, ['php', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
            assert runner.run(name + '-' + temperature + '-run', [build['executable']], expected=1 if name == 'unwind' else 0) == expected.encode()
            if name == 'unwind':
                assert "operator '/' rejected a zero divisor" in (out / 'logs' / (name + '-' + temperature + '-run.stderr')).read_text()
            if temperature == 'warm':
                assert build['compiled'] == build['linked'] == 0
        if name in ['snapshot', 'expression-order', 'unwind', 'containers']:
            build = json.loads(runner.run(name + '-sanitized', ['php', APP / 'tests/async_sanitized_build.php', folder / 'published', folder / 'sanitized-cache', sanitizer], timeout=300))
            assert runner.run(name + '-sanitized-run', [build['executable']], expected=1 if name == 'unwind' else 0) == expected.encode()
    tasks = [Task(name, lambda n=name, s=source, e=expected: check(n, s, e)) for name, (source, expected) in cases.items()]
    tasks.append(Task('retained', lambda: runner.run('retained', ['php', APP / 'tests/async_retained.php', out / 'retained-source'])))
    run_tasks(tasks)
(out / 'summary.json').write_text(json.dumps({'passed': True, 'cases': list(cases)}, indent=2) + '\n')

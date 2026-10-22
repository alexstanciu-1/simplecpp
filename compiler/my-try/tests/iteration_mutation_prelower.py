"""V-D01 focused source mutation checks with default prelowering and cold/warm builds."""
import argparse
import json
from pathlib import Path
import sys

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks

CASES = {
    'vector-live': ('$xs vector<int> = [1,2,3];foreach($xs as $v){echo $v,",";if($v===1){$xs[1]=20;}}', '1,20,3,'),
    'vector-append': ('$xs vector<int> = [1,2,3];foreach($xs as $k=>$v){echo $k,":",$v,",";if($v===1){$xs[1]=20;$xs[]=4;$xs[2]=30;}}echo count($xs);', '0:1,1:20,2:3,4'),
    'vector-replace': ('$xs vector<int> = [1,2,3];foreach($xs as $v){echo $v;if($v===1){$xs=[9];}}echo ":",$xs[0];', '123:9'),
    'vector-call': ('function change(vector<int> &$xs):void{$xs[]=4;$xs[1]=20;}$xs vector<int> = [1,2,3];foreach($xs as $v){echo $v;if($v===1){change($xs);}}echo ":",$xs[1];', '123:20'),
    'vector-copy': ('$xs vector<int> = [1,2,3];$copy=$xs;foreach($xs as $v){echo $v,",";if($v===1){$copy[]=4;$xs[1]=20;}}', '1,20,3,'),
    'vector-nested': ('$xs vector<int> = [1,2,3];foreach($xs as $v){echo $v;if($v===1){$xs[]=4;foreach($xs as $inner){echo "[",$inner,"]";}$xs[1]=20;}}', '1[1][2][3][4]23'),
    'vector-break': ('$xs vector<int> = [1,2,3];foreach($xs as $v){$xs[]=4;break;}$xs[1]=20;foreach($xs as $v){echo $v,",";}', '1,20,3,4,'),
}
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--results', required=True, type=Path)
out = parser.parse_args().results.resolve()
out.mkdir(parents=True, exist_ok=False)
with CommandRunner(out / 'logs', ROOT, jobs=12, journals=[out / 'commands.json']) as runner:
    def check(name, source, expected):
        directory = out / name
        (directory / 'source').mkdir(parents=True)
        (directory / 'source/main.phs').write_text(source)
        published = directory / 'published'
        runner.run(name + '-generate', ['php', APP / 'main.php', '--s2s', directory / 'source', '--output', published])
        assert json.loads((published / '.scpp-output.manifest').read_text())['application_templates'] == 'forbidden'
        for mode in ['cold', 'warm']:
            build = json.loads(runner.run(name + '-' + mode,
                ['php', APP / 'tools/build_cpp.php', published, directory / 'cache'], timeout=600))
            assert runner.run(name + '-' + mode + '-run', [build['executable']]).decode() == expected
            if mode == 'warm':
                assert build['compiled'] == 0 and not build['linked'], build
    run_tasks([Task(name, lambda n=name, v=value: check(n, *v)) for name, value in CASES.items()])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'cases': list(CASES), 'default_prelowering': True}) + '\n')

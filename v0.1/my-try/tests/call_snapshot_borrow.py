"""Conservative snapshot elision: settled facts, fallback, effects and both native modes."""
import argparse
import json
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
source = out / 'source'
source.mkdir()
(source / 'main.phs').write_bytes((APP / 'tests/fixtures/call_snapshot_borrow.phs').read_bytes())
(source / 'prism.json').write_text('{"runtime":{"languages":{"php":{"profile":"strict"}}}}\n')
expected = b'0\n0\n0:zzz\n0:zzz:yyy\n0:zzz\nxbx:z\nxbx:123\nxbc:3\nupdated\n5\n3\n'
with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
    def prove(mode, failure=None):
        name = mode if failure is None else mode + '-' + failure
        inputs = source
        if failure is not None:
            inputs = out / (name + '-source')
            inputs.mkdir()
            program = 'function later():int{echo "unexpected";return 0;}'
            if failure == 'argument_error':
                program += 'function bad():string{echo "before-error";$v=1/0;return "abc";}$s="abc";echo substr_compare($s,bad(),later());'
            else:
                program += '$s ?string=null;echo "before-error";echo substr_compare($s,"abc",later());'
            (inputs/'main.phs').write_text(program)
            (inputs/'prism.json').write_bytes((source/'prism.json').read_bytes())
        published = out / name
        flag = '--prelower-templates' if mode == 'concrete' else '--no-prelower-templates'
        runner.run(name+'-emit', ['php', APP/'main.php', '--s2s', inputs, '--entry', 'main.phs', '--output', published, flag])
        build = json.loads(runner.run(name+'-build', ['php', APP/'tools/build_cpp.php', published, out/(name+'-cache'), '--no-pch', '-O0'], cli_reason='Native builder process'))
        actual = runner.run(name+'-execute', [build['executable']], expected=0 if failure is None else 1)
        assert actual == (expected if failure is None else b'before-error'), (name, actual)
    def allocations(mode):
        name = mode + '-allocations'
        folder = out/name
        runner.run(name+'-emit', ['php', APP/'tests/call_snapshot_allocations.php', folder, mode])
        build = json.loads(runner.run(name+'-build', ['php', APP/'tools/build_cpp.php', folder/'published', folder/'cache', '--no-pch', '-O0'], cli_reason='Native builder process'))
        actual = runner.run(name+'-execute', [build['executable']])
        assert actual == b'0:65536:0\n1:1\n', (mode, actual)
    run_tasks([Task('facts', lambda: runner.run('facts', ['php', APP/'tests/call_snapshot_borrow.php', out])),
               *[Task(mode, lambda mode=mode: prove(mode)) for mode in ['concrete', 'ordinary']],
               *[Task(mode+'-'+failure, lambda mode=mode, failure=failure: prove(mode, failure))
                 for mode in ['concrete', 'ordinary'] for failure in ['argument_error', 'conversion_error']],
               *[Task(mode+'-allocations', lambda mode=mode: allocations(mode)) for mode in ['concrete','ordinary']]])
(out/'summary.json').write_text(json.dumps({'passed': True, 'modes': ['concrete', 'ordinary'],
    'checks': json.loads((out/'checks.json').read_text()), 'expected': expected.decode()}, indent=2)+'\n')

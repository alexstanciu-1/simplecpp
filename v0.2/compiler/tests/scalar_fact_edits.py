"""Measure preparation separately and check conversion facts against native output after edits."""
import json
from pathlib import Path
import subprocess
import tempfile

from classes_native import fixture
from collection_goal_native import revisions


def prove(work, flavor):
    forms = fixture(flavor)
    var = forms['v']
    fn = forms['function']
    # Every function has 64 int-to-bool conversions (32 initializers and 32 assignment RHSs).
    def function(index, operand):
        body = ''
        for n in range(32):
            value = var + 'flag' + str(n)
            initial = f'(bool){operand}'
            body += forms['infer']('flag' + str(n), initial) + f'{value}={initial};'
        return fn('probe' + str(index), 'int', [('arg', 'int')], f'{body}return (int){value};')

    sources = {f'provider{i}': function(i, '0') for i in range(8)}
    sources['main'] = 'echo probe0(7);return 8;'
    dynamic = function(0, var + 'arg')
    revisions(work, flavor, sources, [(None, None, b'0'), (None, None, b'0'),
              ('provider0', dynamic, b'1'), ('provider0', None, 'Unknown function'),
              ('provider0', sources['provider0'], b'0')])

    # Use the same sources for inspection. The resident build above has finished.
    app = work / flavor
    changed = work / (flavor + '-edit.txt')
    changed.write_text(dynamic)
    command = ['php', Path(__file__).with_name('scalar_fact_prepare.php'), app, flavor, changed]
    rows = json.loads(subprocess.check_output(command))
    print(flavor, json.dumps(rows), flush=True)


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-scalar-facts-') as tmp:
        for flavor in ('php', 'cpp', 'js'):
            prove(Path(tmp), flavor)

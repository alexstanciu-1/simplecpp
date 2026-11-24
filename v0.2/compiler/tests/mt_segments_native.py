"""Fresh multi-file compilation and native slot identity, batch admission and joined failure."""
import json
import os
import re
import subprocess
import sys
import tempfile
from pathlib import Path

from classes_native import fixture

ROOT = Path(__file__).resolve().parents[2]
FACTS, LIBRARY, CXX = sys.argv[1:]
FLAGS = [CXX, '-std=c++23', '-stdlib=libc++', '-pthread', '-I' + str(ROOT / 'runtime/generated/public')]


def compile_source(app, out, sources, error=None):
    command = ['php', ROOT / 'compiler/main.php', 'program', app, 'main.php', FACTS, out]
    for name, source in sources.items():
        (app / name).write_text(source)
        command.extend([name.split('.')[-1], name])
    run = subprocess.run(command, text=True, capture_output=True)
    if error:
        assert run.returncode and error in run.stderr and not (out / 'manifest.json').exists(), run.stderr
        return
    assert run.returncode == 0, run.stderr
    return json.loads((out / 'manifest.json').read_text())


MAIN = """
$p Point;
$rows vector<Point> = [];
$rows[] = $p;
$rows[] = $p;
$rows[] = $p;
$deltas vector<int> = [2,4,6];
$split = 1;
$out = dispatch($rows, $deltas, $deltas, $split);
echo $out[0], $out[1], $rows[0]->x, $rows[1]->x, $rows[2]->x, $deltas[0], $deltas[1], $deltas[2];
return 0;
"""

# A second name for the same adjustment vector exercises backing identity at admission.
DISPATCH = """function dispatch(vector<Point> &$rows, vector<int> &$deltas,
                                vector<int> &$other, int $offset): vector<int> {
    return mt_join(work(segment($rows,0,1), segment($deltas,0,1)),
                   work(segment($rows,1,1), segment($other,$offset,1)));
}
"""

PY_WORK = """def work(rows: segment<Point>, deltas: segment<int>) -> int:
    i: int = 0
    subtotal: int = 0
    while i < count(rows):
        subtotal = subtotal + update(rows, deltas, i)
        i = i + 1
    return subtotal
"""
PY_HELPER = """def update(rows: segment<Point>, deltas: segment<int>, i: int) -> int:
    rows[i].x = rows[i].x + deltas[i]
    deltas[i] = deltas[i] + 1
    return rows[i].x
"""


def sources_for(flavor):
    sources = {'main.php': MAIN, 'dispatch.php': DISPATCH, 'point.php': 'struct Point { public int $x = 1; }'}
    if flavor == 'py':
        return dict(sources, **{'worker.py': PY_WORK, 'helper.py': PY_HELPER})
    f = fixture(flavor)
    v = f['v']
    params = [('rows', 'segment<Point>'), ('deltas', 'segment<int>')]
    field = f'{v}rows[{v}i]{f["access"]}x'
    update = f'{field} = {field} + {v}deltas[{v}i]; {v}deltas[{v}i] = {v}deltas[{v}i] + 1; return {field};'
    body = (f['infer']('i', '0') + f['infer']('subtotal', '0') +
            f'while ({v}i < count({v}rows)) {{ {v}subtotal += update({v}rows,{v}deltas,{v}i); {v}i += 1; }} return {v}subtotal;')
    sources['helper.' + flavor] = f['function']('update', 'int', params + [('i', 'int')], update)
    sources['worker.' + flavor] = f['function']('work', 'int', params, body)
    return sources


def prove(work):
    app = work / 'app'
    app.mkdir()
    for flavor in ('php', 'cpp', 'js', 'py'):
        out = work / flavor
        sources = sources_for(flavor)
        manifest = compile_source(app, out, sources)
        units = [out / u for u in manifest['translation_units']]
        # The compiled helper's store must use the record reference produced by the view.
        helper_cpp = (out / manifest['sources']['helper.' + flavor]).read_text()
        assert re.search(r'\(\*static_cast<scpp_records::Point\*>\(.*?\.at\([^;]*?\)\)+\.x =', helper_cpp), helper_cpp
        binary = work / ('run-' + flavor)
        subprocess.run([*FLAGS, *units, LIBRARY, '-o', binary], check=True)
        for policy in ('sleep', 'active'):
            env = dict(os.environ, SCPP_MT_WAIT=policy)
            assert subprocess.check_output([binary], env=env, timeout=10) == b'35351356'
        if flavor == 'php':
            # Check the transitive header closure in an MT-enabled, mixed-use build.
            view, launch = 'mt_segment.hpp', 'mt_segments.hpp'
            for header, includes in [(view, ['stddef.h', 'stdint.h']), (launch, ['scpp/' + view])]:
                path = ROOT / 'runtime/generated/public/scpp' / header
                direct = re.findall(r'^#include [<"]([^>"]+)[>"]', path.read_text(), re.M)
                assert sorted(direct) == sorted(includes), (header, direct)
                subprocess.run([*FLAGS, '-x', 'c++', '-fsyntax-only', '-'],
                               input=f'#include <scpp/{header}>\n', text=True, check=True)
            for source, expected in [('main.php', set()), ('point.php', set()),
                                     ('helper.php', {view}), ('worker.php', {view}),
                                     ('dispatch.php', {view, launch})]:
                deps = subprocess.check_output([*FLAGS, '-MM', out / manifest['sources'][source]], text=True)
                assert {Path(dep).name for dep in deps.split() if '/scpp/mt_' in dep} == expected, deps
            # Exercise the compiler-generated application vector and the actual worker body.
            headers = [out / name for name in manifest['headers']]
            collection = next(h for h in headers if 'mt_data(' in h.read_text())
            vec = re.search(r'class (scpp_vector_\w+) final', collection.read_text())[1]
            driver = work / 'probe.cpp'
            record = next(h for h in headers if 'struct Point {' in h.read_text())
            driver.write_text(f'#include "{record}"\n#include "{collection}"\nusing Rows = App::{vec};\n' +
                              Path(__file__).with_name('mt_segments_probe.cpp').read_text())
            probe = work / 'probe'
            subprocess.run([*FLAGS, driver, *[p for p in units if p.name != 'entry.cpp'],
                            LIBRARY, '-o', probe], check=True)
            for policy in ('sleep', 'active'):
                subprocess.run([probe], env=dict(os.environ, SCPP_MT_WAIT=policy), check=True, timeout=15)

    valid = sources_for('php')
    helper = valid['helper.php']
    cases = [
        ({'helper.php': 'global $counter int = 7;' + helper.replace('return $rows', 'return $counter + $rows')}, 'MT safety'),
        ({'helper.php': helper.replace('return $rows[$i]->x;', 'return $rows;')}, 'conversion'),
        ({'helper.php': helper.replace('return $rows', '$rows[] = 7; return $rows')}, 'Append'),
        ({'point.php': 'class Shared {} struct Point { public int $x = 1; public Shared $shared; }'}, 'isolated inline'),
    ]
    for i, (change, error) in enumerate(cases):
        compile_source(app, work / f'invalid-{i}', valid | change, error)


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-mt-') as temp:
        prove(Path(temp))

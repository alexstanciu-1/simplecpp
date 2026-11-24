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
$cells vector<int> = [1,2,3,4];
$m = mixed_batch($cells, $cells, 3);
echo $m[0], $m[1], $cells[0], $cells[1], $cells[2], $cells[3];
check_blocks($cells);
$fresh vector<int> = [1,2,3,4];
$pair = pair_batch($fresh, $fresh, 1);
echo $pair[0], $pair[1], $fresh[0], $fresh[1];
check_snapshots();
echo mixed_each(0), mixed_each(2);
$r = read_batch($rows, $fresh, 0);
echo $r[0], $r[1], $fresh[0];
return 0;
"""

# A second name for the same adjustment vector exercises backing identity at admission.
DISPATCH = """function dispatch(vector<Point> &$rows, vector<int> &$deltas,
                                vector<int> &$other, int $offset): vector<int> {
    return mt_join(work(segment($rows,0,1), segment($deltas,0,1)),
                   work(segment($rows,1,1), segment($other,$offset,1)));
}
"""

MIXED_BATCH = """function parallel(): int { return 7; }
function tick(int &$calls, int $result): int {
    $calls += 1;
    return $result;
}
function mixed_each(int $parts): int {
    $calls = 0;
    $cells vector<int> = [0,0,0];
    $results = parallel (workers: tick($calls, 1)) {
        job (each: $i in 0 ..< tick($calls, $parts), write: $a = segment($cells,$i,1),
             value: $v = tick($calls,$i)) { $a[0] = $v; return $i; }
        single_slot(segment($cells,2,1));
        job (each: $i in 0 ..< 1, read: $a = segment($cells,0,0)) { return 9; }
    };
    return $calls * 100 + $results[$parts] + $results[$parts + 1];
}
function each_batch(vector<int> &$entries, vector<int> &$output, int $parts, int $cap, int $width): vector<int> {
    return parallel (workers: $cap) {
        job (each: $part in 0 ..< $parts,
             write: $entered = segment($entries,$part,1),
             write: $dst = segment($output,balanced_range($width,$width,$part % $width)),
             value: $base = $part + 10) {
            $entered[0] = $entered[0] + 1;
            $dst[0] = $base;
            return $part;
        }
    };
}
function range_batch(const vector<int> &$input, vector<int> &$output, int $parts, int $part): vector<int> {
    $range = balanced_range(count($input), $parts, $part);
    return parallel {
        job (read: $src = segment($input,$range), write: $dst = segment($output,$range)) {
            $i = 0;
            while ($i < count($src)) {
                $dst[$i] = $src[$i];
                $i += 1;
            }
            return $i;
        }
    };
}
function grow_for_range(vector<int> &$cells, int &$calls): segment_range {
    $cells[] = 2;
    $calls += 1;
    return balanced_range(count($cells),2,1);
}
function snapshot_range(): int {
    $cells vector<int> = [1];
    $calls = 0;
    $s = mt_join(single_slot(segment($cells,0,1)), single_slot(segment($cells,grow_for_range($cells,$calls))));
    return $calls * 100 + $s[0] + $s[1];
}
function range_for(int $size, int $parts, int $part): segment_range {
    return balanced_range($size,$parts,$part);
}
function range_end(segment_range $range): int { return $range->offset + $range->length; }
function read_first(read_segment<Point> $points): int { return $points[0]->x; }
function relay_read(read_segment<Point> $points): int { return read_first($points); }
function read_batch(const vector<Point> &$rows, vector<int> &$cells, int $offset): vector<int> {
    return parallel {
        relay_read(segment($rows,0,2));
        job (read: $points = segment($rows,0,1), read: $a = segment($cells,1,1),
             write: $out = segment($cells,$offset,1), value: $extra = 1) {
            $out[0] = relay_read($points) + $a[0] + $extra;
            return $out[0];
        }
    };
}
function check_snapshots(): void {
    $snap vector<int> = [5];
    $seed = 2;
    $s = parallel {
        single_slot(segment($snap,0,1));
        job (value: $first = $seed, write: $dst = segment($snap,1,1),
             value: $second = extend_input($snap,$seed), value: $rate = 0.5) {
            if ($rate < 1.0) { $first += 10; }
            $dst[0] = $first + $second;
            return $dst[0];
        }
    };
    echo $seed, count($snap), $s[0], $s[1], $snap[1];
}
function extend_input(vector<int> &$cells, int &$seed): int {
    $seed += 1;
    $cells[] = $seed;
    return $seed;
}
function check_blocks(vector<int> &$cells): void {
    $more = parallel { job (write: $cell = segment($cells,0,1)) { return single_slot($cell); } };
    parallel { single_slot(segment($cells,0,1)); };
    echo $more[0], $cells[0], parallel();
}
const int WIDTH = 5;
const int LIMIT = WIDTH * 2;
function adjustment(): int { return LIMIT; }
function mixed_batch(vector<int> &$cells, vector<int> &$alias, int $last): vector<int> {
    return mt_join(single_slot(segment($cells,0,1)),
                   three_slots(segment($cells,1,1), segment($cells,2,1), segment($alias,$last,1)));
}
function pair_batch(vector<int> &$cells, vector<int> &$alias, int $offset): vector<int> {
    return parallel {
        single_slot(segment($cells,3,1));
        job (write: $prices = segment($cells,0,1), write: $fees = segment($alias,$offset,1)) {
            $sum = 0;
            $i = 0;
            while ($i < count($prices)) {
                $prices[$i] = $prices[$i] + $fees[$i];
                $fees[$i] = 0;
                $sum += $prices[$i];
                $i += 1;
            }
            return $sum;
        }
    };
}
function single_slot(segment<int> $a): int {
    if (Point::ENABLED) { $a[0] = $a[0] + adjustment(); }
    return $a[0];
}
function three_slots(segment<int> $a, segment<int> $b, segment<int> $c): int {
    $a[0] = $a[0] + 20 + STR_PAD_LEFT;
    $b[0] = $b[0] + 30;
    $c[0] = $c[0] + 40;
    return $a[0] + $b[0] + $c[0];
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
    sources = {'main.php': MAIN, 'dispatch.php': DISPATCH, 'mixed.php': MIXED_BATCH, 'point.php': 'struct Point { public const bool ENABLED = true; public int $x = 1; }'}
    if flavor == 'py':
        return dict(sources, **{'worker.py': PY_WORK, 'helper.py': PY_HELPER})
    # Use the same direct-call block in each brace frontend. The Python worker
    # fixture above retains the original mt_join launch for compatibility.
    del sources['dispatch.php']
    header = {
        'php': 'function dispatch(vector<Point> &$rows, vector<int> &$deltas, vector<int> &$other, int $offset): vector<int>',
        'cpp': 'vector<int> dispatch(vector<Point>& rows, vector<int>& deltas, vector<int>& other, int offset)',
        'js': 'function dispatch(&rows:vector<Point>, &deltas:vector<int>, &other:vector<int>, offset:int):vector<int>',
    }[flavor]
    body = """{ return parallel (workers: 2) {
        work(segment($rows,0,1), segment($deltas,0,1));
        job (value: $enabled = true, write: $points = segment($rows,1,1),
             value: $index = $offset - 1, write: $changes = segment($other,$offset,1)) {
            if (!$enabled) { return 0; }
            return update($points, $changes, $index);
        }
        job (each: $part in 0..<2, read: $empty = segment($rows,balanced_range(0,2,$part))) { return $part + count($empty); }
    }; }"""
    sources['dispatch.' + flavor] = header + (body if flavor == 'php' else body.replace('$', ''))
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
            assert subprocess.check_output([binary], env=env, timeout=10) == b'35351356' + b'119911223344' + b'21317' + b'14330' + b'32151515' + b'219419' + b'344'
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
    dispatch = valid['dispatch.php']
    cases = [
        ({'mixed.php': MIXED_BATCH.replace('each: $part in 0 ..< $parts,', 'workers: 2, each: $part in 0 ..< $parts,')},
         'Expected each, read, write or value'),
        ({'mixed.php': MIXED_BATCH.replace('value: $base = $part + 10', 'value: $base = tick($part,10)')},
         'each index is read-only'),
        ({'mixed.php': MIXED_BATCH.replace('return $part;', '$part += 1; return $part;')}, 'each index is read-only'),
        ({'mixed.php': MIXED_BATCH.replace('workers: $cap', 'workers: true')}, 'Group workers requires int'),
        ({'mixed.php': MIXED_BATCH.replace('0 ..< $parts', '0 ..< false')}, 'each bound requires int'),

        ({'mixed.php': MIXED_BATCH.replace('segment($output,$range)', 'segment($output,$parts)')},
         'Expected a segment_range'),
        ({'mixed.php': MIXED_BATCH.replace('return $range->offset +', '$range->length = 5; return $range->offset +')},
         'Cannot modify a segment_range field'),
        ({'mixed.php': MIXED_BATCH.replace('return $points[0]->x;', '$points[0]->x = 4; return 4;')},
         'Cannot write through a read-only segment'),
        ({'mixed.php': MIXED_BATCH.replace('$out[0] = relay_read', '$a[0] = relay_read')},
         'Cannot write through a read-only segment'),
        ({'mixed.php': MIXED_BATCH.replace('return read_first($points);', 'return mutate_points($points);') +
                       'function mutate_points(segment<Point> $points): int { $points[0]->x = 1; return 1; }'},
         'conversion from read_segment<Point> to segment<Point>'),
        ({'mixed.php': MIXED_BATCH.replace('write: $cell = segment($cells,0,1)', 'value: $cell = 1')},
         'at least one segment binding'),
        ({'dispatch.php': dispatch.replace('value: $enabled = true', 'value: $enabled = $rows')},
         'requires a numeric or boolean scalar'),
        ({'dispatch.php': dispatch.replace('value: $index', 'value: $points')}, 'Duplicate inline job binding: points'),
        ({'dispatch.php': dispatch.replace('return update($points, $changes, $index);', 'return $offset;')},
         'Unknown or not-yet-initialized local/global: offset'),
        ({'dispatch.php': dispatch.replace('write: $changes', 'write: $points')}, 'Duplicate inline job binding: points'),
        ({'dispatch.php': dispatch.replace('return update($points, $changes, $index);', 'return $changes;')}, 'conversion'),
        ({'dispatch.php': dispatch.replace('return update($points, $changes, $index);', 'return forbidden();')
                          + ' global $counter int = 1; function forbidden(): int { return $counter; }'},
         'MT safety: worker cannot access global or static storage'),
        ({'dispatch.php': valid['dispatch.php'].replace('work(segment($rows,0,1), segment($deltas,0,1));', '1;')},
         'Expected worker call'),
        ({'dispatch.php': valid['dispatch.php'].replace('segment($deltas,0,1));', 'segment($deltas,0,1))')},
         'Expected ; after parallel worker call'),
        ({'dispatch.php': DISPATCH[:DISPATCH.index('return')] + 'return parallel {}; }'},
         'Expected at least one segment worker'),
        ({'mixed.php': 'global $limit int = 10;' + MIXED_BATCH.replace('return LIMIT;', 'return $limit;')},
         'MT safety: worker cannot access global or static storage'),
        ({'mixed.php': 'const string LABEL = "on";' + MIXED_BATCH.replace('Point::ENABLED', 'LABEL === LABEL')},
         'MT safety: worker requires a numeric or boolean constant'),
        ({'mixed.php': MIXED_BATCH.replace('single_slot(segment($cells,0,1))', 'single_slot()')}, 'matching segment arguments'),
        ({'mixed.php': MIXED_BATCH.replace(', segment($alias,$last,1)', '')}, 'matching segment arguments'),
        ({'helper.php': 'global $counter int = 7;' + helper.replace('return $rows', 'return $counter + $rows')}, 'MT safety'),
        ({'helper.php': helper.replace('return $rows[$i]->x;', 'return $rows;')}, 'conversion'),
        ({'helper.php': helper.replace('return $rows', '$rows[] = 7; return $rows')}, 'Append'),
        ({'point.php': 'class Shared {} struct Point { public const bool ENABLED = true; public int $x = 1; public Shared $shared; }'}, 'isolated inline'),
    ]
    for i, (change, error) in enumerate(cases):
        compile_source(app, work / f'invalid-{i}', valid | change, error)


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-mt-') as temp:
        prove(Path(temp))

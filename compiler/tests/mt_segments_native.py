"""Fresh multi-file compilation and native slot identity, batch admission and joined failure."""
from source_suffix import source_suffix
import json
import os
import re
import subprocess
import sys
import tempfile
from pathlib import Path

from classes_native import fixture

ROOT = Path(__file__).resolve().parents[2]
FACTS, LIBRARY, CXX = sys.argv[1:4]
FLAGS = [CXX, '-std=c++23', '-stdlib=libc++', '-pthread', '-I' + str(ROOT / 'runtime/generated/public')]


def compile_source(app, out, sources, error=None):
    command = ['php', ROOT / 'compiler/main.php', 'program', app, 'main.phs', FACTS, out]
    for name, source in sources.items():
        (app / name).write_text(source)
        command.extend(['php' if name.endswith('.phs') else name.split('.')[-1], name])
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

EACH_BATCH = """function each_batch(vector<int> &$entries, vector<int> &$output, int $parts, int $cap, int $width): vector<int> {
    return parallel (workers: $cap) {
        job (each: $part in 0 ..< $parts,
             workers: 3, write: $entered = segment($entries,$part,1),
             write: $dst = segment($output,balanced_range($width,$width,$part % $width)),
             value: $base = $part + 10) {
            $entered[0] = $entered[0] + 1;
            $dst[0] = $base;
            return $part;
        }
    };
}
"""

CAP_BATCH = """function tick(int &$calls, int $result): int {
    $calls += 1;
    return $result;
}
function capped_batch(vector<int> &$ids, int $parts, int $limit, int &$calls): vector<int> {
    return parallel (workers: 5) {
        job (each: $part in 0 ..< $parts, workers: tick($calls, $limit),
             read: $id = segment($ids,$part,1)) { return note_entry($id[0]); }
        sibling(segment($ids,12,1));
        sibling(segment($ids,13,1));
    };
}
function independent_caps(vector<int> &$ids): vector<int> {
    return parallel {
        job (each: $i in 0 ..< 4, workers: 1,
             read: $id = segment($ids,$i,1)) { return note_entry($id[0]); }
        job (each: $i in 0 ..< 10, workers: 2,
             read: $id = segment($ids,balanced_range(14,14,$i + 4))) { return note_entry($id[0]); }
    };
}
function sibling(read_segment<int> $id): int { return note_entry($id[0]); }
"""

MIXED_BATCH = "function parallel(): int { return 7; }\n" + CAP_BATCH + """function mixed_each(int $parts): int {
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
""" + EACH_BATCH + """function range_batch(const vector<int> &$input, vector<int> &$output, int $parts, int $part): vector<int> {
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


HASH_BATCH = """function record_hash(): int {
    $one Point;
    $records hash<Point,string> = [];
    $records["a"] = $one;
    $records["b"] = $one;
    $result = parallel {
        job (write: $slice = segment($records,balanced_range(2,1,0))) {
            foreach ($slice as $key => $row) {
                if ($key === "b") { $row->x = 5; }
            }
            return count($slice);
        }
    };
    return $result[0] + $records["a"]->x + $records["b"]->x;
}
function vector_digits(read_segment<int> $s): int { return $s[0]*10+$s[1]; }
function vector_read_pair(vector<int> &$v): vector<int> {
    return parallel {
        vector_digits(segment($v,1,2));
        job (read: $s = segment($v,1,2)) { return vector_digits($s); }
    };
}
function entry_digits(read_hash_segment<int,int> $s): int {
    $digits = 0;
    foreach ($s as $key => $value) { $digits = $digits*1000 + $key*100 + $value; }
    return $digits;
}
function ordered_results(vector<int> &$values, hash<int,int> &$table): vector<int> {
    $results vector<int> = mt_join(vector_digits(segment($values,0,2)),
                                   entry_digits(segment($table,0,2)),
                                   vector_digits(segment($values,2,2)));
    return $results;
}
function repeated_results(vector<int> &$values, int $parts): vector<int> {
    return parallel {
        job (each: $i in 0 ..< $parts, read: $s = segment($values,$i,1)) { return $s[0]; }
    };
}
function hole_read_pair(hash<int,int> &$table): vector<int> {
    return parallel {
        entry_digits(segment($table,0,2));
        job (read: $s = segment($table,0,2)) { return entry_digits($s); }
    };
}
function read_order(read_hash_segment<int,int> $s): int {
    $order = 0;
    foreach ($s as $key => $v) { $order = $order * 10 + $key; }
    return $order;
}
function read_pair(hash<int,int> &$table, int $offset, int $length): vector<int> {
    return parallel {
        read_order(segment($table,$offset,$length));
        job (read: $s = segment($table,$offset,$length)) { return count($s); }
    };
}
function update_hash(hash_segment<int,int> $s): int {
    $order = 0;
    foreach ($s as $key => $value) {
        $order = $order * 10 + $key;
        $value += 10;
    }
    return $order;
}
function hash_write_pair(hash<int,int> &$table, hash<int,int> &$alias, int $offset): vector<int> {
    return parallel {
        update_hash(segment($table,1,1));
        job (write: $s = segment($alias,$offset,1)) { return update_hash($s); }
    };
}
function hash_write_read(hash<int,int> &$table, hash<int,int> &$alias): vector<int> {
    return parallel {
        update_hash(segment($table,1,1));
        read_order(segment($alias,1,1));
    };
}
function write_digit(segment<int> $s): int { $s[0] = $s[0] + 10; return $s[0]; }
function vector_write_pair(vector<int> &$v): vector<int> {
    return parallel {
        write_digit(segment($v,1,1));
        job (write: $s = segment($v,2,1)) { return write_digit($s); }
    };
}
function hash_batch(hash<int,int> &$table, hash<int,int> &$alias, vector<int> &$entered, int $offset): vector<int> {
    return parallel {
        job (write: $flag = segment($entered,0,1), write: $slice = segment($table,1,2)) {
            $flag[0] = 1;
            return update_hash($slice);
        }
        job (write: $flag = segment($entered,1,1), read: $slice = segment($alias,$offset,1)) {
            $flag[0] = 1;
            foreach ($slice as $key => $value) { return $key; }
            return 0;
        }
    };
}
"""


def sources_for(flavor):
    sources = {'main.phs': MAIN, 'dispatch.phs': DISPATCH, 'mixed.phs': MIXED_BATCH, 'hash.phs': HASH_BATCH, 'point.phs': 'struct Point { public const bool ENABLED = true; public int $x = 1; }'}
    sources['probe_hook.phs'] = 'function note_entry(int $id): int { return $id; }'
    if flavor == 'py':
        return dict(sources, **{'worker.py': PY_WORK, 'helper.py': PY_HELPER})
    # Use the same direct-call block in each brace frontend. The Python worker
    # fixture above retains the original mt_join launch for compatibility.
    del sources['dispatch.phs']
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
        job (each: $part in 0..<2, workers: 1, read: $empty = segment($rows,balanced_range(0,2,$part))) { return $part + count($empty); }
    }; }"""
    sources['dispatch.' + source_suffix(flavor)] = header + (body if flavor == 'php' else body.replace('$', ''))
    f = fixture(flavor)
    v = f['v']
    params = [('rows', 'segment<Point>'), ('deltas', 'segment<int>')]
    field = f'{v}rows[{v}i]{f["access"]}x'
    update = f'{field} = {field} + {v}deltas[{v}i]; {v}deltas[{v}i] = {v}deltas[{v}i] + 1; return {field};'
    body = (f['infer']('i', '0') + f['infer']('subtotal', '0') +
            f'while ({v}i < count({v}rows)) {{ {v}subtotal += update({v}rows,{v}deltas,{v}i); {v}i += 1; }} return {v}subtotal;')
    sources['helper.' + source_suffix(flavor)] = f['function']('update', 'int', params + [('i', 'int')], update)
    sources['worker.' + source_suffix(flavor)] = f['function']('work', 'int', params, body)
    return sources


def prove_each(work):
    app = work / 'app'
    app.mkdir()
    out = work / 'out'
    sources = {'main.phs': 'return 0;', 'batch.phs': EACH_BATCH}
    manifest = compile_source(app, out, sources)
    units = [out / unit for unit in manifest['translation_units'] if unit != 'entry.cpp']
    binary = work / 'probe'
    subprocess.run([*FLAGS, Path(__file__).with_name('mt_each_probe.cpp'), *units, LIBRARY, '-o', binary], check=True)
    for policy in ('sleep', 'active'):
        subprocess.run([binary], env=dict(os.environ, SCPP_MT_WAIT=policy), check=True, timeout=10)
    sources['batch.phs'] = EACH_BATCH.replace('return $part;', 'return observe();')
    sources['helper.phs'] = 'global $counter int = 7; function observe():int { return $counter; }'
    compile_source(app, work / 'rejected', sources, 'MT safety: worker cannot access global or static storage')


def prove_readonly_helper(work):
    app = work / 'app'
    app.mkdir()
    sources = {'main.phs': 'return 0;', 'worker.phs': """
        function write_slot(segment<int> $s): int { $s[0] = 1; return $s[0]; }
        function relay(read_segment<int> $s): int { return write_slot($s); }
        function batch(vector<int> &$v): vector<int> {
            return parallel { relay(segment($v,0,1)); };
        }
    """}
    compile_source(app, work / 'rejected', sources, 'conversion from read_segment<int64> to segment<int64>')


def prove_native_contract(work, name):
    app = work / 'app'
    app.mkdir()
    sources = {'main.phs': 'return 0;'}
    if name == 'job_caps':
        sources.update({'batch.phs': CAP_BATCH, 'probe_hook.phs': 'function note_entry(int $id): int { return $id; }'})
    else:
        sources.update({'batch.phs': HASH_BATCH, 'point.phs': 'struct Point { public int $x = 1; }'})
    out = work / 'out'
    manifest = compile_source(app, out, sources)
    # The cap observer supplies the instrumented implementation of note_entry.
    hook = manifest['sources'].get('probe_hook.phs')
    units = [out / u for u in manifest['translation_units'] if u not in ('entry.cpp', hook)]
    driver = work / 'probe.cpp'
    driver.write_text(f'#include "mt_{name}_probe.hpp"\nint main() {{ check_{name}(); }}\n')
    binary = work / 'probe'
    subprocess.run([*FLAGS, '-I', Path(__file__).parent, driver, *units, LIBRARY, '-o', binary], check=True)
    for policy in ('sleep', 'active'):
        subprocess.run([binary], env=dict(os.environ, SCPP_MT_WAIT=policy), check=True, timeout=15)


def prove(work):
    app = work / 'app'
    app.mkdir()
    for flavor in ('php', 'cpp', 'js', 'py'):
        out = work / flavor
        sources = sources_for(flavor)
        manifest = compile_source(app, out, sources)
        units = [out / u for u in manifest['translation_units']]
        # The compiled helper's store must use the record reference produced by the view.
        helper_cpp = (out / manifest['sources']['helper.' + source_suffix(flavor)]).read_text()
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
            for source, expected in [('main.phs', set()), ('point.phs', set()),
                                     ('helper.phs', {view}), ('worker.phs', {view}),
                                     ('dispatch.phs', {view, launch})]:
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
            # Link a synchronized observer in place of the identity helper for this native probe.
            hook = out / manifest['sources']['probe_hook.phs']
            subprocess.run([*FLAGS, '-I', Path(__file__).parent, driver, *[p for p in units if p.name != 'entry.cpp' and p != hook],
                            LIBRARY, '-o', probe], check=True)
            for policy in ('sleep', 'active'):
                subprocess.run([probe], env=dict(os.environ, SCPP_MT_WAIT=policy), check=True, timeout=15)

    valid = sources_for('php')
    helper = valid['helper.phs']
    dispatch = valid['dispatch.phs']
    cases = [
        ({'hash.phs': HASH_BATCH.replace('write: $slice = segment($records', 'read: $slice = segment($records')},
         'read-only hash segment binding'),
        ({'hash.phs': HASH_BATCH.replace('$value += 10;', '$key += 10;')}, 'read-only hash segment binding'),
        ({'hash.phs': HASH_BATCH.replace('return $key;', '$value += 10; return $key;')}, 'read-only hash segment binding'),
        ({'mixed.phs': MIXED_BATCH.replace('workers: tick($calls, $limit)', 'workers: true')}, 'Job workers requires int'),
        ({'mixed.phs': MIXED_BATCH.replace('workers: tick($calls, $limit)', 'workers: $part')}, 'local/global: part'),
        ({'mixed.phs': MIXED_BATCH.replace('workers: tick($calls, $limit)', 'workers: 2, workers: 3')}, 'Job workers must appear once'),
        ({'mixed.phs': MIXED_BATCH.replace('each: $part in 0 ..< $parts,', 'workers: 2, each: $part in 0 ..< $parts,')},
         'Job workers must appear once, immediately after each'),
        ({'mixed.phs': MIXED_BATCH.replace('value: $base = $part + 10', 'value: $base = tick($part,10)')},
         'each index is read-only'),
        ({'mixed.phs': MIXED_BATCH.replace('return $part;', '$part += 1; return $part;')}, 'each index is read-only'),
        ({'mixed.phs': MIXED_BATCH.replace('workers: $cap', 'workers: true')}, 'Group workers requires int'),
        ({'mixed.phs': MIXED_BATCH.replace('0 ..< $parts', '0 ..< false')}, 'each bound requires int'),

        ({'mixed.phs': MIXED_BATCH.replace('segment($output,$range)', 'segment($output,$parts)')},
         'Expected a segment_range'),
        ({'mixed.phs': MIXED_BATCH.replace('return $range->offset +', '$range->length = 5; return $range->offset +')},
         'Cannot modify a segment_range field'),
        ({'mixed.phs': MIXED_BATCH.replace('return $points[0]->x;', '$points[0]->x = 4; return 4;')},
         'Cannot write through a read-only segment'),
        ({'mixed.phs': MIXED_BATCH.replace('$out[0] = relay_read', '$a[0] = relay_read')},
         'Cannot write through a read-only segment'),
        ({'mixed.phs': MIXED_BATCH.replace('return read_first($points);', 'return mutate_points($points);') +
                       'function mutate_points(segment<Point> $points): int { $points[0]->x = 1; return 1; }'},
         'conversion from read_segment<Point> to segment<Point>'),
        ({'mixed.phs': MIXED_BATCH.replace('write: $cell = segment($cells,0,1)', 'value: $cell = 1')},
         'at least one segment binding'),
        ({'dispatch.phs': dispatch.replace('value: $enabled = true', 'value: $enabled = $rows')},
         'requires a numeric or boolean scalar'),
        ({'dispatch.phs': dispatch.replace('value: $index', 'value: $points')}, 'Duplicate inline job binding: points'),
        ({'dispatch.phs': dispatch.replace('return update($points, $changes, $index);', 'return $offset;')},
         'Unknown or not-yet-initialized local/global: offset'),
        ({'dispatch.phs': dispatch.replace('write: $changes', 'write: $points')}, 'Duplicate inline job binding: points'),
        ({'dispatch.phs': dispatch.replace('return update($points, $changes, $index);', 'return $changes;')}, 'conversion'),
        ({'dispatch.phs': dispatch.replace('return update($points, $changes, $index);', 'return forbidden();')
                          + ' global $counter int = 1; function forbidden(): int { return $counter; }'},
         'MT safety: worker cannot access global or static storage'),
        ({'dispatch.phs': valid['dispatch.phs'].replace('work(segment($rows,0,1), segment($deltas,0,1));', '1;')},
         'Expected worker call'),
        ({'dispatch.phs': valid['dispatch.phs'].replace('segment($deltas,0,1));', 'segment($deltas,0,1))')},
         'Expected ; after parallel worker call'),
        ({'dispatch.phs': DISPATCH[:DISPATCH.index('return')] + 'return parallel {}; }'},
         'Expected at least one segment worker'),
        ({'mixed.phs': 'global $limit int = 10;' + MIXED_BATCH.replace('return LIMIT;', 'return $limit;')},
         'MT safety: worker cannot access global or static storage'),
        ({'mixed.phs': 'const string LABEL = "on";' + MIXED_BATCH.replace('Point::ENABLED', 'LABEL === LABEL')},
         'MT safety: worker requires a numeric or boolean constant'),
        ({'mixed.phs': MIXED_BATCH.replace('single_slot(segment($cells,0,1))', 'single_slot()')}, 'matching segment arguments'),
        ({'mixed.phs': MIXED_BATCH.replace(', segment($alias,$last,1)', '')}, 'matching segment arguments'),
        ({'helper.phs': 'global $counter int = 7;' + helper.replace('return $rows', 'return $counter + $rows')}, 'MT safety'),
        ({'helper.phs': helper.replace('return $rows[$i]->x;', 'return $rows;')}, 'conversion'),
        ({'helper.phs': helper.replace('return $rows', '$rows[] = 7; return $rows')}, 'Append'),
        ({'point.phs': 'class Shared {} struct Point { public const bool ENABLED = true; public int $x = 1; public Shared $shared; }'}, 'isolated inline'),
    ]
    cases.append(({'main.phs': '$m hash<int,int> = [];$m[1]=2;$alias =& $m;'
                  'foreach ($m as &$v) {parallel { update_hash(segment($alias,0,1)); };}return 8;'},
                  'during writable foreach'))
    for i, (change, error) in enumerate(cases):
        compile_source(app, work / f'invalid-{i}', valid | change, error)


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-mt-') as temp:
        if sys.argv[4:] == ['--quick']:
            prove_each(Path(temp))
        elif sys.argv[4:] == ['--readonly_helper']:
            prove_readonly_helper(Path(temp))
        elif sys.argv[4:] in (['--job_caps'], ['--hash_segments']):
            prove_native_contract(Path(temp), sys.argv[4][2:])
        else:
            prove(Path(temp))

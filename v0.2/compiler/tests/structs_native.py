"""Two-unit inline structs: native value semantics, layout watches and recovery versus fresh builds."""
import hashlib
import json
import os
import subprocess
import tempfile
from pathlib import Path

from app_namespace_native import artifacts, invoke, service, write_build


PROGRAMS = {
    'php': ('struct Point { public int $x; public int $y; }',
            '$p Point; echo $p->x, $p->y; $p->x=3; $q=$p; $q->x=8; echo $p->x,",",$q->x; return 8;'),
    'cpp': ('struct Point { int x; int y; };',
            'Point p; echo p->x, p->y; p->x=3; auto q=p; q->x=8; echo p->x,",",q->x; return 8;'),
    'js': ('struct Point { x: int; y: int; }',
           'let p:Point; echo p.x, p.y; p.x=3; let q=p; q.x=8; echo p.x,",",q.x; return 8;'),
}


def build(output, native, expected=b'003,8'):
    native.mkdir(exist_ok=True)
    data = json.loads((output / 'manifest.json').read_text())
    command = write_build(native, [output / name for name in data['translation_units']])
    command[2] = '8'
    graph = native / 'build.ninja'
    graph.write_text(graph.read_text().replace(' -MD ', ' -ftime-trace -MD '))
    subprocess.run(command, cwd=native, check=True, capture_output=True)
    run = subprocess.run([native / 'program'], capture_output=True)
    assert (run.returncode, run.stdout, run.stderr) == (8, expected, b''), run


def owner_lifetime(output, work):
    # Compile the actual emitted owner. Count frees only for the two allocations under observation.
    unit = next(path for path in output.rglob('*.cpp') if 'struct owner_Point {' in path.read_text())
    probe = work / 'owner_lifetime.cpp'
    probe.write_text("""
#include <cassert>
#include <cstdlib>
#include <new>
#include <utility>
void* first;
void* second;
unsigned frees;
void* operator new(std::size_t size) {
    if (void* p = std::malloc(size)) return p;
    throw std::bad_alloc{};
}
void operator delete(void* p) noexcept {
    if (p && p == first) { ++frees; first = nullptr; }
    if (p && p == second) { ++frees; second = nullptr; }
    std::free(p);
}
int main() {
    using Owner = App::owner_Point;
    static_assert(sizeof(Owner) == sizeof(void*));
    Owner empty;
    Owner empty_copy = empty;
    assert(!empty && !empty_copy && empty.ptr == nullptr);
    try {
        Owner a = Owner::make();
        first = a.ptr;
        assert(a.ptr->count == 1);
        Owner b = a;
        Owner c = std::move(b);
        assert(!b.ptr && c.ptr == a.ptr && a.ptr->count == 2);
        a = c;
        assert(a.ptr->count == 2);
        a = Owner::make();
        second = a.ptr;
        assert(a.ptr->count == 1 && c.ptr->count == 1 && frees == 0);
        c = a;
        assert(frees == 1 && a.ptr->count == 2);
        throw 1;
    } catch (int) {}
    assert(frees == 2);
    auto last = Owner::make();
    first = last.ptr;
    last = Owner{};
    assert(!last && !first && frees == 3);
}
""")
    exe = work / 'owner_lifetime'
    root = Path(__file__).resolve().parents[2]
    subprocess.run(['clang++', '-std=c++23', '-stdlib=libc++', '-O0', '-I', root / 'runtime/generated/public',
                    '-include', unit.resolve(), probe, root / 'build/clang/libscpp_runtime.a', '-o', exe], check=True)
    subprocess.run([exe], check=True)


def prove(work, flavor):
    record, body = PROGRAMS[flavor]
    app = work / ('app-' + flavor)
    app.mkdir()
    owner = app / ('point.' + flavor)
    entry = app / ('main.' + flavor)
    (app / 'module.json').write_text(json.dumps({'entry': entry.name}))
    owner.write_text(record)
    entry.write_text(body)
    output = work / ('output-' + flavor)
    native = work / ('native-' + flavor)
    with service(app, output) as resident:
        edits = [('initial', None, '', None),
                 ('rename', owner, record.replace('x', 'width'), 'Unknown field Point.x'),
                 ('retry', None, '', 'Unknown field Point.x'),
                 ('repair', entry, body.replace('x', 'width'), None)]
        # Reject invalid layouts before lowering, including fields in an unused record.
        duplicate = PROGRAMS[flavor][0].replace('y', 'x')
        edits.extend([('duplicate-field', owner, duplicate, 'Duplicate struct field: x'),
                      ('invalid-type', owner, record.replace(' int ', ' float ', 1) if flavor != 'js'
                       else record.replace(': int', ': float', 1), 'Struct fields require int or int64'),
                      ('restore-layout', owner, record.replace('x', 'width'), None)])
        if flavor == 'php':
            edits.extend([('nominal-copy', entry, 'struct Other { public int $x; } $p Point; $q Other = $p; return 8;',
                           'conversion from Point to Other'),
                          ('allocation', entry, '$p = new Point(); echo $p->width,$p->y; $q shared<Point> = $p; '
                           '$q->width=3; $p=$p; echo $p->width,","; $p=new Point; $p->width=8; echo $p->width; '
                           'if ($q->width != 3) { return 1; } return 8;', None),
                          ('missing-payload', owner, '', 'Shared construction requires a struct'),
                          ('restore-payload', owner, record.replace('x', 'width'), None),
                          ('constructor-args', entry, '$p = new Point(1, 2); return 8;', 'zero-argument'),
                          ('recover-allocation', entry, body.replace('x', 'width'), None)])
        for revision, (label, path, text, error) in enumerate(edits):
            if path:
                path.write_text(text)
                os.utime(path, (1900000000 + revision,) * 2)
            before = artifacts(output)
            current = invoke(resident)
            baseline = work / f'fresh-{flavor}-{label}'
            with service(app, baseline) as fresh:
                other = invoke(fresh)
                fresh.stdin.close()
            if error:
                assert error in current.get('error', '') and current['error'] == other.get('error'), (label, current, other)
                assert artifacts(output) == before
                continue
            assert 'error' not in current and 'error' not in other, (label, current, other)
            assert artifacts(output) == artifacts(baseline)
            if label == 'allocation':
                owner_lifetime(output, work)
            build(output, native)
            build(baseline, work / f'build-{flavor}-{label}')
        resident.stdin.close()


def nested(work, flavor):
    def record(name, **fields):
        spell = {'php': 'public {t} ${n};', 'cpp': '{t} {n};', 'js': '{n}: {t};'}[flavor]
        return 'struct ' + name + ' { ' + ' '.join(spell.format(t=t, n=n) for n, t in fields.items()) + ' }'

    # Both branches share the Position layout. Fields and copies still hold independent values.
    scene = record('Scene', bounds='Bounds', marker='Marker')
    marker = record('Marker', at='Position')
    position = record('Position', mm='int')
    bounds = record('Bounds', low='Point', high='Point')
    point = record('Point', position='Position')
    decl, copy, local, member = {
        'php': ('$s Scene;', '$q = $s;', '$', '->'),
        'cpp': ('Scene s;', 'auto q=s;', '', '->'),
        'js': ('let s: Scene;', 'let q=s;', '', '.'),
    }[flavor]

    def path(name):
        return local + name.replace('.', member)

    low, high, qlow = [path(x) for x in ('s.bounds.low.position.mm', 's.bounds.high.position.mm', 'q.bounds.low.position.mm')]
    body = (decl + f'echo {low}, ","; {low}=120; ' + copy + f'{qlow}=8; '
            + path('s.bounds.high') + '=' + path('q.bounds.low') + '; '
            + path('s.marker.at') + '=' + path('s.bounds.low.position') + '; '
            + f'echo {low}, ",", {high}, ",", {qlow}, ",", ' + path('s.marker.at.mm') + '; return 8;')
    app = work / ('nested-app-' + flavor)
    app.mkdir()
    entry, owner, mid, inner, leaf = [app / f'{name}.{flavor}' for name in ('main', 'outer', 'middle', 'inner', 'leaf')]
    (app / 'module.json').write_text(json.dumps({'entry': entry.name}))
    for file, content in [(owner, scene), (mid, bounds + marker), (inner, point), (leaf, position), (entry, body)]:
        file.write_text(content)
    output = work / ('nested-output-' + flavor)
    good = position.replace('mm', 'width')
    edits = [
        ('initial', None, '', None), ('no-edit', None, '', None),
        ('leaf-rename', leaf, good, 'Unknown field Position.mm'),
        ('repair-reader', entry, body.replace('mm', 'width'), None),
        ('scalar-in-path', inner, record('Point', position='int'), 'Member reads are not supported'),
        ('restore-inner', inner, point, None),
        ('direct-cycle', mid, record('Bounds', low='Bounds'), 'Recursive inline layout: Bounds -> Bounds'),
        ('retry-cycle', None, '', 'Recursive inline layout: Bounds -> Bounds'),
        ('restore-mid', mid, bounds + marker, None),
        ('four-cycle', leaf, record('Position', width='Scene'), 'Recursive inline layout:'),
        ('recover', leaf, good, None),
        ('missing-type', inner, '', 'inline struct: Point'),
        ('re-add', inner, point, None),
        ('split-layouts', owner, scene + good, None),
        ('three-units', mid, bounds + marker + point, None),
        # Co-locate every declaration privately, intentionally in reverse dependency order.
        ('private-layouts', entry, scene + bounds + marker + point + position + body, None),
    ]
    with service(app, output) as resident:
        for rev, (label, file, content, error) in enumerate(edits):
            if label == 'split-layouts':
                leaf.unlink()  # Edges now run both ways between files, but the type graph still has no cycle.
            if label == 'three-units':
                inner.unlink()
            if label == 'private-layouts':
                for retired in (owner, mid):
                    retired.unlink()
            if file:
                file.write_text(content)
                os.utime(file, (1910000000 + rev,) * 2)
            before = artifacts(output)
            result = invoke(resident)
            baseline = work / f'nested-fresh-{flavor}-{label}'
            with service(app, baseline) as fresh:
                reference = invoke(fresh)
                fresh.stdin.close()
            if error:
                assert error in result.get('error', '') and result['error'] == reference.get('error'), (label, result, reference)
                assert artifacts(output) == before
                continue
            assert 'error' not in result and 'error' not in reference, (label, result, reference)
            assert artifacts(output) == artifacts(baseline), label
            if label == 'no-edit':
                assert result['preparation']['prepared'] == 0 and not result['emitted_sources']
            if label == 'private-layouts':
                assert not list(output.rglob('*.hpp'))
            build(output, work / f'nested-native-{flavor}', b'0,120,8,8,120')
            build(baseline, work / f'nested-build-{flavor}-{label}', b'0,120,8,8,120')
        resident.stdin.close()



def check_call_counts(output, work):
    # Replace the native entry adapter with a probe that can inspect the otherwise source-invisible count.
    units = [output / name for name in json.loads((output / 'manifest.json').read_text())['translation_units']]
    entry_body = next(path for path in units if 'int scpp_entry_body()\n{' in path.read_text())
    probe = work / 'call_counts.cpp'
    probe.write_text(f'#include "{entry_body.resolve()}"\n' + """
#include <cassert>
int main() {
    auto p = App::makePoint_();
    assert(p.ptr->count == 1);
    {
        auto q = App::identity_(p);
        assert(q.ptr == p.ptr && p.ptr->count == 2);
        App::write_(q);
        App::replaceLocal_(q);
        assert(p->x == 12 && p.ptr->count == 2);
    }
    assert(p.ptr->count == 1);
    auto separate = App::fromLocal_();
    assert(separate.ptr != p.ptr && separate.ptr->count == 1);
}
""")
    native = work / 'counts-native'
    native.mkdir()
    command = write_build(native, [probe, *(u for u in units if u != entry_body and u.name != 'entry.cpp')])
    subprocess.run(command, cwd=native, capture_output=True, check=True)
    subprocess.run([native / 'program'], check=True)


def native_names(work):
    app = work / 'spelling'
    app.mkdir()
    (app / 'module.json').write_text(json.dumps({'entry': 'main.php', 'cpp_namespace': 'company::app'}))
    (app / 'main.php').write_text(
        'struct point { public int $x; } struct Point { public point $point; } '
        'struct owner_point { public int $alignas; } '
        'struct Z_plane { public Point $scpp_records; public owner_point $owner_point; } '
        '$p=new point(); $p->x=3; $z Z_plane; $z->scpp_records->point->x=$p->x; '
        '$z->owner_point->alignas=5; echo $z->scpp_records->point->x+$z->owner_point->alignas; return 8;')
    out = work / 'spelling-output'
    with service(app, out) as proc:
        result = invoke(proc)
        proc.stdin.close()
    assert 'error' not in result, result
    text = '\n'.join(p.read_text() for p in out.rglob('*.cpp'))
    for spelling in ('point', 'Point', 'Z_plane', 'owner_point'):
        assert 'struct ' + spelling + ' {' in text, spelling
    assert ')->x' in text and ').point' in text and '.ptr->value.' not in text
    build(out, work / 'spelling-native', b'8')


def direct_construction(work):
    app = work / 'direct-new'
    app.mkdir()
    (app / 'module.json').write_text('{"entry":"main.php"}')
    (app / 'model.php').write_text('namespace Geo; struct Point { public int $x; }')
    # Root-qualified construction in three different syntaxes, followed by mutation from another flavor.
    factories = {
        'php': r'function fromPhp(): shared<Point> { $p = new \Geo\Point(); return $p; }',
        'cpp': 'shared<Point> fromCpp() { auto p = new ::Geo::Point(); return p; }',
        'js': r'function fromJs(): shared<Point> { let p = new \Geo.Point(); return p; }',
    }
    namespaces = {'php': 'namespace Geo; ', 'cpp': 'namespace Geo { ', 'js': 'namespace Geo { '}
    for flavor, factory in factories.items():
        end = '' if flavor == 'php' else '}'
        (app / ('factory.' + flavor)).write_text(namespaces[flavor] + factory + end)
    (app / 'touch.js').write_text('namespace Geo { function touch(p: shared<Point>): shared<Point> { p.x=9; return p; } }')
    (app / 'touch.cpp').write_text('namespace Geo { shared<Point> touchCpp(shared<Point> p) { p->x=9; return p; } }')
    source = 'namespace Geo; '
    for factory, touch in [('fromPhp', 'touchCpp'), ('fromCpp', 'touch'), ('fromJs', 'touchCpp')]:
        name = '$' + factory
        source += (f'{name}={factory}(); echo {name}->x; '
                   f'if ({touch}({name}) !== {name}) {{ return 1; }} echo {name}->x; ')
    (app / 'main.php').write_text(source + 'return 8;')
    out = work / 'new-output'
    edits = [(None, '', None)]
    for flavor in factories:
        path = app / ('factory.' + flavor)
        original = path.read_text()
        qualified = {'php': r'new \Geo\Point()', 'cpp': 'new ::Geo::Point()', 'js': r'new \Geo.Point()'}[flavor]
        short = original.replace(qualified, 'new Point()')
        inferred, typed = {'php': ('$p =', '$p shared<Point> ='), 'cpp': ('auto p =', 'shared<Point> p ='),
                           'js': ('let p =', 'let p: shared<Point> =')}[flavor]
        edits += [(path, short.replace(inferred, typed), None),
                  (path, original.replace('Point()', 'Point(1)'), 'zero-argument'),
                  (path, original.replace('Point()', 'Point'), None if flavor == 'php' else '( after the constructed type'),
                  (path, original.replace('Point()', 'Missing()'), 'Shared construction requires a struct'),
                  (path, original, None)]
    with service(app, out) as resident:
        for rev, (path, text, error) in enumerate(edits):
            if path:
                path.write_text(text)
                os.utime(path, (1970000000 + rev,) * 2)
            before = artifacts(out)
            observed = invoke(resident)
            baseline = work / f'new-fresh-{rev}'
            with service(app, baseline) as fresh:
                expected = invoke(fresh)
                fresh.stdin.close()
            if error:
                assert error in observed.get('error', '') and observed['error'] == expected.get('error'), (rev, observed, expected)
                assert artifacts(out) == before
                continue
            assert 'error' not in observed and 'error' not in expected, (rev, observed, expected)
            assert artifacts(out) == artifacts(baseline)
            build(out, work / 'new-native', b'090909')
            build(baseline, work / f'new-build-{rev}', b'090909')
        resident.stdin.close()


def shared_functions(work):
    app = work / 'shared-functions'
    app.mkdir()
    (app / 'module.json').write_text('{"entry":"main.php"}')
    record, api, main = [app / name for name in ('point.php', 'factory.php', 'main.php')]
    layout = 'struct Point { public int $x; }'
    record.write_text(layout)
    factory = ('function makePoint(): shared<Point> { return new Point(); } '
               'function fromLocal(): shared<Point> { $p = new Point(); $p->x=5; return $p; } '
               'function replaceLocal(shared<Point> $p): void { $p=new Point(); $p->x=90; }')
    api.write_text(factory)
    (app / 'identity.cpp').write_text('shared<Point> identity(shared<Point> p) { return p; }')
    (app / 'write.js').write_text('function write(p: shared<Point>): void { p.x=12; }')
    source = ('$p=makePoint(); $q=identity($p); write($q); replaceLocal($q); '
              'echo $p->x,",",$q->x,",",fromLocal()->x; return 8;')
    main.write_text(source)
    output = work / 'shared-output'
    with service(app, output) as resident:
        for rev, (path, content, error) in enumerate([
            (main, source, None), (record, layout.replace('int $x;', 'int $y; int $x;'), None),
            (record, '', 'Shared type requires one struct payload'),
            (record, layout, None),
            (api, factory.replace('shared<Point> $p', 'shared<Point> &$p'), None),
            (api, factory, None),
            (main, source.replace('identity($p)', 'identity(5)'), 'conversion from int64 to shared<Point>'),
            (main, source, None),
            (record, layout.replace('int $x;', 'int $y;'), 'Unknown field Point.x'),
            (record, layout, None),
        ]):
            if rev >= 8:
                # Replace all nominal signatures in the same publication batch as their payload.
                for sig in (api, app / 'identity.cpp', app / 'write.js'):
                    os.utime(sig, (1920000000 + rev,) * 2)
            path.write_text(content)
            os.utime(path, (1920000000 + rev,) * 2)
            old = artifacts(output)
            observed = invoke(resident)
            baseline = work / f'shared-fresh-{rev}'
            with service(app, baseline) as fresh:
                expected = invoke(fresh)
                fresh.stdin.close()
            if error:
                assert error in observed.get('error', '') and expected.get('error') == observed['error'], (rev, observed, expected)
                assert old == artifacts(output)
                continue
            assert 'error' not in observed and 'error' not in expected, (rev, observed, expected)
            assert artifacts(output) == artifacts(baseline), rev
            if rev == 0:
                check_call_counts(baseline, work)
            stdout = b'12,90,5' if rev == 4 else b'12,12,5'
            build(output, work / 'shared-native', stdout)
            build(baseline, work / f'shared-build-{rev}', stdout)
        resident.stdin.close()


def empty_owners(work, flavor):
    app = work / ('empty-' + flavor)
    app.mkdir()
    entry = app / ('main.' + flavor)
    (app / 'module.json').write_text(json.dumps({'entry': entry.name}))
    model = app / 'model.php'
    definitions = ('struct Point { public int $x; } '
                   'function makePoint(): shared<Point> { return new Point(); } '
                   'function absent(): null { echo "N"; return null; } '
                   'function makeObserved(): shared<Point> { echo "P"; return new Point(); }')
    model.write_text(definitions)
    p, q, decl, copy, arrow = {
        'php': ('$p', '$q', '$p shared<Point>;', '$q=$p;', '->'),
        'cpp': ('p', 'q', 'shared<Point> p;', 'auto q=p;', '->'),
        'js': ('p', 'q', 'let p: shared<Point>;', 'let q=p;', '.'),
    }[flavor]
    ops = ['==', '===', '!=', '!==']

    def compare():
        return ''.join(f'if ({lhs} {op} {rhs}) {{ echo "1"; }} else {{ echo "0"; }} '
                       for op in ops for lhs, rhs in [(p, 'null'), ('null', p)])

    source = (decl + copy + f'if (!{q}) {{ echo "E"; }} ' + compare()
              + f'{p}=makePoint(); if ({p}) {{ echo "P"; }} ' + compare()
              + f'{q}={p}; {q}{arrow}x=7; {p}=null; echo {q}{arrow}x; '
              + f'{q}=absent(); if (makeObserved() != absent()) {{ echo "1"; }} '
              + f'if (absent() === makeObserved()) {{ return 1; }} if ({q}) {{ return 1; }} return 8;')
    out = work / ('empty-output-' + flavor)
    transitions = [(entry, source, None)]
    transitions += [(entry, source, None), (model, definitions.replace('int $x', 'int $y'), 'Unknown field Point.x'),
                    (model, definitions, None), (entry, decl + f'echo {p}{arrow}x; return 8;', 'throw'),
                    (entry, decl + f'{p}{arrow}x=7; return 8;', 'throw'), (entry, source, None)]
    with service(app, out) as resident:
        for rev, (path, content, error) in enumerate(transitions):
            path.write_text(content)
            os.utime(path, (1930000000 + rev,) * 2)
            old = artifacts(out)
            result = invoke(resident)
            baseline = work / f'empty-fresh-{flavor}-{rev}'
            with service(app, baseline) as fresh:
                expected = invoke(fresh)
                fresh.stdin.close()
            if error and error != 'throw':
                assert error in result.get('error', '') and result['error'] == expected.get('error'), (rev, result, expected)
                assert artifacts(out) == old
                continue
            assert 'error' not in result and 'error' not in expected, (rev, result, expected)
            assert artifacts(out) == artifacts(baseline), rev
            if error == 'throw':
                check_empty_access(out, work / f'checked-{flavor}-{rev}')
            else:
                build(out, work / ('empty-native-' + flavor), b'E11110000P000011117NPN1NP')
        resident.stdin.close()


def check_empty_access(output, work):
    work.mkdir()
    harness = work / 'catch.cpp'
    harness.write_text("""
#include <stdexcept>
#include <cstring>
namespace App { int scpp_entry_body(); }
int main() {
    try { App::scpp_entry_body(); }
    catch (const std::runtime_error& e) { return std::strcmp(e.what(), "ownership_empty_access") != 0; }
    return 1;
}
""")
    units = [output / f for f in json.loads((output / 'manifest.json').read_text())['translation_units'] if f != 'entry.cpp']
    subprocess.run(write_build(work, [harness, *units]), cwd=work, capture_output=True, check=True)
    run = subprocess.run([work / 'program'], capture_output=True, check=True)
    assert run.stdout == b'', run.stdout


def shared_fields(work, flavor):
    app = work / ('fields-' + flavor)
    app.mkdir()
    entry, segment, point, node = [app / (name + '.' + flavor) for name in ('main', 'segment', 'point', 'node')]
    (app / 'module.json').write_text(json.dumps({'entry': entry.name}))
    (app / 'factory.php').write_text('function makePoint(): shared<Point> { return new Point(); } '
                                     'function makeNode(): shared<Node> { return new Node(); }')

    def record(name, **fields):
        syntax = {'php': 'public {t} ${n};', 'cpp': '{t} {n};', 'js': '{n}: {t};'}[flavor]
        return 'struct ' + name + ' { ' + ' '.join(syntax.format(t=t, n=n) for n, t in fields.items()) + ' }'

    p = record('Point', x='int')
    s = record('Segment', start='shared<Point>', end='shared<Point>')
    n = record('Node', value='int', next='shared<Node>')
    declare, copy, mark, sep = {
        'php': ('$s Segment;', '$t=$s;', '$', '->'),
        'cpp': ('Segment s;', 'auto t=s;', '', '->'),
        'js': ('let s: Segment;', 'let t=s;', '', '.'),
    }[flavor]
    body = (declare + 'if (S.start === null && S.end === null) { echo "E"; } '
            'S.start=makePoint(); S.end=S.start; ' + copy + 'T.start.x=7; T.start=null; '
            'echo S.end.x; S.start=null; S.end=null; echo T.end.x; T.end=null; ')
    body = body.replace('S.', mark+'s.').replace('T.', mark+'t.').replace('.', sep)
    root = {'php': '$root=makeNode();', 'cpp': 'auto root=makeNode();', 'js': 'let root=makeNode();'}[flavor]
    chain = ('R.next=makeNode(); R.next.value=8; R.next.next=makeNode(); R.next.next.value=9; '
             'echo R.next.value,R.next.next.value; R=R.next; echo R.next.value; R.next=null; '
             'if (!R.next) { echo "E"; } return 8;').replace('R',mark+'root').replace('.',sep)
    source = body + root + chain
    for path, text in [(entry, source), (segment, s), (point, p), (node, n)]:
        path.write_text(text)
    out = work / ('fields-output-' + flavor)
    revisions = [(None, '', None), (point, record('Point', y='int'), 'Unknown field Point.x'),
                 (None, '', 'Unknown field Point.x'), (point, p, None),
                 (node, record('Node', value='int', next='Node'), 'Recursive inline layout: Node -> Node'),
                 (node, n, None), (segment, s.replace('shared<Point>', 'shared<Node>', 1), 'conversion from shared<Point>'),
                 (segment, s, None), (point, '', 'Shared type requires one struct payload'), (point, p, None)]
    with service(app, out) as resident:
        for rev, (path, text, error) in enumerate(revisions):
            if path:
                path.write_text(text)
                os.utime(path, (1940000000 + rev,) * 2)
            old = artifacts(out)
            result = invoke(resident)
            fresh_out = work / f'fields-fresh-{flavor}-{rev}'
            with service(app, fresh_out) as fresh:
                expected = invoke(fresh)
                fresh.stdin.close()
            if error:
                assert error in result.get('error', '') and result['error'] == expected.get('error'), (rev, flavor, result, expected)
                assert old == artifacts(out)
                continue
            assert 'error' not in result and 'error' not in expected, (rev, flavor, result, expected)
            assert artifacts(out) == artifacts(fresh_out), rev
            build(out, work / ('fields-native-' + flavor), b'E77899E')
            build(fresh_out, work / f'fields-native-{flavor}-{rev}', b'E77899E')
            if rev == 0:
                field_lifetime(fresh_out, work / ('fields-count-' + flavor))
        # Mixed cycle: Segment embeds a Node, and Node can own a Segment. The inline graph remains finite.
        segment.write_text(record('Segment', start='shared<Point>', end='shared<Point>', head='Node'))
        node.write_text(record('Node', parent='shared<Segment>', value='int', next='shared<Node>'))
        # The old destination is the last owner of the field being copied from.
        factory = app / 'factory.php'
        factory.write_text(factory.read_text() + ' function makeSegment(): shared<Segment> { return new Segment(); }')
        overlap = 'S.head.parent=makeSegment(); S.head.parent.head.value=8; S.head=S.head.parent.head; echo S.head.value;'
        entry.write_text(source.replace('return 8;', overlap.replace('S.', mark+'s.').replace('.', sep) + 'return 8;'))
        result = invoke(resident)
        fresh_out = work / ('fields-mixed-cycle-' + flavor)
        with service(app, fresh_out) as fresh:
            expected = invoke(fresh)
            fresh.stdin.close()
        assert 'error' not in result and 'error' not in expected and artifacts(out) == artifacts(fresh_out)
        build(out, work / ('fields-native-' + flavor), b'E77899E8')
        field_lifetime(out, work / ('mixed-count-' + flavor))
        # Put all layouts in a single private unit, including the mixed cycle, and remove every external user.
        entry.write_text(segment.read_text() + point.read_text() + node.read_text() + declare
                         + f'if (!{mark}s{sep}head{sep}parent) {{ return 8; }} return 1;')
        for path in (segment, point, node, app / 'factory.php'):
            path.unlink()
        result = invoke(resident)
        fresh_out = work / ('fields-private-' + flavor)
        with service(app, fresh_out) as fresh:
            expected = invoke(fresh)
            fresh.stdin.close()
        assert 'error' not in result and 'error' not in expected and artifacts(out) == artifacts(fresh_out)
        assert not list(out.rglob('*.hpp'))
        build(out, work / ('fields-native-' + flavor), b'')
        resident.stdin.close()


def field_lifetime(output, work):
    # Consume the published headers as a native client and count only the observed allocations.
    work.mkdir()
    headers = []
    for name in ('Node', 'Segment'):
        headers.append(hashlib.sha256(('struct\0class:' + name).encode()).hexdigest() + '.hpp')
    probe = work / 'counts.cpp'
    probe.write_text(''.join(f'#include "{(output / "units" / h).resolve()}"\n' for h in headers) + """
#include <cassert>
#include <cstdlib>
#include <new>
void* watched[3]{};
int frees{};
void* operator new(std::size_t size) {
    if (void* p = std::malloc(size)) return p;
    throw std::bad_alloc{};
}
void operator delete(void* p) noexcept {
    for (auto& w : watched) if (p && w == p) { w = nullptr; ++frees; }
    std::free(p);
}
int main() {
    using namespace App;
    using namespace scpp_records;
    static_assert(sizeof(decltype(Segment::start)) == sizeof(void*));
    Segment a;
    assert(!a.start && !a.end);
    a.start = owner_Point::make();
    watched[0] = a.start.ptr;
    a.end = a.start;
    {
        Segment b = a;
        assert(b.start.ptr->count == 4);
        b.start = {};
        assert(b.end.ptr->count == 3 && a.start);
    }
    a.start = {};
    assert(a.end.ptr->count == 1 && frees == 0);
    a.end = {};
    assert(frees == 1);
    {
        auto parent = owner_Node::make();
        watched[1] = parent.ptr;
        parent->next = owner_Node::make();
        watched[2] = parent->next.ptr;
    }
    assert(frees == 3);
}
""")
    root = Path(__file__).resolve().parents[2]
    exe = work / 'counts'
    subprocess.run(['clang++', '-std=c++23', '-stdlib=libc++', '-O0', '-I', root/'runtime/generated/public',
                    probe, root/'build/clang/libscpp_runtime.a', '-o', exe], check=True)
    subprocess.run([exe], check=True)


if __name__ == '__main__':
    import argparse
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory() as temp:
        folder = args.output or Path(temp)
        folder.mkdir(exist_ok=True)
        direct_construction(folder)
        native_names(folder)
        shared_functions(folder)
        for language in PROGRAMS:
            shared_fields(folder, language)
            empty_owners(folder, language)
            prove(folder, language)
            nested(folder, language)
            print(language, 'passed')

"""Compare cross-unit static property writes and edits and recovery with fresh native builds."""
import argparse
import json
import os
import subprocess
from pathlib import Path
import tempfile

from app_namespace_native import artifacts, invoke, service, write_build
from structs_native import build, check_empty_access


def prove(work, flavor):
    app = work / flavor
    app.mkdir()
    entry = app / ('main.' + flavor)
    model = app / ('model.' + flavor)
    (app / 'module.json').write_text(json.dumps({'entry': entry.name}))
    prop, reader, writer, source = {
        'php': ('public static int $count = 2;', 'public function readCount():int { return Point::$count; }',
                'function bump():void { Point::$count = Point::$count+1; }',
                '$a Point; $b=new Point(); echo $a->readCount(); bump(); echo $b->readCount();'),
        'cpp': ('static int count = 2;', 'int readCount() { return Point::count; }',
                'void bump() { Point::count = Point::count+1; }',
                'Point a; auto b=new Point(); echo a->readCount(); bump(); echo b->readCount();'),
        'js': ('static count:int = 2;', 'readCount():int { return Point.count; }',
               'function bump():void { Point.count = Point.count+1; }',
               'let a:Point; let b=new Point(); echo a.readCount(); bump(); echo b.readCount();'),
    }[flavor]
    # A visible JS parameter selects an instance field even when its name also names a type.
    if flavor == 'js':
        source = ('struct Local { count:int; } function check(Point:shared<Local>):int { '
                  'Point.count=42; return Point.count; } if (check(new Local()) != 42) { return 1; } ') + source
    entry.write_text(source + 'return 8;')
    (app / ('writer.' + flavor)).write_text(writer)
    out = work / ('out-' + flavor)
    with service(app, out) as resident:
        for rev, (member, error, value) in enumerate([
            (prop, None, b'23'), (prop.replace('2', '5'), None, b'56'), ('', 'count', None),
            (prop + prop, 'Duplicate', None), (prop.replace('int', 'bool').replace('2', 'true'), 'Unsupported binary', None),
            (prop, None, b'23'),
        ]):
            model.write_text('struct Point { ' + member + reader + ' }')
            os.utime(model, (1981000000 + rev,) * 2)
            previous = artifacts(out)
            current = invoke(resident)
            baseline = work / f'fresh-{flavor}-{rev}'
            with service(app, baseline) as fresh:
                expected = invoke(fresh)
                fresh.stdin.close()
            if error:
                assert error in current.get('error', '') and current['error'] == expected['error'], (current, expected)
                assert artifacts(out) == previous
            else:
                assert 'error' not in current and 'error' not in expected, (current, expected)
                assert artifacts(out) == artifacts(baseline)
                build(out, work / ('native-' + flavor), value)
                build(baseline, work / f'fresh-native-{flavor}-{rev}', value)
        resident.stdin.close()


def compounds(work, flavor):
    app = work / ('compound-' + flavor)
    app.mkdir()
    entry = app / ('main.' + flavor)
    (app / 'module.json').write_text(json.dumps({'entry': entry.name}))
    prop, prop_ref, lexical, method = {
        'php': ('public static byte $n=250;', 'Gauge::$n', 'self::$n', 'public static function scale():void'),
        'cpp': ('static byte n=250;', 'Gauge::n', 'Self::n', 'static void scale()'),
        'js': ('static n:byte=250;', 'Gauge.n', 'class.n', 'static scale():void'),
    }[flavor]
    model = app / ('gauge.' + flavor)
    record = 'struct Gauge { '+prop+method+' { '+lexical+'-=5; '+lexical+'*=2; '+lexical+'/=2; '+lexical+'%=9; } }'
    # The cross-unit reader is also invoked by native code after an exception.
    (app / 'reader.php').write_text('function observe():int { return (int)Gauge::$n; }')
    call = 'Gauge.scale();' if flavor == 'js' else 'Gauge::scale();'
    def source(delta):
        return (prop_ref+'+='+str(delta)+'; echo observe(); '+prop_ref+'-=245; '+call+' echo observe(); return 8;')
    entry.write_text(source(5))
    floating = {'php': 'public static float $f=8.0;', 'cpp': 'static float f=8.0;', 'js': 'static f:float=8.0;'}[flavor]
    record = record.replace(prop, prop+floating)
    float_ref = prop_ref[:-1]+'f'
    # mutate() overwrites f while evaluating the RHS. The compound's original left operand is 8.0.
    (app / 'effect.php').write_text('function mutate():int { Gauge::$f=100.0; return 2; }')
    effect = float_ref+'+=mutate(); '+float_ref+'*=2; '+float_ref+'/=4; '+float_ref+'-=1; echo (int)'+float_ref+'; return 8;'
    out = work / ('compound-out-' + flavor)
    with service(app, out) as resident:
        for rev, (path, text, expected) in enumerate([
            (model, record, b'2555'), (entry, source(6), None),
            (model, record.replace('byte', 'int'), b'2566'), (model, record, None), (entry, source(5), b'2555'),
            (entry, effect, b'4'), (entry, float_ref+'%=2; return 8;', 'Unsupported compound'), (entry, effect, b'4'),
        ]):
            path.write_text(text)
            os.utime(path, (1982000000 + rev,) * 2)
            before = artifacts(out)
            current = invoke(resident)
            baseline = work / f'compound-fresh-{flavor}-{rev}'
            with service(app, baseline) as fresh:
                other = invoke(fresh)
                fresh.stdin.close()
            if isinstance(expected, str):
                assert expected in current.get('error', '') and current['error'] == other['error'], (current, other)
                assert artifacts(out) == before
                continue
            assert 'error' not in current and 'error' not in other, (current, other)
            assert artifacts(out) == artifacts(baseline)
            for bundle in [out, baseline]:
                native = work / ('build-' + bundle.name)
                if expected is not None:
                    build(bundle, native, expected)
                    continue
                native = work / f'catch-{bundle.name}-{rev}'
                native.mkdir()
                harness = native / 'catch.cpp'
                harness.write_text("""
#include <scpp/core/scalars.hpp>
#include <stdexcept>
#include <cstring>
namespace App { int scpp_entry_body(); scpp::int64 observe_(); }
int main() {
    try { App::scpp_entry_body(); }
    catch (const std::out_of_range& e) {
        return std::strcmp(e.what(), "integer_cast_out_of_range") || App::observe_() != 250;
    }
    return 1;
}
""")
                units = json.loads((bundle / 'manifest.json').read_text())['translation_units']
                cmd = write_build(native, [harness, *(bundle / unit for unit in units if unit != 'entry.cpp')])
                subprocess.run(cmd, cwd=native, check=True, capture_output=True)
                run = subprocess.run([native / 'program'], check=True, capture_output=True)
                assert run.stdout == b''
        resident.stdin.close()


def instance_compounds(work, flavor):
    app = work / ('fields-' + flavor)
    app.mkdir()
    entry = app / ('main.' + flavor)
    model = app / 'model.php'
    (app / 'module.json').write_text(json.dumps({'entry': entry.name}))
    definitions = ('struct Point { public int $x; } struct Box { public Point $p; } '
                   'function replaceBox(shared<Box> &$b):int { $b->p->x=90; $b=new Box(); echo "R"; return 3; } '
                   'function clearBox(shared<Box> &$b,weak<Box> $w):int { $b=null; '
                   'if (lock($w)) { echo "L"; } return 2; } '
                   'function side():int { echo "BAD"; return 1; }')
    decl, access, shared, empty, inline = {
        'php': ('$', '->', '$b=new Box(); $q=$b;', '$b shared<Box>;', '$v Point;'),
        'cpp': ('', '->', 'auto b=new Box(); auto q=b;', 'shared<Box> b;', 'Point v;'),
        'js': ('', '.', 'let b=new Box(); let q=b;', 'let b:shared<Box>;', 'let v:Point;'),
    }[flavor]
    b, q, v = [decl+n for n in ['b','q','v']]
    field = access+'p'+access+'x'
    x = v+access+'x'
    source = (shared+b+field+'+=1; '+b+field+'+=replaceBox('+b+'); echo '+q+field+','+b+field+'; '
              +b+field+'+=clearBox('+b+',weak('+b+')); '+inline
              +x+'+=9; '+x+'-=1; '+x+'*=3; '+x+'/=4; '+x+'%=5; echo '+x+'; return 8;')
    out = work / ('fields-out-' + flavor)
    model.write_text(definitions)
    entry.write_text(source)
    with service(app, out) as resident:
        for rev, (path, text, error, expected) in enumerate([
            (entry, source, None, b'R40L1'), (model, definitions.replace('public int $x', 'private int $x'), 'private field', None),
            (model, definitions, None, b'R40L1'), (entry, empty+b+field+'+=side(); return 8;', None, None),
            (entry, source, None, b'R40L1'),
        ]):
            path.write_text(text)
            os.utime(path, (1983000000+rev,)*2)
            previous = artifacts(out)
            current = invoke(resident)
            baseline = work / f'fields-fresh-{flavor}-{rev}'
            with service(app, baseline) as fresh:
                other = invoke(fresh)
                fresh.stdin.close()
            if error:
                assert error in current.get('error', '') and other['error'] == current['error'], (current, other)
                assert artifacts(out) == previous
                continue
            assert 'error' not in current and 'error' not in other, (current, other)
            assert artifacts(out) == artifacts(baseline)
            for bundle in (out, baseline):
                native = work / ('native-'+bundle.name)
                if expected is None:
                    check_empty_access(bundle, work / f'empty-{bundle.name}-{rev}')
                else:
                    code = b'\n'.join(artifacts(bundle).values())
                    assert any(b'.x += 1LL;' in line and b'->p' in line and b'compound_' not in line and b'[&]' not in line
                               for line in code.splitlines())
                    assert b'auto& compound_target = receiver_owner->p.x;' in code
                    build(bundle, native, expected)
        resident.stdin.close()


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory() as tmp:
        work = (args.output or Path(tmp)).resolve()
        work.mkdir(exist_ok=True)
        for flavor in ('php', 'cpp', 'js'):
            prove(work, flavor)
            compounds(work, flavor)
            instance_compounds(work, flavor)
            print(flavor, 'passed', flush=True)

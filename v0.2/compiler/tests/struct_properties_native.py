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
    bit_source = ''.join(prop_ref + op + ';' for op in ('&=15', '|=16', '^=3', '<<=1', '>>=2')) + 'echo observe(); return 8;'
    out = work / ('compound-out-' + flavor)
    narrowing = ('out_of_range', 'integer_cast_out_of_range')
    with service(app, out) as resident:
        for rev, (path, text, expected) in enumerate([
            (model, record, b'2555'), (entry, source(6), narrowing),
            (model, record.replace('byte', 'int'), b'2566'), (model, record, narrowing), (entry, source(5), b'2555'),
            (entry, effect, b'4'), (entry, float_ref+'%=2; return 8;', 'Unsupported compound'), (entry, effect, b'4'),
            (entry, bit_source, b'12'), (entry, prop_ref+'|=256; return 8;', narrowing),
            (entry, prop_ref+'<<=8; return 8;', ('domain_error','shift_count_out_of_range')),
            (entry, bit_source, b'12'),
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
                if isinstance(expected, bytes):
                    build(bundle, native, expected)
                    continue
                native = work / f'catch-{bundle.name}-{rev}'
                native.mkdir()
                harness = native / 'catch.cpp'
                exception, code = expected
                harness.write_text("""
#include <scpp/core/scalars.hpp>
#include <stdexcept>
#include <cstring>
namespace App { int scpp_entry_body(); scpp::int64 observe_(); }
int main() {
    try { App::scpp_entry_body(); }
    catch (const std::EXCEPTION& e) {
        return std::strcmp(e.what(), "CODE") || App::observe_() != 250;
    }
    return 1;
}
""".replace("EXCEPTION", exception).replace("CODE", code))
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
    bit_source = (shared + b+field+'=5; '+b+field+'<<=replaceBox('+b+'); echo '+q+field+',",",'+b+field+'; '
                  + q+field+'&=15; '+q+field+'|=17; '+q+field+'^=5; '+q+field+'>>=1; echo '+q+field+'; return 8;')
    out = work / ('fields-out-' + flavor)
    model.write_text(definitions)
    entry.write_text(source)
    with service(app, out) as resident:
        for rev, (path, text, error, expected) in enumerate([
            (entry, source, None, b'R40L1'), (model, definitions.replace('public int $x', 'private int $x'), 'private field', None),
            (model, definitions, None, b'R40L1'), (entry, empty+b+field+'+=side(); return 8;', None, None),
            (entry, source, None, b'R40L1'),
            (entry, bit_source, None, b'R40,014'), (model, definitions.replace('public int $x', 'private int $x'), 'private field', None),
            (model, definitions, None, b'R40,014'), (entry, bit_source.replace('>>=1', '>>=true'), 'Unsupported compound', None),
            (entry, bit_source, None, b'R40,014'),
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
                    if expected == b'R40L1':
                        assert any(b'.x += 1LL;' in line and b'->p' in line and b'compound_' not in line and b'[&]' not in line
                                   for line in code.splitlines())
                    assert b'auto& compound_target = receiver_owner->p.x;' in code
                    build(bundle, native, expected)
        resident.stdin.close()


def updates(work, flavor):
    app = work / ('updates-' + flavor)
    app.mkdir()
    entry, model = [app / (name + '.' + flavor) for name in ('main', 'model')]
    (app / 'module.json').write_text(json.dumps({'entry': entry.name}))
    field, prop, sample, step, lexical, access, scope, sigil, setup, empty = {
        'php': ('public int $x;', 'public static int $n=4;', 'public static byte $b=255; public static float $f=2.5;',
                'public function step():void', 'self::', '->', '::', '$', '$q=new Box();', '$q shared<Box>;'),
        'cpp': ('public: int x;', 'public: static int n=4;', 'static byte b=255; static float f=2.5;',
                'public: void step()', 'Self::', '->', '::', '', 'auto q=new Box();', 'shared<Box> q;'),
        'js': ('public x:int;', 'public static n:int=4;', 'static b:byte=255; static f:float=2.5;',
               'public step():void', 'class.', '.', '.', '', 'let q=new Box();', 'let q:shared<Box>;'),
    }[flavor]
    (app / 'box.php').write_text('struct Box { public Point $p; }')
    n = 'Point' + scope + sigil + 'n'
    b = 'Point' + scope + sigil + 'b'
    f = 'Point' + scope + sigil + 'f'
    x = sigil+'q'+access+'p'+access+'x'
    this_x = sigil+'this'+access+'x'
    definition = ('struct Point { '+field+prop+sample+step+' { ++'+this_x+'; --'+lexical+sigil+'f; } }')
    program = (setup+x+'++; '+sigil+'q'+access+'p'+access+'step(); echo '+x+'; --'+x+'; echo '+x+'; '
               +n+'++; --'+n+'; echo '+n+'; ++'+b+'; echo (int)'+b+'; '+b+'--; echo (int)'+b+'; '
               +'++'+f+'; if ('+f+'==2.5) { echo "F"; } return 8;')
    i = sigil+'i'
    init = {'php':'$i=0;', 'cpp':'int i=0;', 'js':'let i:int=0;'}[flavor]
    def loop(update, read):
        return ('for ('+init+i+'<4; '+update+') { echo '+read+'; '+i+'++; '
                'if ('+i+'==2) { continue; } if ('+i+'==3) { break; } } echo '+read+';')
    loops = setup + loop(x+'++', x) + loop('--'+n, '(int)'+n) + 'return 8;'
    loop_output = b'01224322'
    good = b'2140255F'
    model.write_text(definition)
    out = work / ('updates-out-'+flavor)
    with service(app, out) as resident:
        for rev,(path,text,error,expected) in enumerate([
            (entry,program,None,good),
            (model,definition.replace(prop,prop.replace('int','bool').replace('4','true')), 'Unsupported update type',None),
            (model,definition,None,good),
            (model,definition.replace(field,field.replace('public','private')), 'private field',None),
            (model,definition,None,good), (entry,empty+x+'++; return 8;',None,None),
            (entry,setup+'echo '+x+'++; return 8;', 'Expected',None), (entry,program,None,good),
            (entry,loops,None,loop_output),
            (model,definition.replace(prop,prop.replace('int','bool').replace('4','true')), 'Unsupported update type',None),
            (model,definition,None,loop_output),
        ]):
            path.write_text(text)
            os.utime(path,(1984000000+rev,)*2)
            before = artifacts(out)
            current = invoke(resident)
            baseline = work / f'updates-fresh-{flavor}-{rev}'
            with service(app, baseline) as fresh:
                other = invoke(fresh)
                fresh.stdin.close()
            if error:
                assert error in current.get('error','') and current['error']==other['error'], (rev,current,other)
                assert before == artifacts(out)
                continue
            assert 'error' not in current and 'error' not in other, (rev,current,other)
            assert artifacts(out)==artifacts(baseline)
            for bundle in (out,baseline):
                if expected is None:
                    check_empty_access(bundle,work / f'empty-{bundle.name}-{rev}')
                else:
                    spelling = b').x++)' if expected == loop_output else b').x++;'
                    assert any(spelling in line and b'[&]' not in line for unit in artifacts(bundle).values()
                               for line in unit.splitlines())
                    build(bundle,work / ('build-'+bundle.name),expected)
        resident.stdin.close()



def string_properties(work, flavor):
    """One reader, writer and record across units; compare every edit with a fresh compiler."""
    app = work / ('strings-' + flavor)
    app.mkdir()
    entry = app / ('main.' + flavor)
    model = app / ('model.' + flavor)
    (app / 'module.json').write_text(json.dumps({'entry': entry.name}))
    prop, common, ref, decl, copy, field, cat = {
        'php': ('public string $text', 'public static string $sharedText', 'Label::$sharedText',
                '$a Label;', '$b=$a;', '$V->text', '.='),
        'cpp': ('string text', 'static string sharedText', 'Label::sharedText',
                'Label a;', 'auto b=a;', 'V->text', '+='),
        'js': ('text:string', 'static sharedText:string', 'Label.sharedText',
               'let a:Label;', 'let b=a;', 'V.text', '+='),
    }[flavor]
    a, b = (field.replace('V', v) for v in ('a', 'b'))
    # This extra unit only writes the static member; the entry reads its initial and updated values.
    (app / 'writer.php').write_text('function publish():void { Label::$sharedText="published"; }')
    # All fixtures also consume a PHP-authored record, exercising typed scalar and empty-owner defaults.
    (app / 'defaults.php').write_text('struct Defaults { public bool $active=true; public byte $code=65; '
        'public int $delta=-2; public float $ratio=1.5; public shared<Label> $link=null; } '
        'function defaultsOK():bool { $d Defaults; return $d->active && $d->code==65 && $d->delta==-2 '
        '&& $d->ratio==1.5 && $d->link==null; }')
    entry.write_text('if (!defaultsOK()) { return 1; }' + decl + a + cat + a + ';' + copy + b + '[0]=65;' + b + cat + '"!";'
                     + 'echo ' + a + ',"/",' + b + ',"/",' + ref + '; publish(); echo ' + ref + ';return 8;')
    out = work / ('string-prop-out-' + flavor)
    with service(app, out) as resident:
        for rev, (literal, error) in enumerate([('"ready"', None), ('"other"', None),
                                                ('123', 'conversion'), ('"ready"', None)]):
            model.write_text('struct Label { ' + prop + '=' + literal + ';' + common + '="start"; }')
            os.utime(model, (1989100000 + rev,) * 2)
            before = artifacts(out)
            result = invoke(resident)
            baseline = work / f'string-prop-fresh-{flavor}-{rev}'
            with service(app, baseline) as fresh:
                other = invoke(fresh)
                fresh.stdin.close()
            if error:
                assert error in result.get('error', '') and result['error'] == other.get('error'), (result, other)
                assert artifacts(out) == before
                continue
            assert 'error' not in result and 'error' not in other, (result, other)
            assert artifacts(out) == artifacts(baseline)
            text = literal.strip('"').encode() * 2
            expected = text + b'/A' + text[1:] + b'!/startpublished'
            build(out, work / ('string-prop-native-' + flavor), expected)
            build(baseline, work / f'string-prop-fresh-native-{flavor}-{rev}', expected)
        resident.stdin.close()


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory() as tmp:
        work = (args.output or Path(tmp)).resolve()
        work.mkdir(exist_ok=True)
        for flavor in ('php', 'cpp', 'js'):
            string_properties(work, flavor)
            prove(work, flavor)
            compounds(work, flavor)
            instance_compounds(work, flavor)
            updates(work, flavor)
            print(flavor, 'passed', flush=True)

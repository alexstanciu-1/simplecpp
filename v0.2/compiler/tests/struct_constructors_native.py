"""Constructor contracts, inline/shared cleanup, and cross-unit recovery."""
import argparse
import json
import os
from pathlib import Path
import subprocess
import tempfile

from app_namespace_native import artifacts, invoke, service, write_build
from structs_native import build


def fixture(flavor):
    # Passing a shared token into a throwing constructor lets the native probe inspect unwind cleanup.
    sources = {
        'php': ('struct Point { public int $x; public int $y; public shared<Token> $hold; '
                'public function __construct(int $x, int $y) { $this->x=$x; $this->y=$y; } }',
                '$p Point(step($i),step($i)); $q=new Point(3,true); echo $p->x,$p->y,$q->x,$q->y;',
                'struct Guard { public shared<Token> $hold; public function __construct(shared<Token> $t) { '
                '$this->hold=$t; $missing shared<Token>; echo $missing->v; } } '
                'function inlineFail(shared<Token> $t): void { $g Guard($t); } '
                'function heapFail(shared<Token> $t): void { $g=new Guard($t); }',
                '$i=0;', '$p Point;', '$this->x=$x;', 'int $x, int $y'),
        'cpp': ('struct Point { int x; int y; shared<Token> hold; Point(int x,int y) { this->x=x; this->y=y; } };',
                'Point p(step(i),step(i)); auto q=new Point(3,true); echo p->x,p->y,q->x,q->y;',
                'struct Guard { shared<Token> hold; Guard(shared<Token> t) { this->hold=t; shared<Token> missing; echo missing->v; } }; '
                'void inlineFail(shared<Token> t) { Guard g(t); } void heapFail(shared<Token> t) { auto g=new Guard(t); }',
                'int i=0;', 'Point p;', 'this->x=x;', 'int x,int y'),
        'js': ('struct Point { x:int; y:int; hold:shared<Token>; constructor(x:int,y:int) { this.x=x; this.y=y; } }',
               'let p:Point(step(i),step(i)); let q=new Point(3,true); echo p.x,p.y,q.x,q.y;',
               'struct Guard { hold:shared<Token>; constructor(t:shared<Token>) { this.hold=t; let missing:shared<Token>; echo missing.v; } } '
               'function inlineFail(t:shared<Token>): void { let g:Guard(t); } function heapFail(t:shared<Token>): void { let g=new Guard(t); }',
               'let i:int=0;', 'let p:Point;', 'this.x=x;', 'x:int,y:int'),
    }
    return sources[flavor]


def cleanup(out, work, memcheck):
    units = [out / n for n in json.loads((out / 'manifest.json').read_text())['translation_units'] if n != 'entry.cpp']
    factory = next(p for p in units if 'return owner_Token::make();' in p.read_text())
    work.mkdir()
    probe = work / 'cleanup.cpp'
    probe.write_text(f'#include "{factory.resolve()}"\n' + """
#include <cassert>
#include <initializer_list>
#include <stdexcept>
#include <cstring>
namespace App { void inlineFail_(owner_Token); void heapFail_(owner_Token); }
int main() {
    auto token = App::makeToken_();
    for (int i=0; i<1024; ++i) {
        for (auto f : {App::inlineFail_, App::heapFail_}) {
            try { f(token); return 1; }
            catch (const std::runtime_error& ex) {
                assert(std::strcmp(ex.what(),"ownership_empty_access") == 0);
                assert(token.ptr->count == 1);
            }
        }
    }
}
""")
    subprocess.run(write_build(work,[probe,*(p for p in units if p != factory)]),cwd=work,check=True, stdout=subprocess.DEVNULL)
    subprocess.run([work / 'program'],check=True)
    if memcheck:
        subprocess.run(['valgrind', '--leak-check=full', '--show-leak-kinds=all', '--errors-for-leak-kinds=definite,indirect,possible',
                        '--error-exitcode=99',f'--log-file={work}/memcheck.log',work / 'program'],check=True)


def prove(work, flavor, memcheck):
    app = work / flavor
    app.mkdir()
    entry, record = (app / (s+'.'+flavor) for s in ('main', 'point'))
    (app / 'module.json').write_text(json.dumps({'entry':entry.name}))
    (app / 'support.php').write_text('struct Token { public int $v; } function makeToken(): shared<Token> { return new Token(); } '
                                   'function step(int &$i): int { $i++; return $i; }')
    model, body, guard, init, default, assign, params = fixture(flavor)
    (app / ('guard.'+flavor)).write_text(guard)
    source = init+body+'return 8;'
    record.write_text(model)
    zero = model.replace(params, '').replace(assign,assign.split('=')[0]+'=7;')
    zero = zero.replace('=$y;', '=8;').replace('=y;', '=8;')
    entry.write_text(source)
    box = {'php': '$b Box; echo $b->p->x,$b->p->y;', 'cpp': 'Box b; echo b->p->x,b->p->y;',
           'js': 'let b:Box; echo b.p.x,b.p.y;'}[flavor]
    no_args = default + {'php': '$q=new Point(); echo $p->x,$q->y;', 'cpp': 'auto q=new Point(); echo p->x,q->y;',
                         'js': 'let q=new Point(); echo p.x,q.y;'}[flavor] + 'return 8;'
    wrong = source.replace('3,true', '"invalid",true')
    out = work / ('out-'+flavor)
    edits = [(None, '', None, b'1231'),(entry, default+'return 8;', 'Argument count mismatch', None),
           (record, zero, None, b''),(entry, source.replace('step($i),step($i)', '').replace('step(i),step(i)', '').replace('3,true', ''),
                                    'typed declaration without ()', None),
           (entry, default+'return 8;', None, b''),(app / 'nested.php', 'struct Box { public Point $p; }', None, b''),
           (entry, box+'return 8;', None, b'78'), (record, model, 'Inline field requires', None),
           (None, '', 'Inline field requires', None), (record, zero, None, b'78'), (entry, no_args, None, b'78'),
           (app / 'nested.php', '', None, b'78'), (record, model, 'Argument count mismatch', None),
           (entry, wrong, 'conversion', None), (entry, source, None, b'1231')]
    ctor, inline, factory, copy_use = {
        'php': ('public function __construct', '$value Point(4,5);',
                'public static function at():shared<Point> { return new Point(2,3); } '
                'public static function value():Point { $value Point(4,5); return $value; }',
                '$v=Point::value(); $copy=$v; $copy->x=9; $p=Point::at(); echo $v->x,$copy->x,$p->x;'),
        'cpp': ('public: Point(', 'Point value(4,5);',
                'public: static shared<Point> at() { return new Point(2,3); } '
                'static Point value() { Point v(4,5); return v; }',
                'auto v=Point::value(); auto copy=v; copy->x=9; auto p=Point::at(); echo v->x,copy->x,p->x;'),
        'js': ('public constructor', 'let value:Point(4,5);',
               'public static at():shared<Point> { return new Point(2,3); } '
               'public static value():Point { let v:Point(4,5); return v; }',
               'let v=Point.value(); let copy=v; copy.x=9; let p=Point.at(); echo v.x,copy.x,p.x;'),
    }[flavor]
    def with_visibility(definition):
        if flavor == 'cpp':
            return definition.replace(' Point(', ' '+ctor, 1)
        if flavor == 'js':
            return definition.replace(' constructor(', ' '+ctor+'(', 1)
        return definition
    public = with_visibility(model)
    close = public.rindex('}')
    public = public[:close]+factory+public[close:]
    private = public.replace(ctor, ctor.replace('public','private'), 1)
    public_zero = with_visibility(zero)
    private_zero = public_zero.replace(ctor, ctor.replace('public','private'), 1)
    denied = 'Cannot call private constructor Point::__construct'
    new_only = {'php':'$q=new Point(3,1);', 'cpp':'auto q=new Point(3,1);','js':'let q=new Point(3,1);'}[flavor]
    edits.extend([
        (record,public,None,b'1231'), (entry,new_only+'return 8;',None,b''),
        (record,private,denied,None),(None,'',denied,None),(record,public,None,b''),
        (entry,inline+'return 8;',None,b''),(record,private,denied,None),
        (entry,copy_use+'return 8;',None,b'492'),(record,public,None,b'492'),
        (entry,'return 8;',None,b''),(record,public_zero,None,b''),
        (entry,default+'return 8;',None,b''),(record,private_zero,denied,None),
        (entry,'return 8;',None,b''),
        (app/'nested.php','struct Box { public Point $p; }',denied,None),
        (record,public_zero,None,b''),(record,private_zero,denied,None),
        (app/'nested.php','struct Box { public shared<Point> $p; }',None,b''),
    ])
    first, second = params.split(',')
    def defaults(x, y):
        return model.replace(params, first + x + ',' + second + y)
    make_one = {'php':'$one Point(7);', 'cpp':'Point one(7);', 'js':'let one:Point(7);'}[flavor]
    var, member = ('$' if flavor == 'php' else ''), ('.' if flavor == 'js' else '->')
    def y(name):
        return var + name + member + 'y'
    bind = {'php':'$full=', 'cpp':'auto full=', 'js':'let full='}[flavor]
    calls = (default + make_one + bind + 'new Point(7,4); echo ' + y('p') + ',' + y('one') + ',' + y('full')
             + ',(new Point())' + member + 'y' + ',(new Point(7))' + member + 'y;')
    nested = app/'nested.php'
    edits.extend([
        (record, defaults('=true', '=2'), None, b''),
        (nested, 'struct Box { public Point $p; public shared<Point> $owner; }', None, b''),
        (entry, calls + box + ' if (' + var+'b'+member+'owner != null) { return 1; } return 8;', None, b'2242212'),
        (record, defaults('=true', '=9'), None, b'9949919'),
        (record, model, 'Inline field requires', None),
        (None, '', 'Inline field requires', None),
        (record, defaults('=true', '=2'), None, b'2242212'),
        (record, defaults('=1', ''), 'Required parameter follows optional parameter', None),
        (record, defaults('=1', '=step(0)'), 'Parameter defaults require literals', None),
        (record, defaults('=1', '="bad"'), 'conversion', None),
        (record, defaults('=true', '=2'), None, b'2242212'),
        (entry, make_one + 'echo ' + y('one') + ';return 8;', None, b'2'),
        (nested, '', None, b'2'),
        (record, defaults('', '=9'), None, b'9'),
        (entry, default+'return 8;', 'Argument count mismatch', None),
        (record, defaults('=1', '=9'), None, b''),
    ])
    with service(app,out) as resident:
        for rev, (path, text, error, stdout) in enumerate(edits):
            if path is not None:
                path.write_text(text)
                os.utime(path,(1990000000+rev,)*2)
            before = artifacts(out)
            observed = invoke(resident)
            fresh_out = work / f'fresh-{flavor}-{rev}'
            with service(app,fresh_out) as fresh:
                other = invoke(fresh)
                fresh.stdin.close()
            if error:
                assert error in observed.get('error', '') and observed['error']==other.get('error'),(rev,observed,other)
                assert before == artifacts(out)
                continue
            assert 'error' not in observed and 'error' not in other,(rev,observed,other)
            assert artifacts(out) == artifacts(fresh_out)
            build(out, work / ('build-'+flavor), stdout)
            build(fresh_out, work / f'fresh-build-{flavor}-{rev}', stdout)
            if rev == 0:
                cleanup(out, work / ('heap-'+flavor), memcheck)
        resident.stdin.close()


def record_arguments(work, flavor):
    app = work / ('record-args-' + flavor)
    app.mkdir()
    model, segment, entry = [app / (name + '.' + flavor) for name in ('point', 'segment', 'main')]
    (app / 'module.json').write_text(json.dumps({'entry': entry.name}))
    var = '$' if flavor == 'php' else ''
    member = '.' if flavor == 'js' else '->'
    def typed(name, typ):
        return name + ':' + typ if flavor == 'js' else typ + ' ' + var + name
    def field(name, typ):
        return ('public ' if flavor == 'php' else '') + typed(name, typ)
    def local(name, typ):
        return {'php': '$' + name + ' ' + typ, 'cpp': typ + ' ' + name, 'js': 'let ' + name + ':' + typ}[flavor]
    def ctor(name):
        return {'php': 'public function __construct', 'cpp': name, 'js': 'constructor'}[flavor]
    point = 'struct Cell {} struct Point {' + field('x','int') + '=3;' + field('child','shared<Cell>') + ';}'
    start, end, s, h = [var + n for n in ('a','b','s','h')]
    sig = typed('a','Point') + ', ' + typed('b','Point')
    body = (start + member + 'x+=1;' + var + 'this' + member + 'first=' + start + ';'
            + var + 'this' + member + 'last=' + end + ';')
    record = ('struct Segment {' + field('first','Point') + ';' + field('last','Point') + ';'
              + ctor('Segment') + '(' + sig + '){' + body + '}}')
    args = '(' + start + ',' + end + ')'
    heap = ('$h=' if flavor == 'php' else ('let h=' if flavor == 'js' else 'auto h='))
    src = (local('a','Point') + ';' + local('b','Point') + ';' + start + member + 'child=new Cell();'
           + end + member + 'x=7;' + local('s','Segment') + args + ';' + heap + 'new Segment' + args + ';'
           + 'echo ' + start + member + 'x;' + start + member + 'x=0;' + end + member + 'x=0;')
    for receiver in (s,h):
        src += ('echo ' + receiver + member + 'first' + member + 'x,' + receiver + member + 'last' + member
                + 'x; if (' + start + member + 'child !== ' + receiver + member + 'first' + member + 'child) {return 1;}')
    src += 'return 8;'
    entry.write_text(src)
    segment.write_text(record)
    # These parameters occur only in signatures, including mutually referring and self-taking constructors.
    (app/'edges.php').write_text('struct Left { public function __construct(Right $r) {} } '
        'struct Right { public function __construct(Left $l) {} } '
        'struct Same { public function __construct(Same $s) {} } '
        'function copySame(Same $s):Same { $v Same($s); return $v; } '
        'function ownSame(Same $s):shared<Same> { return new Same($s); }')
    out = work / ('record-out-' + flavor)
    with service(app, out) as resident:
        for rev, (path, text, error, expected) in enumerate([
            (model, point, None, b'34747'),
            (model, point.replace('=3;', '=5;' + field('label','string') + ';'), None, b'56767'),
            (segment, record.replace(sig, sig.replace('Point', 'Missing', 1)), 'parameter type: Missing', None),
            (segment, record, None, b'56767'),
            (model, point.split('struct Point')[0], 'Point', None),
            (model, point, None, b'34747'),
            (entry, local('other','Cell') + ';' + src.replace(args, '(' + var + 'other,' + end + ')', 1),
             'conversion from Cell to Point', None),
            (entry, src, None, b'34747'),
        ]):
            path.write_text(text)
            os.utime(path, (1991000000 + rev,) * 2)
            before = artifacts(out)
            current = invoke(resident)
            baseline = work / f'record-fresh-{flavor}-{rev}'
            with service(app, baseline) as fresh:
                other = invoke(fresh)
                fresh.stdin.close()
            if error:
                assert error in current.get('error', '') and current['error'] == other.get('error'), (rev,current,other)
                assert artifacts(out) == before
                continue
            assert 'error' not in current and 'error' not in other, (rev,current,other)
            assert artifacts(out) == artifacts(baseline)
            build(out, work / ('record-native-' + flavor), expected)
            build(baseline, work / f'record-fresh-native-{flavor}-{rev}', expected)
        resident.stdin.close()


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--memcheck',action='store_true')
    parser.add_argument('--output',type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory() as tmp:
        work = (args.output or Path(tmp)).resolve()
        work.mkdir(exist_ok=True)
        for flavor in ['php', 'cpp', 'js']:
            record_arguments(work, flavor)
            prove(work, flavor, args.memcheck)
            print(flavor, 'passed',flush=True)

"""Ordinary and contributed destructor selection, storage lifetime and incremental recovery."""
from source_suffix import source_suffix
import argparse
import json
from pathlib import Path
import tempfile

from collection_goal_native import revisions
from protected_methods_native import forms
from struct_constructors_native import cleanup, fixture
from app_namespace_native import service, invoke
from application_vectors_native import measure


def prove(work, flavor):
    s, klass, _ = forms(flavor)
    def destructor(name, body, access='public'):
        head = {'php': access+' function __destruct()', 'cpp': access+': ~'+name+'()',
                'js': access+' destructor()'}[flavor]
        return head+'{'+body+'}'
    def trait(name, body):
        return 'trait '+name+'{'+body+'}'+(';' if flavor == 'cpp' else '')
    part = s['record']('Part', destructor('Part', 'echo "P";'))
    base = klass('Base', destructor('Base', 'echo "B";', 'protected'))
    origin = trait('Finish', destructor('Finish', 'echo "T";'))
    host = klass('Child', s['field']('part', 'Part')+s['field']('second','Tail')+'use Finish;', 'Base')
    main = s['local']('p','Part') + s['infer']('q',s['v']+'p') + s['infer']('x','new Child()') + s['infer']('y',s['v']+'x')+'echo "M";return 8;'
    sources = dict(part=part, tail=s['record']('Tail',destructor('Tail','echo "Z";')), base=base, finish=origin, host=host, main=main)
    other = trait('Other', destructor('Other', 'echo "X";'))
    revisions(work, flavor, sources, [
        (None, None, b'MTZPBPP'), (None, None, b'MTZPBPP'),
        ('finish', origin.replace('"T"', '"E"'), b'MEZPBPP'),
        ('host', host.replace('use Finish;', destructor('Child','echo "H";')+'use Finish;'), b'MHZPBPP'),
        ('other', other, b'MHZPBPP'),
        ('host', host.replace('use Finish;', 'use Finish,Other;'), 'Finish and Other'),
        ('host', host, b'MEZPBPP'),
        ('base', base.replace('protected','private'), 'Inaccessible destructor'),
        ('base', base, b'MEZPBPP'),
        ('finish', trait('Finish', destructor('Finish','return 7;')), None),
        ('finish', origin, b'MTZPBPP'),
        ('finish', trait('Finish', destructor('Finish','echo "T";','private')), 'Inaccessible destructor'),
        ('finish', origin, b'MTZPBPP'),
    ])
    # Reject malformed declaration shapes and explicit invocation at the source boundary.
    invalid = [
        destructor('Finish','').replace('()', '(int arg)' if flavor == 'cpp' else '(arg:int)' if flavor == 'js' else '(int $arg)'),
        destructor('Finish','').replace('()', '():int'),
        destructor('Finish','').replace('public: ', 'public: static ') if flavor == 'cpp' else destructor('Finish','').replace('public ','public static '),
    ]
    rejects = work/(flavor+'-rejects')
    rejects.mkdir()
    repeated = [('finish',trait('Finish',member),None) for member in invalid]
    explicit = {'cpp':'__destruct', 'php':'__destruct', 'js':'destructor'}[flavor]
    call = s['infer']('x','new Child()') + s['v'] + 'x' + s['access'] + explicit+'();return 8;'
    repeated.extend([('finish',origin,b'MTZPBPP'),('main',call,None),('main',main,b'MTZPBPP')])
    revisions(rejects,flavor,sources,repeated)
    # Fail after acquiring a shared member. Outer destructor must never run; member releases its token.
    guard = fixture(flavor)[2]
    boundary = guard.index('void inlineFail' if flavor == 'cpp' else 'function inlineFail')
    methods, functions = guard[:boundary], guard[boundary:]
    pos = methods.rindex('}')
    methods = methods[:pos]+destructor('Guard', 'echo "unexpected";')+methods[pos:]
    methods = methods.replace('struct Guard', 'trait GuardInit').replace(' Guard(', ' GuardInit(').replace('~Guard(', '~GuardInit(')
    app = work/flavor
    (app/('guard.'+source_suffix(flavor))).write_text(methods+s['record']('Guard','use GuardInit;')+functions)
    (app/'support.phs').write_text('struct Token { public int $v; } function makeToken():shared<Token>{return new Token();}')
    out = work/(flavor+'-cleanup-out')
    proc = service(app, out)
    try:
        result = invoke(proc)
        assert 'error' not in result, result
    finally:
        proc.stdin.close()
        proc.wait()
    cleanup(out, work/(flavor+'-cleanup'), False)

    # Force a native move, with two live objects, then release a shared object whose destructor throws.
    broken = s['local']('absent', 'shared<Token>') + 'echo '+s['v']+'absent'+s['access']+'v;'
    (app/('doomed.'+source_suffix(flavor))).write_text(s['record']('Doomed',destructor('Doomed',broken)))
    (app/'factory.phs').write_text('function doomed():void { $o=new Doomed(); }')
    proc = service(app, out)
    try:
        result = invoke(proc)
        assert 'error' not in result, result
    finally:
        proc.stdin.close()
        proc.wait()
    part = next(path for path in (out/'units').glob('*.hpp') if 'struct Part {' in path.read_text())
    (out/'entry.cpp').write_text('#include '+json.dumps(str(part.resolve()))+'''
#include <utility>
#include <exception>
#include <cstdlib>
#include <cstdio>
namespace App { void doomed_(); }
int main() {
    { App::scpp_records::Part original; auto moved = std::move(original); }
    std::set_terminate([] { std::fflush(stdout); std::_Exit(8); });
    App::doomed_();
    return 91;
}
''')
    measure(out, work/(flavor+'-termination'), b'PP')


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-trait-lifetime-') as tmp:
        work = args.output or Path(tmp)
        work.mkdir(exist_ok=True)
        for flavor in ('php','cpp','js'):
            prove(work, flavor)

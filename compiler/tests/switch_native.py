"""Constant dispatch, case-local flow, protected exits and incremental publication."""
from source_suffix import source_suffix
import json
import argparse
import tempfile
from pathlib import Path

from app_namespace_native import artifacts, invoke, service
from classes_native import fixture
from exceptions_native import catch
from extends_native import runtime, source_edit
from interfaces_native import fresh
from inline_adapters_native import incremental_native_build
from parent_methods_native import write_app

ROOT = Path(__file__).resolve().parents[2]


def prove(work, flavor, library, cxx_flags=()):
    s = fixture(flavor)
    v, fn, local = s['v'], s['function'], s['infer']
    label = 'const KEY:string="a\\0b";' if flavor == 'js' else 'const string KEY="a\\0b";'
    sep = '.' if flavor == 'js' else '::'
    kind = 'enum Kind {' + ('case A=1;case B=2;' if flavor == 'php' else 'A=1,B=2') + '}'
    api = fn('pick', 'string', [('x','string')], f'echo "S";return {v}x;')
    # Default in the middle and a fallthrough into a byte label, then into default.
    api += fn('text', 'void', [('x','string')], f'switch(pick({v}x)){{case "run":case KEY:'
              + local('same', '2') + f'echo {v}same;default:' + local('same','3') + f'echo {v}same;break;case "":echo "E";}}')
    api += fn('number', 'int', [('x','int')], f'switch({v}x){{case -1:return 1;default:case 2:return 2;}}')
    api += fn('enumerated', 'void', [('x','Kind')], f'switch({v}x){{case Kind{sep}A:echo "A";}}')
    api += fn('none', 'void', [], 'switch(pick("z")){} switch(pick("")){default:echo "D";}')
    # The same protected region has two different destinations. Nested finally blocks replay each exit.
    api += fn('flow', 'void', [], local('i','0') + f'while ({v}i < 3) {{{v}i++;switch({v}i){{'
              f'case 1:case 2:try{{try{{if ({v}i === 1){{break;}}continue;}}finally{{echo "I";}}}}finally{{echo "F";}}'
              'default:echo "D";}echo "L";}')
    # A break inside a switch nested in a protected region stays local. Continue must leave the region.
    api += fn('outer', 'void', [], local('i','0') + f'while ({v}i < 2) {{{v}i++;try{{switch({v}i){{'
              'case 1:break;default:continue;}echo "B";}finally{echo "F";}echo "L";}')
    nested = fn('nested', 'void', [], 'switch(1){case 1:while(true){break;}switch(2){case 2:echo "N";break;}echo "Z";break;}')
    node = s['klass']('Node', '')
    observe = s['klass']('Observed', s['field']('watch','weak<Node>'))
    a = s['access']
    api += fn('lifetime','void',[],local('o','new Observed()') + 'switch(1){case 1:'
              + local('owned','new Node()') + f'{v}o{a}watch=weak({v}owned);case 2:'
              + f'if (lock({v}o{a}watch) === null) {{echo "X";}}break;}}')
    factory = 'Exception.make' if flavor=='js' else 'Exception::make'
    api += fn('bad','string',[],f'echo "T";throw {factory}("stop");')
    api += fn('failure','void',[],'try{switch(bad()){default:echo "bad";}}'
              +catch(flavor,'Exception','e')+'{echo "C";}')
    field = s['field']('x','int')
    readonly = field.replace('public ', 'public readonly ', 1) if flavor == 'php' else 'readonly ' + field
    ctor = {'php':'public function __construct(int $x)', 'cpp':'Box(int x)', 'js':'constructor(x:int)'}[flavor]
    write = f'{v}this{a}x=1;'
    cases = f'switch({v}x){{case 1:{write}break;default:{v}this{a}x=2;}}'
    outward = f'switch({v}x){{case 1:switch({v}x){{default:{write}break 2;}}default:{v}this{a}x=2;}}'
    def box(body):
        return s['klass']('Box',readonly + ctor + '{' + body + '}')
    initialized = local('box','new Box(1)') + f'echo {v}box{a}x;'
    main = f'text("run");text("a\\0b");text("a\\0c");text("");echo number(-1),number(0);enumerated(Kind{sep}A);enumerated(Kind{sep}B);none();flow();outer();lifetime();failure();{initialized}nested();return 8;'
    expected = b'S23S23S3SE12ASSDIFLIFDLBFLFXTC1NZ'
    app, out = work/flavor, work/(flavor+'-out')
    write_app(app,flavor,dict(box=box(outward),api=api,node=node,observe=observe,nested=nested,labels=label,kinds=kind,main=main))
    session = service(app,out)
    try:
        for rev,(unit,source,error,stdout) in enumerate([
                (None,None,None,expected),('nested',nested.replace('echo "N";','echo "n";'),None,expected[:-2]+b'nZ'),
                ('nested',nested,None,expected),('labels',label.replace('a\\0b','run'),'Duplicate switch label',None),
                ('labels',None,'Unknown',None),('labels',label,None,expected),
                ('labels',label.replace('a\\0b','a\\0c'),None,expected.replace(b'S23S23S3',b'S23S3S23')),
                (None,None,None,expected.replace(b'S23S23S3',b'S23S3S23'))]):
            before = artifacts(out)
            if unit:
                path = app/(unit+'.'+source_suffix(flavor))
                if source is None: path.unlink()
                else: source_edit(path,source)
            got = invoke(session)
            clean_out = work/f'{flavor}-fresh-{rev}'
            clean = fresh(app,clean_out)
            if error:
                assert error in got.get('error','') and got['error']==clean.get('error'), got
                assert before==artifacts(out)
                continue
            assert 'error' not in got and 'error' not in clean and artifacts(out)==artifacts(clean_out), got
            units = {Path(p).stem:u for p,u in json.loads((out/'manifest.json').read_text())['sources'].items()}
            emitted = (out/units['api']).read_text()
            assert 'std::memcmp(' in emitted and '[[fallthrough]];' in emitted and 'case -1LL:' in emitted
            changed = incremental_native_build(out,work/(flavor+'-native'),'clang++',library,stdout,cxx_flags=cxx_flags)
            if rev in [1,2]: assert changed=={units['nested']} and got['preparation']['prepared']==1,got
            if rev==7: assert not changed and not got['emitted_sources'] and got['preparation']['prepared']==0,got
    finally:
        session.stdin.close()
        session.wait()
    for index,(body,message) in enumerate([
        ('switch(1){default:default:break;}','Duplicate switch default'),
        (kind+f'switch(Kind{sep}A){{case Kind{sep}B:case Kind{sep}B:break;}}','Duplicate switch label'),
        (fn('computed','int',[],'echo "bad";return 1;')+'switch(1){case computed():break;}','literal, named constant'),
        ('switch(1){case "1":break;}', 'label type'),
        ('switch(false){}','constant-label dispatch'),
        ('switch(1){default:continue;}','enclosing loop'),
        ('switch(1){case 1:'+local('privateVar','3')+f'case 2:echo {v}privateVar;}}','Unknown'),
        ('switch(1){default:try{echo "x";}finally{break;}}','cannot leave a finally'),
        ('switch(1){default:return 1;}echo "bad";','after return'),
    ]):
        bad=work/f'{flavor}-bad-{index}'
        write_app(bad,flavor,dict(main=body+'return 8;'))
        got=fresh(bad,work/f'{flavor}-bad-out-{index}')
        assert message.lower() in got.get('error','').lower(),(index,got)
    for index,(source,diagnostic) in enumerate([
        (outward.replace(write, ''), 'not initialized'),
        (cases.replace('break;', ''), 'exactly once'),
        (cases.replace('default:', 'case 2:'), 'not initialized'),
    ]):
        bad = work/f'{flavor}-readonly-{index}'
        write_app(bad,flavor,dict(box=box(source),main=initialized+'return 8;'))
        got = fresh(bad,work/f'{flavor}-readonly-out-{index}')
        assert diagnostic in got.get('error',''),got
    print(flavor,'switch flow, protected exits and incremental recovery passed',flush=True)


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--quick", action="store_true")
    quick = parser.parse_args().quick
    with tempfile.TemporaryDirectory(prefix='scpp-switch-') as directory:
        work=Path(directory)
        library = Path(__file__).resolve().parents[2]/'build/clang/libscpp_runtime.a' if quick else runtime(work,'clang++')
        for flavor in (('php',) if quick else ('cpp','php','js')):
            prove(work,flavor,library,('-stdlib=libc++',) if quick else ())

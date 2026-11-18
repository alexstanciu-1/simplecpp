"""Key presence for runtime/application hashes, borrowed receivers and incremental reuse."""
import json
import subprocess
import tempfile
from pathlib import Path

from app_namespace_native import artifacts, invoke, service
from classes_native import fixture
from extends_native import runtime, source_edit, build
from interfaces_native import fresh
from inline_adapters_native import incremental_native_build
from parent_methods_native import write_app

ROOT=Path(__file__).resolve().parents[2]


def prove(work, flavor, libraries):
    s=fixture(flavor);v,a,fn=s['v'],s['access'],s['function']
    def empty(n,t):
        return {'cpp':f'{t} {n} = [];','php':f'${n} {t} = [];','js':f'let {n}:{t} = [];'}[flavor]
    node=s['record']('Node',s['field']('x','int','7')).replace('struct ','class ',1)
    ctor={'php':'public function __construct(int $x) {$this->x=$x;}',
          'cpp':'Point(int x) {this->x=x;}', 'js':'constructor(x:int) {this.x=x;}'}[flavor]
    point=s['record']('Point',s['field']('x','int','0')+ctor)
    holder=s['record']('Holder',s['field']('items','hash<Point,string>')).replace('struct ','class ',1)
    api=fn('query','bool',[('h','hash<Point,string>'),('key','string')],f'return hash_contains({v}h,{v}key);')
    api+=fn('key','string',[],'echo "K";return "x";')
    api+=fn('make','hash<int,string>',[],empty('h','hash<int,string>')+f'{v}h["x"]=0;echo "H";return {v}h;')
    api+=fn('makeHolder','Holder',[],s['infer']('h','new Holder()')+{'cpp':'Point p(7);','php':'$p Point(7);','js':'let p:Point(7);'}[flavor]+f'{v}h{a}items["x"]={v}p;return {v}h;')
    ref={'cpp':'?Holder &h','php':'?Holder &$h','js':'&h:?Holder'}[flavor]
    head=f'string release({ref})' if flavor=='cpp' else f'function release({ref}):string'
    api+=head+'{'+v+'h=null;echo "R";return "x";}'
    api+=fn('nullable','?Holder',[],'return makeHolder();')
    api+=fn('emptyFailure','void',[],{'cpp':'?Holder h = null;','php':'$h ?Holder = null;','js':'let h:?Holder = null;'}[flavor]+f'hash_contains({v}h{a}items,key());')
    api+=fn('badKey','string',[],'echo "B";'+s['infer']('z','8/0')+'return "x";')
    api+=fn('keyFailure','bool',[],'return hash_contains(make(),badKey());')
    owners=s['record']('OwnerBag',s['field']('items','hash<Node,string>')).replace('struct ','class ',1)
    api+=fn('queryOwners','bool',[('b','OwnerBag'),('key','string')],f'return hash_contains({v}b{a}items,{v}key);')
    main=''
    for i,typ in enumerate(['int','string','Point','Node']):
        value={'int':'0','string':'""','Point':v+'p','Node':'new Node()'}[typ]
        if typ=='Point':main+={'cpp':'Point p(7);','php':'$p Point(7);','js':'let p:Point(7);'}[flavor]
        for j,keytype in enumerate(['int','string']):
            name=f'h{i}_{j}';key='1' if j==0 else '"x\\0y"';miss='2' if j==0 else '"x"'
            main+=empty(name,f'hash<{typ},{keytype}>')
            main+=f'if (hash_contains({v}{name},{key})) {{return 91;}}'
            main+=f'if (count({v}{name})!==0) {{return 92;}}'
            main+=f'{v}{name}[{key}]={value};'
            main+=f'if (!hash_contains({v}{name},{key}) || hash_contains({v}{name},{miss})) {{return 93;}}'
            main+=f'if (count({v}{name})!==1) {{return 94;}}'
    main+=f'{v}h0_1["01"]=1;if (hash_contains({v}h0_1,"1")) {{return 95;}}'
    main+='if (!hash_contains(make(),key())) {return 96;}'
    main+=f'if (!query({v}h2_1,"x\\0y")) {{return 97;}}'
    main+=s['infer']('owner','nullable()')+s['infer']('observer',f'weak({v}owner)')
    main+=f'if (!hash_contains({v}owner{a}items,release({v}owner))) {{return 98;}}'
    main+=f'if (lock({v}observer)!==null) {{return 99;}}'
    main+='if (!hash_contains(makeHolder()'+a+'items,"x")) {return 100;}'
    main+='echo "OK";return 8;'
    expected=b'HKROK'
    app,out=work/flavor,work/(flavor+'-out');write_app(app,flavor,{'node':node,'owners':owners,'point':point,'holder':holder,'api':api,'main':main,'idle':fn('idle','int',[],'return 1;')})
    resident=service(app,out)
    steps=[(None,None,None),(None,None,None),('api',api.replace('return hash_contains','return !!hash_contains',1),None),
           ('api',api,None),('point',point.replace(ctor,ctor+s['field']('padding','int','0')),None),('point',point,None),
           ('point',None,'requires a record'),(None,None,'requires a record'),('point',point,None),
           ('api',api + fn('hash_contains','bool',[('h','hash<int,string>'),('key','string')],'return true;'),'Argument'),
           ('api',api,None),(None,None,None)]
    # Publishing a same-named source function must rebind unchanged callers; a
    # deliberately incompatible signature proves it cannot silently use the builtin.
    try:
        for revision,(name,text,error) in enumerate(steps):
            before=artifacts(out)
            if name:
                path=app/(name+'.'+flavor)
                if text is None:path.unlink()
                else:source_edit(path,text)
            got=invoke(resident);fresh_out=work/f'{flavor}-fresh-{revision}';clean=fresh(app,fresh_out)
            if error:
                assert 'error' in got and got['error']==clean.get('error'),(revision,got,clean)
                assert artifacts(out)==before
                continue
            assert 'error' not in got and 'error' not in clean,(revision,got,clean)
            assert artifacts(out)==artifacts(fresh_out),revision
            units={Path(p).stem:u for p,u in json.loads((out/'manifest.json').read_text())['sources'].items()}
            generated=(out/units['api']).read_text()
            assert '.has(' in generated and '.at(' not in generated and '.find(' not in generated,generated
            for cc,lib in libraries.items():
                changed=incremental_native_build(out,work/(flavor+'-native-'+cc),cc,lib,expected)
                if revision in [1,11]:assert not changed and got['preparation']['prepared']==0 and got['emitted_sources']==[],got
                if revision in [2,3]:assert changed=={units['api']},changed
                if revision in [4,5]:assert units['idle'] not in changed and units['main'] in changed,changed
            if revision==0:
                probe=work/(flavor+'-probe.cpp')
                probe.write_text('#include "'+str(out/units['api'])+'"\n#include <cassert>\n#include <stdexcept>\nint main(){'
                    'try{App::emptyFailure_();return 1;}catch(const std::runtime_error&){}'
                    'try{App::keyFailure_();return 2;}catch(const std::domain_error&){}'
                    'auto bag=App::owner_OwnerBag::make();scpp::string key{"empty",5};'
                    'bag->items.set(key,App::owner_Node{});assert(App::queryOwners_(bag,key));return 0;}\n')
                others=[out/u for u in json.loads((out/'manifest.json').read_text())['translation_units'] if u not in ['entry.cpp',units['api']]]
                for cc,lib in libraries.items():
                    exe=work/(flavor+'-probe-'+cc)
                    p=subprocess.run([cc,'-std=c++23','-O1','-Wall','-Wextra','-Werror','-I',ROOT/'runtime/generated/public',probe,*others,lib,'-o',exe],capture_output=True,text=True)
                    assert p.returncode==0,p.stderr
                    p=subprocess.run([exe],capture_output=True);assert (p.returncode,p.stdout,p.stderr)==(0,b'HB',b''),p
            print(flavor,revision,'hash presence native/recovery passed',flush=True)
    finally:resident.stdin.close();resident.wait()
    prefix=empty('h','hash<int,string>')
    for i,(source,needle) in enumerate([
        (prefix+'hash_contains('+v+'h,1);return 8;','requires string'),
        (prefix+'hash_contains('+v+'h,true);return 8;','requires string'),
        (prefix+'hash_contains('+v+'h);return 8;','collection and key'),
        ('hash_contains(1,"x");return 8;','key-presence'),
        ('use function Missing as hash_contains;'+prefix+'hash_contains('+v+'h,"x");return 8;','Unknown function'),
    ]):
        bad=work/f'{flavor}-bad-{i}';write_app(bad,flavor,{'main':source})
        got=fresh(bad,work/f'{flavor}-bad-out-{i}');assert needle in got.get('error',''),got
    # A constant alias does not shadow the function namespace. A real declaration does.
    constant='const FLAG:int=1;' if flavor=='js' else 'const int FLAG=1;'
    for kind,source,wanted in [
        ('constant','use const FLAG as hash_contains;'+constant+prefix+f'if (hash_contains({v}h,"x")) {{return 90;}}return 8;',b''),
        ('function',fn('hash_contains','int',[('x','int')],f'return {v}x;')+'echo hash_contains(7);return 8;',b'7')]:
        case=work/(flavor+'-'+kind);output=work/(flavor+'-'+kind+'-out');write_app(case,flavor,{'main':source})
        got=fresh(case,output);assert 'error' not in got,got
        for cc,lib in libraries.items():build(output,work/(flavor+'-'+kind+'-'+cc),cc,lib,wanted)


if __name__=='__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-hash-contains-') as temporary:
        work=Path(temporary)
        libraries={cc:runtime(work,cc,[ROOT/'runtime/generated/src/hash.cpp']) for cc in ['clang++','g++']}
        for flavor in ['cpp','php','js']:prove(work,flavor,libraries)

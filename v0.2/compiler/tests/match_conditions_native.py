"""Ordered match(true) conditions, fallback invalidation and native incremental reuse."""
import json
import subprocess
import tempfile
from pathlib import Path

from classes_native import fixture
from extends_native import runtime, source_edit
from interfaces_native import fresh
from app_namespace_native import artifacts, invoke, service
from inline_adapters_native import incremental_native_build
from parent_methods_native import write_app

ROOT = Path(__file__).resolve().parents[2]


def prove(work, flavor, libraries):
    s=fixture(flavor);v,fn=s['v'],s['function']
    const=lambda n,x: f'const {n}:int={x};' if flavor=='js' else f'const int {n}={x};'
    predicates=fn('test','bool',[('tag','string'),('value','bool')],f'echo {v}tag;return {v}value;')
    predicates+=fn('result','int',[('tag','string'),('value','int')],f'echo {v}tag;return {v}value;')
    predicates+=fn('explode','bool',[],'echo "E";return (8/0)>0;')
    predicates+=fn('explodeResult','int',[],'echo "R";return 8/0;')
    predicates+=fn('narrow','int',[('x','byte')],f'echo "BAD";return (int){v}x;')
    ref={'cpp':'int &x','php':'int &$x','js':'&x:int'}[flavor]
    signature=f'bool mutate({ref})' if flavor=='cpp' else f'function mutate({ref}):bool'
    predicates+=signature+'{echo "M";'+v+'x=256;return false;}'
    choose=fn('choose','int',[('x','int')],f'''return match(true){{
        default=>result("D",3),
        test("A",{v}x<0)=>result("X",1),
        test("B",{v}x<LIMIT)=>result("Y",2),
        test("C",true)=>result("Z",4)
    }};''')
    api=choose+fn('fallback','int',[],'return match(true){default=>result("D",3),test("F",false)=>explodeResult()};')
    api+=fn('repeated','int',[],'return match(true){test("Q",false)=>0,test("Q",false)=>0,default=>5};')
    api+=fn('conditionFailure','int',[],'return match(true){explode()=>result("BAD",1),test("BAD",true)=>0,default=>result("BAD",2)};')
    api+=fn('resultFailure','int',[],'return match(true){test("P",true)=>explodeResult(),explode()=>0,default=>0};')
    api+=fn('defaultFailure','int',[],s['infer']('x','8')+f'return match(true){{default=>narrow({v}x),mutate({v}x)=>0}};')
    api+=fn('mutating','int',[],s['infer']('x','8')+f'return match(true){{mutate({v}x)=>0,{v}x===256=>5,default=>0}};')
    api+=fn('copy','string',[('x','string')],f'return match(true){{false=>"unused",true=>{v}x,default=>"other"}};')
    main='echo choose(-1),choose(5),choose(20),fallback(),repeated(),mutating();'
    main+='if (false && match(true){explode()=>true,default=>false}) {echo "BAD";}'
    main+='if (true || match(true){explode()=>true,default=>false}) {}'
    main+=s['infer']('nested','match(true){-1<0=>match(true){false=>0,default=>6},default=>0}')+f'echo {v}nested;'
    main+=s['infer']('a','"a\\0b"')+s['infer']('b',f'copy({v}a)')+f'{v}a="changed";echo {v}b;'
    main+='echo match(true){default=>7};return 8;'
    expected=b'AX1ABY2ABCZ4FD3QQ5M56a\0b7'
    app,out=work/flavor,work/(flavor+'-out')
    write_app(app,flavor,{'predicates':predicates,'api':api,'limits':const('LIMIT',10),'main':main,'idle':fn('idle','int',[],'return 1;')})
    resident=service(app,out)
    edits=[(None,None,None,expected),(None,None,None,expected),
           ('predicates',predicates.replace('return '+v+'value;', 'return !!'+v+'value;',1),None,expected),
           ('api',api.replace('result("Y",2)','result("Y",9)'),None,expected.replace(b'Y2',b'Y9')),
           ('api',api,None,expected),('limits',const('LIMIT',3),None,expected.replace(b'ABY2',b'ABCZ4')),
           ('limits',const('LIMIT',10),None,expected),
           ('predicates',None,'label type',None),
           (None,None,'label type',None),('predicates',predicates,None,expected),
           ('predicates',None,'Unknown',None),(None,None,'Unknown',None),('predicates',predicates,None,expected),
           ('api',api.replace('default=>result("D",3)', 'default=>"bad"',1),'result types',None),
           ('api',api,None,expected),(None,None,None,expected)]
    # Change only the test return signature AND its body, so the diagnostic comes
    # from the unchanged match consumer, not an invalid predicate return boundary.
    invalid_predicates=fn('test','int',[('tag','string'),('value','bool')],f'echo {v}tag;return 1;')+predicates[predicates.index('}')+1:]
    edits[7]=('predicates',invalid_predicates,'label type',None)
    try:
        for i,(name,source,error,stdout) in enumerate(edits):
            before=artifacts(out)
            if name:
                path=app/(name+'.'+flavor)
                if source is None:path.unlink()
                else:source_edit(path,source)
            got=invoke(resident);fresh_out=work/f'{flavor}-fresh-{i}';clean=fresh(app,fresh_out)
            if error:
                assert error.lower() in got.get('error','').lower(),(i,got)
                assert got['error']==clean.get('error'),(got,clean)
                assert artifacts(out)==before
                continue
            assert 'error' not in got and 'error' not in clean,(i,got,clean)
            assert artifacts(out)==artifacts(fresh_out),i
            units={Path(p).stem:u for p,u in json.loads((out/'manifest.json').read_text())['sources'].items()}
            generated=(out/units['api']).read_text()
            assert 'switch (' not in generated and '([&]() -> ::scpp::int64' not in generated,generated
            assert 'checked_int64_to_uint8' in generated,generated
            for cc,lib in libraries.items():
                changed=incremental_native_build(out,work/(flavor+'-native-'+cc),cc,lib,stdout)
                if i in [1,15]:assert not changed and got['preparation']['prepared']==0 and got['emitted_sources']==[],got
                if i==2:assert changed=={units['predicates']},changed
                if i==3:assert changed=={units['api']},changed
            if i==0:
                for cc,lib in libraries.items():
                    probe=work/(flavor+'-'+cc+'-probe.cpp')
                    probe.write_text('#include "'+str(out/units['api'])+'"\n#include <stdexcept>\nint main(){'
                        'try{App::conditionFailure_();return 1;}catch(const std::domain_error&){}'
                        'try{App::resultFailure_();return 2;}catch(const std::domain_error&){}'
                        'try{App::defaultFailure_();return 3;}catch(const std::exception&){}return 0;}\n')
                    exe=probe.with_suffix('')
                    p=subprocess.run([cc,'-std=c++23','-O1','-Wall','-Wextra','-Werror','-I',ROOT/'runtime/generated/public',probe,out/units['predicates'],lib,'-o',exe],capture_output=True,text=True)
                    assert p.returncode==0,p.stderr
                    p=subprocess.run([exe],capture_output=True);assert (p.returncode,p.stdout,p.stderr)==(0,b'EPRM',b''),p
    finally:resident.stdin.close();resident.wait()
    invalid=[('match(true){1=>2,default=>3}','label type'),('match(true){"x"=>2,default=>3}','label type'),
             ('match(true){true=>1}','default arm'),('match(true){default=>1,default=>2}','Duplicate match default'),
             ('match(true){true=>1,false=>"x",default=>0}','result types'),
             ('match(true){true=>1,missing()=>2,default=>0}','Unknown'),
             ('match(false){default=>1}','integer or enum')]
    for i,(expr,error) in enumerate(invalid):
        bad=work/f'{flavor}-bad-{i}';write_app(bad,flavor,{'main':s['infer']('x',expr)+'return 8;'})
        got=fresh(bad,work/f'{flavor}-bad-out-{i}');assert error.lower() in got.get('error','').lower(),got
    print(flavor,'ordered match conditions, failures and incremental/native recovery passed',flush=True)


if __name__=='__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-match-conditions-') as temporary:
        work=Path(temporary)
        libraries={cc:runtime(work,cc) for cc in ['clang++','g++']}
        for flavor in ['cpp','php','js']:prove(work,flavor,libraries)

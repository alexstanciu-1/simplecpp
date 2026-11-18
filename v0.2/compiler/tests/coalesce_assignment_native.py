"""Conditional nullable local writes: conversion, lazy effects, copies and incremental reuse."""
import json
import subprocess
import tempfile
from pathlib import Path
from app_namespace_native import artifacts, invoke, service
from classes_native import fixture
from extends_native import runtime, source_edit
from interfaces_native import fresh
from inline_adapters_native import incremental_native_build
from parent_methods_native import write_app

ROOT=Path(__file__).resolve().parents[2]


def prove(work,flavor,libraries):
    s=fixture(flavor);v,fn=s['v'],s['function']
    def local(n,t,value):
        return {'cpp':f'{t} {n} = {value};','php':f'${n} {t} = {value};','js':f'let {n}:{t} = {value};'}[flavor]
    fallback=fn('fallback','int',[],'echo "F";return 9;')
    api=fallback+fn('bad','int',[],'echo "D";return 8/0;')
    api+=fn('failed','int',[],local('n','nullable<int>','null')+f'{v}n ??= bad();echo "REACHED";return 0;')
    api+=fn('text','string',[],'echo "T";return "heap backed text\\0tail";')
    api+=fn('assign','int',[],local('n','nullable<int>','null')+f'{v}n ??= fallback(); {v}n ??= bad(); return {v}n;')
    api+=fn('checked','int',[('input','float')],local('n','nullable<int>','null')+f'echo "B";{v}n ??= (int){v}input;echo "C";return {v}n;')
    api+=fn('skipped','int',[('input','float')],local('n','nullable<int>','0')+f'{v}n ??= (int){v}input;return {v}n;')
    api+=fn('narrow','int',[('n','byte')],f'echo "BAD";return (int){v}n;')
    def reference_function(name,t,body):
        ref={'cpp':f'{t} &n','php':f'{t} &$n','js':f'&n:{t}'}[flavor]
        signature=f'int {name}({ref})' if flavor=='cpp' else f'function {name}({ref}):int'
        return signature+'{'+body+'}'
    api+=reference_function('fill','nullable<int>',f'echo "M";{v}n=2;return 3;')
    api+=reference_function('change','int',f'echo "I";{v}n=256;return 5;')
    api+=fn('invalidation','int',[],local('x','int','8')+local('n','nullable<int>','null')+f'{v}n ??= change({v}x);return narrow({v}x);')
    main='echo assign();'+local('zero','nullable<int>','0')+f'{v}zero ??= bad();echo (int){v}zero;'
    main+=local('flag','nullable<bool>','false')+f'{v}flag ??= true;if ({v}flag) {{return 91;}}'
    main+=local('textValue','nullable<string>','""')+f'{v}textValue ??= text();'+local('plain','string',v+'textValue')+f'if ({v}plain!=="") {{return 92;}}'
    main+=f'{v}textValue=null;{v}textValue ??= text();'+local('copy','string',v+'textValue')+f'{v}textValue="other";if ({v}copy!=="heap backed text\\0tail") {{return 93;}}'
    main+=local('items','vector<string>','[]')+f'{v}items[]="old";'+local('wrapped','nullable<vector<string>>','null')+f'{v}wrapped ??= {v}items;{v}items[0]="new";'
    main+=local('original','vector<string>',v+'wrapped')+f'if ({v}original[0]!=="old") {{return 94;}}'
    main+=local('n','nullable<int>','null')+f'{v}n ??= null;if (isset({v}n)) {{return 95;}}'
    main+=local('other','nullable<int>','null')+f'{v}n ??= {v}other;if (isset({v}n)) {{return 96;}}{v}other=4;{v}n ??= {v}other;echo (int){v}n;'
    main+=f'{v}n=null;{v}n ??= fill({v}n);{v}n ??= fill({v}n);echo (int){v}n;'
    main+='echo checked(3.75);return 8;'
    expected=b'F90T4M3BC3'
    app,out=work/flavor,work/(flavor+'-out');write_app(app,flavor,{'api':api,'main':main,'idle':fn('idle','int',[],'return 1;')})
    wrong=api.replace(fallback,fn('fallback','string',[],'return "wrong";'))
    steps=[(None,None,None,expected),(None,None,None,expected),
           ('api',api.replace('return 9;','return 7;',1),None,expected.replace(b'9',b'7')),('api',api,None,expected),
           ('api',wrong,'conversion',None),(None,None,'conversion',None),('api',api,None,expected),
           ('api',None,'Unknown',None),(None,None,'Unknown',None),('api',api,None,expected),(None,None,None,expected)]
    resident=service(app,out)
    try:
        for revision,(name,text,error,wanted) in enumerate(steps):
            before=artifacts(out)
            if name:
                path=app/(name+'.'+flavor)
                if text is None:path.unlink()
                else:source_edit(path,text)
            got=invoke(resident);fresh_out=work/f'{flavor}-fresh-{revision}';clean=fresh(app,fresh_out)
            if error:
                assert error.lower() in got.get('error','').lower() and got['error']==clean.get('error'),(revision,got,clean)
                assert artifacts(out)==before
                continue
            assert 'error' not in got and 'error' not in clean,(revision,got,clean)
            assert artifacts(out)==artifacts(fresh_out),revision
            units={Path(p).stem:u for p,u in json.loads((out/'manifest.json').read_text())['sources'].items()}
            code=(out/units['api']).read_text();assert 'if (!(' in code and '.has_value()' in code and 'coalesce_value' not in code,code
            for cc,lib in libraries.items():
                changed=incremental_native_build(out,work/(flavor+'-native-'+cc),cc,lib,wanted)
                if revision in [1,10]:assert not changed and got['preparation']['prepared']==0 and got['emitted_sources']==[],got
                if revision in [2,3]:assert changed=={units['api']},changed
            if revision==0:
                probe=work/(flavor+'-probe.cpp')
                probe.write_text('#include "'+str(out/units['api'])+'"\n#include <limits>\n#include <initializer_list>\n#include <exception>\nint main(){'
                    'try{App::failed_();return 4;}catch(const std::exception&){}'
                    'for(double v:{std::numeric_limits<double>::quiet_NaN(),std::numeric_limits<double>::infinity(),1e100}) {'
                    'if(App::skipped_(v)!=0)return 1;try{App::checked_(v);return 2;}catch(const std::exception&){} }'
                    'try{App::invalidation_();return 3;}catch(const std::exception&){}return 0;}\n')
                for cc,lib in libraries.items():
                    exe=work/(flavor+'-probe-'+cc)
                    p=subprocess.run([cc,'-std=c++23','-O1','-Wall','-Wextra','-Werror','-I',ROOT/'runtime/generated/public',probe,lib,'-o',exe],capture_output=True,text=True);assert p.returncode==0,p.stderr
                    p=subprocess.run([exe],capture_output=True);assert (p.returncode,p.stdout,p.stderr)==(0,b'DBBBI',b''),p
            print(flavor,revision,'coalescing assignment native/recovery passed',flush=True)
    finally:resident.stdin.close();resident.wait()
    bad_sources=[
        (local('n','int','0')+f'{v}n ??= 2;','nullable carrier'),
        (local('n','result<int>','1')+f'{v}n ??= 2;','nullable carrier'),
        (f'{v}missing ??= 2;','existing local'),
        (local('n','nullable<int>','1')+f'{v}n ??= missing();','Unknown'),
        (local('n','nullable<int>','1')+f'{v}n ??= "wrong";','conversion'),
        (local('n','nullable<int>','null')+s['infer']('x',f'({v}n ??= 2)'),'Expected'),
        (fn('parameter','int',[('n','nullable<int>')],f'{v}n ??= 2;return 0;'),'plain local'),
        (local('n','nullable<int>','null')+f'for ({v}n ??= 2;false;{v}n=3) {{}}','plain local'),
        (local('n','nullable<int>','null')+f'for ({v}n=null;false;{v}n ??= 3) {{}}','plain local'),
        (s['record']('Point',s['field']('x','int','0'))+s['local']('p','Point')+f'{v}p{s["access"]}x ??= 2;','compound'),
        (local('items','vector<int>','[]')+f'{v}items[]=1;{v}items[0] ??= 2;','compound'),
    ]
    for i,(source,error) in enumerate(bad_sources):
        bad=work/f'{flavor}-bad-{i}';write_app(bad,flavor,{'main':source+'return 8;'})
        got=fresh(bad,work/f'{flavor}-bad-out-{i}');assert error.lower() in got.get('error','').lower(),got


if __name__=='__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-coalesce-assignment-') as temporary:
        work=Path(temporary);libraries={cc:runtime(work,cc) for cc in ['clang++','g++']}
        for flavor in ['cpp','php','js']:prove(work,flavor,libraries)

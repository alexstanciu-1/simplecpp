"""Published optional hash lookup: owned payload copies, failures and incremental reuse."""
from source_suffix import source_suffix
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


def prove(work, flavor, libraries, *, quick=False):
    s=fixture(flavor);v,a,fn=s['v'],s['access'],s['function']
    def local(n,t,value):
        return {'cpp':f'{t} {n} = {value};','php':f'${n} {t} = {value};','js':f'let {n}:{t} = {value};'}[flavor]
    box=s['record']('Box',s['field']('items','hash<string,string>')).replace('struct ','class ',1)
    api=fn('query','nullable<int>',[('h','hash<int,string>'),('key','string')],f'return hash_find({v}h,{v}key);')
    api+=fn('make','hash<int,string>',[],local('h','hash<int,string>','[]')+f'{v}h["x"]=0;echo "H";return {v}h;')
    api+=fn('key','string',[],'echo "K";return "x";')
    api+=fn('makeBox','?Box',[],s['infer']('b','new Box()')+f'{v}b{a}items["x"]="owned\\0value";return {v}b;')
    ref={'cpp':'?Box &b','php':'?Box &$b','js':'&b:?Box'}[flavor]
    signature=f'string release({ref})' if flavor=='cpp' else f'function release({ref}):string'
    api+=signature+'{'+v+'b=null;echo "R";return "x";}'
    api+=fn('emptyFailure','void',[],local('b','?Box','null')+f'hash_find({v}b{a}items,key());')
    api+=fn('badKey','string',[],'echo "B";'+s['infer']('z','8/0')+'return "x";')
    api+=fn('keyFailure','nullable<int>',[],'return hash_find(make(),badKey());')
    api+=fn('readFailure','int',[],s['infer']('h','make()')+f'return {v}h["missing"];')
    main=''
    for i,value in enumerate(['int','string']):
        for j,keytype in enumerate(['int','string']):
            n=f'h{i}{j}';k='-1' if j==0 else '"x\\0y"';miss='2' if j==0 else '"x"';payload='0' if i==0 else '""'
            main+=local(n,f'hash<{value},{keytype}>','[]')+s['infer']('missing'+n,f'hash_find({v}{n},{k})')
            main+=f'if (isset({v}missing{n}) || count({v}{n})!==0) {{return 91;}}'
            main+=f'{v}{n}[{k}]={payload};'+s['infer']('found'+n,f'hash_find({v}{n},{k})')
            main+=f'if (!isset({v}found{n}) || ({value}){v}found{n}!=={payload}) {{return 92;}}'
            main+=s['infer']('absent'+n,f'hash_find({v}{n},{miss})')+f'if (isset({v}absent{n}) || count({v}{n})!==1) {{return 93;}}'
            main+=f'{v}{n}{a}clear();if (({value}){v}found{n}!=={payload}) {{return 94;}}'
    main+=f'{v}h11["01"]="heap backed payload that survives overwriting\\0tail";'
    main+=s['infer']('held',f'hash_find({v}h11,"01")')+s['infer']('numeric',f'hash_find({v}h11,"1")')
    main+=f'if (isset({v}numeric)) {{return 95;}} {v}h11["01"]="replacement";{v}h11{a}clear();'
    main+=f'if ((string){v}held!=="heap backed payload that survives overwriting\\0tail") {{return 96;}}'
    main+=s['infer']('r','hash_find(make(),key())')+f'if (!isset({v}r) || (int){v}r!==0) {{return 97;}}'
    main+=s['infer']('q','query(make(),"x")')+f'if (isset({v}q)) {{echo "F";}} else {{echo "M";}}'
    main+=s['infer']('owner','makeBox()')+s['infer']('observer',f'weak({v}owner)')
    main+=s['infer']('copied',f'hash_find({v}owner{a}items,release({v}owner))')
    main+=f'if (lock({v}observer)!==null || !isset({v}copied)) {{return 98;}} echo (string){v}copied;return 8;'
    expected=b'HKHFRowned\0value'
    app,out=work/flavor,work/(flavor+'-out');sources = {'box':box,'api':api,'main':main,'idle':fn('idle','int',[],'return 1;')}
    if quick:
        from collection_goal_native import revisions
        revisions(work, flavor, sources, [
            (None, None, expected),
            ('api', fn('query','int',[('h','hash<int,string>'),('key','string')],'return 7;')+api[api.index('}')+1:], 'carrier'),
            ('api', api.replace(f'hash_find({v}h,{v}key)',f'hash_find({v}h,"missing")',1), expected.replace(b'HF',b'HM')),
        ])
        return
    write_app(app, flavor, sources)
    # A valid changed signature forces the existing caller's isset to be checked again.
    end=api.index('}')+1
    invalid_api=fn('query','int',[('h','hash<int,string>'),('key','string')],'return 7;')+api[end:]
    steps=[(None,None,None,expected),(None,None,None,expected),
           ('api',api.replace(f'hash_find({v}h,{v}key)',f'hash_find({v}h,"missing")',1),None,expected.replace(b'HF',b'HM')),
           ('api',api,None,expected),('api',invalid_api,'carrier',None),(None,None,'carrier',None),('api',api,None,expected),
           ('api',None,'Unknown',None),(None,None,'Unknown',None),('api',api,None,expected),
           ('box',box.replace(s['field']('items','hash<string,string>'),s['field']('padding','int','0')+s['field']('items','hash<string,string>')),None,expected),
           ('box',box,None,expected),(None,None,None,expected)]
    resident=service(app,out)
    try:
        for revision,(name,text,error,wanted) in enumerate(steps):
            before=artifacts(out)
            if name:
                path=app/(name+'.'+source_suffix(flavor))
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
            generated=(out/units['api']).read_text();assert '.find(' in generated and '.has(' not in generated,generated
            for cc,lib in libraries.items():
                changed=incremental_native_build(out,work/(flavor+'-native-'+cc),cc,lib,wanted)
                if revision in [1,12]:assert not changed and got['preparation']['prepared']==0 and got['emitted_sources']==[],got
                if revision in [2,3]:assert changed=={units['api']},changed
                if revision in [10,11]:assert units['main'] in changed and units['idle'] not in changed,changed
            if revision==0:
                probe=work/(flavor+'-probe.cpp')
                probe.write_text('#include "'+str(out/units['api'])+'"\n#include <stdexcept>\nint main(){'
                    'try{App::emptyFailure_();return 1;}catch(const std::runtime_error&){}'
                    'try{App::keyFailure_();return 2;}catch(const std::domain_error&){}'
                    'try{App::readFailure_();return 3;}catch(const std::out_of_range&){}return 0;}\n')
                others=[out/u for u in json.loads((out/'manifest.json').read_text())['translation_units'] if u not in ['entry.cpp',units['api']]]
                for cc,lib in libraries.items():
                    exe=work/(flavor+'-probe-'+cc)
                    p=subprocess.run([cc,'-std=c++23','-O1','-Wall','-Wextra','-Werror','-I',ROOT/'runtime/generated/public',probe,*others,lib,'-o',exe],capture_output=True,text=True);assert p.returncode==0,p.stderr
                    p=subprocess.run([exe],capture_output=True);assert (p.returncode,p.stdout,p.stderr)==(0,b'HBH',b''),p
            print(flavor,revision,'optional lookup native/recovery passed',flush=True)
    finally:resident.stdin.close();resident.wait()
    invalid=[(local('h','hash<int,string>','[]')+f'hash_find({v}h,1);return 8;','requires string'),
             (local('h','hash<int,string>','[]')+f'hash_find({v}h);return 8;','collection and key'),
             (s['record']('Point','')+local('h','hash<Point,string>','[]')+f'hash_find({v}h,"x");return 8;','published optional lookup'),
             ('use function Missing as hash_find;'+local('h','hash<int,string>','[]')+f'hash_find({v}h,"x");return 8;','Unknown function')]
    for i,(source,error) in enumerate(invalid):
        bad=work/f'{flavor}-bad-{i}';write_app(bad,flavor,{'main':source});got=fresh(bad,work/f'{flavor}-bad-out-{i}');assert error in got.get('error',''),got
    constant='const FLAG:int=1;' if flavor=='js' else 'const int FLAG=1;'
    for kind,source,wanted in [
        ('constant','use const FLAG as hash_find;'+constant+local('h','hash<int,string>','[]')+s['infer']('r',f'hash_find({v}h,"x")')+f'if (isset({v}r)) {{return 90;}}return 8;',b''),
        ('function',fn('hash_find','int',[('x','int')],f'return {v}x;')+'echo hash_find(7);return 8;',b'7')]:
        case=work/(flavor+'-'+kind);output=work/(flavor+'-'+kind+'-out');write_app(case,flavor,{'main':source});got=fresh(case,output);assert 'error' not in got,got
        for cc,lib in libraries.items():build(output,work/(flavor+'-'+kind+'-'+cc),cc,lib,wanted)


if __name__=='__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-hash-find-') as temporary:
        work=Path(temporary)
        import argparse
        parser = argparse.ArgumentParser(description=__doc__)
        parser.add_argument('--quick', action='store_true')
        if parser.parse_args().quick:
            prove(work, 'php', {}, quick=True)
        else:
            libraries={cc:runtime(work,cc,[ROOT/'runtime/generated/src/hash.cpp']) for cc in ['clang++','g++']}
            for flavor in ['cpp','php','js']:prove(work,flavor,libraries)

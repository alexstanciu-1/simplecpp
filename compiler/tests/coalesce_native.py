"""Nullable payload coalescing: lazy fallback, owned results and retained/native recovery."""
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


def prove(work,flavor,libraries, *, quick=False):
    s=fixture(flavor);v,a,fn=s['v'],s['access'],s['function']
    def local(n,t,value):
        return {'cpp':f'{t} {n} = {value};','php':f'${n} {t} = {value};','js':f'let {n}:{t} = {value};'}[flavor]
    api=fn('number','nullable<int>',[('present','bool')],f'echo "L";if ({v}present) {{return 0;}}return null;')
    api+=fn('fallback','int',[],'echo "F";return 9;')
    api+=fn('bad','int',[],'echo "B";return 8/0;')
    api+=fn('badLeft','nullable<int>',[],'echo "E";'+s['infer']('z','8/0')+'return null;')
    api+=fn('leftFailure','int',[],'return badLeft() ?? fallback();')
    api+=fn('rightFailure','int',[],'return number(false) ?? bad();')
    api+=fn('resolve','int',[('n','nullable<int>')],f'return {v}n ?? fallback();')
    api+=fn('narrow','int',[('n','byte')],f'echo "BAD";return (int){v}n;')
    ref={'cpp':'int &n','php':'int &$n','js':'&n:int'}[flavor]
    signature=f'int change({ref})' if flavor=='cpp' else f'function change({ref}):int'
    api+=signature+'{echo "C";'+v+'n=256;return 5;}'
    api+=fn('invalidation','int',[],local('x','int','8')+local('n','nullable<int>','null')+s['infer']('r',f'{v}n ?? change({v}x)')+f'return narrow({v}x);')
    main='echo number(true) ?? fallback(),number(false) ?? fallback();'
    main+='echo number(true) ?? bad();'
    main+=local('none','nullable<int>','null')+local('zero','nullable<int>','0')
    main+=f'echo {v}none ?? {v}none ?? 3,{v}none ?? {v}zero ?? bad(),{v}none ?? 1 + 2;'
    main+=local('flag','nullable<bool>','false')+f'if ({v}flag ?? true) {{return 91;}}'
    main+=f'if ({v}flag ?? false || true) {{return 92;}}'
    main+=local('absentFlag','nullable<bool>','null')+f'if (!({v}absentFlag ?? false || true)) {{return 93;}}'
    main+='if (false && (number(false) ?? 1)>0) {return 94;}if (true || (number(false) ?? 1)>0) {}'
    main+=local('text','nullable<string>','""')+s['infer']('empty',f'{v}text ?? "fallback"')+f'if ({v}empty!=="") {{return 95;}}'
    main+=f'{v}text="heap backed selected text\\0tail";'+s['infer']('copy',f'{v}text ?? "unused"')
    main+=f'{v}text="replacement";if ({v}copy!=="heap backed selected text\\0tail") {{return 96;}}'
    main+=local('items','vector<string>','[]')+f'{v}items[]="old";'+local('wrapped','nullable<vector<string>>',v+'items')
    main+=s['infer']('selected',f'{v}wrapped ?? {v}items')+f'{v}selected[0]="new";'
    main+=local('original','vector<string>',v+'wrapped')+f'if ({v}original[0]!=="old") {{return 97;}}'
    main+='echo resolve(number(false));return 8;'
    expected=b'L0LF9L0303LF9'
    app,out=work/flavor,work/(flavor+'-out');sources = {'api':api,'main':main,'idle':fn('idle','int',[],'return 1;')}
    # Source signature changes invalidate unchanged coalescing consumers.
    # number has an inner if: reconstruct the other functions from the next signature.
    next_signature=fn('fallback','int',[],'echo "F";return 9;')
    wrong=fn('number','int',[('present','bool')],'return 0;')+api[api.index(next_signature):]
    if quick:
        from collection_goal_native import revisions
        revisions(work, flavor, sources, [
            (None, None, expected), ('api', wrong, 'nullable carrier'),
            ('api', api.replace('return 9;', 'return 7;', 1), expected.replace(b'9', b'7')),
        ])
        return
    write_app(app, flavor, sources)
    steps=[(None,None,None,expected),(None,None,None,expected),
           ('api',api.replace('return 9;','return 7;',1),None,expected.replace(b'9',b'7')),('api',api,None,expected),
           ('api',wrong,'nullable carrier',None),(None,None,'nullable carrier',None),('api',api,None,expected),
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
            code=(out/units['api']).read_text();assert 'const auto& coalesce_value' in code and '.has_value()' in code,code
            for cc,lib in libraries.items():
                changed=incremental_native_build(out,work/(flavor+'-native-'+cc),cc,lib,wanted)
                if revision in [1,10]:assert not changed and got['preparation']['prepared']==0 and got['emitted_sources']==[],got
                if revision in [2,3]:assert changed=={units['api']},changed
            if revision==0:
                probe=work/(flavor+'-probe.cpp')
                probe.write_text('#include "'+str(out/units['api'])+'"\n#include <stdexcept>\nint main(){'
                    'try{App::leftFailure_();return 1;}catch(const std::domain_error&){}'
                    'try{App::rightFailure_();return 2;}catch(const std::domain_error&){}'
                    'try{App::invalidation_();return 3;}catch(const std::exception&){}return 0;}\n')
                for cc,lib in libraries.items():
                    exe=work/(flavor+'-probe-'+cc)
                    p=subprocess.run([cc,'-std=c++23','-O1','-Wall','-Wextra','-Werror','-I',ROOT/'runtime/generated/public',probe,lib,'-o',exe],capture_output=True,text=True);assert p.returncode==0,p.stderr
                    p=subprocess.run([exe],capture_output=True);assert (p.returncode,p.stdout,p.stderr)==(0,b'ELBC',b''),p
            print(flavor,revision,'coalesce native/recovery passed',flush=True)
    finally:resident.stdin.close();resident.wait()
    for i,(prefix,expr,error) in enumerate([
        ('','1 ?? 2','nullable carrier'),('','null ?? 2','nullable carrier'),
        (local('n','nullable<int>','null'),v+'n ?? 1.0','exact payload'),
        (local('n','nullable<int>','null'),v+'n ?? '+v+'n','exact payload'),
        (local('n','nullable<int>','1'),v+'n ?? missing()','Unknown'),
        (local('n','result<int>','1'),v+'n ?? 2','nullable carrier'),
        (local('n','nullable<int>','null'),v+'n ??= 2','Expected'),
    ]):
        bad=work/f'{flavor}-bad-{i}';write_app(bad,flavor,{'main':prefix+s['infer']('x',expr)+'return 8;'})
        got=fresh(bad,work/f'{flavor}-bad-out-{i}');assert error.lower() in got.get('error','').lower(),got


if __name__=='__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-coalesce-') as temporary:
        work=Path(temporary)
        import argparse
        parser = argparse.ArgumentParser(description=__doc__)
        parser.add_argument('--quick', action='store_true')
        if parser.parse_args().quick:
            prove(work, 'php', {}, quick=True)
        else:
            libraries={cc:runtime(work,cc) for cc in ['clang++','g++']}
            for flavor in ['cpp','php','js']:prove(work,flavor,libraries)

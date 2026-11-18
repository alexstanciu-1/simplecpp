"""Common application vector/hash references: slot mutation, copies, aliasing and recovery."""
import argparse
import json
import tempfile
from pathlib import Path
from app_namespace_native import artifacts, invoke, service
from application_vectors_native import measure
from classes_native import fixture
from extends_native import build, runtime, source_edit
from inline_adapters_native import incremental_native_build

ROOT=Path(__file__).resolve().parents[2]


def prove(work, flavor, libraries):
    s=fixture(flavor);v,a=s['v'],s['access']
    def fn(name,params,body,result='void'):
        return s['function'](name,result,params,body)
    def ref(name,t):return ('&'+name,t)
    def empty(name,t):
        return {'php':f'${name} {t} = [];','cpp':f'{t} {name} = [];','js':f'let {name}:{t} = [];'}[flavor]
    # PHP/C++ put & immediately before the variable sigil.
    def source(text):return text.replace('$&','&$')
    types=s['record']('Node',s['field']('x','int','3')).replace('struct ','class ',1)
    types+=s['record']('Point',s['field']('x','int','7')+s['field']('text','string','"heap-backed reference payload"')+s['field']('owner','Node'))
    api='';main='';methods='';expected=b''
    for n,t,k in [('v','vector<Point>','0'),('h','hash<Point,string>','"k"'),('i','hash<Point,int>','0')]:
        slot=f'{v}items[{k}]';put=f'{v}items[]' if n=='v' else slot
        api+=fn('make'+n,[],empty('items',t)+s['local']('p','Point')+f'{v}p{a}owner=new Node();{put}={v}p;return {v}items;',t)
        api+=fn('mutate'+n,[ref('items',t)],s['infer']('p',slot)+f'{v}p{a}x=9;{v}p{a}text="changed";{v}p{a}owner{a}x=5;{slot}={v}p;')
        api+=fn('forward'+n,[ref('items',t)],f'mutate{n}({v}items);')
        api+=fn('isolate'+n,[('items',t)],f'forward{n}({v}items);')
        api+=fn('replace'+n,[ref('items',t)],f'{v}items=make{n}();')
        api+=fn('alias'+n,[ref('items',t),ref('other',t)],f'replace{n}({v}items);mutate{n}({v}other);')
        m=fn('reset'+n,[ref('items',t)],f'replace{n}({v}items);')
        methods+=('public '+m if flavor=='php' else m.replace('function ','') if flavor=='js' else m)
        main+=s['infer']('items'+n,'make'+n+'()')+s['infer']('copy'+n,v+'items'+n)
        observations=[]
        def observe(name):
            local='p'+n+str(len(observations));observations.append(local)
            return s['infer'](local,v+name+'['+k+']')+f'echo {v}{local}{a}x,":",{v}{local}{a}owner{a}x,";";'
        main+=f'isolate{n}({v}items{n});'+observe('items'+n)
        main+=f'forward{n}({v}items{n});'+observe('items'+n)+observe('copy'+n)
        main+=f'alias{n}({v}items{n},{v}items{n});'+observe('items'+n)
        main+=f'{v}worker{a}reset{n}({v}items{n});'+observe('items'+n)
        expected+=b'7:5;9:5;7:5;9:5;7:3;'
    types+=s['record']('Worker',methods)+s['record']('Holder',s['field']('items','vector<Point>'))
    main=s['infer']('worker','new Worker()')+main+'return 8;'
    app=work/flavor;out=work/(flavor+'-out');app.mkdir()
    source_edit(app/'module.json',json.dumps({'entry':'main.'+flavor}))
    for name,text in dict(types=types,api=api,main=main).items():source_edit(app/(name+'.'+flavor),source(text))
    edits=[(None,None,expected),(None,None,expected),
        ('api',api.replace('x=9','x=11'),expected.replace(b'9:',b'11:')),('api',api,expected),
        ('types',None,None),(None,None,None),('types',types,expected),
        ('api',api.replace('vector<Point>','vector<Node>'),None),('api',api,expected),
        ('main',main.replace(f'forwardv({v}itemsv)', 'forwardv(makev())'),None),('main',main,expected),
        ('main',s['infer']('holder','new Holder()')+f'forwardv({v}holder{a}items);return 8;',None),('main',main,expected),
        ('api',api.replace(fn('forwardv',[ref('items','vector<Point>')],f'mutatev({v}items);'),
                           fn('forwardv',[('items','vector<Point>')],f'mutatev({v}items);')),expected.replace(b'7:5;9:5;',b'7:5;7:5;',1)),('api',api,expected)]
    proc=service(app,out);times=[]
    try:
        for rev,(name,text,wanted) in enumerate(edits):
            before=artifacts(out)
            if name:
                path=app/(name+'.'+flavor)
                if text is None:path.unlink()
                else:source_edit(path,source(text))
            got=invoke(proc);fresh_out=work/f'{flavor}-fresh-{rev}';fresh=service(app,fresh_out)
            try:clean=invoke(fresh)
            finally:fresh.stdin.close();fresh.wait()
            if wanted is None:
                assert 'error' in got and got['error']==clean.get('error'),(flavor,rev,got,clean)
                assert artifacts(out)==before
                continue
            assert 'error' not in got and 'error' not in clean,(flavor,rev,got,clean)
            assert artifacts(out)==artifacts(fresh_out),(flavor,rev)
            for cc,lib in libraries.items():
                changed=incremental_native_build(out,work/(flavor+'-native-'+cc),cc,lib,wanted)
                if rev==1:assert not changed and got['preparation']['prepared']==0
                if rev in [0,6,8,14]:build(fresh_out,work/(flavor+'-fresh-native-'+cc),cc,lib,wanted)
            times.append({'revision':rev,'php_ms':got['php_ms'],'prepared':got['preparation']['prepared']})
            if rev==0:times[-1].update(measure(out,work/(flavor+'-timing'),wanted))
            print(flavor,rev,'application references passed',flush=True)
    finally:proc.stdin.close();proc.wait()
    (work/(flavor+'-timings.json')).write_text(json.dumps(times,indent=2)+'\n')


def main():
    p=argparse.ArgumentParser(description=__doc__);p.add_argument('--output',type=Path);p.add_argument('--runtime-dir',type=Path);args=p.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-app-refs-') as temporary:
        work=(args.output or Path(temporary)).resolve();work.mkdir(exist_ok=True)
        libs={cc:args.runtime_dir/cc/'runtime.a' if args.runtime_dir else runtime(work,cc,[ROOT/'runtime/generated/src/hash.cpp']) for cc in ['clang++','g++']}
        for flavor in ['php','cpp','js']:prove(work,flavor,libs)


if __name__=='__main__':main()

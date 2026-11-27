"""Audit boxed collection admission, with native proofs for common value/reference passing."""
import argparse
import json
import tempfile
from pathlib import Path
from app_namespace_native import invoke, service
from classes_native import fixture
from extends_native import source_edit
from application_vectors_native import measure


def prove(work,flavor):
    s=fixture(flavor);v,a=s['v'],s['access'];rows={}
    app=work/flavor;app.mkdir();source_edit(app/'module.json',json.dumps({'entry':'main.'+flavor}))
    for family in ['mixed','dynamic']:
        def decl(name,t,value):
            return {'php':f'${name} {t} = {value};','cpp':f'{t} {name} = {value};','js':f'let {name}:{t} = {value};'}[flavor]
        init=decl('items',family,'[]')+f'{v}items["a"]=7;'
        params=[('items',family)]
        mutate=s['function']('mutate','void',[('&items',family)],f'{v}items["a"]=9;').replace('$&','&$')
        forward=s['function']('forward','void',[('&items',family)],f'mutate({v}items);').replace('$&','&$')
        isolate=s['function']('isolate','void',params,f'forward({v}items);')
        make=s['function']('make',family,[],init+f'return {v}items;')
        replace=s['function']('replace','void',[('&items',family)],f'{v}items=make();').replace('$&','&$')
        copy=s['function']('copy',family,params,f'return {v}items;')
        method=s['function']('edit','void',[('&items',family)],f'forward({v}items);').replace('$&','&$')
        method='public '+method if flavor=='php' else method.replace('function ','') if flavor=='js' else method
        worker=s['record']('Worker',method)
        code=mutate+forward+isolate+make+replace+copy+worker+init+decl('saved',family,v+'items')
        code+=f'isolate({v}items);echo (int){v}items["a"];forward({v}items);echo (int){v}items["a"];'
        code+=f'replace({v}items);echo (int){v}items["a"],(int){v}saved["a"];'
        code+=decl('other',family,f'copy({v}items)')+f'{v}other["a"]=5;echo (int){v}items["a"];'
        code+=s['infer']('worker','new Worker()')+f'{v}worker{a}edit({v}items);echo (int){v}items["a"];return 8;'
        source_edit(app/('main.'+flavor),code);out=work/(flavor+'-'+family);proc=service(app,out)
        try:got=invoke(proc)
        finally:proc.stdin.close();proc.wait()
        assert 'error' not in got,(flavor,family,got)
        wanted=b'797779' if family=='mixed' else b'997959'
        timing=measure(out,work/(flavor+'-'+family+'-native'),wanted)
        rows[family]={'value/reference functions and methods':'native passed','timing':timing}
        probes={
            'append':init+f'{v}items[]=8;return 8;',
            'indexed compound':init+f'{v}items["a"]+=2;return 8;',
            'indexed update':init+f'{v}items["a"]++;return 8;',
            'count':init+f'echo count({v}items);return 8;',
            'empty':init+f'if (empty({v}items)) {{return 1;}}return 8;',
            'clear':init+f'{v}items{a}clear();return 8;',
            'has':init+f'if ({v}items{a}has("a")) {{return 8;}}return 1;',
            'remove':init+f'{v}items{a}remove("a");return 8;',
            'instance field':s['record']('Holder',s['field']('items',family))+s['local']('holder','Holder')+'return 8;',
            'two-key write':init+f'{v}items["a"][0]=8;return 8;',
            'two-key read':init+f'return (int){v}items["a"][0];',
            'two-key update':init+f'{v}items["a"][0]++;return 8;',
            'two-key compound':init+f'{v}items["a"][0]+=1;return 8;',
            'deep compound':init+f'{v}items["a"][0][1]+=8;return 8;',
            'deep append':init+f'{v}items["a"][0][]=8;return 8;',
            'nested RHS read':init+f'{v}items["a"][0][1]={v}items["b"][0];return 8;',
            'three-key write':init+f'{v}items["a"][0][1]=8;return 8;',
            'keyed foreach':init+f'foreach ({v}items as {v}key => {v}value) {{echo (int){v}value;}}return 8;',
            'temporary reference':mutate+make+'mutate(make());return 8;',
            'family mismatch':mutate+decl('other','dynamic' if family=='mixed' else 'mixed','[]')+f'mutate({v}other);return 8;',
        }
        for feature,code in probes.items():
            source_edit(app/('main.'+flavor),code);proc=service(app,work/(flavor+'-'+family+'-probe'))
            try:result=invoke(proc)
            finally:proc.stdin.close();proc.wait()
            accepted='error' not in result
            nested_read = family == "dynamic" and feature in ("two-key read", "nested RHS read")
            assert accepted==(nested_read or feature in ['keyed foreach','count','empty','has','remove','clear','append','instance field','indexed compound','indexed update','two-key write','two-key update','two-key compound','three-key write','deep append','deep compound']),(flavor,family,feature,result)
            rows[family][feature]='accepted' if accepted else result['error'].split(': ',1)[-1]
    return rows


if __name__=='__main__':
    parser=argparse.ArgumentParser(description=__doc__);parser.add_argument('--output',type=Path);parser.add_argument('--quick',action='store_true');args=parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-boxed-coverage-') as temporary:
        work=(args.output or Path(temporary)).resolve();work.mkdir(exist_ok=True)
        results={f:prove(work,f) for f in (['php'] if args.quick else ['php','cpp','js'])}
        (work/'coverage.json').write_text(json.dumps(results,indent=2)+'\n')
        print(json.dumps(results,indent=2))

"""Application vector by-value signatures, cross-unit publication and incremental recovery."""
from source_suffix import source_suffix
import argparse
import json
import tempfile
from pathlib import Path

from app_namespace_native import artifacts, invoke, service
from application_vectors_native import measure
from classes_native import fixture
from extends_native import build, runtime, source_edit
from inline_adapters_native import incremental_native_build


def prove(work, flavor, libraries, collection='vector'):
    s = fixture(flavor)
    v, access, fn, infer = s['v'], s['access'], s['function'], s['infer']
    def method(name, result, params, body):
        text = fn(name, result, params, body)
        return 'public ' + text if flavor == 'php' else text.replace('function ', '') if flavor == 'js' else text
    def empty(name, element):
        return {'php': f'${name} vector<{element}> = [];', 'cpp': f'vector<{element}> {name} = [];',
                'js': f'let {name}:vector<{element}> = [];'}[flavor]
    modify = infer('p', v+'items[0]') + f'{v}p{access}x=9; {v}items[0]={v}p; return {v}items;'
    echo_method = method('copy', 'vector<Point>', [('items','vector<Point>')], f'return {v}items;')
    point = s['record']('Point', s['field']('x','int','7') + echo_method)
    node = s['record']('Node',s['field']('x','int','7')).replace('struct ','class ',1)
    worker = s['record']('Worker', method('update','vector<Point>', [('items','vector<Point>')], modify)
                       + method('owners','vector<Node>', [('items','vector<Node>')],
                                infer('n',v+'items[0]')+f'{v}n{access}x=13; return {v}items;')).replace('struct ','class ',1)
    api = fn('copy','vector<Point>', [('items','vector<Point>')],f'return {v}items;')
    api += fn('updated','vector<Point>', [('items','vector<Point>')], modify)
    api += fn('make','vector<Point>', [],empty('items','Point')+s['local']('p','Point')+f'{v}items[]={v}p; return {v}items;')
    main = infer('a','make()') + infer('b',f'updated(copy({v}a))') + infer('p',v+'a[0]')+infer('q',v+'b[0]')
    main += f'echo {v}p{access}x,":",{v}q{access}x,":";' + infer('w','new Worker()')
    main += infer('c',f'{v}w{access}update({v}p{access}copy({v}a))') + infer('r',v+'c[0]')
    main += f'echo {v}r{access}x,":";' + empty('owners','Node') + infer('n','new Node()')+f'{v}owners[]={v}n;'
    main += infer('owners2',f'{v}w{access}owners({v}owners)')+f'echo {v}n{access}x; return 8;'
    def source(text):
        if collection == 'vector':return text
        key = collection.split('-')[1]
        text=text.replace('vector<Point>',f'hash<Point,{key}>').replace('vector<Node>',f'hash<Node,{key}>')
        index='"item"' if key=='string' else '0'
        return text.replace('[0]', '['+index+']').replace('[]=', '['+index+']=')
    app, out = work/flavor, work/(flavor+'-out'); app.mkdir()
    source_edit(app/'module.json',json.dumps({'entry':'main.'+source_suffix(flavor)}))
    for name,text in {'point':point,'node':node,'worker':worker,'api':api,'main':main}.items():source_edit(app/(name+'.'+source_suffix(flavor)),source(text))
    expected=b'7:9:9:13'
    edits=[(None,None,expected), (None,None,expected),
           ('api',api.replace('x=9','x=11'),b'7:11:9:13'), ('api',api,expected),
           ('point',point.replace(s['field']('x','int','7'),s['field']('x','int','8')),b'8:9:9:13'),
           ('point',point,expected), ('point',None,None), (None,None,None), ('point',point,expected),
           ('api',api.replace('vector<Point>','vector<Node>'),None), ('api',api,expected),
           ('worker',worker.replace('vector<Point>','vector<Node>'),None), ('worker',worker,expected),
           ('worker',None,None), ('worker',worker,expected),
           ('point',point.replace(s['field']('x','int','7'),s['field']('x','int','7')+s['field']('text','string','"long enough heap-backed payload"')),expected),
           ('point',point,expected), ('namespace','acme::nested',expected), ('namespace',None,expected),
           ('namespace','App',expected)]
    if collection != 'vector':
        edits += [('main',main.replace('[0]','[false]'),None),('main',main,expected),('retire',None,b'')]
    proc=service(app,out);timings=[]
    try:
        for rev,(name,text,wanted) in enumerate(edits):
            before=artifacts(out)
            if name=='retire':
                for source_name in ['point','node','worker','api']:(app/(source_name+'.'+source_suffix(flavor))).unlink()
                source_edit(app/('main.'+source_suffix(flavor)),'return 8;')
            elif name=='namespace':source_edit(app/'module.json',json.dumps({'entry':'main.'+source_suffix(flavor),'cpp_namespace':text}))
            elif name:
                path=app/(name+'.'+source_suffix(flavor))
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
            if name=='retire':assert not any(b'// Concrete application collection;' in t for t in artifacts(out).values())
            assert artifacts(out)==artifacts(fresh_out),(flavor,rev,'fresh artifacts differ')
            for cc,lib in libraries.items():
                changed=incremental_native_build(out,work/(flavor+'-native-'+cc),cc,lib,wanted)
                if rev==1:assert not changed and got['preparation']['prepared']==0,got
                if rev in [0,8,10,12,14]:build(fresh_out,work/(flavor+'-fresh-native-'+cc),cc,lib,wanted)
            timings.append({'revision':rev,'php_ms':got['php_ms'],'prepared':got['preparation']['prepared']})
            if rev==0:timings[-1].update(measure(out,work/(flavor+'-timing'),wanted))
            print(collection,flavor,rev,'application collection signatures passed',flush=True)
    finally:proc.stdin.close();proc.wait()
    (work/(flavor+'-timings.json')).write_text(json.dumps(timings,indent=2)+'\n')


def main():
    p=argparse.ArgumentParser(description=__doc__);p.add_argument('--output',type=Path);p.add_argument('--runtime-dir',type=Path);args=p.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-vector-signatures-') as temporary:
        work=(args.output or Path(temporary)).resolve();work.mkdir(exist_ok=True)
        libraries={cc:args.runtime_dir/cc/'runtime.a' if args.runtime_dir else runtime(work,cc) for cc in ['clang++','g++']}
        for flavor in ['php','cpp','js']:prove(work,flavor,libraries)


if __name__=='__main__':main()

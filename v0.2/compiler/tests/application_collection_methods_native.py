"""Generated methods on application collections, receiver lifetime and incremental recovery."""
import argparse
import json
import re
import tempfile
from pathlib import Path
from app_namespace_native import artifacts, invoke, service
from application_vectors_native import measure
from classes_native import fixture
from collection_goal_native import revisions
from extends_native import source_edit


def typed_local(flavor, name, type_name, value):
    return {
        'php': '$' + f'{name} {type_name} = {value};',
        'cpp': f'{type_name} {name} = {value};',
        'js': f'let {name}:{type_name} = {value};',
    }[flavor]


def typed_collection_donor(work, flavor):
    """Exercise the typed equivalents of the donor's literal and indexed operations."""
    s = fixture(flavor)
    access = s['access']
    v = s['v']
    values, counts, ids = (v + name for name in ('values', 'counts', 'ids'))
    vector_type = 'vector<int>'
    vector = s['function'](
        'initialValues', vector_type, [],
        typed_local(flavor, 'values', vector_type, '[1, 2]') + f'return {values};',
    )
    main = (
        typed_local(flavor, 'emptyValues', vector_type, '[]')
        + typed_local(flavor, 'values', vector_type, 'initialValues()')
        + f'{values}[]=3;{values}[0]=9;'
        + f'echo count({v}emptyValues),":",{values}[0],":",{values}[1],":",{values}[2],":";'
        + typed_local(flavor, 'counts', 'hash<int,string>', '["1"=>0,"01"=>2]')
        + typed_local(flavor, 'ids', 'hash<string,int>', '[1=>"int"]')
        + f'if (!hash_contains({counts},"1") || {counts}["1"]!==0) {{return 2;}}'
        + f'if (!{counts}{access}remove("1") || {counts}{access}remove("1")) {{return 3;}}'
        + f'if (hash_contains({counts},"1") || count({counts})!==1) {{return 4;}}'
        + f'echo {counts}["01"],":",{ids}[1];return 8;'
    )
    expected = b'0:9:2:3:2:int'
    work.mkdir(exist_ok=True)
    revisions(work, flavor, dict(main=main, vector=vector), [
        (None, None, expected),
        (None, None, expected),
        ('vector', vector.replace('[1, 2]', '[1, 4]'), b'0:9:4:3:2:int'),
        ('main', main.replace(f'{counts}["01"]', f'{counts}[1]'), 'Hash key requires string'),
        ('main', main.replace(f'{ids}[1]', f'{ids}["1"]'), 'Hash key requires int64'),
        ('vector', None, None),
        ('main', main, None),
        ('vector', vector, expected),
    ])


def prove(work,flavor):
    s=fixture(flavor);v,a=s['v'],s['access']
    def decl(name,t,value):
        return typed_local(flavor, name, t, value)
    ctor={'php':'public function __construct(int $x) {$this->x=$x;}', 'cpp':'Point(int x) {this->x=x;}', 'js':'constructor(x:int) {this.x=x;}'}[flavor]
    point={'php':'$p Point(7);','cpp':'Point p(7);','js':'let p:Point(7);'}[flavor]
    types=s['record']('Point',s['field']('x','int','0')+s['field']('text','string','"owned heap-backed payload"')+ctor)
    types+=s['record']('Box',s['field']('items','vector<Point>')).replace('struct ','class ',1)
    api='';body='';expected=b''
    for name,t,k in [('v','vector<Point>','0'),('h','hash<Point,string>','"a\\0b"'),('i','hash<Point,int>','-2')]:
        insert=f'{v}items[]' if name=='v' else f'{v}items[{k}]'
        api+=s['function']('make'+name,t,[],decl('items',t,'[]')+point+f'{insert}={v}p;return {v}items;')
        x=v+name
        body+=decl(name,t,'make'+name+'()')+decl('copy'+name,t,x)
        body+=f'echo count({x}),{x}{a}has({k}),{x}{a}remove({k}),{x}{a}remove({k}),empty({x}),count({v}copy{name});'
        body+=f'{v}copy{name}{a}clear();{v}copy{name}{a}clear();echo empty({v}copy{name});'
        body+=f'make{name}(){a}clear();echo make{name}(){a}has({k});'
        expected+=b'11101111'
    api+=s['function']('reset','int',[('&box','Box')],f'{v}box=new Box();return 0;').replace('$&','&$')
    body+=s['infer']('box','new Box()')+f'{v}box{a}items=makev();'+s['infer']('old','weak('+v+'box)')
    body+=f'echo {v}box{a}items{a}remove(reset({v}box)),empty({v}box{a}items),lock({v}old)===null;'
    expected+=b'111'
    main=re.sub(r'echo ([^;]+);',lambda m:'echo '+','.join('(int)('+x+')' for x in m[1].split(','))+';',body)+'return 8;'
    app=work/flavor;app.mkdir();out=work/(flavor+'-out');source_edit(app/'module.json',json.dumps({'entry':'main.'+flavor}))
    for name,text in [('types',types),('api',api),('main',main)]:source_edit(app/(name+'.'+flavor),text)
    edits=[(None,None,expected),(None,None,expected),('api',api.replace('Point(7)','Point(8)'),expected),
           ('types',None,None),(None,None,None),('types',types,expected),
           ('main',main.replace('has(0)','has("bad")'),None),('main',main,expected),
           ('types',types.replace('owned heap-backed payload','changed layout payload'),expected),('types',types,expected)]
    proc=service(app,out);times=[]
    try:
        for rev,(name,text,wanted) in enumerate(edits):
            previous=artifacts(out)
            if name:
                path=app/(name+'.'+flavor)
                if text is None:path.unlink()
                else:source_edit(path,text)
            got=invoke(proc);fresh_out=work/f'{flavor}-fresh-{rev}';fresh=service(app,fresh_out)
            try:clean=invoke(fresh)
            finally:fresh.stdin.close();fresh.wait()
            if wanted is None:
                assert 'error' in got and got['error']==clean.get('error'),(flavor,rev,got,clean)
                assert artifacts(out)==previous
                continue
            assert 'error' not in got and 'error' not in clean,(flavor,rev,got,clean)
            assert artifacts(out)==artifacts(fresh_out)
            if rev==1:assert got['preparation']['prepared']==0
            row={'revision':rev,'php_ms':got['php_ms']}
            if rev!=1:row.update(measure(out,work/f'{flavor}-native-{rev}',wanted))
            times.append(row);print(flavor,rev,'application methods passed',flush=True)
    finally:proc.stdin.close();proc.wait()
    (work/(flavor+'-timings.json')).write_text(json.dumps(times,indent=2)+'\n')


if __name__=='__main__':
    p=argparse.ArgumentParser(description=__doc__);p.add_argument('--output',type=Path);args=p.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-app-methods-') as tmp:
        work=(args.output or Path(tmp)).resolve();work.mkdir(exist_ok=True)
        for flavor in ['php','cpp','js']:
            typed_collection_donor(work / 'typed-donor', flavor)
            prove(work,flavor)

"""Concrete vector/hash fields: empty initialization, copy/mutation and retained/fresh recovery."""
from source_suffix import source_suffix
import argparse
import json
import subprocess
import tempfile
from pathlib import Path
from app_namespace_native import artifacts, invoke, service
from application_vectors_native import measure
from classes_native import fixture
from extends_native import build, runtime, source_edit
from inline_adapters_native import incremental_native_build


def prove(work, flavor, libraries):
    s=fixture(flavor);v,a=s['v'],s['access']
    ctor={'php':'public function __construct(int $x) {$this->x=$x;}',
          'cpp':'Point(int x) {this->x=x;}', 'js':'constructor(x:int) {this.x=x;}'}[flavor]
    point=s['record']('Point',s['field']('x','int','0')+s['field']('text','string','"heap-backed field element"')+ctor)
    node=s['record']('Node',s['field']('x','int','3')).replace('struct ','class ',1)
    fields=''.join(s['field'](name,typ) for name,typ in [('items','vector<Point>'),('lookup','hash<Point,string>'),
                  ('numbers','hash<Point,int>'),('owners','vector<Node>'),('mapped','hash<Node,string>')])
    method=s['function']('fill','void',[('p','Point')],f'{v}this{a}items[]={v}p; {v}this{a}lookup["k"]={v}p;')
    if flavor=='php':method='public '+method
    elif flavor=='js':method=method.replace('function ','')
    bag=s['record']('Bag',fields+method)
    box=s['record']('Box',fields).replace('struct ','class ',1)
    wrap=s['record']('Wrap',s['field']('bag','Bag'))
    api=s['function']('copy','Wrap',[('value','Wrap')],f'return {v}value;')
    api+=s['function']('makeBox','Box',[], 'return new Box();')
    body=s['local']('w','Wrap')
    body+=f'if (count({v}w{a}bag{a}items) != 0 || count({v}w{a}bag{a}lookup) != 0) {{return 91;}}'
    body+={'php':'$p Point(7);','cpp':'Point p(7);','js':'let p:Point(7);'}[flavor]
    body+=f'{v}w{a}bag{a}fill({v}p); {v}w{a}bag{a}numbers[2]={v}p;'
    body+=s['infer']('n','new Node()')+f'{v}w{a}bag{a}owners[]={v}n; {v}w{a}bag{a}mapped["n"]={v}n;'
    body+=s['infer']('other',f'copy({v}w)')+s['infer']('q',f'{v}other{a}bag{a}items[0]')
    body+=f'{v}q{a}x=9; {v}q{a}text="changed"; {v}other{a}bag{a}items[0]={v}q; {v}other{a}bag{a}lookup["k"]={v}q;'
    body+=s['infer']('original',f'{v}w{a}bag{a}items[0]')+s['infer']('changed',f'{v}other{a}bag{a}lookup["k"]')
    body+=f'if ({v}original{a}text === "changed") {{return 92;}}'
    body+=s['infer']('shared',f'{v}other{a}bag{a}mapped["n"]')+f'{v}shared{a}x=11;'
    body+=s['infer']('owner',f'{v}w{a}bag{a}owners[0]')
    body+=s['infer']('b','makeBox()')+f'{v}b{a}items={v}w{a}bag{a}items; {v}b{a}lookup={v}other{a}bag{a}lookup;'
    body+=s['infer']('held',f'{v}b{a}lookup["k"]')
    body+=s['infer']('i','0')+f'while ({v}i<80) {{{v}b{a}items[]={v}p; {v}b{a}numbers[{v}i]={v}p; {v}i++;}}'
    body+=f'echo {v}original{a}x,":",{v}changed{a}x,":",{v}owner{a}x,":",{v}held{a}x,":",count({v}b{a}items),":",count({v}b{a}numbers); return 8;'
    expected=b'7:9:11:9:81:80'
    app=work/flavor;out=work/(flavor+'-out');app.mkdir()
    source_edit(app/'module.json',json.dumps({'entry':'main.'+source_suffix(flavor)}))
    for name,text in dict(point=point,node=node,bag=bag,box=box,wrap=wrap,api=api,main=body).items():source_edit(app/(name+'.'+source_suffix(flavor)),text)
    recursive=s['record']('Recursive',s['field']('children','vector<Recursive>'))
    mutual=s['record']('Left',s['field']('right','hash<Right,int>'))+s['record']('Right',s['field']('left','vector<Left>'))
    edits=[(None,None,expected),(None,None,expected),
           ('point',point.replace('x=x','x=x+1').replace('x=$x','x=$x+1'),b'8:9:11:9:81:80'),
           ('point',point,expected),('point',None,None),(None,None,None),('point',point,expected),
           ('bag',bag.replace('vector<Point>','vector<Node>'),None),('bag',bag,expected),
           ('point',point.replace(ctor,ctor+s['field']('extra','int','5')),expected),('point',point,expected),
           ('bad',recursive,None),('bad',mutual,None),('bad',None,expected),
           ('namespace','acme::fields',expected),('namespace',None,expected),('namespace','App',expected),
           ('main',body.replace(f'{v}w{a}bag{a}numbers[2]={v}p;',f'{v}w{a}bag{a}numbers[2]=new Node();'),None),
           ('main',body,expected)]
    proc=service(app,out);times=[]
    try:
        for rev,(name,text,wanted) in enumerate(edits):
            before=artifacts(out)
            if name=='namespace':source_edit(app/'module.json',json.dumps({'entry':'main.'+source_suffix(flavor),'cpp_namespace':text}))
            elif name:
                path=app/(name+'.'+source_suffix(flavor))
                if text is None:path.unlink(missing_ok=True)
                else:source_edit(path,text)
            got=invoke(proc);fresh_out=work/f'{flavor}-fresh-{rev}';fresh=service(app,fresh_out)
            try:clean=invoke(fresh)
            finally:fresh.stdin.close();fresh.wait()
            if wanted is None:
                assert 'error' in got and got['error']==clean.get('error'),(flavor,rev,got,clean)
                if name=='bad':assert 'Recursive concrete collection fields are deferred' in got['error'],got
                assert artifacts(out)==before
                continue
            assert 'error' not in got and 'error' not in clean,(flavor,rev,got,clean)
            assert artifacts(out)==artifacts(fresh_out),(flavor,rev,'fresh output differs')
            for cc,lib in libraries.items():
                changed=incremental_native_build(out,work/(flavor+'-native-'+cc),cc,lib,wanted)
                if rev==1:assert not changed and got['preparation']['prepared']==0,got
                if rev in [0,6,8,13,18]:build(fresh_out,work/(flavor+'-fresh-native-'+cc),cc,lib,wanted)
            times.append({'revision':rev,'php_ms':got['php_ms'],'prepared':got['preparation']['prepared']})
            if rev==0:
                times[-1].update(measure(out,work/(flavor+'-timing'),wanted))
                single=work/(flavor+'-single.source')
                single.write_text(''.join((app/(name+'.'+source_suffix(flavor))).read_text() for name in ['point','node','bag','box','wrap','api','main']))
                cpp=work/(flavor+'-single.cpp')
                code='require $argv[1]; $p=(new scpp\\compiler\\Source_Frontend())->prepare(new scpp\\compiler\\source_file("single",file_get_contents($argv[2]),scpp\\compiler\\flavor::from($argv[3])),new scpp\\compiler\\entry_target(31)); file_put_contents($argv[4],(new scpp\\compiler\\Cpp_Emitter())->emit($p));'
                root=Path(__file__).resolve().parents[2]
                subprocess.run(['php','-r',code,root/'compiler/boot.php',single,flavor,cpp],check=True)
                exe=work/(flavor+'-single')
                subprocess.run(['clang++','-std=c++23','-O2','-g0','-I',root/'runtime/generated/public',cpp,libraries['clang++'],'-o',exe],check=True,capture_output=True)
                run=subprocess.run([exe],capture_output=True);assert (run.returncode,run.stdout,run.stderr)==(8,wanted,b''),run
            print(flavor,rev,'application collection fields passed',flush=True)
    finally:proc.stdin.close();proc.wait()
    (work/(flavor+'-timings.json')).write_text(json.dumps(times,indent=2)+'\n')


def retirement(app, out):
    proc=service(app,out)
    try:
        assert 'error' not in invoke(proc)
        entry=json.loads((app/'module.json').read_text())['entry']
        for path in app.iterdir():
            if path.suffix in ['.phs','.cpp','.js'] and path.name!=entry:path.unlink()
        source_edit(app/entry,'return 8;')
        result=invoke(proc);assert 'error' not in result,result
        assert not any(p.suffix=='.hpp' for p in artifacts(out)),artifacts(out).keys()
        fresh=service(app,out.with_name(out.name+'-retired-fresh'))
        try:assert 'error' not in invoke(fresh)
        finally:fresh.stdin.close();fresh.wait()
        assert artifacts(out)==artifacts(out.with_name(out.name+'-retired-fresh'))
    finally:proc.stdin.close();proc.wait()


def main():
    p=argparse.ArgumentParser(description=__doc__);p.add_argument('--output',type=Path);p.add_argument('--runtime-dir',type=Path);args=p.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-application-collection-fields-') as temporary:
        work=(args.output or Path(temporary)).resolve();work.mkdir(exist_ok=True)
        libraries={cc:args.runtime_dir/cc/'runtime.a' if args.runtime_dir else runtime(work,cc,[Path(__file__).resolve().parents[2]/'runtime/generated/src/hash.cpp']) for cc in ['clang++','g++']}
        for flavor in ['php','cpp','js']:
            prove(work,flavor,libraries)
            retirement(work/flavor,work/(flavor+'-out'))


if __name__=='__main__':main()

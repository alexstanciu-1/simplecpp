"""Application vector/hash traversal and construction-aware output lifetime, including suspended loops."""
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

ROOT=Path(__file__).resolve().parents[2]


def prove(work,flavor,libraries):
    s=fixture(flavor);v,a=s['v'],s['access']
    def empty(name,t):
        return {'php':f'${name} {t} = [];','cpp':f'{t} {name} = [];','js':f'let {name}:{t} = [];'}[flavor]
    def point(name,value):
        return {'php':f'${name} Point({value});','cpp':f'Point {name}({value});','js':f'let {name}:Point({value});'}[flavor]
    def loop(source,body,key=False,value='item'):
        return f'foreach ({source} as '+(v+'key => ' if key else '')+v+value+') {'+body+'}'
    ctor={'php':'public function __construct(int $x) {$this->x=$x;}', 'cpp':'Point(int x) {this->x=x;}', 'js':'constructor(x:int) {this.x=x;}'}[flavor]
    noisy={'php':'public function __construct() {echo "BAD";}', 'cpp':'Noisy() {echo "BAD";}', 'js':'constructor() {echo "BAD";}'}[flavor]
    types=s['record']('Point',s['field']('x','int','0')+s['field']('text','string','"heap-backed traversal payload"')+ctor)
    types+=s['record']('Noisy',noisy)+s['record']('Node',s['field']('x','int','3')).replace('struct ','class ',1)
    types+=s['record']('Box',s['field']('items','vector<Point>')+s['field']('table','hash<Point,string>'))
    make=s['function']('make','vector<Point>',[],empty('items','vector<Point>')+point('p','7')+f'{v}items[]={v}p; {v}p{a}x=8; {v}items[]={v}p; return {v}items;')
    table=s['function']('table','hash<Point,string>',[],empty('items','hash<Point,string>')+point('p','7')+f'{v}items["a\\0b"]={v}p; {v}p{a}x=8; {v}items["c"]={v}p; return {v}items;')
    vacant=s['function']('vacant','vector<Point>',[],empty('items','vector<Point>')+f'return {v}items;')
    vacant+=s['function']('vacantHash','hash<Point,string>',[],empty('items','hash<Point,string>')+f'return {v}items;')
    producers=''
    for name,t in [('stream','vector<Point>'),('mapped','hash<Point,string>')]:
        producers+=s['function'](name,'Iterator<int>',[('items',t)],loop(v+'items',f'yield {v}item{a}x;'))
        producers+=s['function'](name+'Existing','Iterator<int>',[('items',t)],point('item','99')+loop(v+'items',f'yield {v}item{a}x;',value='item')+f'yield {v}item{a}x;')
    producers+=s['function']('owned','Iterator<int>',[('box','shared<Box>')],loop(v+'box'+a+'items',f'yield {v}item{a}x; {v}box=null;'))
    body=empty('quiet','vector<Noisy>')+loop(v+'quiet','return 90;')+empty('quietHash','hash<Noisy,string>')+loop(v+'quietHash','return 91;')
    body+=point('item','99')+loop('vacant()',f'{v}item{a}x=0;')+f'echo {v}item{a}x,":";'
    body+=s['infer']('items','make()')+loop(v+'items',f'echo {v}key,":",{v}value{a}x,";"; {v}value{a}x=90; {v}value{a}text="changed";',True,'value')
    body+=s['infer']('first',v+'items[0]')+f'if ({v}first{a}text === "changed") {{return 92;}} echo {v}first{a}x,":";'
    body+=s['infer']('hash','table()')+loop(v+'hash',f'echo strlen({v}key),":",{v}value{a}x,";";',True,'value')
    body+=empty('ints','hash<Point,int>')+f'{v}ints[-2]={v}first;'+loop(v+'ints',f'echo {v}key,":",{v}value{a}x,";";',True,'value')
    body+=s['infer']('seen','0')+point('extra','20')
    body+=loop(v+'items',f'{v}seen++; {v}items[]={v}extra;',value='value')+f'echo {v}seen,":";'
    body+=loop(v+'items',f'{v}items=vacant(); echo {v}value{a}x,":";',value='value')
    body+=loop(v+'hash',f'{v}hash=vacantHash(); echo {v}value{a}x,":";',value='value')
    body+=empty('owners','hash<Node,int>')+s['infer']('n','new Node()')+f'{v}owners[1]={v}n;'
    body+=loop(v+'owners',f'{v}value{a}x=11;',value='value')+f'echo {v}n{a}x,":";'
    body+=s['infer']('box','new Box()')+f'{v}box{a}items=make();'
    body+=loop(v+'box'+a+'items',f'{v}box=new Box(); echo {v}value{a}x;',value='value')+'echo ":";'
    for expr in ['stream(make())','mapped(table())','streamExisting(make())','mappedExisting(table())']:
        body+=loop(expr,f'echo {v}number;',value='number')+'echo ":";'
    body+=f'{v}box{a}items=make();'+loop('owned('+v+'box)',f'echo {v}number;',value='number')+'return 8;'
    expected=b'99:0:7;1:8;7:3:7;1:8;-2:7;2:7:7:11:78:78:78:788:788:78'
    app=work/flavor;out=work/(flavor+'-out');app.mkdir()
    source_edit(app/'module.json',json.dumps({'entry':'main.'+source_suffix(flavor)}))
    for name,text in dict(types=types,factory=make+table+vacant,producer=producers,main=body).items():source_edit(app/(name+'.'+source_suffix(flavor)),text)
    edits=[(None,None,expected),(None,None,expected),
           ('producer',producers.replace(f'yield {v}item{a}x;',f'yield {v}item{a}x+1;'),expected.replace(b':78:78:788:788:78',b':89:89:899:899:89')),
           ('producer',producers,expected),('types',None,None),(None,None,None),('types',types,expected),
           ('types',types.replace(s['field']('x','int','0'),s['field']('x','int','0')+s['field']('extra','string','"extra heap-backed layout"')),expected),
           ('types',types,expected),('factory',(make+table+vacant).replace('vector<Point>','vector<Node>'),None),('factory',make+table+vacant,expected),
           ('producer',None,None),('producer',producers,expected),('namespace','acme::walk',expected),('namespace',None,expected),('namespace','App',expected)]
    proc=service(app,out);times=[]
    try:
        for rev,(name,text,wanted) in enumerate(edits):
            before=artifacts(out)
            if name=='namespace':source_edit(app/'module.json',json.dumps({'entry':'main.'+source_suffix(flavor),'cpp_namespace':text}))
            elif name:
                path=app/(name+'.'+source_suffix(flavor))
                if text is None:path.unlink()
                else:source_edit(path,text)
            got=invoke(proc);fresh_out=work/f'{flavor}-fresh-{rev}';fresh=service(app,fresh_out)
            try:clean=invoke(fresh)
            finally:fresh.stdin.close();fresh.wait()
            if wanted is None:
                assert 'error' in got and got['error']==clean.get('error'),(flavor,rev,got,clean)
                assert artifacts(out)==before
                continue
            assert 'error' not in got and 'error' not in clean,(flavor,rev,got,clean)
            assert artifacts(out)==artifacts(fresh_out),(flavor,rev,'fresh mismatch')
            for cc,lib in libraries.items():
                changed=incremental_native_build(out,work/(flavor+'-native-'+cc),cc,lib,wanted)
                if rev==1:assert not changed and got['preparation']['prepared']==0,got
                if rev in [0,6,10,12]:build(fresh_out,work/(flavor+'-fresh-native-'+cc),cc,lib,wanted)
            times.append({'revision':rev,'php_ms':got['php_ms'],'prepared':got['preparation']['prepared']})
            if rev==0:times[-1].update(measure(out,work/(flavor+'-timing'),wanted))
            print(flavor,rev,'application traversal passed',flush=True)
    finally:proc.stdin.close();proc.wait()
    (work/(flavor+'-timings.json')).write_text(json.dumps(times,indent=2)+'\n')
    single=work/(flavor+'-single.source');single.write_text(types+make+table+vacant+producers+body);cpp=work/(flavor+'-single.cpp')
    code='require $argv[1]; $p=(new scpp\\compiler\\Source_Frontend())->prepare(new scpp\\compiler\\source_file("single",file_get_contents($argv[2]),scpp\\compiler\\flavor::from($argv[3])),new scpp\\compiler\\entry_target(31)); file_put_contents($argv[4],(new scpp\\compiler\\Cpp_Emitter())->emit($p));'
    subprocess.run(['php','-r',code,ROOT/'compiler/boot.php',single,flavor,cpp],check=True)
    exe=work/(flavor+'-single');subprocess.run(['clang++','-std=c++23','-O1','-g0','-I',ROOT/'runtime/generated/public',cpp,libraries['clang++'],'-o',exe],check=True,capture_output=True)
    run=subprocess.run([exe],capture_output=True);assert (run.returncode,run.stdout,run.stderr)==(8,expected,b''),run


def lifetime_probe(work, flavor, libraries, sanitize=False):
    s=fixture(flavor);v,a=s['v'],s['access'];app=work/flavor;out=work/(flavor+'-out')
    path=app/('producer.'+source_suffix(flavor))
    if 'failing(' not in path.read_text(): source_edit(path,path.read_text()+s['function']('failing','Iterator<int>',[('box','shared<Box>'),('zero','int')],
        f'foreach ({v}box{a}items as {v}value) {{yield {v}value{a}x; yield 1/{v}zero;}}'))
    proc=service(app,out)
    try:result=invoke(proc);assert 'error' not in result,result
    finally:proc.stdin.close();proc.wait()
    producer=next(p for p in (out/'units').glob('*.cpp') if 'failing_cursor::next' in p.read_text())
    source=work/(flavor+'-lifetime.cpp')
    source.write_text('#include "'+str(producer)+'"\n#include <cassert>\n#include <exception>\n'+r"""
namespace App { decltype(owner_Box::make()->items) make_(); }
int main() {
    scpp::int64 value=0;
    auto box=App::owner_Box::make(); box->items=App::make_();
    App::weak_Box weak(box);
    {
        auto original=App::owned_(box); box={};
        assert(original.next(value) && value==7 && weak.lock());
        auto moved=static_cast<App::owned_cursor&&>(original);
        assert(!original.next(value));
        assert(moved.next(value) && value==8);
        moved.close(); assert(!weak.lock());
    }
    box=App::owner_Box::make(); box->items=App::make_(); weak=App::weak_Box(box);
    {
        auto early=App::owned_(box); box={}; assert(early.next(value));
    }
    assert(!weak.lock());
    box=App::owner_Box::make(); box->items=App::make_(); weak=App::weak_Box(box);
    {
        auto failing=App::failing_(box,0); box={}; assert(failing.next(value));
        try { failing.next(value); assert(false); } catch (const std::exception&) {}
        assert(!weak.lock() && !failing.next(value));
    }
}
""")
    units=[out/u for u in json.loads((out/'manifest.json').read_text())['translation_units'] if u!='entry.cpp' and out/u!=producer]
    for cc,lib in libraries.items():
        flags=['-fsanitize=address,undefined','-fno-omit-frame-pointer'] if sanitize and cc=='clang++' else []
        exe=work/(flavor+'-lifetime-'+cc)
        built=subprocess.run([cc,'-std=c++23','-O1','-g0',*flags,'-I',ROOT/'runtime/generated/public',*units,source,lib,'-o',exe],capture_output=True,text=True)
        assert built.returncode==0,built.stderr
        run=subprocess.run([exe],capture_output=True);assert (run.returncode,run.stdout,run.stderr)==(0,b'',b''),run
    print(flavor,'cursor move, early close and exception cleanup passed',flush=True)


def main():
    p=argparse.ArgumentParser(description=__doc__);p.add_argument('--output',type=Path);p.add_argument('--runtime-dir',type=Path);args=p.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-app-traversal-') as temporary:
        work=(args.output or Path(temporary)).resolve();work.mkdir(exist_ok=True)
        libs={cc:args.runtime_dir/cc/'runtime.a' if args.runtime_dir else runtime(work,cc,[ROOT/'runtime/generated/src/hash.cpp']) for cc in ['clang++','g++']}
        for flavor in ['php','cpp','js']:
            prove(work,flavor,libs)
            lifetime_probe(work,flavor,libs)


if __name__=='__main__':main()

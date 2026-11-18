"""Application vector recipe reuse, value/owner copies and incremental/fresh equivalence."""
import argparse
import json
import subprocess
import tempfile
import time
from pathlib import Path

from app_namespace_native import artifacts, invoke, service
from classes_native import fixture
from extends_native import runtime, source_edit, build
from inline_adapters_native import incremental_native_build

ROOT = Path(__file__).resolve().parents[2]


def vector_files(output):
    return {p: text for p, text in artifacts(output).items() if b'// Concrete application vector;' in text}


def prove(work, flavor, libraries, memcheck):
    s = fixture(flavor)
    v, access = s['v'], s['access']
    def decl(name, typ, value):
        return {'php': f'${name} {typ} = {value};', 'cpp': f'{typ} {name} = {value};',
                'js': f'let {name}:{typ} = {value};'}[flavor]
    def klass(name, body):
        return s['record'](name, body).replace('struct ', 'class ', 1)
    ctor = {'php': 'public function __construct(int $n) {$this->x=$n;}',
            'cpp': 'Point(int n) {this->x=n;}', 'js': 'constructor(n:int) {this.x=n;}'}[flavor]
    node = klass('Node', s['field']('x', 'int', '7'))
    leaf = s['record']('Leaf', '', 'Node').replace('struct ', 'class ', 1)
    point = s['record']('Point', s['field']('x', 'int', '0') + s['field']('text', 'string', '"heap-backed string value for a concrete element"')
                       + s['field']('node', 'Node') + ctor)
    factory = s['function']('makePoint', 'Point', [], {'php':'$r Point(7); return $r;', 'cpp':'Point r(7); return r;', 'js':'let r:Point(7); return r;'}[flavor])
    body = decl('a', 'vector<Point>', '[]') + s['infer']('p', 'makePoint()')
    body += f'{v}p{access}node=new Node(); {v}a[]={v}p;'
    body += decl('typed_copy','vector<Point>',v+'a') + s['infer']('b', v+'a') + s['infer']('q', v+'b[0]')
    body += f'{v}q{access}x=9; {v}q{access}text="changed"; {v}q{access}node{access}x=11; {v}b[0]={v}q;'
    body += s['infer']('original',v+'a[0]')
    body += f'echo {v}original{access}x, ":", {v}q{access}x, ":", {v}original{access}node{access}x, ":";'
    body += f'if ({v}original{access}text === "changed") {{return 2;}}'
    body += s['infer']('i','0') + f'while ({v}i < 100) {{{v}a[]={v}p; {v}i++;}}'
    body += f'echo count({v}a),":"; {v}a={v}a; {v}b={v}a;'
    body += decl('owners','vector<Node>','[]') + f'{v}owners[]={v}p{access}node;'
    body += s['infer']('owners2',v+'owners') + s['infer']('n',v+'owners2[0]')
    body += f'{v}n{access}x=13;' + s['infer']('n2',v+'owners[0]') + f'echo {v}n2{access}x;'
    body += decl('views','vector<Node>','[]') + f'{v}views[]=new Leaf(); {v}views[0]=new Leaf(); {v}views={v}owners;'
    use = s['function']('exercise', 'int', [], body + 'return 0;')
    main = 'return 8 + exercise();'
    idle = s['function']('idle','int',[],'return 4;')
    app, out = work/flavor, work/(flavor+'-out'); app.mkdir()
    source_edit(app/'module.json',json.dumps({'entry':'main.'+flavor}))
    for name,text in {'node':node,'leaf':leaf,'point':point+factory,'use':use,'main':main,'idle':idle}.items():
        source_edit(app/(name+'.'+flavor),text)
    edits = [(None,None,b'7:9:11:101:13',None), (None,None,b'7:9:11:101:13',None),
             ('point',point+factory.replace('(7)','(8)'),b'8:9:11:101:13',None),
             ('point',point.replace(s['field']('x','int','0'),s['field']('x','int','0')+s['field']('extra','int','2'))+factory,b'7:9:11:101:13',None),
             ('point',None,None,'unknown'), (None,None,None,'unknown'), ('point',point+factory,b'7:9:11:101:13',None),
             ('use',use.replace(f'{v}a[]={v}p;',f'{v}a[]=new Node();',1),None,'conversion'),
             ('use',use,b'7:9:11:101:13',None),
             ('use',s['function']('exercise','int',[],'echo "retired"; return 0;'),b'retired',None),
             ('use',use,b'7:9:11:101:13',None), (None,None,b'7:9:11:101:13',None)]
    edits += [('namespace','acme::collections',b'7:9:11:101:13',None), ('namespace',None,b'7:9:11:101:13',None),
              ('namespace','App',b'7:9:11:101:13',None),
              ('other',s['function']('other','void',[],decl('empty','vector<Point>','[]')),b'7:9:11:101:13',None),
              ('use',s['function']('exercise','int',[],'echo "retired"; return 0;'),b'retired',None),
              ('other',None,b'retired',None),('use',use,b'7:9:11:101:13',None)]
    signature=s['function']('rejected','void',[('items','vector<Point>')],'')
    field=s['record']('Rejected',s['field']('items','vector<Point>'))
    edits += [('use',use+signature,None,'deferred'), ('use',use,b'7:9:11:101:13',None),
              ('use',use+field,None,'field'), ('use',use,b'7:9:11:101:13',None)]
    other_present=False
    measurements=[]
    resident=service(app,out)
    try:
        for revision,(name,text,expected,error) in enumerate(edits):
            before=artifacts(out)
            if name=='namespace':
                source_edit(app/'module.json',json.dumps({'entry':'main.'+flavor,'cpp_namespace':text}))
            elif name:
                if name=='other':other_present=text is not None
                path=app/(name+'.'+flavor)
                if text is None:path.unlink()
                else:source_edit(path,text)
            current=invoke(resident); fresh_out=work/f'{flavor}-fresh-{revision}'; fresh=service(app,fresh_out)
            try: comparison=invoke(fresh)
            finally:fresh.stdin.close();fresh.wait()
            if error:
                assert error in current.get('error','').lower(),(flavor,revision,current)
                assert current['error']==comparison.get('error'),(revision,current,comparison)
                assert artifacts(out)==before
                continue
            assert 'error' not in current and 'error' not in comparison,(flavor,revision,current,comparison)
            assert artifacts(out)==artifacts(fresh_out),(flavor,revision,'fresh mismatch')
            concrete=vector_files(out);assert len(concrete)==((1 if other_present else 0) if expected==b'retired' else 2),(revision,concrete.keys())
            for data in concrete.values():assert b'std::vector' not in data and b'template<' not in data
            for old in before.keys()-artifacts(out).keys():assert not (out/old).exists(),old
            if revision in [1,11]:assert current['preparation']['prepared']==0 and current['emitted_sources']==[],current
            for cc,library in libraries.items():
                changed=incremental_native_build(out,work/(flavor+'-native-'+cc),cc,library,expected)
                if revision in [1,11]:assert not changed,changed
                if revision in [0,6,8]:build(fresh_out,work/(flavor+'-fresh-'+cc),cc,library,expected)
            measurements.append({'revision':revision,'php_ms':current['php_ms'],'prepared':current['preparation']['prepared']})
            if revision==0:
                measurements[-1].update(measure(out,work/(flavor+'-timing'),expected))
                if memcheck:
                    units=[out/u for u in json.loads((out/'manifest.json').read_text())['translation_units']]
                    exe=work/(flavor+'-sanitized')
                    command=['clang++','-std=c++23','-O1','-g0','-fsanitize=address,undefined','-fno-omit-frame-pointer',
                             '-I',ROOT/'runtime/generated/public',*units,libraries['clang++'],'-o',exe]
                    run=subprocess.run(list(map(str,command)),capture_output=True,text=True);assert run.returncode==0,run.stderr
                    run=subprocess.run([exe],capture_output=True);assert (run.returncode,run.stdout,run.stderr)==(8,expected,b''),run
            print(flavor,revision,'application vector incremental/native passed',flush=True)
    finally:resident.stdin.close();resident.wait()
    (work/(flavor+'-timings.json')).write_text(json.dumps(measurements,indent=2)+'\n')


def measure(output, destination, expected):
    from native_build import write_build
    destination.mkdir()
    units=[output/u for u in json.loads((output/'manifest.json').read_text())['translation_units']]
    command=write_build(destination,units);command[2]='8'
    graph=destination/'build.ninja';graph.write_text(graph.read_text().replace(' -MD ',' -ftime-trace -MD '))
    start=time.perf_counter();result=subprocess.run(command,cwd=destination,capture_output=True,text=True)
    elapsed=(time.perf_counter()-start)*1000;assert result.returncode==0,result.stdout+result.stderr
    run=subprocess.run([destination/'program'],capture_output=True);assert (run.returncode,run.stdout,run.stderr)==(8,expected,b''),run
    totals={}
    for path in destination.glob('unit-*.json'):
        for event in json.loads(path.read_text())['traceEvents']:
            if event['name'].startswith('Total '):totals[event['name']]=totals.get(event['name'],0)+event['dur']/1000
    link=[row.split('\t') for row in (destination/'.ninja_log').read_text().splitlines()[1:] if row.split('\t')[3]=='program'][-1]
    return {'native_ms':elapsed,'frontend_ms':totals.get('Total Frontend',0),'llvm_ms':totals.get('Total Backend',0),
            'optimizer_ms':totals.get('Total Optimizer',0),'codegen_ms':totals.get('Total CodeGenPasses',0),
            'link_ms':int(link[1])-int(link[0]),'configuration':'O2/g0, Ninja8, mold, no PCH/LTO, prebuilt libc++ runtime; direct Ninja qualification'}


def main():
    parser=argparse.ArgumentParser(description=__doc__);parser.add_argument('--output',type=Path);parser.add_argument('--memcheck',action='store_true');args=parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-application-vectors-') as temporary:
        work=(args.output or Path(temporary)).resolve();work.mkdir(exist_ok=True)
        libraries={cc:runtime(work,cc) for cc in ['clang++','g++']}
        for flavor in ['php','cpp','js']:prove(work,flavor,libraries,args.memcheck)


if __name__=='__main__':main()

"""Weak AST parents: retained roots, expiry and real compiler incremental reuse."""
from source_suffix import source_suffix
import argparse
import json
import subprocess
import tempfile
from pathlib import Path

from app_namespace_native import artifacts, invoke, service
from extends_native import runtime, source_edit
from interfaces_native import forms, fresh
from interface_access_native import verify_manifest
from inline_adapters_native import incremental_native_build
from parent_methods_native import write_app

ROOT=Path(__file__).resolve().parents[2]


def sources(flavor):
    s,evaluate,klass,method,interface,implementation=forms(flavor)
    v,a,fn=s['v'],s['access'],s['function']
    params={'cpp':'int begin,int end','php':'int $begin,int $end','js':'begin:int,end:int'}[flavor]
    range_method=method('setRange',f'{v}this{a}start={v}begin;{v}this{a}end={v}end;',result='void',params=params)
    node=klass('Node',s['field']('parent','weak<Node>')+s['field']('start','int','0')+
        s['field']('end','int','0')+s['field']('value','int','0')+range_method+evaluate('virtual',v+'this'+a+'value'))
    addition=implementation('Addition',s['field']('left','?Node')+s['field']('right','?Node')+
        evaluate('override',v+'this'+a+'left'+a+'evaluate(0) + '+v+'this'+a+'right'+a+'evaluate(0)'),'Node',('Evaluable',))
    snapshot=klass('Snapshot',s['field']('stamp','int','0')+s['field']('text','string','"1+2+3"'))
    root=klass('Root',s['field']('snapshot','?Snapshot'),'Addition')
    literal=klass('Literal','','Node')
    marker='Evaluable'+('.class' if flavor=='js' else '::class')
    api=fn('capability','int',[('n','Node')],f'if ({v}n instanceof Evaluable) {{'+s['infer']('cap',f'object_cast({v}n,{marker})')+f'return {v}cap{a}evaluate();}} return -1;')
    api+=fn('getParent','?Node',[('n','Node')],f'return lock({v}n{a}parent);')
    api+=fn('bindParent','void',[('child','Node'),('parent','Node')],f'{v}child{a}parent=weak({v}parent);')
    api+=fn('attach','void',[('parent','Addition'),('left','Node'),('right','Node')],
        f'{v}parent{a}left={v}left;{v}parent{a}right={v}right;bindParent({v}left,{v}parent);bindParent({v}right,{v}parent);')
    api+=fn('read','int',[('n','Node')],f'return {v}n{a}evaluate(0);')
    api+=fn('asNode','Node',[('n','Root')],f'return {v}n;')
    api+=fn('child','Node',[('n','Root')],f'return {v}n{a}right;')
    api+=fn('inner','Addition',[('n','Root')], 'return object_cast('+v+'n'+a+'left,Addition'+('.class' if flavor=='js' else '::class')+');')
    api+=fn('sourceOf','Snapshot',[('n','Root')],f'return {v}n{a}snapshot;')
    body=''
    for name,type_name in [('root','Root'),('inner','Addition'),('one','Literal'),('two','Literal'),('three','Literal'),('source','Snapshot')]:
        body+=s['infer'](name,'new '+type_name+'()')
    body+=f'{v}one{a}value=1;{v}two{a}value=2;{v}three{a}value=3;'
    for name,begin,end in [('root',0,5),('inner',0,3),('one',0,1),('two',2,3),('three',4,5)]:
        body+=f'{v}{name}{a}setRange({begin},{end});'
    body+=f'{v}source{a}stamp={v}stamp;{v}root{a}snapshot={v}source;attach({v}inner,{v}one,{v}two);attach({v}root,{v}inner,{v}three);return {v}root;'
    api+=fn('makeTree','Root',[('stamp','int')],body)
    return {'contract':interface('Evaluable'),'node':node,'addition':addition,'literal':literal,'snapshot':snapshot,'root':root,'api':api,
        'main':'echo read(makeTree(100)),capability(asNode(makeTree(100)));return 8;','idle':fn('idle','int',[],'return 7;')}


def native_lifetime(work, flavor, out, libraries, memcheck, *, flags=('-O1',)):
    units=[out/u for u in json.loads((out/'manifest.json').read_text())['translation_units'] if u!='entry.cpp']
    api=next(p for p in units if 'owner_Root makeTree_(' in p.read_text())
    probe=work/(flavor+'-lifetime.cpp')
    probe.write_text(f'#include "{api}"\n'+r'''
#include <cassert>
#include <stdexcept>
int main() {
 using namespace App;
 for(int cycle=0;cycle<32;++cycle) {
  // Two source slots; only one is replaced. Readers retain roots, never sessions.
  auto unchanged=makeTree_(100);auto current=makeTree_(200);
  auto reused=unchanged;auto old_reader=current;
  auto old_observer=weak_Root(current);auto unchanged_observer=weak_Root(unchanged);
  auto retained_child=child_(old_reader);auto child_observer=weak_Node(retained_child);
  auto source=sourceOf_(old_reader);auto source_observer=weak_Snapshot(source);
  assert(read_(asNode_(unchanged))==6 && !getParent_(asNode_(unchanged)));
  auto before=retained_child.ptr->count;
  auto parent=getParent_(retained_child);
  assert(parent.ptr==old_reader.ptr && parent->start==0 && parent->end==5);
  assert(capability_(parent)==6 && capability_(retained_child)==-1);
  assert(retained_child->start==4 && retained_child->end==5);
  parent={};assert(retained_child.ptr->count==before);
  current=makeTree_(300);
  assert(current.ptr!=old_reader.ptr && reused.ptr==unchanged.ptr);
  assert(child_(reused).ptr==child_(unchanged).ptr);
  assert(sourceOf_(reused).ptr==sourceOf_(unchanged).ptr);
  auto inner=inner_(old_reader);auto grandchild=inner->left;
  auto locked_inner=getParent_(grandchild);
  assert(locked_inner.ptr==inner.ptr);
  assert(getParent_(locked_inner).ptr==old_reader.ptr);
  assert(locked_inner->start==0 && locked_inner->end==3);
  assert(grandchild->start==0 && grandchild->end==1);
  assert(sourceOf_(current)->stamp==300 && sourceOf_(old_reader)->stamp==200);
  assert(capability_(asNode_(current))==6 && capability_(asNode_(old_reader))==6);
  // Source attachment creates live backlinks before the unpublished candidate fails.
  auto surviving_child=owner_Node::make();surviving_child->value=4;
  auto surviving_observer=weak_Node(surviving_child);
  auto current_identity=current.ptr;auto reused_identity=reused.ptr;
  weak_Addition abandoned;
  try {
   auto candidate=owner_Addition::make();
   attach_(candidate,surviving_child,owner_Node::make());
   assert(getParent_(surviving_child).ptr==candidate.ptr);
   abandoned=weak_Addition(candidate);throw std::runtime_error("candidate failure");
  } catch(const std::runtime_error&) {}
  assert(!abandoned.lock() && !getParent_(surviving_child));
  assert(surviving_observer.lock() && read_(surviving_child)==4);
  assert(current.ptr==current_identity && reused.ptr==reused_identity);
  assert(read_(asNode_(current))==6 && read_(asNode_(reused))==6);
  surviving_child={};assert(!surviving_observer.lock());
  old_reader={};assert(!old_observer.lock());
  assert(!getParent_(retained_child) && read_(retained_child)==3);
  assert(!getParent_(locked_inner)); // Holding a parent does not retain its ancestors.
  assert(getParent_(grandchild).ptr==locked_inner.ptr);
  inner={};locked_inner={};assert(!getParent_(grandchild));
  // A source snapshot outlives its tree without keeping that tree alive.
  assert(source->stamp==200 && source_observer.lock());
  source={};assert(!source_observer.lock());
  retained_child={};assert(!child_observer.lock());grandchild={};
  unchanged={};assert(unchanged_observer.lock());
  reused={};assert(!unchanged_observer.lock());
  // A locked parent independently retains its payload until the lock is released.
  auto child=child_(current);auto lock=getParent_(child);auto watch=weak_Node(lock);
  current={};assert(watch.lock() && getParent_(child));
  lock={};assert(!watch.lock() && !getParent_(child));
  child={};
 }
 // All observer handles have now gone: Valgrind also checks allocation release.
}
''')
    for cc,lib in libraries.items():
        exe=work/(flavor+'-lifetime-'+cc)
        r=subprocess.run([cc,'-std=c++23',*flags,'-Wall','-Wextra','-Werror','-I',ROOT/'runtime/generated/public',probe,*[u for u in units if u!=api],lib,'-o',exe],capture_output=True,text=True)
        assert r.returncode==0,r.stderr
        r=subprocess.run([exe],capture_output=True);assert (r.returncode,r.stdout,r.stderr)==(0,b'',b''),r
        if memcheck:
            r=subprocess.run(['valgrind','--leak-check=full','--errors-for-leak-kinds=all','--error-exitcode=99',exe],capture_output=True,text=True)
            assert r.returncode==0 and 'All heap blocks were freed' in r.stderr,r.stderr
    print(flavor,'weak parent and retained-root lifetime passed',flush=True)


def prove(work, flavor, libraries, memcheck):
    src=sources(flavor);app,out=work/flavor,work/(flavor+'-out');write_app(app,flavor,src)
    s,evaluate,klass,*_=forms(flavor)
    body=src['node'].replace('return '+s['v']+'this'+s['access']+'value;', 'return '+s['v']+'this'+s['access']+'value + 1;')
    assert body!=src['node']
    layout=src['node'].replace(s['field']('start','int','0'),s['field']('start','int','0')+s['field']('padding','int','7'))
    steps=[(None,None,None,b'66','initial'),(None,None,None,b'66','noop'),
        ('node',body,None,b'99','body'),('node',src['node'],None,b'66','body'),
        ('node',layout,None,b'66','layout'),('node',src['node'],None,b'66','layout'),
        ('literal',None,'requires a record',None,'invalid'),(None,None,'requires a record',None,'invalid'),
        ('literal',src['literal'],None,b'66','recover'),(None,None,None,b'66','noop')]
    resident=service(app,out)
    try:
        for i,(name,text,error,expected,kind) in enumerate(steps):
            before=artifacts(out);stamps={p:(out/p).stat().st_mtime_ns for p in before}
            if name:
                path=app/(name+'.'+source_suffix(flavor))
                if text is None:path.unlink()
                else:source_edit(path,text)
            r=invoke(resident);f=work/f'{flavor}-fresh-{i}';comparison=fresh(app,f)
            if error:
                assert error in r.get('error','').lower(),(i,r)
                assert r['error']==comparison.get('error') and artifacts(out)==before,(i,r,comparison)
                continue
            assert 'error' not in r and 'error' not in comparison,(i,r,comparison)
            now=artifacts(out);assert now==artifacts(f),(i,'fresh mismatch')
            manifest=verify_manifest(out,f);units={Path(p).stem:u for p,u in manifest['sources'].items()}
            if kind in ['body','layout']:
                for name in ['api','main']:
                    path=Path(units[name]);assert before[path]==now[path] and stamps[path]==(out/path).stat().st_mtime_ns,(i,name)
            for cc,lib in libraries.items():
                changed=incremental_native_build(out,work/(flavor+'-native-'+cc),cc,lib,expected)
                if kind=='noop':assert not changed and r['preparation']['prepared']==0 and r['emitted_sources']==[],r
                if kind=='body':assert changed=={units['node']},changed
                if kind=='layout':assert units['api'] in changed and units['main'] in changed,changed
                if i:assert units['idle'] not in changed,changed
            if kind in ['initial','layout','recover']:native_lifetime(work,flavor,out,libraries,memcheck)
            print(flavor,i,kind,'parent fixture incremental/native passed',flush=True)
    finally:resident.stdin.close();resident.wait()


if __name__=='__main__':
    parser=argparse.ArgumentParser(description=__doc__);parser.add_argument('--output',type=Path);parser.add_argument('--memcheck',action='store_true');parser.add_argument('--quick-runtime', type=Path)
    args=parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-parent-lifetime-') as temporary:
        work=(args.output or Path(temporary)).resolve();work.mkdir(exist_ok=True)
        if args.quick_runtime:
            libraries = {'clang++': args.quick_runtime}
            app, out = work/'php', work/'out'
            src = sources('php')
            write_app(app, 'php', src)
            resident = service(app, out)
            try:
                assert 'error' not in invoke(resident)
                original = artifacts(out)
                assert invoke(resident)['preparation']['prepared'] == 0
                (app/'literal.phs').unlink()
                assert 'requires a record' in invoke(resident)['error'].lower()
                assert artifacts(out) == original
                source_edit(app/'literal.phs', src['literal'])
                assert 'error' not in invoke(resident) and artifacts(out) == original
            finally:
                resident.stdin.close()
                resident.wait()
            native_lifetime(work, 'php', out, libraries, False, flags=('-O0', '-stdlib=libc++'))
        else:
            libraries={cc:runtime(work,cc) for cc in ['clang++','g++']}
            for flavor in ['cpp','php','js']:prove(work,flavor,libraries,args.memcheck)

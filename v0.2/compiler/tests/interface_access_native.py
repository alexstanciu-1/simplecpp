"""Interface capability checks: shared ownership, runtime overlap and incremental publication."""
import argparse
import json
import subprocess
import tempfile
from pathlib import Path

from app_namespace_native import artifacts, invoke, service
from extends_native import runtime, source_edit
from inline_adapters_native import adapter_files, incremental_native_build
from interfaces_native import forms, fresh
from object_access_native import syntax
from parent_methods_native import write_app

ROOT = Path(__file__).resolve().parents[2]


def verify_manifest(output, expected=None):
    """Publication lists exactly the live headers, including concrete adapters."""
    manifest=json.loads((output/'manifest.json').read_text())
    headers=manifest['headers']
    assert len(headers)==len(set(headers)),headers
    assert set(headers)=={str(path) for path in artifacts(output) if path.suffix=='.hpp'},headers
    if expected is not None:
        assert set(headers)==set(json.loads((expected/'manifest.json').read_text())['headers'])
    return manifest


def behavior(work, flavor, libraries, memcheck):
    s, evaluate, klass, method, interface, implementation = forms(flavor)
    cast = syntax(flavor)[3]
    v, access, fn = s['v'], s['access'], s['function']
    types = interface('Left')+interface('Right')+interface('Missing')+klass('Token')
    types += klass('Node', s['field']('x','int','8')+evaluate('virtual',v+'this'+access+'x + '+v+'delta'))
    types += implementation('Leaf',s['field']('hold','Token'),'Node',('Left','Right'))
    types += klass('Twig','','Leaf')+implementation('OnlyLeft','','Node',('Left',))
    # A class need not itself implement the interface to overlap it through a descendant.
    types += klass('Plain')+implementation('PlainLeaf',evaluate('override'),'Plain',('Left',))
    api = ''
    for name, source, target in [('toLeft','Node','Left'),('toRight','Left','Right'),
            ('toLeaf','Left','Leaf'),('toNode','Left','Node'),('same','Left','Left'),
            ('up','Leaf','Left'),('plain','Left','Plain')]:
        api += fn(name,target,[('n','?'+source)],'return '+cast(v+'n',target)+';')
    for name, source, target in [('testLeft','Node','Left'),('testRight','Left','Right'),
            ('testMissing','Left','Missing'),('testNode','Left','Node'),('testSame','Left','Left')]:
        api += fn(name,'bool',[('n','?'+source)],f'return {v}n instanceof {target};')
    api += fn('notRight','bool',[('n','?Left')],f'return !{v}n instanceof Right;')
    api += fn('produce','?Left',[('n','?Left')],f'echo "P";return {v}n;')
    api += fn('once','Right',[('n','?Left')],'return '+cast('produce('+v+'n)','Right')+';')
    api += fn('testOnce','bool',[('n','?Left')],f'return produce({v}n) instanceof Right;')
    api += fn('shorted','bool',[('n','?Left')],f'return {v}n instanceof Right && testOnce({v}n);')
    api += fn('make','Left',[],'return new Twig();')
    api += fn('makeMismatch','Left',[],'return new OnlyLeft();')
    api += fn('makePlain','Left',[],'return new PlainLeaf();')
    api += fn('temporary','Right',[],'return '+cast('make()','Right')+';')
    api += fn('read','int',[('n','Right')],f'return {v}n{access}evaluate();')
    main='echo read(toRight(make()));return 8;'
    app,out=work/(flavor+'-behavior'),work/(flavor+'-behavior-out')
    write_app(app,flavor,{'types':types,'api':api,'main':main})
    result=fresh(app,out);assert 'error' not in result,result
    adapters=adapter_files(out)
    assert any(b'dynamic_cast<' in text and b'const owner_Left& source' in text for text in adapters.values())
    assert all(b'template<' not in text and b'new ' not in text for text in adapters.values())
    units=[out/u for u in json.loads((out/'manifest.json').read_text())['translation_units'] if u!='entry.cpp']
    api_unit=next(p for p in units if 'owner_Right toRight_(' in p.read_text())
    probe=work/(flavor+'-ownership.cpp')
    probe.write_text(f'#include "{api_unit}"\n'+r'''
#include <cassert>
#include <cstring>
#include <stdexcept>
int main() {
 using namespace App;
 auto token=owner_Token::make();auto token_watch=weak_Token(token);
 auto leaf=owner_Leaf::make();leaf->hold=token;
 auto left=up_(leaf);auto right=toRight_(left);auto observer=weak_Right(right);
 assert(left.ptr==leaf.ptr && right.ptr==leaf.ptr);
 assert(static_cast<void*>(left.view)!=static_cast<void*>(right.view));
 assert(right.view==static_cast<scpp_records::Right*>(leaf.view));
 auto count=left.ptr->count;
 assert(testRight_(left) && testSame_(left) && !testMissing_(left) && testNode_(left));
 assert(!notRight_(left));assert(left.ptr->count==count);
 auto back=toLeaf_(left);assert(back.ptr==left.ptr && back.view==leaf.view);
 back->x=41;assert(read_(right)==42 && leaf->x==41);back={};
 assert(same_(left).ptr==left.ptr && toNode_(left).ptr==left.ptr);
 assert(toLeft_(toNode_(left)).ptr==left.ptr);
 assert(once_(left).ptr==left.ptr && testOnce_(left));
 auto mismatch=makeMismatch_();auto before=mismatch.ptr->count;
 for(int n=0;n<16;++n) {
  try {toRight_(mismatch);assert(false);}
  catch(const std::runtime_error& e){assert(std::strcmp(e.what(),"invalid_object_cast")==0);}
  assert(mismatch.ptr->count==before);
 }
 assert(!testRight_(mismatch) && notRight_(mismatch) && !shorted_(mismatch));
 try {once_(mismatch);assert(false);} catch(const std::runtime_error&) {}
 assert(!testOnce_(mismatch));
 assert(!testRight_({}) && !testSame_({}) && !testMissing_({}));
 try {once_({});assert(false);} catch(const std::runtime_error&) {}
 assert(!testOnce_({}));
 try {same_({});assert(false);} catch(const std::runtime_error&) {}
 try {up_({});assert(false);} catch(const std::runtime_error&) {}
 assert(read_(temporary_())==9);
 token={};leaf={};left={};assert(token_watch.lock() && observer.lock());
 right={};assert(!token_watch.lock() && !observer.lock());
 auto last=make_();auto watch=weak_Left(last);
 auto result=toRight_(static_cast<owner_Left&&>(last));assert(!last && watch.lock());
 result={};assert(!watch.lock());
 auto bad=makeMismatch_();auto bad_watch=weak_Left(bad);
 try {toRight_(static_cast<owner_Left&&>(bad));assert(false);} catch(const std::runtime_error&) {}
 assert(!bad && !bad_watch.lock());
 auto plain_left=makePlain_();
 assert(plain_(plain_left).ptr==plain_left.ptr);
}
''')
    facts=work/'facts.json';facts.write_text(json.dumps({'schema':1,'native_integers':{'int':{'signed':True,'digits':31}}}))
    combined=work/(flavor+'-standalone-source.'+flavor);combined.write_text(types+api+main)
    cpp=work/(flavor+'-standalone.cpp')
    result=subprocess.run(['php',ROOT/'compiler/main.php','cpp',flavor,combined,facts,cpp],capture_output=True,text=True)
    assert result.returncode==0,result.stderr
    for cc,library in libraries.items():
        incremental_native_build(out,work/(flavor+'-behavior-native-'+cc),cc,library,b'9')
        for path,others,label,code,stdout in [(probe,[u for u in units if u!=api_unit],'ownership',0,b'PPPPPP'),(cpp,[],'standalone',8,b'9')]:
            exe=work/(flavor+'-'+label+'-'+cc)
            result=subprocess.run([cc,'-std=c++23','-O1','-Wall','-Wextra','-Werror','-I',ROOT/'runtime/generated/public',path,*others,library,'-o',exe],capture_output=True,text=True)
            assert result.returncode==0,result.stderr
            result=subprocess.run([exe],capture_output=True);assert (result.returncode,result.stdout,result.stderr)==(code,stdout,b''),result
            if memcheck and label=='ownership':
                result=subprocess.run(['valgrind','--leak-check=full','--errors-for-leak-kinds=all','--error-exitcode=99',exe],capture_output=True,text=True)
                assert result.returncode==0 and 'All heap blocks were freed' in result.stderr,result.stderr
        failure=subprocess.run([cc,'-std=c++23','-fno-rtti','-I',ROOT/'runtime/generated/public','-fsyntax-only',cpp],capture_output=True,text=True)
        assert failure.returncode!=0 and 'require native RTTI' in failure.stderr,failure.stderr
    print(flavor,'interface access ownership, overlap, standalone and RTTI passed',flush=True)


def incremental(work, flavor, libraries):
    s,evaluate,klass,method,interface,implementation=forms(flavor)
    v,fn=s['v'],s['function']
    node=klass('Node',evaluate('virtual'))
    leaf=implementation('Leaf',base='Node',interfaces=('Readable',))
    first=fn('first','bool',[('n','?Node')],f'return {v}n instanceof Readable;')
    second=fn('second','bool',[('n','?Node')],f'return {v}n instanceof Readable;')
    sources={'node':node,'leaf':leaf,'contract':interface(),'first':first,'second':second,
             'main':'if (first(new Leaf())) {echo 1;}return 8;','idle':fn('idle','int',[],'return 7;')}
    app,out=work/(flavor+'-incremental'),work/(flavor+'-incremental-out');write_app(app,flavor,sources)
    steps=[(None,None,b'1',None,'initial'),(None,None,b'1',None,'noop'),
        ('leaf',klass('Leaf','','Node'),b'',None,'capability'),('leaf',leaf,b'1',None,'capability'),
        ('node',klass('Node',s['field']('padding','int','7')+evaluate('virtual')),b'1',None,'layout'),
        ('node',node,b'1',None,'layout'),
        ('node',klass('Node',evaluate('')),None,'polymorphic','invalid'),(None,None,None,'polymorphic','invalid'),
        ('node',node,b'1',None,'recovery'),
        ('contract',None,None,'interface','invalid'),(None,None,None,'interface','invalid'),
        ('contract',interface(),b'1',None,'recovery'),
        ('leaf',None,None,'requires a record','invalid'),('leaf',leaf,b'1',None,'recovery'),
        ('first',fn('first','bool',[('n','?Node')],'return true;'),b'1',None,'first-unused'),
        ('second',None,b'1',None,'last-unused'),('second',second,b'1',None,'restore-use'),
        ('first',first,b'1',None,'restore-use'),(None,None,b'1',None,'noop')]
    resident=service(app,out)
    try:
        for i,(name,text,expected,error,kind) in enumerate(steps):
            before=artifacts(out);stamps={p:(out/p).stat().st_mtime_ns for p in before};old=adapter_files(out)
            if name:
                path=app/(name+'.'+flavor)
                if text is None:path.unlink()
                else:source_edit(path,text)
            r=invoke(resident);f=work/f'{flavor}-fresh-{i}';comparison=fresh(app,f)
            if error:
                assert error in r.get('error','').lower(),(flavor,i,r)
                assert r['error']==comparison.get('error') and artifacts(out)==before,(i,r,comparison)
                continue
            assert 'error' not in r and 'error' not in comparison,(flavor,i,r,comparison)
            now=artifacts(out);assert now==artifacts(f),(flavor,i,'fresh mismatch')
            manifest=verify_manifest(out,f)
            adapters=adapter_files(out)
            assert sum(b'dynamic_cast<' in t for t in adapters.values())==(0 if kind=='last-unused' else 1),(i,adapters)
            for path in old.keys()-adapters.keys():assert not (out/path).exists(),path
            units={Path(p).stem:u for p,u in manifest['sources'].items()}
            if kind in ['capability','layout']:
                for n in ['first','second']:
                    path=Path(units[n]);assert before[path]==now[path] and stamps[path]==(out/path).stat().st_mtime_ns,(i,n)
            if kind=='first-unused':assert not any(p.endswith('/second.'+flavor) for p in r['emitted_sources']),r
            if kind=='restore-use' and name=='second':assert not any(p.endswith('/first.'+flavor) for p in r['emitted_sources']),r
            for cc,lib in libraries.items():
                changed=incremental_native_build(out,work/(flavor+'-incremental-native-'+cc),cc,lib,expected)
                if kind=='noop':assert not changed and r['preparation']['prepared']==0 and r['emitted_sources']==[],r
                if i:assert units['idle'] not in changed,(i,changed)
                if kind=='capability':assert units['leaf'] in changed and units['first'] not in changed and units['second'] not in changed,(i,changed)
                if kind=='layout':assert {units['first'],units['second']}<=changed,(i,changed)
            print(flavor,i,kind,'interface access incremental/native passed',flush=True)
    finally:resident.stdin.close();resident.wait()


def adapter_transitions(work, flavor, libraries):
    """Same source/target names switch proof mode; reused consumers keep both adapter demands."""
    s,evaluate,klass,method,interface,implementation=forms(flavor)
    v,fn=s['v'],s['function'];cast=syntax(flavor)[3]
    node=klass('Node','','Base');proven=implementation('Node',base='Base')
    def consumer(name, used=True):
        body='return '+(cast(v+'n','Readable') if used else 'new Leaf()')+';'
        return fn(name,'Readable',[('n','Node')],body)+fn(name+'Test','bool',[('n','Node')],
            f'return {v}n instanceof Readable;' if used else 'return true;')
    sources={'base':klass('Base',evaluate('virtual')),'node':node,'contract':interface(),
        'leaf':implementation('Leaf',base='Node'),'first':consumer('first'),'second':consumer('second'),
        'read':fn('read','int',[('n','Readable')],f'return {v}n{s["access"]}evaluate();'),
        'idle':fn('idle','int',[],'return 7;'),
        'main':'if(firstTest(new Leaf())) {echo read(first(new Leaf()));}return 8;'}
    app,out=work/(flavor+'-adapters'),work/(flavor+'-adapters-out');write_app(app,flavor,sources)
    steps=[(None,None,'initial',True,2),(None,None,'noop',True,2),
        ('node',proven,'transition',False,2),('node',node,'transition',True,2),
        ('node',proven,'transition',False,2),('node',node,'transition',True,2),
        ('first',consumer('first',False),'first-unused',True,2),
        ('second',None,'last-unused',False,0),(None,None,'noop',False,0),
        ('second',sources['second'],'restore-second',True,2),
        ('first',sources['first'],'restore-first',True,2),(None,None,'noop',True,2)]
    resident=service(app,out)
    try:
        for i,(name,text,kind,dynamic,count) in enumerate(steps):
            before=artifacts(out);stamps={p:(out/p).stat().st_mtime_ns for p in before};old=adapter_files(out)
            if name:
                path=app/(name+'.'+flavor)
                if text is None:path.unlink()
                else:source_edit(path,text)
            r=invoke(resident);f=work/f'{flavor}-adapters-fresh-{i}';comparison=fresh(app,f)
            assert 'error' not in r and 'error' not in comparison,(flavor,i,r,comparison)
            now=artifacts(out);assert now==artifacts(f),(flavor,i,'fresh mismatch')
            manifest=verify_manifest(out,f);adapters=adapter_files(out)
            checks={p:t for p,t in adapters.items() if b'throw_invalid_object_cast()' in t or b'return view != nullptr;' in t}
            assert len(checks)==count,(i,checks)
            assert sum(b'dynamic_cast<' in t for t in checks.values())==(count if dynamic else 0),(i,checks)
            assert sum(b'throw_invalid_object_cast()' in t for t in checks.values())==(1 if count else 0),(i,checks)
            for path in old.keys()-adapters.keys():
                assert not (out/path).exists() and str(path) not in manifest['headers'],(i,path)
            units={Path(p).stem:u for p,u in manifest['sources'].items()}
            cached='second' if kind=='first-unused' else ('first' if kind=='restore-second' else None)
            if cached:
                path=Path(units[cached])
                assert before[path]==now[path] and stamps[path]==(out/path).stat().st_mtime_ns,(i,cached)
                assert not any(p.endswith('/'+cached+'.'+flavor) for p in r['emitted_sources']),r
            for cc,lib in libraries.items():
                changed=incremental_native_build(out,work/(flavor+'-adapters-native-'+cc),cc,lib,b'8')
                if kind=='noop':assert not changed and r['preparation']['prepared']==0 and r['emitted_sources']==[],r
                if i:assert units['idle'] not in changed,(i,changed)
                if cached:assert units[cached] not in changed,(i,changed)
            print(flavor,i,kind,'cast/test adapter transition and manifest passed',flush=True)
    finally:resident.stdin.close();resident.wait()


def rejections(work, flavor):
    s,evaluate,klass,method,interface,implementation=forms(flavor)
    v,fn=s['v'],s['function']
    types=interface()+interface('Other')+klass('Plain')+s['record']('Point','')
    sep={'cpp':'::','php':'\\','js':'.'}[flavor]
    body=f'use Forest{sep}Other as Target;'+types+fn('check','bool',[('n','?Readable')],f'return {v}n instanceof Target;')+'return 8;'
    good=('namespace Forest; '+body) if flavor=='php' else 'namespace Forest {'+body+'}'
    app,out=work/(flavor+'-rejections'),work/(flavor+'-rejections-out');write_app(app,flavor,{'main':good})
    cases=[(fn('check','bool',[('n','?Plain')],f'return {v}n instanceof Readable;'),'polymorphic'),
        (fn('check','bool',[('n','?Readable')],f'return {v}n instanceof Point;'),'target must be'),
        (fn('check','bool',[('n','?Readable')],f'return weak({v}n) instanceof Other;'),'operand must be'),
        (fn('check','bool',[('n','?Readable')],'return null instanceof Other;'),'operand must be'),
        (fn('acceptOther','int',[('n','Other')],'return 1;')+fn('check','int',[('n','Readable')],f'if ({v}n instanceof Other) {{return acceptOther({v}n);}}return 0;'),'conversion'),
        (fn('check','Other',[('n','?Readable')],f'return object_cast({v}n,"Other");'),'Expected'),
        (('class Closed final {};' if flavor=='cpp' else 'final '+klass('Closed')),'Expected'),
        (fn('check','bool',[('n','unique<Plain>')],f'return {v}n instanceof Other;'),'does not accept arguments'),
        (fn('check','bool',[],s['local']('p','Point')+f'return {v}p instanceof Other;'),'operand must be'),
    ]
    resident=service(app,out)
    try:
        assert 'error' not in invoke(resident)
        for i,(bad,message) in enumerate(cases):
            before=artifacts(out);source_edit(app/('main.'+flavor),types+bad+'return 8;')
            for retry in range(2):
                r=invoke(resident);comparison=fresh(app,work/f'{flavor}-bad-{i}-{retry}')
                assert 'error' in r and message.lower() in r['error'].lower(),(i,r)
                assert r['error']==comparison.get('error') and artifacts(out)==before,(i,r,comparison)
            source_edit(app/('main.'+flavor),good);r=invoke(resident);f=work/f'{flavor}-recovery-{i}'
            assert 'error' not in r and 'error' not in fresh(app,f),r
            assert artifacts(out)==artifacts(f)
    finally:resident.stdin.close();resident.wait()
    print(flavor,'interface access rejection/recovery passed',flush=True)


def main():
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output',type=Path);parser.add_argument('--memcheck',action='store_true');args=parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-interface-access-') as temporary:
        work=(args.output or Path(temporary)).resolve();work.mkdir(exist_ok=True)
        libraries={cc:runtime(work,cc) for cc in ['clang++','g++']}
        for flavor in ['cpp','php','js']:
            behavior(work,flavor,libraries,args.memcheck)
            incremental(work,flavor,libraries)
            adapter_transitions(work,flavor,libraries)
            rejections(work,flavor)


if __name__=='__main__':main()

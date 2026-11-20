"""Checked shared class access: native RTTI, ownership, adapter demand and retained recovery."""
import argparse
import json
import subprocess
import tempfile
from pathlib import Path

from app_namespace_native import artifacts, invoke, service
from extends_native import runtime, source_edit
from inline_adapters_native import adapter_files, incremental_native_build
from virtual_methods_native import forms

ROOT = Path(__file__).resolve().parents[2]


def syntax(flavor):
    s, method, klass = forms(flavor)
    marker = lambda name: name + ('.class' if flavor == 'js' else '::class')
    cast = lambda value, name='Leaf': f'object_cast({value},{marker(name)})'
    return s, method, klass, cast


def publish(app, out, sources, flavor):
    app.mkdir()
    (app/'module.json').write_text(json.dumps({'entry':'main.'+flavor}))
    for name, text in sources.items(): source_edit(app/(name+'.'+flavor), text)
    process = service(app, out)
    try:
        result = invoke(process)
        assert 'error' not in result, result
    finally: process.stdin.close(); process.wait()


def behavior(work, flavor, libraries, memcheck):
    s, method, klass, cast = syntax(flavor)
    v, access, fn = s['v'], s['access'], s['function']
    base = klass('Node', s['field']('x','int','3')+method('virtual','7'))
    leaf = klass('Leaf', s['field']('hold','?Token'), 'Node')
    definitions = base+klass('Token')+leaf+klass('Twig','','Leaf')+klass('Other','','Node')
    api = fn('down','Leaf',[('n','?Node')], 'return '+cast(v+'n')+';')
    api += fn('same','Node',[('n','?Node')], 'return '+cast(v+'n','Node')+';')
    api += fn('up','Node',[('n','?Leaf')], 'return '+cast(v+'n','Node')+';')
    api += fn('test','bool',[('n','?Node')],f'return {v}n instanceof Leaf;')
    api += fn('other','bool',[('n','?Node')],f'return {v}n instanceof Other;')
    api += fn('notTest','bool',[('n','?Node')],f'return !{v}n instanceof Leaf;')
    api += fn('precedence','bool',[('n','?Node')],f'return {v}n instanceof Leaf && true || false;')
    api += fn('produce','?Node',[('n','?Node')],f'echo "P"; return {v}n;')
    api += fn('once','Leaf',[('n','?Node')],'return '+cast('produce('+v+'n)')+';')
    api += fn('testOnce','bool',[('n','?Node')],f'return produce({v}n) instanceof Leaf;')
    api += fn('makeNode','Node',[],'return new Twig();')
    api += fn('fresh','Leaf',[],'return '+cast('makeNode()')+';')
    app, out = work/(flavor+'-behavior'),work/(flavor+'-behavior-out')
    publish(app,out,{'types':definitions,'api':api,'main':'return 8;'},flavor)
    adapters=adapter_files(out)
    tests=[text for text in adapters.values() if b'bool' in text and b'dynamic_cast<' in text]
    assert len(tests)==2 and all(b'const owner_Node& source' in text for text in tests), tests  # Same signature, distinct body-only target dependencies.
    assert all(b'template<' not in text and b'new ' not in text for text in adapters.values())
    units=[out/u for u in json.loads((out/'manifest.json').read_text())['translation_units'] if u!='entry.cpp']
    unit=next(p for p in units if 'owner_Leaf down_(' in p.read_text())
    probe=work/(flavor+'-ownership.cpp')
    probe.write_text(f'#include "{unit}"\n'+r'''
#include <cassert>
#include <cstring>
#include <stdexcept>
int main() {
    using namespace App;
    auto token=owner_Token::make();
    auto token_watch=weak_Token(token);
    {
        auto leaf=owner_Leaf::make();
        leaf->hold=token;
        auto base=up_(leaf);
        auto observer=weak_Node(base);
        auto count=base.ptr->count;
        auto checked=down_(base);
        assert(base.ptr==leaf.ptr && checked.ptr==base.ptr && checked.view==leaf.view);
        assert(base.ptr->count==count+1);
        checked->x=29;
        assert(leaf->x==29 && base->x==29);
        checked={};
        assert(test_(base) && !other_(base) && !notTest_(base) && precedence_(base));
        assert(base.ptr->count==count);
        assert(once_(base).ptr==base.ptr && testOnce_(base));
        assert(same_(base).ptr==base.ptr);
        auto sibling=owner_Other::make();
        owner_Node mismatch; mismatch.ptr=sibling.ptr; mismatch.view=sibling.view;
        ++mismatch.ptr->count;
        auto before=mismatch.ptr->count;
        for (int i=0;i<16;++i) {
            try { down_(mismatch); assert(false); }
            catch (const std::runtime_error& e) { assert(std::strcmp(e.what(),"invalid_object_cast")==0); }
            assert(mismatch.ptr->count==before);
        }
        assert(!test_(mismatch) && other_(mismatch) && notTest_(mismatch));
        try { once_(mismatch); assert(false); } catch (const std::runtime_error&) {}
        assert(!testOnce_(mismatch));
        owner_Node absent;
        assert(!test_(absent) && notTest_(absent));
        try { once_(absent); assert(false); } catch (const std::runtime_error&) {}
        assert(!testOnce_(absent));
        try { same_(absent); assert(false); } catch (const std::runtime_error&) {}
        owner_Leaf empty_leaf;
        try { up_(empty_leaf); assert(false); } catch (const std::runtime_error&) {}
        assert(fresh_()->x==3);
        token={}; leaf={};
        assert(observer.lock() && token_watch.lock());
        base={};
        assert(!observer.lock() && !token_watch.lock());
    }
    // Last-owner rvalue: successful transfer and failure release both concrete payloads.
    {
        auto leaf=owner_Leaf::make();
        auto watch=weak_Leaf(leaf);
        auto result=down_(up_(static_cast<owner_Leaf&&>(leaf)));
        assert(!leaf && watch.lock());
        result={};
        assert(!watch.lock());
        auto sibling=owner_Other::make();
        auto bad_watch=weak_Other(sibling);
        owner_Node base; base.ptr=sibling.ptr; base.view=sibling.view;
        sibling.ptr=nullptr; sibling.view=nullptr;
        try { down_(static_cast<owner_Node&&>(base)); assert(false); }
        catch (const std::runtime_error&) {}
        assert(!base && !bad_watch.lock());
        auto last=up_(owner_Leaf::make());
        auto last_watch=weak_Node(last);
        assert(test_(static_cast<owner_Node&&>(last)));
        assert(!last && !last_watch.lock());
        assert(test_(up_(owner_Leaf::make())));
    }
}
''')
    for cc,library in libraries.items():
        binary=work/(flavor+'-ownership-'+cc)
        result=subprocess.run([cc,'-std=c++23','-O1','-Wall','-Wextra','-Werror','-I',ROOT/'runtime/generated/public',
            probe,*[u for u in units if u!=unit],library,'-o',binary],capture_output=True,text=True)
        assert result.returncode==0,result.stderr
        result=subprocess.run([binary],capture_output=True)
        assert (result.returncode,result.stdout,result.stderr)==(0,b'PPPPPP',b''),result
        if memcheck:
            result=subprocess.run(['valgrind','--leak-check=full','--errors-for-leak-kinds=all','--error-exitcode=99',binary],capture_output=True,text=True)
            assert result.returncode==0 and 'All heap blocks were freed' in result.stderr,result.stderr
    print(flavor,'ownership, RTTI behavior and single evaluation passed',flush=True)


def incremental(work, flavor, libraries):
    s,method,klass,cast=syntax(flavor)
    v,fn=s['v'],s['function']
    base=klass('Node',method('virtual','7'))
    leaf=klass('Leaf','','Middle')
    # Target appears only in test body, never its signature. Two consumers share demand.
    first=fn('first','bool',[('n','?Middle')],f'return {v}n instanceof Leaf;')
    second=fn('second','bool',[('n','?Middle')],f'return {v}n instanceof Leaf;')
    main='if (first(new Leaf())) {echo 1;} return 8;'
    sources={'node':base,'middle':klass('Middle','','Node'),'leaf':leaf,'first':first,'second':second,'main':main,
             'idle':fn('idle','int',[],'return 11;')}
    app,out=work/flavor,work/(flavor+'-out')
    publish(app,out,sources,flavor)
    changes=[(None,None,None), (None,None,None),
        ('node',klass('Node',method('virtual','9')),None),('node',base,None),
        ('node',klass('Node'), 'polymorphic'),(None,None,'polymorphic'),('node',base,None),
        ('leaf',klass('Leaf'),'related classes'),('leaf',leaf,None),
        ('leaf',None,'target must be a class'),(None,None,'target must be a class'),('leaf',leaf,None),
        ('node',klass('Node',s['field']('extra','int','17')+method('virtual','7')),None),('node',base,None),
        ('first',fn('first','bool',[('n','?Middle')],'return true;'),None),
        ('second',None,None),('second',second,None),('first',first,None)]
    resident=service(app,out)
    try:
        for revision,(name,text,error) in enumerate(changes):
            before=artifacts(out)
            stamps={p:(out/p).stat().st_mtime_ns for p in before}
            old=adapter_files(out)
            if name:
                path=app/(name+'.'+flavor)
                if text is None:path.unlink()
                else:source_edit(path,text)
            current=invoke(resident)
            fresh_out=work/f'{flavor}-fresh-{revision}'
            fresh=service(app,fresh_out)
            try:comparison=invoke(fresh)
            finally:fresh.stdin.close();fresh.wait()
            if error:
                assert error.lower() in current.get('error','').lower(),(revision,current)
                assert current['error']==comparison.get('error'),(revision,current,comparison)
                assert artifacts(out)==before
                continue
            assert 'error' not in current and 'error' not in comparison,(revision,current,comparison)
            assert artifacts(out)==artifacts(fresh_out),(revision,'retained/fresh differs')
            adapters=adapter_files(out)
            if revision in [14,15,16,17]:
                assert sum(b'dynamic_cast<' in text for text in adapters.values())==(0 if revision==15 else 1),(revision,adapters)
                if revision==14:assert not any(p.endswith('/second.'+flavor) for p in current['emitted_sources']),current
                if revision==16:assert not any(p.endswith('/first.'+flavor) for p in current['emitted_sources']),current
            for p in old.keys()-adapters.keys():assert not (out/p).exists()
            manifest=json.loads((out/'manifest.json').read_text())
            units={Path(p).stem:u for p,u in manifest['sources'].items()}
            if revision==1:
                assert current['preparation']['prepared']==0 and current['emitted_sources']==[],current
            if revision in [12,13]:
                for name in ['first','second','main']:
                    p=Path(units[name]);assert before[p]==artifacts(out)[p]
                    assert (out/p).stat().st_mtime_ns==stamps[p]
            for cc,library in libraries.items():
                native=work/(flavor+'-native-'+cc)
                changed=incremental_native_build(out,native,cc,library,b'1')
                if revision==1:assert changed==set(),changed
                if revision in [2,3]:assert changed=={units['node']},(revision,changed)
                if revision in [12,13]:
                    assert {units['first'],units['second'],units['main']}<=changed,changed
                    assert units['idle'] not in changed
                if revision==0:
                    graph=native/'build.ninja'
                    graph.write_text(graph.read_text().replace('-std=c++23','-std=c++23 -fno-rtti'))
                    failure=subprocess.run(['ninja','-j','4'],cwd=native,capture_output=True,text=True)
                    assert failure.returncode!=0 and 'checked downcasts require native RTTI' in failure.stdout,failure.stdout
                    incremental_native_build(out,native,cc,library,b'1')
            print(flavor,revision,'incremental/native passed',flush=True)
    finally:resident.stdin.close();resident.wait()


def syntax_and_recovery(work, flavor, libraries):
    """Namespace/import targets, unsupported operands, standalone emission and RTTI-free upward views."""
    s,method,klass,cast=syntax(flavor)
    v,fn=s['v'],s['function']
    sep={'cpp':'::','php':'\\','js':'.'}[flavor]
    wrap=lambda body: ('namespace Forest; '+body if flavor=='php' else 'namespace Forest {'+body+'}')
    classes=klass('Node',method('virtual','7'))+klass('Leaf','','Node')+klass('Other','','Node')+klass('Unrelated')
    classes+=s['record']('Point','')+klass('Plain')+klass('PlainLeaf','','Plain')
    check=fn('check','bool',[('n','?Node')],f'return {v}n instanceof Target;')
    checked=fn('checked','Leaf',[('n','?Node')],'return '+cast(v+'n','Target')+';')
    main='if (check(new Leaf())) {echo 1;} return 8;'
    body=f'use Forest{sep}Leaf as Target;'+classes+check+checked+main
    source=wrap(body)
    app,out=work/(flavor+'-syntax'),work/(flavor+'-syntax-out')
    publish(app,out,{'main':source},flavor)
    facts=work/'facts.json'
    facts.write_text(json.dumps({'schema':1,'native_integers':{'int':{'signed':True,'digits':31}}}))
    def standalone(text, label, no_rtti=False):
        path=work/(flavor+'-'+label+'.'+flavor)
        source_edit(path,text)
        cpp=work/(flavor+'-'+label+'-single.cpp')
        result=subprocess.run(['php',ROOT/'compiler/main.php','cpp',flavor,path,facts,cpp],capture_output=True,text=True)
        assert result.returncode==0,result.stderr
        for cc,library in libraries.items():
            exe=work/(flavor+'-'+label+'-'+cc)
            result=subprocess.run([cc,'-std=c++23','-O1','-Wall','-Wextra','-Werror',*(['-fno-rtti'] if no_rtti else []),
                '-I',ROOT/'runtime/generated/public',cpp,library,'-o',exe],capture_output=True,text=True)
            assert result.returncode==0,result.stderr
            result=subprocess.run([exe],capture_output=True)
            assert (result.returncode,result.stdout,result.stderr)==(8,b'1',b''),result
    standalone(source,'qualified')
    nonpoly=klass('Base')+klass('Derived','','Base')
    nonpoly+=fn('up','Base',[('n','?Derived')],'return '+cast(v+'n','Base')+';')
    nonpoly+=fn('same','Base',[('n','?Base')],'return '+cast(v+'n','Base')+';')
    nonpoly+='if (same(up(new Derived())) instanceof Base) {echo 1;} return 8;'
    standalone(nonpoly,'nonpoly',True)
    valid=fn('check','bool',[('n','?Node')],f'return {v}n instanceof Leaf;')
    cases=[
        (fn('check','bool',[('n','?Node')],s['local']('p','Point')+f'return {v}p instanceof Node;'),'operand must be a shared class'),
        (fn('check','bool',[('n','?Node')],'return '+cast('new Plain()','PlainLeaf')+' instanceof PlainLeaf;'),'polymorphic'),
        (fn('check','bool',[('n','?Node')],f'return {v}n instanceof Unrelated;'),'related classes'),
        (fn('check','bool',[('n','?Node')],'return null instanceof Leaf;'),'operand must be a shared class'),
        (fn('check','bool',[('n','?Node')],f'return weak({v}n) instanceof Leaf;'),'operand must be a shared class'),
        (fn('check','bool',[('n','?Node')],f'return {v}n instanceof Point;'),'related structs'),
        (fn('check','bool',[('n','?Node')],'return 1 instanceof Leaf;'),'operand must be a shared class'),
        (fn('check','bool',[('n','?Node')],f'return {v}n instanceof shared<Leaf>;'),'bare class, struct or interface name'),
        (fn('check','bool',[('n','?Node')],f'return object_cast({v}n,"Leaf") instanceof Leaf;'),'Expected'),
        (fn('check','bool',[('n','?Node')],f'return object_cast({v}n,{v}n) instanceof Leaf;'),'class type marker'),
        # No implicit narrowing from a successful test.
        (fn('takeLeaf','bool',[('n','Leaf')],'return true;')+fn('check','bool',[('n','?Node')],
            f'if ({v}n instanceof Leaf) {{return takeLeaf({v}n);}} return false;'),'conversion'),
    ]
    resident=service(app,out)
    try:
        assert 'error' not in invoke(resident)
        revisions=[]
        for bad,error in cases:
            revisions.extend([(wrap(classes+bad+main),error),(wrap(classes+bad+main),error),(source,None)])
        # Editing the import retargets a body-only type test without altering its signature.
        alternate=wrap(body.replace('Leaf as Target','Other as Target').replace(checked,''))
        revisions.extend([(alternate,None),(source,None)])
        for revision,(text,error) in enumerate(revisions):
            before=artifacts(out)
            source_edit(app/('main.'+flavor),text)
            result=invoke(resident)
            fresh_out=work/f'{flavor}-syntax-fresh-{revision}'
            fresh=service(app,fresh_out)
            try:comparison=invoke(fresh)
            finally:fresh.stdin.close();fresh.wait()
            if error:
                assert error.lower() in result.get('error','').lower(),(revision,result)
                assert result['error']==comparison.get('error'),(revision,result,comparison)
                assert artifacts(out)==before
            else:
                assert 'error' not in result and 'error' not in comparison,(revision,result,comparison)
                assert artifacts(out)==artifacts(fresh_out)
                if revision>=len(revisions)-2:
                    for cc,library in libraries.items():
                        incremental_native_build(out,work/(flavor+'-syntax-native-'+cc),cc,library,b'' if text==alternate else b'1')
    finally:resident.stdin.close();resident.wait()
    print(flavor,'namespace/import, standalone, RTTI-free upward and rejection recovery passed',flush=True)


if __name__=='__main__':
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output',type=Path)
    parser.add_argument('--memcheck',action='store_true')
    args=parser.parse_args()
    with tempfile.TemporaryDirectory() as directory:
        work=(args.output or Path(directory)).resolve();work.mkdir(exist_ok=True)
        libraries={cc:runtime(work,cc) for cc in ['clang++','g++']}
        for flavor in ['cpp','php','js']:
            behavior(work,flavor,libraries,args.memcheck)
            incremental(work,flavor,libraries)
            syntax_and_recovery(work,flavor,libraries)

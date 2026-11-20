"""Fixed ancestor calls: ordinary call contracts, current-object lifetime and retained/native recovery."""
import argparse
import json
import subprocess
import tempfile
from pathlib import Path

from app_namespace_native import artifacts, invoke, service
from extends_native import runtime, source_edit
from inline_adapters_native import incremental_native_build
from virtual_methods_native import forms

ROOT=Path(__file__).resolve().parents[2]


def syntax(flavor):
    s,evaluate,klass=forms(flavor)
    def parent(name='evaluate',args='',base='Middle'):
        return {'php':'parent::','js':'super.','cpp':base+'::'}[flavor]+name+'('+args+')'
    def method(name,body,result='int',params='',intent='',access='public',default=None):
        if flavor=='cpp':
            return access+': '+('virtual ' if intent=='virtual' else '')+result+' '+name+'('+params+')'+(' override' if intent=='override' else '')+' {'+body+'}'
        if flavor=='php':return access+' '+(intent+' ' if intent else '')+'function '+name+'('+params+'):'+result+' {'+body+'}'
        return (access+' ' if access!='public' else '')+(intent+' ' if intent else '')+name+'('+params+'):'+result+' {'+body+'}'
    return s,evaluate,klass,parent,method


def write_app(app,flavor,sources):
    app.mkdir()
    (app/'module.json').write_text(json.dumps({'entry':'main.'+flavor}))
    for name,text in sources.items():source_edit(app/(name+'.'+flavor),text)


def incremental(work,flavor,libraries):
    s,evaluate,klass,parent,method=syntax(flavor)
    v,acc=s['v'],s['access']
    node=klass('Node',evaluate('virtual',v+'this'+acc+'hook() + '+v+'delta',default='1')
        +method('hook','return 1;',intent='virtual'))
    middle=klass('Middle','','Node')
    leaf=klass('Leaf',evaluate('override',parent()+' + '+v+'delta',default='2')+method('hook','return 9;',intent='override'),'Middle')
    api=s['function']('read','int',[('n','Node')],f'return {v}n{acc}evaluate();')
    main=s['infer']('n','new Leaf()')+f'echo {v}n{acc}evaluate(),read({v}n); return 8;'
    idle=s['function']('idle','int',[],'return 7;')
    app,out=work/flavor,work/(flavor+'-out')
    write_app(app,flavor,{'node':node,'middle':middle,'leaf':leaf,'api':api,'main':main,'idle':idle,'alternate':klass('Alternate',evaluate('override','30',default='1'),'Node')})
    mid_override=klass('Middle',evaluate('override',parent(args=v+'delta',base='Node')+' + 10',default='1'),'Node')
    edits=[(None,None,b'1211',None),(None,None,b'1211',None),
        ('node',node.replace('hook() +','hook() + 1 +'),b'1312',None),('node',node,b'1211',None),
        ('middle',mid_override,b'2221',None),('middle',middle,b'1211',None),
        ('node',node.replace('delta=1','delta=3').replace('delta:int=1','delta:int=3'),b'1415',None),('node',node,b'1211',None),
        ('middle',klass('Middle'),None,'override'),(None,None,None,'override'),('middle',middle,b'1211',None),
        ('node',None,None,'base'),(None,None,None,'base'),('node',node,b'1211',None),
        ('node',klass('Node',s['field']('unused','int','17')+evaluate('virtual',v+'this'+acc+'hook() + '+v+'delta',default='1')
            +method('hook','return 1;',intent='virtual')),b'1211',None),('node',node,b'1211',None)]
    edits += [('middle',klass('Middle','','Alternate'),b'3231',None),('middle',middle,b'1211',None)]
    if flavor=='cpp':
        edits += [('middle',mid_override,b'2221',None),
                  ('leaf',leaf.replace('Middle::evaluate()', 'Node::evaluate()'),b'1211',None),
                  ('leaf',leaf,b'2221',None),('middle',middle,b'1211',None)]
    resident=service(app,out)
    try:
        for revision,(name,text,expected,error) in enumerate(edits):
            before=artifacts(out);stamps={p:(out/p).stat().st_mtime_ns for p in before}
            if name:
                path=app/(name+'.'+flavor)
                if text is None:path.unlink()
                else:source_edit(path,text)
            current=invoke(resident)
            fresh_out=work/f'{flavor}-fresh-{revision}';fresh=service(app,fresh_out)
            try:comparison=invoke(fresh)
            finally:fresh.stdin.close();fresh.wait()
            if error:
                assert error.lower() in current.get('error','').lower(),(revision,current)
                assert current['error']==comparison.get('error'),(revision,current,comparison)
                assert artifacts(out)==before
                continue
            assert 'error' not in current and 'error' not in comparison,(revision,current,comparison)
            now=artifacts(out);assert now==artifacts(fresh_out),(revision,'fresh mismatch')
            units={Path(p).stem:u for p,u in json.loads((out/'manifest.json').read_text())['sources'].items()}
            text=(out/units['leaf']).read_text()
            selected={4:'Middle',16:'Alternate',18:'Middle',20:'Middle'}.get(revision,'Node')
            assert '.scpp_records::'+selected+'::scpp_virtual_evaluate(' in text,text
            if revision==1:assert current['preparation']['prepared']==0 and current['emitted_sources']==[],current
            if revision in [14,15]:
                for name in ['leaf','api','main']:
                    p=Path(units[name]);assert now[p]==before[p] and (out/p).stat().st_mtime_ns==stamps[p],name
            for cc,library in libraries.items():
                changed=incremental_native_build(out,work/(flavor+'-native-'+cc),cc,library,expected)
                if revision==1:assert not changed,changed
                if revision in [2,3]:assert changed=={units['node']},changed
                if revision in [14,15]:
                    assert {units['leaf'],units['api'],units['main']}<=changed,changed
                    assert units['idle'] not in changed
            print(flavor,revision,'parent incremental/native passed',flush=True)
    finally:resident.stdin.close();resident.wait()


def behavior(work,flavor,libraries,memcheck):
    s,evaluate,klass,parent,method=syntax(flavor)
    v,acc=s['v'],s['access']
    # Ordinary methods use free functions; a base field mutation must hit this same payload.
    parameter={'cpp':'int &n','php':'int &$n','js':'&n:int'}[flavor]
    base=klass('Node',s['field']('x','int','3')+s['field']('hold','?Token')
        +method('bump',f'{v}this{acc}x += 2; {v}n += 1; return {v}this{acc}x;',params=parameter)
        +method('echoValue',f'echo {v}this{acc}x;',result='void'))
    body=s['infer']('n','8')+s['infer']('result',parent('bump',v+'n',base='Node'))+parent('echoValue',base='Node')+';'+f'return {v}result + {v}n;'
    leaf=klass('Leaf',method('run',body),'Node')
    box=klass('Box',s['field']('value','?Leaf'))
    reset=s['function']('reset','int',[('b','Box')],f'{v}b{acc}value=null; return 1;')
    # Outer ordinary call must retain its receiver while an argument clears its final source owner.
    p={'cpp':'int ignored','php':'int $ignored','js':'ignored:int'}[flavor]
    leaf=leaf.replace(method('run',body),method('run',body,params=p))
    api=s['function']('run','int',[('b','Box')],f'return {v}b{acc}value{acc}run(reset({v}b));')
    source=klass('Token')+base+leaf+box+reset+api+'return 8;'
    app,out=work/(flavor+'-behavior'),work/(flavor+'-behavior-out');write_app(app,flavor,{'main':source})
    resident=service(app,out)
    try:result=invoke(resident);assert 'error' not in result,result
    finally:resident.stdin.close();resident.wait()
    units=[out/u for u in json.loads((out/'manifest.json').read_text())['translation_units'] if u!='entry.cpp']
    unit=next(p for p in units if ' run_(' in p.read_text())
    probe=work/(flavor+'-lifetime.cpp')
    probe.write_text(f'#include "{unit}"\n'+r'''
#include <cassert>
int main() {
    using namespace App;
    auto token=owner_Token::make();
    auto watch=weak_Token(token);
    auto b=owner_Box::make();
    b->value=owner_Leaf::make();
    b->value->hold=token;
    auto object_watch=weak_Leaf(b->value);
    token={};
    assert(run_(b)==14);
    assert(!b->value && !object_watch.lock() && !watch.lock());
}
''')
    for cc,library in libraries.items():
        exe=work/(flavor+'-lifetime-'+cc)
        result=subprocess.run([cc,'-std=c++23','-O1','-Wall','-Wextra','-Werror','-I',ROOT/'runtime/generated/public',probe,*[u for u in units if u!=unit],library,'-o',exe],capture_output=True,text=True)
        assert result.returncode==0,result.stderr
        result=subprocess.run([exe],capture_output=True);assert (result.returncode,result.stdout,result.stderr)==(0,b'5',b''),result
        if memcheck:
            result=subprocess.run(['valgrind','--leak-check=full','--errors-for-leak-kinds=all','--error-exitcode=99',exe],capture_output=True,text=True)
            assert result.returncode==0 and 'All heap blocks were freed' in result.stderr,result.stderr
    print(flavor,'ordinary parent calls, reference arguments and ownership passed',flush=True)


def boundaries(work,flavor,libraries):
    s,evaluate,klass,parent,method=syntax(flavor)
    v,acc=s['v'],s['access']
    base=klass('Base',method('get','return 3;'))
    call=parent('get',base='Base')
    child=klass('Child',method('run','return '+call+';'),'Base')
    main=s['infer']('c','new Child()')+f'echo {v}c{acc}run(); return 8;'
    unrelated=klass('Other',method('get','return 9;'))
    app,out=work/(flavor+'-boundaries'),work/(flavor+'-boundaries-out')
    write_app(app,flavor,{'base':base,'child':child,'main':main,'other':unrelated})
    static=method('run','return '+call+';').replace('public: ','public: static ',1) if flavor=='cpp' else method('run','return '+call+';').replace('function ','static function ',1) if flavor=='php' else 'static '+method('run','return '+call+';')
    cases=[('base',klass('Base'), 'method'),('base',klass('Base',method('get','return 3;',access='private')),'private'),
        ('child',klass('Child',method('run','return '+call+';')),'base' if flavor!='cpp' else 'strict base'),
        ('child',klass('Child',static,'Base'),'current instance'),
        ('child',klass('Child',method('run','return '+parent('get','1',base='Base')+';'),'Base'),'argument'),
        ('main',s['function']('outside','int',[],'return '+call+';')+main,'current instance' if flavor=='cpp' else 'declaring record'),
        ('child',klass('Child',method('run','return '+parent('missing',base='Base')+';'),'Base'),'method')]
    if flavor=='cpp':cases.append(('child',klass('Child',method('run','return Other::get();'),'Base'),'strict base'))
    else:
        st=method('get','return 3;').replace('function ','static function ',1) if flavor=='php' else 'static '+method('get','return 3;')
        cases.append(('base',klass('Base',st),None))
    resident=service(app,out)
    good={'base':base,'child':child,'main':main}
    try:
        result=invoke(resident);assert 'error' not in result,result
        revision=0
        for name,text,error in cases:
            for content,expected in [(text,error),(text,error),(good[name],None)]:
                before=artifacts(out)
                source_edit(app/(name+'.'+flavor),content)
                result=invoke(resident)
                fresh_out=work/f'{flavor}-boundary-fresh-{revision}';fresh=service(app,fresh_out)
                try:comparison=invoke(fresh)
                finally:fresh.stdin.close();fresh.wait()
                if expected:
                    assert expected.lower() in result.get('error','').lower(),(revision,result)
                    assert result['error']==comparison.get('error'),(revision,result,comparison)
                    assert artifacts(out)==before
                else:
                    assert 'error' not in result and 'error' not in comparison,(revision,result,comparison)
                    assert artifacts(out)==artifacts(fresh_out)
                    for cc,library in libraries.items():
                        incremental_native_build(out,work/(flavor+'-boundary-native-'+cc),cc,library,b'3')
                revision+=1
    finally:resident.stdin.close();resident.wait()
    # Parent instance calls as the first authored statement follow implicit base construction.
    set_method=method('initializeValue',f'{v}this{acc}x=7;',result='void')
    root=klass('Base',s['field']('x','int','1')+set_method+method('get',f'return {v}this{acc}x;'))
    ctor={'cpp':'public: Child() {Base::initializeValue();}',
          'php':'public function __construct() {parent::initializeValue();}',
          'js':'constructor() {super.initializeValue();}'}[flavor]
    descendant=klass('Child',ctor+method('run','return '+call+';'),'Base')
    for name,text in [('base',root),('child',descendant)]:source_edit(app/(name+'.'+flavor),text)
    resident=service(app,out)
    try:
        result=invoke(resident);assert 'error' not in result,result
        for cc,library in libraries.items():incremental_native_build(out,work/(flavor+'-boundary-native-'+cc),cc,library,b'7')
        # Current-instance access must not leak into explicit base-initializer arguments.
        base_ctor={'cpp':'public: Base(int x=1) {this->x=x;}',
                   'php':'public function __construct(int $x=1) {$this->x=$x;}',
                   'js':'constructor(x:int=1) {this.x=x;}'}[flavor]
        source_edit(app/('base.'+flavor),klass('Base',s['field']('x','int','1')+base_ctor+set_method+method('get',f'return {v}this{acc}x;')))
        bad_ctor={'cpp':'public: Child() : Base(Base::get()) {}',
                  'php':'public function __construct() {parent::__construct(parent::get());}',
                  'js':'constructor() {super(super.get());}'}[flavor]
        source_edit(app/('child.'+flavor),klass('Child',bad_ctor+method('run','return '+call+';'),'Base'))
        result=invoke(resident);assert 'unavailable during base initialization' in result.get('error',''),result
        retry=invoke(resident);assert retry['error']==result['error'],retry
        source_edit(app/('child.'+flavor),descendant)
        recovered=invoke(resident);assert 'error' not in recovered,recovered
        fresh_out=work/(flavor+'-ctor-recovered');fresh=service(app,fresh_out)
        try:comparison=invoke(fresh);assert 'error' not in comparison,comparison
        finally:fresh.stdin.close();fresh.wait()
        assert artifacts(out)==artifacts(fresh_out)
        for cc,library in libraries.items():incremental_native_build(out,work/(flavor+'-boundary-native-'+cc),cc,library,b'7')
    finally:resident.stdin.close();resident.wait()
    print(flavor,'parent diagnostics/recovery and constructor boundaries passed',flush=True)


def qualified_and_failures(work,flavor,libraries):
    s,evaluate,klass,parent,method=syntax(flavor)
    v,acc=s['v'],s['access']
    param={'cpp':'int n','php':'int $n','js':'n:int'}[flavor]
    base=klass('Base',method('get','echo "B"; return '+v+'n;',params=param))
    mark=s['function']('mark','float',[],'echo "M"; return 1.5;')
    bad=s['function']('bad','float',[],'echo "F"; return 1.0e100;')
    child=klass('Child',method('run','return '+parent('get','mark()',base='Base')+';')
        +method('fail','return '+parent('get','bad()',base='Base')+';'),'Base')
    wrappers=s['function']('ok','int',[],s['infer']('n','new Child()')+f'return {v}n{acc}run();')
    wrappers+=s['function']('fail','int',[],s['infer']('n','new Child()')+f'return {v}n{acc}fail();')
    source=base+child+mark+bad+wrappers+'return 8;'
    app,out=work/(flavor+'-checks'),work/(flavor+'-checks-out');write_app(app,flavor,{'main':source})
    resident=service(app,out)
    try:result=invoke(resident);assert 'error' not in result,result
    finally:resident.stdin.close();resident.wait()
    units=[out/u for u in json.loads((out/'manifest.json').read_text())['translation_units'] if u!='entry.cpp']
    unit=units[0]
    probe=work/(flavor+'-failure.cpp')
    probe.write_text(f'#include "{unit}"\n'+r'''
#include <cassert>
#include <stdexcept>
int main() {
    assert(App::ok_()==1);
    try { App::fail_(); assert(false); } catch (const std::exception&) {}
}
''')
    for cc,library in libraries.items():
        exe=work/(flavor+'-checks-'+cc)
        result=subprocess.run([cc,'-std=c++23','-O1','-Wall','-Wextra','-Werror','-I',ROOT/'runtime/generated/public',probe,*[u for u in units if u!=unit],library,'-o',exe],capture_output=True,text=True)
        assert result.returncode==0,result.stderr
        result=subprocess.run([exe],capture_output=True);assert (result.returncode,result.stdout,result.stderr)==(0,b'MBF',b''),result
    # Standalone emission and source namespace/type alias resolution.
    sep={'cpp':'::','php':'\\','js':'.'}[flavor]
    call=parent('get','8',base='Alias')
    plain=klass('Base',method('get','return '+v+'n;',params=param))
    leaf=klass('Child',method('run','return '+call+';'),'Alias')
    body=f'use Trees{sep}Base as Alias;'+plain+leaf+s['infer']('c','new Child()')+f'echo {v}c{acc}run(); return 8;'
    text='namespace Trees;'+body if flavor=='php' else 'namespace Trees {'+body+'}'
    for storage,text in [('class',text),('struct',text.replace('class ','struct ').replace(s['infer']('c','new Child()'),s['local']('c','Child')))]:
        path=work/(flavor+'-'+storage+'-standalone.'+flavor);source_edit(path,text)
        facts=work/'facts.json';facts.write_text(json.dumps({'schema':1,'native_integers':{'int':{'signed':True,'digits':31}}}))
        cpp=work/(flavor+'-'+storage+'-standalone-generated.cpp')
        result=subprocess.run(['php',ROOT/'compiler/main.php','cpp',flavor,path,facts,cpp],capture_output=True,text=True)
        assert result.returncode==0,result.stderr
        for cc,library in libraries.items():
            exe=work/(flavor+'-'+storage+'-standalone-'+cc)
            result=subprocess.run([cc,'-std=c++23','-O1','-Wall','-Wextra','-Werror','-I',ROOT/'runtime/generated/public',cpp,library,'-o',exe],capture_output=True,text=True)
            assert result.returncode==0,result.stderr
            result=subprocess.run([exe],capture_output=True);assert (result.returncode,result.stdout,result.stderr)==(8,b'8',b''),result
    print(flavor,'checked arguments, single evaluation, namespaces and standalone passed',flush=True)


if __name__=='__main__':
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output',type=Path);parser.add_argument('--memcheck',action='store_true');args=parser.parse_args()
    with tempfile.TemporaryDirectory() as temporary:
        work=(args.output or Path(temporary)).resolve();work.mkdir(exist_ok=True)
        libraries={cc:runtime(work,cc) for cc in ['clang++','g++']}
        for flavor in ['cpp','php','js']:
            incremental(work,flavor,libraries)
            behavior(work,flavor,libraries,args.memcheck)
            boundaries(work,flavor,libraries)
            qualified_and_failures(work,flavor,libraries)

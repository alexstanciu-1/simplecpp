"""Abstract class contracts without synthetic bodies: ownership and retained/native recovery."""
import argparse
import json
import subprocess
import tempfile
from pathlib import Path
from app_namespace_native import artifacts,invoke,service
from extends_native import runtime,source_edit
from inline_adapters_native import incremental_native_build
from parent_methods_native import syntax,write_app
ROOT=Path(__file__).resolve().parents[2]


def forms(flavor):
    s,evaluate,klass,parent,method=syntax(flavor)
    def abstract(name='evaluate',override=False,default='1',result='int',parameter='int'):
        param=f'{parameter} {s["v"]}delta={default}' if flavor!='js' else f'delta:{parameter}={default}'
        if flavor=='cpp':return ('' if override else 'virtual ')+f'{result} {name}({param})'+(' override' if override else '')+' = 0;'
        if flavor=='php':return 'abstract public '+('override ' if override else '')+f'function {name}({param}):{result};'
        return 'abstract '+('override ' if override else '')+f'{name}({param}):{result};'
    return s,evaluate,klass,parent,method,abstract


def prove(work,flavor,libraries):
    s,evaluate,klass,parent,method,abstract=forms(flavor)
    v=s['v']
    node='abstract '+klass('Node',abstract())
    middle='abstract '+klass('Middle','','Node')
    leaf=klass('Leaf',evaluate('override','8 + '+v+'delta',default='1'),'Middle')
    api=s['function']('read','int',[('n','Node')],f'return {v}n{s["access"]}evaluate();')
    idle=s['function']('idle','int',[],'return 2;')
    main='echo read(new Leaf()); return 8;'
    app,out=work/flavor,work/(flavor+'-out');write_app(app,flavor,{'node':node,'middle':middle,'leaf':leaf,'api':api,'idle':idle,'main':main})
    concrete_node=klass('Node',evaluate('virtual','1',default='1'))
    edits=[(None,None,b'9',None),(None,None,b'9',None),
        ('leaf',leaf.replace('8 +','9 +'),b'10',None),('leaf',leaf,b'9',None),
        ('leaf',klass('Leaf','','Middle'),None,'must implement'),(None,None,None,'must implement'),('leaf',leaf,b'9',None),
        ('node','abstract '+klass('Node',abstract(default='3')),b'11',None),('node',node,b'9',None),
        ('middle','abstract '+klass('Middle',abstract(override=True),'Node'),b'9',None),('middle',middle,b'9',None),
        ('node',concrete_node,b'9',None),('node',node,b'9',None),
        ('main','echo read(new Node()); return 8;',None,'Cannot instantiate'),(None,None,None,'Cannot instantiate'),('main',main,b'9',None),
        ('node','abstract '+klass('Node',evaluate('virtual','1',default='1')),b'9',None),
        ('main','echo read(new Node()); return 8;',None,'Cannot instantiate'),('main',main,b'9',None),('node',node,b'9',None),
        ('node','abstract '+klass('Node',abstract()+abstract('extra')),None,'must implement'),(None,None,None,'must implement'),('node',node,b'9',None),
        ('node',None,None,'base'),(None,None,None,'base'),('node',node,b'9',None),
        ('leaf',leaf.replace('override ',''),None,'requires override'),('leaf',leaf,b'9',None),
        ('leaf',klass('Leaf',evaluate('override',parent(base='Middle'),default='1'),'Middle'),None,'abstract ancestor'),('leaf',leaf,b'9',None),
        ('node','abstract '+klass('Node',s['field']('extra','int','3')+abstract()),b'9',None),('node',node,b'9',None),
        ('node','abstract '+klass('Node',abstract(parameter='float')),None,'signature must match'),('node',node,b'9',None),
        ('middle',klass('Middle',evaluate('override','4',default='1'),'Node'),b'9',None),
        ('leaf',klass('Leaf','','Middle'),b'4',None),('leaf',leaf,b'9',None),('middle',middle,b'9',None)]
    resident=service(app,out)
    try:
        for revision,(name,text,expected,error) in enumerate(edits):
            before=artifacts(out);stamps={p:(out/p).stat().st_mtime_ns for p in before}
            if name:
                path=app/(name+'.'+flavor)
                if text is None:path.unlink()
                else:source_edit(path,text)
            result=invoke(resident);fresh_out=work/f'{flavor}-fresh-{revision}';fresh=service(app,fresh_out)
            try:comparison=invoke(fresh)
            finally:fresh.stdin.close();fresh.wait()
            if error:
                assert error.lower() in result.get('error','').lower(),(revision,result)
                assert result['error']==comparison.get('error'),(revision,result,comparison)
                assert artifacts(out)==before
                continue
            assert 'error' not in result and 'error' not in comparison,(revision,result,comparison)
            now=artifacts(out);assert now==artifacts(fresh_out),(revision,'fresh differs')
            if revision==1:assert result['preparation']['prepared']==0 and result['emitted_sources']==[],result
            units={Path(p).stem:u for p,u in json.loads((out/'manifest.json').read_text())['sources'].items()}
            all_code=b'\n'.join(now.values())
            if revision==0:
                assert result['preparation']=={'prepared':4,'reused':0},result
                assert b' = 0;' in all_code and b'owner_Node::allocation :' not in all_code and b'owner_Node::make(' not in all_code
                assert b'scpp_records::Node::scpp_virtual_evaluate(' not in (out/units['node']).read_bytes()
            if revision in [30,31]:
                for n in ['api','main','leaf']:
                    p=Path(units[n]);assert now[p]==before[p] and (out/p).stat().st_mtime_ns==stamps[p],(revision,n)
            for cc,library in libraries.items():
                changed=incremental_native_build(out,work/(flavor+'-native-'+cc),cc,library,expected)
                if revision==1:assert not changed,changed
                if revision in [2,3]:assert changed=={units['leaf']},changed
                if revision in [30,31]:assert {units['api'],units['main'],units['leaf']}<=changed and units['idle'] not in changed,changed
            print(flavor,revision,'abstract incremental/native passed',flush=True)
    finally:resident.stdin.close();resident.wait()


def ownership(work,flavor,libraries,memcheck):
    s,evaluate,klass,parent,method,abstract=forms(flavor)
    v,acc=s['v'],s['access']
    ctor={'cpp':'Node(int x=3) {this->x=x;}','php':'public function __construct(int $x=3) {$this->x=$x;}','js':'constructor(x:int=3) {this.x=x;}'}[flavor]
    base='abstract '+klass('Node',s['field']('x','int')+ctor+abstract())
    leaf=klass('Leaf',s['field']('hold','Token')+evaluate('override',v+'this'+acc+'x + '+v+'delta',default='1'),'Node')
    api=s['function']('view','Node',[('n','Leaf')],f'return {v}n;')
    api+=s['function']('read','int',[('n','Node')],f'return {v}n{acc}evaluate();')
    marker='Leaf.class' if flavor=='js' else 'Leaf::class'
    api+=s['function']('back','Leaf',[('n','Node')],f'return object_cast({v}n,{marker});')
    source=klass('Token')+base+leaf+api+'echo read(new Leaf(8)); return 8;'
    app,out=work/(flavor+'-own'),work/(flavor+'-own-out');write_app(app,flavor,{'main':source})
    resident=service(app,out)
    try:result=invoke(resident);assert 'error' not in result,result
    finally:resident.stdin.close();resident.wait()
    unit=next(out/p for p in json.loads((out/'manifest.json').read_text())['translation_units'] if p!='entry.cpp')
    probe=work/(flavor+'-own.cpp');probe.write_text(f'#include "{unit}"\n'+r'''
#include <cassert>
#include <type_traits>
int main() {
 using namespace App;
 static_assert(std::is_abstract_v<scpp_records::Node>);
 auto token=owner_Token::make(); auto watch=weak_Token(token);
 auto leaf=owner_Leaf::make(8); leaf->hold=token;
 auto base=view_(leaf); auto observer=weak_Node(base);
 assert(base.ptr==leaf.ptr && back_(base).ptr==leaf.ptr && read_(base)==9);
 base->x=12; assert(leaf->x==12 && read_(base)==13);
 token={};leaf={};assert(watch.lock());base={};
 assert(!observer.lock() && !watch.lock());
}
''')
    facts=work/'facts.json';facts.write_text(json.dumps({'schema':1,'native_integers':{'int':{'signed':True,'digits':31}}}))
    cpp=work/(flavor+'-standalone.cpp')
    r=subprocess.run(['php',ROOT/'compiler/main.php','cpp',flavor,app/('main.'+flavor),facts,cpp],capture_output=True,text=True);assert r.returncode==0,r.stderr
    for cc,library in libraries.items():
        for path,label,code,stdout in [(probe,'ownership',0,b''),(cpp,'standalone',8,b'9')]:
            exe=work/(flavor+'-'+label+'-'+cc)
            r=subprocess.run([cc,'-std=c++23','-O1','-Wall','-Wextra','-Werror','-I',ROOT/'runtime/generated/public',path,library,'-o',exe],capture_output=True,text=True);assert r.returncode==0,r.stderr
            r=subprocess.run([exe],capture_output=True);assert (r.returncode,r.stdout,r.stderr)==(code,stdout,b''),r
            if memcheck and label=='ownership':
                r=subprocess.run(['valgrind','--leak-check=full','--errors-for-leak-kinds=all','--error-exitcode=99',exe],capture_output=True,text=True)
                assert r.returncode==0 and 'All heap blocks were freed' in r.stderr,r.stderr
    print(flavor,'abstract ownership and standalone passed',flush=True)


def invalid_contracts(work,flavor,libraries):
    s,evaluate,klass,parent,method,abstract=forms(flavor)
    base='abstract '+klass('Node',abstract())
    leaf=klass('Leaf',evaluate('override','8',default='1'),'Node')
    app,out=work/(flavor+'-invalid'),work/(flavor+'-invalid-out')
    write_app(app,flavor,{'node':base,'leaf':leaf,'main':'return 8;'})
    pure=abstract()
    private=pure.replace('virtual ','private: virtual ',1) if flavor=='cpp' else pure.replace('public ','private ',1) if flavor=='php' else 'private '+pure
    static=pure.replace('virtual ','static virtual ',1) if flavor=='cpp' else pure.replace('function ','static function ',1) if flavor=='php' else 'static '+pure
    final=pure.replace(' = 0',' final = 0') if flavor=='cpp' else pure.replace('abstract ','abstract final ',1)
    duplicate=pure.replace('delta=1','delta=1,int delta=2') if flavor=='cpp' else pure.replace('$delta=1','$delta=1,int $delta=2') if flavor=='php' else pure.replace('delta:int=1','delta:int=1,delta:int=2')
    cases=[(klass('Node',pure),'abstract class'),('abstract '+klass('Node',private),'public or protected instance'),
        ('abstract '+klass('Node',static),'public or protected instance'),('abstract '+klass('Node',final),'cannot be final'),
        ('abstract '+klass('Node',duplicate),'Duplicate parameter'),
        ('abstract '+klass('Node',abstract(override=True)),'no inherited virtual target')]
    if flavor!='cpp':cases.append(('abstract '+klass('Node',pure[:-1]+' {return 1;}'),'after abstract method'))
    resident=service(app,out)
    try:
        good=invoke(resident);assert 'error' not in good,good
        for revision,(bad,error) in enumerate(cases):
            for attempt,text in enumerate([bad,bad,base]):
                before=artifacts(out);source_edit(app/('node.'+flavor),text)
                result=invoke(resident)
                fresh_out=work/f'{flavor}-invalid-fresh-{revision}-{attempt}';fresh=service(app,fresh_out)
                try:comparison=invoke(fresh)
                finally:fresh.stdin.close();fresh.wait()
                if attempt<2:
                    assert error.lower() in result.get('error','').lower(),(revision,result)
                    assert result['error']==comparison.get('error'),(revision,result,comparison)
                    assert artifacts(out)==before
                else:
                    assert 'error' not in result and 'error' not in comparison,(result,comparison)
                    assert artifacts(out)==artifacts(fresh_out)
                    for cc,library in libraries.items():incremental_native_build(out,work/(flavor+'-invalid-native-'+cc),cc,library,b'')
    finally:resident.stdin.close();resident.wait()
    print(flavor,'abstract declaration rejection/recovery passed',flush=True)


if __name__=='__main__':
    p=argparse.ArgumentParser(description=__doc__);p.add_argument('--output',type=Path);p.add_argument('--memcheck',action='store_true');args=p.parse_args()
    with tempfile.TemporaryDirectory() as directory:
        work=(args.output or Path(directory)).resolve();work.mkdir(exist_ok=True)
        libraries={cc:runtime(work,cc) for cc in ['clang++','g++']}
        for flavor in ['cpp','php','js']:
            prove(work,flavor,libraries)
            ownership(work,flavor,libraries,args.memcheck)
            invalid_contracts(work,flavor,libraries)

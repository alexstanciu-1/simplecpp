"""Bounded class interfaces: original method ownership, shared views and incremental recovery."""
import argparse
import json
import re
import subprocess
import tempfile
from pathlib import Path

from abstract_methods_native import forms as abstract_forms
from app_namespace_native import artifacts, invoke, service
from extends_native import runtime, source_edit
from inline_adapters_native import incremental_native_build, adapter_files
from parent_methods_native import syntax, write_app

ROOT = Path(__file__).resolve().parents[2]


def forms(flavor):
    s, evaluate, klass, parent, method = syntax(flavor)
    def interface(name='Readable', default='1', result='int', extra=''):
        signature = {'php': f'public function evaluate(int $delta={default}):{result};',
                     'cpp': f'{result} evaluate(int delta={default});',
                     'js': f'evaluate(delta:int={default}):{result};'}[flavor]
        return f'interface {name} {{ {signature} {extra} }}'
    def implementation(name, body='', base=None, interfaces=('Readable',)):
        if flavor == 'cpp':
            bases = ([base] if base else []) + list(interfaces)
            return f'class {name}' + (' : '+', '.join('public '+b for b in bases) if bases else '') + ' {'+body+'};'
        return f'class {name}' + (' extends '+base if base else '') + (' implements '+','.join(interfaces) if interfaces else '') + ' {'+body+'}'
    return s, evaluate, klass, method, interface, implementation


def fresh(app, out):
    p = service(app, out)
    try: return invoke(p)
    finally: p.stdin.close(); p.wait()


def incremental(work, flavor, libraries):
    s, evaluate, klass, method, interface, implementation = forms(flavor)
    v, acc = s['v'], s['access']
    node = klass('Node', s['field']('offset', 'int', '8') + evaluate('', v+'this'+acc+'offset + '+v+'delta', default='2'))
    leaf = implementation('Leaf', base='Node')
    api = s['function']('read', 'int', [('x', 'Readable')], f'return {v}x{acc}evaluate();')
    api += s['function']('view', 'Readable', [('x', 'Leaf')], f'return {v}x;')
    main = s['infer']('n', 'new Leaf()') + f'echo read({v}n),{v}n{acc}evaluate();return 8;'
    idle = s['function']('idle', 'int', [], 'return 7;')
    sources = {'alternate':klass('Alternate',s['field']('offset','int','30')+evaluate('',v+'this'+acc+'offset + '+v+'delta',default='2')), 'contract': interface(), 'node': node, 'leaf': leaf, 'api': api, 'main': main, 'idle': idle}
    app, out = work/flavor, work/(flavor+'-out'); write_app(app, flavor, sources)
    extra={'cpp':'int tag();','php':'public function tag():int;','js':'tag():int;'}[flavor]
    tag=method('tag','return 7;',intent='override')
    changes = [
        (None, None, b'910', None, 'initial'),
        (None, None, b'910', None, 'noop'),
        ('node', node.replace('offset +', 'offset + 1 +'), b'1011', None, 'body'),
        ('node', node, b'910', None, 'body'),
        ('contract', interface(default='3'), b'1110', None, 'default'),
        ('contract', interface(), b'910', None, 'restore'),
        ('contract', interface(result='float'), None, 'match exactly', 'invalid'),
        (None, None, None, 'match exactly', 'invalid'),
        ('contract', interface(), b'910', None, 'recover'),
        ('leaf', implementation('Leaf', evaluate('override', '20 + '+v+'delta', default='2'), 'Node'), None, 'hiding', 'invalid'),
        ('leaf', leaf, b'910', None, 'recover'),
        ('node', node.replace(evaluate('', v+'this'+acc+'offset + '+v+'delta', default='2'), evaluate('virtual',v+'this'+acc+'offset + '+v+'delta',default='2')), b'910', None, 'virtual'),
        ('leaf', implementation('Leaf', evaluate('override', '20 + '+v+'delta',default='2'), 'Node'), b'2122', None, 'override'),
        ('leaf', leaf, b'910', None, 'restore'),
        ('node', node, b'910', None, 'restore'),
        ('node', node.replace(s['field']('offset','int','8'),s['field']('offset','int','8')+s['field']('unused','int','0')), b'910', None, 'header'),
        ('node', node, b'910', None, 'header'),
        ('contract', None, None, 'interface', 'invalid'),
        (None, None, None, 'interface', 'invalid'),
        ('contract', interface(), b'910', None, 'recover'),
        ('leaf', None, None, 'unknown', 'invalid'),
        ('leaf', leaf, b'910', None, 'recover'),
        ('leaf', implementation('Leaf',base='Node',interfaces=()),None,'conversion','invalid'),
        ('leaf', leaf,b'910',None,'recover'),
        ('leaf',implementation('Leaf',base='Alternate'),b'3132',None,'reparent'),
        ('leaf',leaf,b'910',None,'recover'),
        ('contract',interface(extra=extra),None,'must implement','invalid'),
        ('leaf',implementation('Leaf',tag,'Node'),b'910',None,'interface-header'),
        ('contract',interface(),None,'no inherited','invalid'),
        ('leaf',leaf,b'910',None,'recover'),
        (None,None,b'910',None,'noop'),
    ]
    resident = service(app,out)
    try:
        for revision,(name,text,expected,error,kind) in enumerate(changes):
            before=artifacts(out); stamps={p:(out/p).stat().st_mtime_ns for p in before}
            if name:
                path=app/(name+'.'+flavor)
                if text is None:path.unlink()
                else:source_edit(path,text)
            current=invoke(resident); fresh_out=work/f'{flavor}-fresh-{revision}'; comparison=fresh(app,fresh_out)
            if error:
                assert error in current.get('error','').lower(),(flavor,revision,current)
                assert current['error']==comparison.get('error'),(revision,current,comparison)
                assert artifacts(out)==before
                continue
            assert 'error' not in current and 'error' not in comparison,(flavor,revision,current,comparison)
            now=artifacts(out);assert now==artifacts(fresh_out),(flavor,revision,'fresh mismatch')
            units={Path(p).stem:u for p,u in json.loads((out/'manifest.json').read_text())['sources'].items()}
            if kind=='noop': assert current['preparation']['prepared']==0 and current['emitted_sources']==[],current
            if kind in ['body','header','interface-header']:
                for n in ['api','main']:
                    path=Path(units[n]); assert before[path]==now[path] and stamps[path]==(out/path).stat().st_mtime_ns,(revision,n)
            for cc,library in libraries.items():
                changed=incremental_native_build(out,work/(flavor+'-native-'+cc),cc,library,expected)
                if kind=='noop':assert not changed,changed
                if kind=='body':assert changed=={units['node']},changed
                if kind in ['header','interface-header']:assert {units['leaf'],units['api'],units['main']}<=changed and units['idle'] not in changed,changed
            print(flavor,revision,kind,'interface incremental/native passed',flush=True)
    finally:resident.stdin.close();resident.wait()


def ownership(work, flavor, libraries, memcheck):
    s, evaluate, klass, method, interface, implementation = forms(flavor)
    v, acc = s['v'], s['access']
    # Both capabilities share a signature but retain their own source default.
    source = interface() + interface('Other', default='3') + klass('Token')
    ctor={'cpp':'Leaf() {}','php':'public function __construct() {}','js':'constructor() {}'}[flavor]
    source += implementation('Leaf', ctor + s['field']('hold','Token') + s['field']('offset','int','8')
        + evaluate('override',v+'this'+acc+'offset + '+v+'delta',default='2'),interfaces=('Readable','Other'))
    source += implementation('Twig',evaluate('override','20 + '+v+'delta',default='2'),'Leaf',interfaces=())
    source += klass('Holder',s['field']('value','Readable'))
    source += s['function']('holder','Holder',[],'return new Holder();')
    source += s['function']('view','Readable',[('x','Leaf')],f'return {v}x;')
    source += s['function']('other','Other',[('x','Leaf')],f'return {v}x;')
    source += s['function']('same','Readable',[('x','Readable')],f'return {v}x;')
    source += s['function']('optional','?Readable',[('x','?Leaf')],f'return {v}x;')
    source += s['function']('required','Readable',[('x','?Readable')],f'return {v}x;')
    source += s['function']('read','int',[('x','Readable')],f'return {v}x{acc}evaluate();')
    source += s['function']('readOther','int',[('x','Other')],f'return {v}x{acc}evaluate();')
    source += s['function']('alias','bool',[('x','Leaf')],f'return view({v}x) === {v}x;')
    # Borrowed parameter mode already exists: releasing the last source owner in an
    # argument must not destroy the receiver before the member invocation finishes.
    ref = {'cpp':'?Readable &x','php':'?Readable &$x','js':'&x:?Readable'}[flavor]
    release = {'cpp':f'int release({ref})', 'php':f'function release({ref}):int','js':f'function release({ref}):int'}[flavor]
    source += release+' {echo "R";'+v+'x=null;return 4;}'
    run = {'cpp':f'int releaseCall({ref})','php':f'function releaseCall({ref}):int','js':f'function releaseCall({ref}):int'}[flavor]
    source += run+' {return '+v+'x'+acc+'evaluate(release('+v+'x));}'
    small_param={'cpp':'byte value','php':'byte $value','js':'value:byte'}[flavor]
    small_contract={'cpp':f'int small({small_param});','php':f'public function small({small_param}):int;',
                    'js':f'small({small_param}):int;'}[flavor]
    source += 'interface Narrow {'+small_contract+'}'
    source += implementation('Guard',method('small','echo "B";return 1;',params=small_param,intent='override'),interfaces=('Narrow',))
    source += s['function']('number','int',[],'echo "K";return 256;')
    source += s['function']('checked','void',[],s['infer']('g','new Guard()')+v+'g'+acc+'small(number());')
    source += 'echo read(new Leaf()),readOther(new Leaf()),read(new Twig()); return 8;'
    app,out=work/(flavor+'-ownership'),work/(flavor+'-ownership-out');write_app(app,flavor,{'main':source})
    result=fresh(app,out);assert 'error' not in result,result
    unit=next(out/n for n in json.loads((out/'manifest.json').read_text())['translation_units'] if n!='entry.cpp')
    probe=work/(flavor+'-ownership.cpp');probe.write_text(f'#include "{unit}"\n'+r'''
#include <cassert>
#include <stdexcept>
#include <type_traits>
int main() {
 using namespace App;
 static_assert(std::is_abstract_v<scpp_records::Readable>);
 static_assert(sizeof(owner_Readable)==2*sizeof(void*));
 for (int n=0;n<32;++n) {
  auto token=owner_Token::make();auto watch=weak_Token(token);
  auto leaf=owner_Leaf::make();leaf->hold=token;
  auto a=view_(leaf);auto b=other_(leaf);auto observer=weak_Readable(a);
  assert(a.ptr==leaf.ptr && b.ptr==leaf.ptr && alias_(leaf));
  assert(a.view==static_cast<scpp_records::Readable*>(leaf.view));
  assert(b.view==static_cast<scpp_records::Other*>(leaf.view));
  assert(static_cast<void*>(a.view)!=static_cast<void*>(b.view));
  assert(same_(a).ptr==a.ptr && read_(a)==9 && readOther_(b)==11);
  leaf->offset=12;assert(read_(a)==13);
  token={};leaf={};b={};assert(watch.lock());
  assert(releaseCall_(a)==16 && !a && !observer.lock() && !watch.lock());
 }
 try { checked_(); return 3; } catch (const std::out_of_range&) {}
 assert(!optional_({}));
 try { required_({}); return 1; } catch (const std::runtime_error&) {}
 try { read_({}); return 2; } catch (const std::runtime_error&) {}
 auto holder=owner_Holder::make();assert(!holder->value);
 auto leaf=owner_Leaf::make();holder->value=view_(leaf);assert(read_(holder->value)==9);
}
''')
    facts=work/'facts.json';facts.write_text(json.dumps({'schema':1,'native_integers':{'int':{'signed':True,'digits':31}}}))
    cpp=work/(flavor+'-standalone.cpp')
    r=subprocess.run(['php',ROOT/'compiler/main.php','cpp',flavor,app/('main.'+flavor),facts,cpp],capture_output=True,text=True);assert r.returncode==0,r.stderr
    for cc,library in libraries.items():
        incremental_native_build(out,work/(flavor+'-ownership-native-'+cc),cc,library,b'91121')
        for path,label,code,stdout in [(probe,'ownership',0,b'R'*32+b'K'),(cpp,'standalone',8,b'91121')]:
            exe=work/(flavor+'-'+label+'-'+cc)
            r=subprocess.run([cc,'-std=c++23','-O1','-Wall','-Wextra','-Werror','-I',ROOT/'runtime/generated/public',path,library,'-o',exe],capture_output=True,text=True);assert r.returncode==0,r.stderr
            r=subprocess.run([exe],capture_output=True);assert (r.returncode,r.stdout,r.stderr)==(code,stdout,b''),r
            if memcheck and label=='ownership':
                r=subprocess.run(['valgrind','--leak-check=full','--errors-for-leak-kinds=all','--error-exitcode=99',exe],capture_output=True,text=True)
                assert r.returncode==0 and 'All heap blocks were freed' in r.stderr,r.stderr
    print(flavor,'multiple interface ownership and lifetime passed',flush=True)


def contracts(work, flavor, libraries):
    s, evaluate, klass, method, interface, implementation = forms(flavor)
    v,acc=s['v'],s['access']
    good=interface()+implementation('Leaf',evaluate('override','8 + '+v+'delta',default='2'))+'return 8;'
    app,out=work/(flavor+'-contracts'),work/(flavor+'-contracts-out');write_app(app,flavor,{'main':good})
    resident=service(app,out)
    invalid=[
        (interface()+implementation('Leaf',evaluate('',default='2'))+'return 8;','requires override'),
        (interface()+implementation('Leaf',evaluate('virtual',default='2'))+'return 8;','requires override'),
        (interface()+implementation('Leaf')+'return 8;','must implement'),
        (interface()+implementation('Leaf',evaluate('override',result='float'))+'return 8;','match exactly'),
        (interface()+implementation('Leaf',evaluate('override'),interfaces=('Readable','Readable'))+'return 8;','duplicate'),
        (interface()+klass('Leaf',evaluate('override'))+'return 8;','no inherited'),
        (interface()+interface('Other',result='float')+implementation('Leaf',evaluate('override'),interfaces=('Readable','Other'))+'return 8;','match exactly'),
        (interface()+implementation('Leaf',evaluate('override'),interfaces=('Missing',))+'return 8;','interface'),
        (interface()+implementation('Leaf',evaluate('override'),interfaces=('?Readable',))+'return 8;','base' if flavor=='cpp' else 'interface'),
        (interface()+s['infer']('x','new Readable()')+'return 8;','abstract'),
        (interface()+implementation('Leaf',evaluate('override'))+s['function']('fail','Readable',[],'return null;')+'return 8;','null'),
        (interface()+implementation('Leaf',evaluate('override')).replace('class Leaf','struct Leaf')+'return 8;','only classes'),
        (interface()+('interface Other : Readable {};' if flavor=='cpp' else 'interface Other extends Readable {}')+'return 8;','inheritance'),
    ]
    abstract = abstract_forms(flavor)[-1]
    inherited_abstract = 'abstract '+klass('Base',abstract())+'abstract '+implementation('Leaf',base='Base')
    invalid.append((interface(result='float')+inherited_abstract+'return 8;','match exactly'))
    invalid.append((interface()+implementation('Leaf',evaluate('override',parameter='float'))+'return 8;','match exactly'))
    no_default=evaluate('override').replace('=2','')
    ref={'cpp':'int &delta','php':'int &$delta','js':'&delta:int'}[flavor]
    ordinary={'cpp':'int delta','php':'int $delta','js':'delta:int'}[flavor]
    invalid.append((interface().replace('=1','')+implementation('Leaf',no_default.replace(ordinary,ref))+'return 8;','match exactly'))
    invalid.append((klass('Token')+interface(result='Token')+implementation('Leaf',evaluate('override','null',result='?Token'))+'return 8;','match exactly'))
    private=evaluate('override')
    if flavor=='cpp': private='private: '+private
    elif flavor=='php': private=private.replace('public','private')
    else:private='private '+private
    invalid.append((interface()+implementation('Leaf',private)+'return 8;','public'))
    base=klass('Base',evaluate('virtual'))
    final=klass('Final',evaluate('override',final=True),'Base')
    invalid.append((interface()+base+final+implementation('Leaf',base='Final')+'return 8;','inherited final'))
    for marker in ['virtual override','override override']:
        if flavor=='cpp':bad=('virtual '+evaluate('override')) if marker.startswith('virtual') else evaluate('override').replace(' override',' override override')
        else:bad=evaluate('override').replace('override',marker)
        invalid.append((interface()+implementation('Leaf',bad)+'return 8;','conflicting' if marker.startswith('virtual') else 'duplicate'))
    try:
        assert 'error' not in invoke(resident)
        for i,(text,message) in enumerate(invalid):
            old=artifacts(out);source_edit(app/('main.'+flavor),text)
            for attempt in range(2):
                r=invoke(resident);comparison=fresh(app,work/f'{flavor}-bad-{i}-{attempt}')
                assert message in r.get('error','').lower(),(flavor,i,r)
                assert r['error']==comparison.get('error') and artifacts(out)==old,(i,r,comparison)
            source_edit(app/('main.'+flavor),good);r=invoke(resident);f=work/f'{flavor}-recovery-{i}'
            assert 'error' not in r and 'error' not in fresh(app,f),r
            assert artifacts(out)==artifacts(f)
        # Inherited ordinary bridge is a new virtual slot on Middle, not on Base.
        base=klass('Base',evaluate('',v+'delta',default='2'))
        middle=implementation('Middle',base='Base')
        leaf=implementation('Leaf',evaluate('override','20 + '+v+'delta',default='2'),'Middle',interfaces=())
        code=interface()+base+middle+leaf
        code+=s['function']('asBase','int',[('x','Base')],f'return {v}x{acc}evaluate();')
        code+=s['function']('asMiddle','int',[('x','Middle')],f'return {v}x{acc}evaluate();')
        code+=s['function']('asInterface','int',[('x','Readable')],f'return {v}x{acc}evaluate();')
        code+=s['infer']('x','new Leaf()')+f'echo asBase({v}x),asMiddle({v}x),asInterface({v}x);return 8;'
        scenarios=[(code,b'22221'),(interface()+inherited_abstract+'return 8;',b'')]
        # Abstract implementers need no invented AST/body for an outstanding slot.
        code=interface()+'abstract '+implementation('Middle')+implementation('Leaf',evaluate('override','8 + '+v+'delta',default='2'),'Middle',interfaces=())
        code+=s['function']('read','int',[('x','Middle')],f'return {v}x{acc}evaluate();')+'echo read(new Leaf());return 8;'
        scenarios.append((code,b'9'))
        # Bridges preserve reference mutation and move by-value handles without cloning payloads.
        ref={'cpp':'int &x','php':'int &$x','js':'&x:int'}[flavor]
        token={'cpp':'Token x','php':'Token $x','js':'x:Token'}[flavor]
        requirements={'cpp':f'void bump({ref}); Token pass({token});',
                      'php':f'public function bump({ref}):void; public function pass({token}):Token;',
                      'js':f'bump({ref}):void; pass({token}):Token;'}[flavor]
        code=klass('Token')+'interface Transfer {'+requirements+'}'
        code+=klass('scpp',method('bump',v+'x += 1;',result='void',params=ref)+method('pass','return '+v+'x;',result='Token',params=token))
        code+=implementation('Leaf',base='scpp',interfaces=('Transfer',))
        body=s['infer']('n','1')+v+'x'+acc+'bump('+v+'n);echo '+v+'n;return '+v+'x'+acc+'pass('+v+'t);'
        code+=s['function']('transfer','Token',[('x','Transfer'),('t','Token')],body)
        code+=s['infer']('t','new Token()')+s['infer']('r','transfer(new Leaf(),'+v+'t)')+f'if ({v}t !== {v}r) {{return 1;}} return 8;'
        scenarios.append((code,b'2'))
        if flavor=='php':scenarios.append((good.replace('public override','#[\\Override] public'),b''))
        for i,(text,expected) in enumerate(scenarios):
            source_edit(app/('main.'+flavor),text);r=invoke(resident);f=work/f'{flavor}-success-{i}'
            assert 'error' not in r and 'error' not in fresh(app,f),(i,r)
            assert artifacts(out)==artifacts(f)
            for cc,library in libraries.items():incremental_native_build(out,work/(flavor+'-contracts-native-'+cc),cc,library,expected)
    finally:resident.stdin.close();resident.wait()
    print(flavor,'interface contracts, unchanged base dispatch and abstract obligations passed',flush=True)


def removal(work, flavor, libraries):
    s,evaluate,klass,method,interface,implementation=forms(flavor)
    v=s['v'];leaf=implementation('Leaf',evaluate('override'))
    sources={'contract':interface(),'leaf':leaf,'main':'return 8;'}
    for n in ['first','second']:sources[n]=s['function'](n,'Readable',[('x','Leaf')],f'return {v}x;')
    app,out=work/(flavor+'-removal'),work/(flavor+'-removal-out');write_app(app,flavor,sources)
    resident=service(app,out)
    try:
        steps=[('initial',None),('noop',None),('delete','first'),('delete','second'),('restore','first'),('restore','second'),('noop',None)]
        for i,(action,name) in enumerate(steps):
            if action=='delete':(app/(name+'.'+flavor)).unlink()
            if action=='restore':source_edit(app/(name+'.'+flavor),sources[name])
            before=artifacts(out);r=invoke(resident);f=work/f'{flavor}-removal-fresh-{i}'
            assert 'error' not in r and 'error' not in fresh(app,f),r
            now=artifacts(out);assert now==artifacts(f)
            assert len(adapter_files(out))==(0 if i==3 else 1)
            for old in before.keys()-now.keys():assert not (out/old).exists(),old
            for cc,lib in libraries.items():
                changed=incremental_native_build(out,work/(flavor+'-removal-native-'+cc),cc,lib,b'')
                if action=='noop':assert not changed and r['preparation']['prepared']==0 and r['emitted_sources']==[],r
    finally:resident.stdin.close();resident.wait()
    print(flavor,'interface adapter demand removal/restoration passed',flush=True)


def forwarding_removal(work, flavor, libraries):
    s,evaluate,klass,method,interface,implementation=forms(flavor)
    factory=s['function']('makeLeaf','Leaf',[],'return new Leaf();')
    field=s['field']('offset','int','8')
    leaf=implementation('Leaf',field,base='Base')+factory
    sources={'contract':interface(),'base':klass('Base',evaluate('', '8', default='2')),
             'leaf':leaf,'observer':s['function']('readLeaf','int',[('x','Leaf')],f"return {s['v']}x{s['access']}offset;"),'idle':s['function']('idle','int',[],'return 7;'),'main':'return 8;'}
    app,out=work/(flavor+'-forwarding'),work/(flavor+'-forwarding-out');write_app(app,flavor,sources)
    resident=service(app,out)
    try:
        for i,present in enumerate([True,True,False,False,True,True]):
            before=artifacts(out);stamps={p:(out/p).stat().st_mtime_ns for p in before}
            if i in [2,4]:source_edit(app/('leaf.'+flavor),leaf if present else implementation('Leaf',field,base='Base',interfaces=())+factory)
            r=invoke(resident);f=work/f'{flavor}-forwarding-fresh-{i}'
            assert 'error' not in r and 'error' not in fresh(app,f),r
            now=artifacts(out);assert now==artifacts(f),(flavor,i,'fresh mismatch')
            for old in before.keys()-now.keys():assert not (out/old).exists(),old
            units={Path(p).stem:u for p,u in json.loads((out/'manifest.json').read_text())['sources'].items()}
            # Only Leaf owns the bridge declaration and out-of-line definition.
            cpp=(out/units['leaf']).read_text()
            assert bool(re.search(r'Leaf::[^\n]*evaluate',cpp))==present,cpp
            headers='\n'.join(p.read_text() for p in out.rglob('*.hpp') if 'struct Leaf' in p.read_text())
            assert bool(re.search(r'evaluate[^\n]*override',headers))==present,headers
            if i:
                for name in ['idle','main','observer']:
                    path=Path(units[name]);assert before[path]==now[path] and stamps[path]==(out/path).stat().st_mtime_ns,(i,name)
            for cc,lib in libraries.items():
                changed=incremental_native_build(out,work/(flavor+'-forwarding-native-'+cc),cc,lib,b'')
                if i in [1,3,5]:assert not changed and r['preparation']['prepared']==0 and r['emitted_sources']==[],r
                if i in [2,4]:assert changed=={units[name] for name in ['leaf','observer','base','contract']},changed
        # The standalone declaration inventory must deduplicate the inherited target.
        combined=work/(flavor+'-forwarding-source.'+flavor)
        combined.write_text('\n'.join(sources.values()))
        facts=work/'facts.json';cpp=work/(flavor+'-forwarding-standalone.cpp')
        result=subprocess.run(['php',ROOT/'compiler/main.php','cpp',flavor,combined,facts,cpp],capture_output=True,text=True)
        assert result.returncode==0,result.stderr
        prototypes=[line for line in cpp.read_text().splitlines() if 'method_4_Base_evaluate(' in line and line.endswith(';') and not '{' in line]
        assert len(prototypes)==1,prototypes
        for cc,lib in libraries.items():
            exe=work/(flavor+'-forwarding-standalone-'+cc)
            result=subprocess.run([cc,'-std=c++23','-Wall','-Wextra','-Werror','-I',ROOT/'runtime/generated/public',cpp,lib,'-o',exe],capture_output=True,text=True)
            assert result.returncode==0,result.stderr
            result=subprocess.run([exe],capture_output=True);assert (result.returncode,result.stdout,result.stderr)==(8,b'',b'')
    finally:resident.stdin.close();resident.wait()
    print(flavor,'forwarding binding removal/restoration and standalone declaration reuse passed',flush=True)


def main():
    parser=argparse.ArgumentParser(description=__doc__);parser.add_argument('--output',type=Path);parser.add_argument('--memcheck',action='store_true');args=parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-interfaces-') as temporary:
        work=(args.output or Path(temporary)).resolve();work.mkdir(exist_ok=True)
        libraries={cc:runtime(work,cc) for cc in ['clang++','g++']}
        for flavor in ['cpp','php','js']:
            incremental(work,flavor,libraries)
            ownership(work,flavor,libraries,args.memcheck)
            contracts(work,flavor,libraries)
            removal(work,flavor,libraries)
            forwarding_removal(work,flavor,libraries)


if __name__=='__main__':main()

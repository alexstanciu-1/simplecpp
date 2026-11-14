"""Class storage, relaxed instance properties and explicit object boundaries across all flavors."""
import argparse
import json
from pathlib import Path
import subprocess
import tempfile

from app_namespace_native import artifacts, invoke, service
from extends_native import build, runtime, source_edit
from extends_regressions import forms

ROOT = Path(__file__).resolve().parents[2]


def fixture(flavor):
    v, access, record, field, getter, local, infer = forms(flavor)

    def function(name, result, parameters, body):
        params = ','.join(f'{n}:{t}' if flavor == 'js' else f'{t} {v}{n}' for n, t in parameters)
        head = f'{result} {name}({params})' if flavor == 'cpp' else f'function {name}({params}):{result}'
        return head + ' {' + body + '}'

    def klass(name, body):
        return record(name, body).replace('struct ', 'class ', 1)

    constructor = {
        'cpp': 'Node(int x=3) { this->x=x; }',
        'php': 'public function __construct(int $x=3) { $this->x=$x; }',
        'js': 'constructor(x:int=3) { this.x=x; }',
    }[flavor]
    node = klass('Node', field('x', 'int', '3') + constructor + getter())
    addition = klass('Addition', field('left', 'Node') + field('spare', 'Node', 'null'))
    holder = record('Holder', field('left', 'Node', 'null') + field('spare', 'Node'))
    other = klass('Other', field('x', 'int', '4'))
    point = record('Point', field('x', 'int', '1'))
    read = function('read', 'int', [('n', 'Node')], f'return {v}n{access}x;')
    optional = function('optional', '?Node', [('n', '?Node')], f'return {v}n;')
    # Default syntax is written on the parameter, not part of its resolved type.
    optional = optional.replace('n)', 'n=null)') if flavor != 'js' else optional.replace('n:?Node)', 'n:?Node=null)')
    mutate = function('mutate', 'void', [('n', 'Node')], f'{v}n{access}x=8; {v}n=new Node(); {v}n{access}x=99;')
    accept = function('accept', 'void', [('n', 'Node')], 'echo "C";')
    main = infer('n', 'new Node()') + infer('copy', v+'n') + f'mutate({v}copy); echo {v}n{access}get();'
    main += infer('a', 'new Addition()') + local('h', 'Holder')
    main += f'if ({v}a{access}spare !== null || {v}h{access}spare !== null) {{return 2;}}'
    main += f'{v}a{access}left={v}n; {v}h{access}left={v}a{access}left; echo read({v}h{access}left);'
    main += f'{v}a{access}left=null; {v}h{access}left=null; if (optional() !== null) {{return 1;}}'
    main += f'echo read(optional({v}n));' + local('p', 'Point') + infer('q', v+'p')
    main += f'{v}q{access}x=9; echo {v}p{access}x,{v}q{access}x; return 8;'
    failure_call = function('failCall', 'void', [], infer('a', 'new Addition()') + f'accept({v}a{access}left);')
    failure_return = function('failReturn', 'Node', [], infer('a', 'new Addition()') + f'return {v}a{access}left;')
    failure_access = function('failAccess', 'void', [], infer('a', 'new Addition()') + f'echo {v}a{access}left{access}x;')
    produce = function('produce', '?Node', [], 'echo "P"; return new Node();')
    once = function('once', 'void', [], 'accept(produce());')
    make = function('make', 'Node', [], 'return new Node();')
    fill = function('fill', 'Addition', [('n', 'Node')], infer('a', 'new Addition()') + f'{v}a{access}left={v}n; return {v}a;')
    reset = function('reset', 'void', [('a', 'Addition')], f'{v}a{access}left=null;')
    weak_setup = infer('n', 'optional(new Node())') + infer('w', 'weak(' + v + 'n)')
    weak_proof = function('weakProof', 'int', [], weak_setup + infer('held', 'lock(' + v + 'w)')
        + f'if ({v}held !== {v}n) {{return 1;}} {v}n=null;'
        + f'if (lock({v}w) === null) {{return 2;}} {v}held=null;'
        + f'if (lock({v}w) !== null) {{return 3;}} return 8;')
    weak_failure = function('weakFailure', 'void', [], weak_setup + f'{v}n=null; accept(lock({v}w));')
    api = read + optional + mutate + accept + failure_call + failure_return + failure_access + produce + once + make + fill + reset + weak_proof + weak_failure
    return locals()


def lifetime(work, flavor, output, libraries, memcheck):
    units = [output / n for n in json.loads((output / 'manifest.json').read_text())['translation_units'] if n != 'entry.cpp']
    api = next(p for p in units if 'void failCall_(' in p.read_text())
    probe = work / (flavor+'-proof.cpp')
    probe.write_text(f'#include "{api}"\n' + r'''
#include <cassert>
#include <cstring>
#include <stdexcept>
int main() {
    using namespace App;
    for (int i=0;i<32;++i) {
        try { failCall_(); return 1; }
        catch (const std::runtime_error& e) { assert(std::strcmp(e.what(),"ownership_empty_access")==0); }
        try { failReturn_(); return 2; }
        catch (const std::runtime_error&) {}
        try { failAccess_(); return 3; }
        catch (const std::runtime_error&) {}
    }
    assert(weakProof_()==8);
    try { weakFailure_(); return 4; }
    catch (const std::runtime_error& e) { assert(std::strcmp(e.what(),"ownership_empty_access")==0); }
    auto n=make_();
    auto observer=weak_Node(n);
    auto a=fill_(n);
    assert(n.ptr->count==2);
    n={};
    assert(observer.lock());
    reset_(a);
    assert(!observer.lock());
    once_();
}
''')
    for cc, library in libraries.items():
        binary=work/(flavor+'-proof-'+cc)
        command=[cc,'-std=c++23','-O1','-Wall','-Wextra','-Werror','-I',str(ROOT/'runtime/generated/public'),str(probe),
                 *[str(u) for u in units if u!=api],str(library),'-o',str(binary)]
        result=subprocess.run(command,capture_output=True,text=True)
        assert result.returncode==0,result.stderr
        result=subprocess.run([binary],capture_output=True)
        assert (result.returncode,result.stdout,result.stderr)==(0,b'PC',b''),result
        if not memcheck:
            continue
        check=subprocess.run(['valgrind','--leak-check=full','--errors-for-leak-kinds=all','--error-exitcode=99',binary],capture_output=True,text=True)
        assert check.returncode==0 and 'All heap blocks were freed' in check.stderr,check


def prove(work, flavor, libraries, memcheck):
    s=fixture(flavor)
    app=work/flavor;app.mkdir()
    (app/'module.json').write_text(json.dumps({'entry':'main.'+flavor}))
    sources={k:s[k] for k in ['node','addition','holder','other','point','api','main']}
    sources['idle']=s['function']('idle','int',[],'return 17;')
    files={k:app/(k+'.'+flavor) for k in sources}
    for k,text in sources.items():source_edit(files[k],text)
    output=work/(flavor+'-out');resident=service(app,output)
    nullable_accept=s['accept'].replace('Node','?Node')
    optional_api=s['api'].replace(s['accept'],nullable_accept)
    null_call='accept(null); return 8;'
    changes=[(None,None,None,b'88819'),(None,None,None,b'88819'),
             ('main',null_call,'Required object cannot be null',None),
             (None,None,'Required object cannot be null',None),
             ('api',optional_api,None,b'C'),('api',s['api'],'Required object cannot be null',None),
             ('main',s['main'],None,b'88819'),
             ('node',s['node'].replace('class ','struct ',1),'Unsupported explicit conversion',None),
             ('node',s['node'],None,b'88819'),
             ('holder',s['holder'].replace('Node','Other'),'Unsupported implicit conversion',None),
             ('holder',s['holder'],None,b'88819'),
             ('node',None,'Unsupported instance field type',None),(None,None,'Unsupported instance field type',None),
             ('node',s['node'],None,b'88819'),
             ('api',s['api'].replace(s['optional'],s['optional'].replace('?Node','Node')),'Required object cannot be null',None),
             ('api',s['api'],None,b'88819'),
             ('api',s['api'].replace(s['make'],s['function']('make','Node',[],'return null;')),'Required object cannot be null',None),
             ('api',s['api'],None,b'88819'),
             ('api',s['api'].replace(s['optional'],s['optional'].replace('?Node','?Missing')),'Unknown or unsupported type: Missing',None),
             ('api',s['api'],None,b'88819')]
    try:
        for revision,(name,text,error,expected) in enumerate(changes):
            if name:
                if text is None:files[name].unlink()
                else:source_edit(files[name],text)
            before=artifacts(output);current=invoke(resident)
            fresh_out=work/f'{flavor}-fresh-{revision}';fresh=service(app,fresh_out)
            try:comparison=invoke(fresh)
            finally:fresh.stdin.close();fresh.wait()
            if error:
                assert error in current.get('error',''),(flavor,revision,current)
                assert current['error']==comparison.get('error'),(current,comparison)
                assert artifacts(output)==before
                continue
            assert 'error' not in current and 'error' not in comparison,(flavor,revision,current,comparison)
            assert artifacts(output)==artifacts(fresh_out),(flavor,revision)
            if revision:
                assert current['preparation']['reused']>=1,current
                assert not any(p.endswith('/idle.'+flavor) for p in current['emitted_sources']),current
            if revision==1:
                assert current['preparation']['prepared']==0 and current['emitted_sources']==[],current
            if revision in [0,4,6,8,10,13,15]:
                for cc,library in libraries.items():build(output,work/(flavor+'-'+cc),cc,library,expected)
        lifetime(work,flavor,output,libraries,memcheck)
        # Property writes stay ordinary owner assignments; presence checks are at required boundaries.
        code=b'\n'.join(artifacts(output).values())
        assert b'owner_Node::require' in code and b'.left' in code
        units = [output / n for n in json.loads((output / 'manifest.json').read_text())['translation_units']]
        api = next(p.read_text() for p in units if 'void failCall_(' in p.read_text())
        for name in ['fill_', 'reset_']:
            body = api.split(name + '(', 2)[2].split('\n}', 1)[0]
            assert '::require(' not in body, body
        assert 'accept_(owner_Node::require(produce_()))' in api
    finally:resident.stdin.close();resident.wait()


def signature_recovery(work, flavor, libraries):
    """Provider-only recovery and return contracts invalidate unchanged consumers."""
    s = fixture(flavor)
    app = work / (flavor + '-signatures')
    app.mkdir()
    output = work / (flavor + '-signatures-out')
    (app / 'module.json').write_text(json.dumps({'entry': 'main.' + flavor}))
    files = {name: app / (name + '.' + flavor) for name in ['main', 'api', 'missing', 'idle']}
    main = 'echo consume(produce()); return 8;'
    consume = s['function']('consume', 'int', [('n', 'Missing')], 'return 7;')
    produce = s['function']('produce', '?Missing', [], 'return new Missing();')
    api = consume + produce
    source_edit(files['main'], main)
    source_edit(files['api'], api)
    source_edit(files['idle'], s['function']('idle', 'int', [], 'return 17;'))
    declaration = s['klass']('Missing', '')
    edits = [(None, None, True, None), (None, None, True, None),
             ('missing', declaration, False, True), ('missing', None, True, None),
             (None, None, True, None), ('missing', declaration, False, True),
             ('api', api.replace('?Missing', 'Missing'), False, False),
             ('api', api, False, True), (None, None, False, True)]
    resident = service(app, output)
    try:
        for revision, (name, text, error, checked) in enumerate(edits):
            if name:
                if text is None:
                    files[name].unlink()
                else:
                    source_edit(files[name], text)
            before = artifacts(output)
            current = invoke(resident)
            fresh_output = work / f'{flavor}-signature-fresh-{revision}'
            fresh = service(app, fresh_output)
            try:
                comparison = invoke(fresh)
            finally:
                fresh.stdin.close()
                fresh.wait()
            if error:
                assert 'Unknown' in current.get('error', ''), current
                assert current['error'] == comparison.get('error'), (current, comparison)
                assert artifacts(output) == before
                continue
            assert 'error' not in current and 'error' not in comparison, (current, comparison)
            assert artifacts(output) == artifacts(fresh_output)
            units = [output / n for n in json.loads((output / 'manifest.json').read_text())['translation_units']]
            caller = next(p.read_text() for p in units if 'consume_(' in p.read_text() and 'produce_()' in p.read_text()
                          and 'scpp::echo_int' in p.read_text())
            assert ('consume_(owner_Missing::require(produce_()))' in caller) == checked, caller
            if revision in [5, 6, 7, 8]:
                assert not any(p.endswith('/idle.' + flavor) for p in current['emitted_sources']), current
                assert current['preparation']['reused'] >= 1, current
            if revision == 8:
                assert current['preparation']['prepared'] == 0 and current['emitted_sources'] == [], current
            else:
                for cc, library in libraries.items():
                    build(output, work / (flavor + '-signature-' + cc), cc, library, b'7')
    finally:
        resident.stdin.close()
        resident.wait()


if __name__=='__main__':
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output',type=Path)
    parser.add_argument('--memcheck',action='store_true')
    args=parser.parse_args()
    with tempfile.TemporaryDirectory() as directory:
        work=(args.output or Path(directory)).resolve();work.mkdir(exist_ok=True)
        libraries={cc:runtime(work,cc) for cc in ['clang++','g++']}
        for flavor in ['cpp','php','js']:
            prove(work,flavor,libraries,args.memcheck)
            signature_recovery(work,flavor,libraries)
            print(flavor,'class defaults and boundary recovery passed',flush=True)

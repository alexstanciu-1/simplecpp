"""Concrete virtual methods: dispatch, explicit intent and retained/native dependency recovery."""
import argparse
import json
import subprocess
import tempfile
from pathlib import Path

from app_namespace_native import artifacts, invoke, service
from classes_native import fixture
from extends_native import runtime, source_edit
from inline_adapters_native import adapter_files, incremental_native_build
from shared_upcasts_native import fixture as ownership_fixture, lifetime as ownership_lifetime

ROOT = Path(__file__).resolve().parents[2]


def forms(flavor):
    s = fixture(flavor)
    def method(intent, value='8', final=False, default='2', result='int', parameter='int'):
        if flavor == 'cpp':
            return f'{"virtual " if intent == "virtual" else ""}{result} evaluate({parameter} delta={default})' + (' override' if intent == 'override' else '') + (' final' if final else '') + f' {{ return {value}; }}'
        if flavor == 'php':
            marker = {'virtual': 'virtual ', 'override': 'override ', 'attribute-virtual': '#[\\Virtual] public ',
                      'attribute-override': '#[\\Override] public ', '': ''}[intent]
            return ('public ' if not intent.startswith('attribute') else '') + marker + ('final ' if final else '') + f'function evaluate({parameter} $delta={default}):{result} {{ return {value}; }}'
        return (intent + ' ' if intent else '') + ('final ' if final else '') + f'evaluate(delta:{parameter}={default}):{result} {{ return {value}; }}'
    def klass(name, body='', base=None):
        return s['record'](name, body, base).replace('struct ', 'class ', 1)
    return s, method, klass


def prove(work, flavor, libraries):
    s, method, klass = forms(flavor)
    v = s['v']
    app, out = work / flavor, work / (flavor + '-out')
    app.mkdir()
    (app / 'module.json').write_text(json.dumps({'entry': 'main.' + flavor}))
    base = klass('Node', method('virtual', v+'delta', default='1'))
    leaf = klass('Leaf', method('override', '8 + '+v+'delta'), 'Node')
    twig = klass('Twig', '', 'Leaf')
    api = s['function']('evaluate_node', 'int', [('n', 'Node')], f'return {v}n{s["access"]}evaluate();')
    main = s['infer']('n', 'new Twig()') + f'echo evaluate_node({v}n),{v}n{s["access"]}evaluate(); return 8;'
    convert = s['function']('convert', 'Node', [('n', 'Leaf')], f'return {v}n;')
    if flavor == 'cpp': convert = 'virtual '+convert
    elif flavor == 'php': convert = 'public virtual '+convert
    else: convert = 'virtual '+convert.removeprefix('function ')
    converter = klass('Converter', convert)
    sources = {'node': base, 'leaf': leaf, 'twig': twig, 'api': api, 'main': main, 'converter': converter,
               'idle': s['function']('unrelated', 'int', [], 'return 17;')}
    paths = {name: app / (name + '.' + flavor) for name in sources}
    for name, text in sources.items(): source_edit(paths[name], text)
    steps = [
        (None, None, b'910', None), (None, None, b'910', None),
        ('leaf', leaf.replace('8 +', '9 +'), b'1011', None), ('leaf', leaf, b'910', None),
        ('leaf', klass('Leaf', '', 'Node'), b'11', None), ('leaf', leaf, b'910', None),
        ('leaf', klass('Leaf', method('', '8'), 'Node'), None, 'requires override'),
        (None, None, None, 'requires override'), ('leaf', leaf, b'910', None),
        ('node', klass('Node', method('', v+'delta', default='1')), None, 'Nonvirtual method hiding'),
        ('node', base, b'910', None),
        ('leaf', klass('Leaf', method('override', 'true', result='bool'), 'Node'), None, 'signature must match'),
        ('leaf', leaf, b'910', None),
        ('leaf', klass('Leaf', method('override', '8', parameter='float'), 'Node'), None, 'signature must match'),
        ('leaf', leaf, b'910', None),
        ('twig', klass('Twig', method('override', '20'), 'Leaf'), b'2020', None),
        ('leaf', klass('Leaf', method('override', '8', final=True), 'Node'), None, 'final method'),
        (None, None, None, 'final method'), ('leaf', leaf, b'2020', None),
        ('twig', twig, b'910', None),
        ('node', None, None, 'Base must be a class'), ('node', base, b'910', None),
        ('twig', klass('Twig', method('override', '20')), None, 'no inherited virtual target'),
        ('twig', twig, b'910', None),
        ('api', api.replace(f'{v}n{s["access"]}evaluate()', f'{v}n{s["access"]}missing()'), None, 'Unknown method'),
        ('api', api, b'910', None),
    ]
    if flavor == 'php':
        steps += [('node', klass('Node', method('attribute-virtual', '$delta', default='1')), b'910', None),
                  ('leaf', klass('Leaf', method('attribute-override', '8 + $delta'), 'Node'), b'910', None),
                  ('leaf', leaf.replace('public override', '#[\\Override] public override'), None, 'Duplicate method modifier'),
                  ('leaf', leaf, b'910', None),
                  ('node', base.replace('public virtual', '#[\\Virtual(1)] public'), None, 'take no arguments'),
                  ('node', base, b'910', None)]
    conflict = method('virtual', '8')
    if flavor == 'cpp': conflict = conflict.replace(' {', ' override {', 1)
    else: conflict = conflict.replace('virtual ', 'virtual override ', 1)
    static = ('static ' + method('virtual', '8')) if flavor == 'cpp' else method('virtual', '8').replace('virtual ', 'static virtual ', 1)
    private = ('private: ' + method('virtual', '8')) if flavor == 'cpp' else method('virtual', '8').replace('public ', 'private ', 1) if flavor == 'php' else 'private '+method('virtual', '8')
    for invalid, message in [(conflict, 'Conflicting virtual'), (static, 'public or protected instance'),
                             (private, 'public or protected instance'),
                             (method('override', '8').replace('=2', ''), 'signature must match')]:
        steps += [('leaf', klass('Leaf', invalid, 'Node'), None, message), ('leaf', leaf, b'910', None)]
    steps += [('node', klass('Node', method('virtual', v+'delta', default='1', result='float')), None, 'signature must match'),
              ('node', base, b'910', None)]
    steps += [('converter', klass('Converter'), b'910', None),
              ('converter', converter, b'910', None),
              ('converter', None, b'910', None), ('converter', converter, b'910', None)]
    layout_revisions = {len(steps), len(steps) + 1}
    steps += [('node', klass('Node', s['field']('extra', 'int', '17') + method('virtual', v+'delta', default='1')), b'910', None),
              ('node', base, b'910', None)]
    resident = service(app, out)
    try:
        for revision, (name, text, expected, error) in enumerate(steps):
            before = artifacts(out)
            before_stamps = {p: (out/p).stat().st_mtime_ns for p in before}
            adapters_before = adapter_files(out)
            if name:
                if text is None: paths[name].unlink()
                else: source_edit(paths[name], text)
            current = invoke(resident)
            fresh_out = work / f'{flavor}-fresh-{revision}'
            fresh = service(app, fresh_out)
            try: comparison = invoke(fresh)
            finally: fresh.stdin.close(); fresh.wait()
            if error:
                assert error in current.get('error', ''), (revision, current)
                assert current['error'] == comparison.get('error'), (revision, current, comparison)
                assert artifacts(out) == before
                continue
            assert 'error' not in current and 'error' not in comparison, (revision, current, comparison)
            now = artifacts(out)
            if name == 'converter':
                assert len(adapter_files(out)) == (2 if text == converter else 1), (revision, adapter_files(out).keys())
            assert now == artifacts(fresh_out), (revision, 'retained/fresh differs')
            for path in adapters_before.keys() - adapter_files(out).keys(): assert not (out / path).exists()
            if revision:
                assert not any(p.endswith('/idle.'+flavor) for p in current['emitted_sources']), current
            if revision == 1:
                assert current['preparation']['prepared'] == 0 and current['emitted_sources'] == [], current
            manifest = json.loads((out / 'manifest.json').read_text())
            units = {source.split('/')[-1].split('.')[0]: unit for source, unit in manifest['sources'].items()}
            if revision in layout_revisions:
                assert any(p.suffix == '.hpp' and before.get(p) != text for p, text in now.items())
                for name in ['api', 'main']:
                    path = Path(units[name])
                    assert now[path] == before[path], (revision, name, 'caller C++ changed')
                    assert (out/path).stat().st_mtime_ns == before_stamps[path], (revision, name, 'caller timestamp changed')
            for cc, library in libraries.items():
                native = work / (flavor+'-native-'+cc)
                exe = native/'program'
                stamp = exe.stat().st_mtime_ns if exe.exists() else None
                changed = incremental_native_build(out, native, cc, library, expected)
                if revision == 1:
                    assert changed == set() and exe.stat().st_mtime_ns == stamp, changed
                if revision in [2, 3]:
                    assert changed == {units['leaf']}, (revision, changed)
                    assert now[Path(units['api'])] == before[Path(units['api'])]
                if revision in layout_revisions:
                    assert {units['api'], units['main']} <= changed, (revision, changed)
                    assert units['idle'] not in changed, (revision, 'unrelated object rebuilt')
                if revision in [4, 5]:
                    assert units['main'] in changed, (revision, changed)
                    assert units['api'] not in changed and units['idle'] not in changed
            print(flavor, revision, 'virtual incremental/native passed', flush=True)
    finally:
        resident.stdin.close(); resident.wait()

def behavior(work, flavor, libraries):
    """Retain a shared receiver across replacement and check presence before argument effects."""
    s, method, klass = forms(flavor)
    v, access = s['v'], s['access']
    app, out = work/(flavor+'-behavior'), work/(flavor+'-behavior-out')
    app.mkdir()
    (app/'module.json').write_text(json.dumps({'entry': 'main.'+flavor}))
    identity = s['function']('identity', 'Node', [('n','Node')], f'return {v}n;')
    if flavor == 'cpp': identity = 'virtual '+identity
    elif flavor == 'php': identity = 'public virtual '+identity
    else: identity = 'virtual '+identity.removeprefix('function ')
    base = klass('Node', method('virtual', v+'delta', default='1')+identity)
    leaf = klass('Leaf', method('override', '8 + '+v+'delta'), 'Node')
    box = klass('Box', s['field']('value','?Node'))
    reset = s['function']('reset', 'int', [('b','Box')], f'{v}b{access}value=new Node(); echo "R"; return 2;')
    mark = s['function']('mark', 'int', [], 'echo "BAD"; return 2;')
    run = s['function']('run', 'int', [], s['infer']('b','new Box()')+f'{v}b{access}value=new Leaf(); return {v}b{access}value{access}evaluate(reset({v}b));')
    fail = s['function']('fail', 'int', [], s['infer']('b','new Box()')+f'return {v}b{access}value{access}evaluate(mark());')
    shared = s['function']('sharedResult', 'Node', [('n','Node')], f'return {v}n{access}identity({v}n);')
    source = base+leaf+box+reset+mark+run+fail+shared+'return 8;'
    source_edit(app/('main.'+flavor),source)
    process = service(app,out)
    try:
        result = invoke(process)
        assert 'error' not in result,result
    finally: process.stdin.close();process.wait()
    units = [out/u for u in json.loads((out/'manifest.json').read_text())['translation_units'] if u!='entry.cpp']
    unit = next(p for p in units if ' run_(' in p.read_text())
    probe = work/(flavor+'-receiver.cpp')
    probe.write_text(f'#include "{unit}"\n'+r'''
#include <cassert>
#include <stdexcept>
int main() {
    assert(App::run_()==10);
    try { App::fail_(); return 3; } catch (const std::runtime_error&) {}
    auto n=App::owner_Node::make();
    assert(App::sharedResult_(n).ptr==n.ptr);
}
''')
    for cc,library in libraries.items():
        binary=work/(flavor+'-receiver-'+cc)
        result=subprocess.run([cc,'-std=c++23','-Wall','-Wextra','-Werror','-I',ROOT/'runtime/generated/public',probe,
            *[u for u in units if u!=unit],library,'-o',binary],capture_output=True,text=True)
        assert result.returncode==0,result.stderr
        result=subprocess.run([binary],capture_output=True)
        assert (result.returncode,result.stdout,result.stderr)==(0,b'R',b''),result
    # Standalone emission uses the same member signature/forward-declaration path.
    facts=work/'facts.json'
    facts.write_text(json.dumps({'schema':1,'native_integers':{'int':{'signed':True,'digits':31}}}))
    cpp=work/(flavor+'-standalone.cpp')
    result=subprocess.run(['php',ROOT/'compiler/main.php','cpp',flavor,app/('main.'+flavor),facts,cpp],capture_output=True,text=True)
    assert result.returncode==0,result.stderr
    for cc,library in libraries.items():
        binary=work/(flavor+'-standalone-'+cc)
        result=subprocess.run([cc,'-std=c++23','-Wall','-Wextra','-Werror','-I',ROOT/'runtime/generated/public',cpp,library,'-o',binary],capture_output=True,text=True)
        assert result.returncode==0,result.stderr
        assert subprocess.run([binary],capture_output=True).returncode==8


    override = identity.replace('virtual ', 'override ', 1)
    optional_parameter = override.replace('Node '+v+'n', '?Node '+v+'n') if flavor != 'js' else override.replace('n:Node', 'n:?Node')
    reference_parameter = override.replace('Node '+v+'n', 'Node &'+v+'n') if flavor != 'js' else override.replace('n:Node', '&n:Node')
    optional_result = override.replace('Node identity', '?Node identity') if flavor == 'cpp' else override.replace('):Node', '):?Node')
    resident = service(app, out)
    try:
        assert 'error' not in invoke(resident)
        for invalid in [optional_parameter, reference_parameter, optional_result]:
            bad_leaf = klass('Leaf', method('override', '8 + '+v+'delta')+invalid, 'Node')
            source_edit(app/('main.'+flavor), source.replace(leaf, bad_leaf))
            failed = invoke(resident)
            assert 'Override signature must match exactly' in failed.get('error', ''), failed
            source_edit(app/('main.'+flavor), source)
            assert 'error' not in invoke(resident)
    finally:
        resident.stdin.close(); resident.wait()


def namespace_shadow(work, flavor, libraries):
    """A source class named scpp must not shadow the runtime namespace in member signatures."""
    s, method, klass = forms(flavor)
    source = klass('scpp', method('virtual', '7')) + klass('LeafShadow', method('override', '9'), 'scpp')
    source += s['infer']('n', 'new LeafShadow()') + f'echo {s["v"]}n{s["access"]}evaluate(); return 8;'
    app, out = work/(flavor+'-shadow'), work/(flavor+'-shadow-out')
    app.mkdir()
    (app/'module.json').write_text(json.dumps({'entry':'main.'+flavor}))
    path = app/('main.'+flavor)
    source_edit(path, source)
    resident = service(app, out)
    try:
        result = invoke(resident)
        assert 'error' not in result, result
    finally:
        resident.stdin.close(); resident.wait()
    assert any(b'virtual ::scpp::int64 scpp_virtual_evaluate' in text for text in artifacts(out).values())
    facts = work/'shadow-facts.json'
    facts.write_text(json.dumps({'schema':1,'native_integers':{'int':{'signed':True,'digits':31}}}))
    cpp = work/(flavor+'-shadow.cpp')
    result = subprocess.run(['php', ROOT/'compiler/main.php', 'cpp', flavor, path, facts, cpp], capture_output=True, text=True)
    assert result.returncode == 0, result.stderr
    for cc, library in libraries.items():
        incremental_native_build(out, work/(flavor+'-shadow-'+cc), cc, library, b'9')
        binary = work/(flavor+'-shadow-single-'+cc)
        result = subprocess.run([cc, '-std=c++23', '-Wall', '-Wextra', '-Werror', '-I', ROOT/'runtime/generated/public',
                                 cpp, library, '-o', binary], capture_output=True, text=True)
        assert result.returncode == 0, result.stderr
        result = subprocess.run([binary], capture_output=True)
        assert (result.returncode, result.stdout, result.stderr) == (8, b'9', b''), result


def ownership(work, flavor, libraries, memcheck):
    """Exercise polymorphic cleanup and actual moves with an owning derived field."""
    s = ownership_fixture(flavor)
    f, method, klass = forms(flavor)
    v, access = f['v'], f['access']
    node = s['node']
    at = node.rfind('}')
    s['node'] = node[:at] + method('virtual', f'{v}this{access}x', default='1') + node[at:]
    s['leaf'] = klass('Leaf', method('override', f'{v}this{access}x + {v}delta'), 'Literal')
    app, out = work / (flavor+'-ownership'), work / (flavor+'-ownership-out')
    app.mkdir()
    (app/'module.json').write_text(json.dumps({'entry': 'main.'+flavor}))
    for name in ['node', 'token', 'other', 'literal', 'leaf', 'alternate', 'explicit', 'fail', 'holder', 'api', 'main']:
        source_edit(app/(name+'.'+flavor), s[name])
    resident = service(app, out)
    try:
        current = invoke(resident)
        assert 'error' not in current, current
    finally:
        resident.stdin.close(); resident.wait()
    ownership_lifetime(work, out, flavor, libraries, memcheck)
    units = [out/u for u in json.loads((out/'manifest.json').read_text())['translation_units'] if u != 'entry.cpp']
    api = next(p for p in units if 'owner_Leaf make_(' in p.read_text())
    probe = work/(flavor+'-payload.cpp')
    probe.write_text(f'#include "{api}"\n'+r'''
#include <cassert>
#include <type_traits>
int main() {
    using namespace App;
    static_assert(std::has_virtual_destructor_v<scpp_records::Node>);
    static_assert(std::is_nothrow_move_constructible_v<scpp_records::Leaf>);
    static_assert(std::is_nothrow_move_assignable_v<scpp_records::Leaf>);
    auto token=makeToken_();
    auto observer=weak_Token(token);
    {
        auto leaf=make_(token);
        auto base=view_(leaf);
        assert(base.ptr==leaf.ptr && base->scpp_virtual_evaluate(2)==7);
        base->x=9;
        assert(leaf->scpp_virtual_evaluate(2)==11);
        scpp_records::Leaf copied=*leaf.view;
        assert(copied.hold.ptr==token.ptr);
        scpp_records::Leaf moved=static_cast<scpp_records::Leaf&&>(copied);
        assert(copied.hold.ptr==nullptr && moved.hold.ptr==token.ptr);
        copied=moved;
        assert(copied.hold.ptr==token.ptr);
        moved=static_cast<scpp_records::Leaf&&>(copied);
        assert(copied.hold.ptr==nullptr && moved.hold.ptr==token.ptr);
        token={};
    }
    assert(!observer.lock());
}
''')
    for cc, library in libraries.items():
        binary = work/(flavor+'-payload-'+cc)
        result = subprocess.run([cc, '-std=c++23', '-O1', '-Wall', '-Wextra', '-Werror', '-I', ROOT/'runtime/generated/public',
            probe, *[u for u in units if u != api], library, '-o', binary], capture_output=True, text=True)
        assert result.returncode == 0, result.stderr
        result = subprocess.run([binary], capture_output=True)
        assert (result.returncode, result.stderr) == (0, b''), result
        if memcheck:
            result = subprocess.run(['valgrind', '--leak-check=full', '--errors-for-leak-kinds=all', '--error-exitcode=99', binary], capture_output=True, text=True)
            assert result.returncode == 0 and 'All heap blocks were freed' in result.stderr, result.stderr



if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    parser.add_argument('--memcheck', action='store_true')
    args = parser.parse_args()
    with tempfile.TemporaryDirectory() as directory:
        work = (args.output or Path(directory)).resolve(); work.mkdir(exist_ok=True)
        libraries = {cc: runtime(work, cc) for cc in ['clang++', 'g++']}
        for flavor in ['cpp', 'php', 'js']:
            prove(work, flavor, libraries)
            ownership(work, flavor, libraries, args.memcheck)
            behavior(work, flavor, libraries)
            namespace_shadow(work, flavor, libraries)

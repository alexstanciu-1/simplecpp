"""Shared class base views: native lifetime, conversion contracts and retained recovery."""
import argparse
import json
from pathlib import Path
import subprocess
import tempfile

from app_namespace_native import artifacts, invoke, service
from classes_native import fixture as class_fixture
from extends_native import build, runtime, source_edit

ROOT = Path(__file__).resolve().parents[2]


def fixture(flavor):
    s = class_fixture(flavor)
    v, access, record, field, local, infer, function = (s[k] for k in ['v', 'access', 'record', 'field', 'local', 'infer', 'function'])

    def klass(name, body='', base=None):
        return record(name, body, base).replace('struct ', 'class ', 1)

    node = s['node']
    replacement_method = s['getter']()
    token = klass('Token')
    other = klass('Other')
    literal = klass('Literal', field('hold', 'Token'), 'Node')
    leaf = klass('Leaf', '', 'Literal')
    alternate = klass('Alternate', field('extra', 'int', '99'), 'Node')
    explicit_ctor = {
        'cpp': 'Explicit(int x) : Node(x) {}',
        'php': 'public function __construct(int $x) { parent::__construct($x); }',
        'js': 'constructor(x:int) { super(x); }',
    }[flavor]
    explicit = klass('Explicit', explicit_ctor, 'Node')
    fail_ctor = {
        'cpp': 'Fail(Token t) : Node(5) { this->hold=t; accept(maybe(null)); }',
        'php': 'public function __construct(Token $t) { parent::__construct(5); $this->hold=$t; accept(maybe(null)); }',
        'js': 'constructor(t:Token) { super(5); this.hold=t; accept(maybe(null)); }',
    }[flavor]
    fail = klass('Fail', field('hold', 'Token') + fail_ctor, 'Node')
    holder = record('Holder', field('value', 'Node'))
    read = function('read', 'int', [('n', 'Node')], f'return {v}n{access}x;')
    view = function('view', 'Node', [('n', 'Leaf')], f'return {v}n;')
    maybe = function('maybe', '?Leaf', [('n', '?Leaf')], f'return {v}n;')
    optional = function('optional', '?Node', [('n', '?Leaf')], f'return {v}n;')
    needed = function('needed', 'Node', [('n', '?Leaf')], f'return {v}n;')
    accept = function('accept', 'void', [('n', 'Node')], 'echo "C";')
    failure = function('failure', 'void', [], 'accept(maybe(null));')
    main = infer('n', 'new Leaf(5)') + infer('b', f'view({v}n)')
    main += f'if ({v}b !== {v}n) {{return 1;}} {v}b{access}x=8; echo read({v}n);'
    main += local('h', 'Holder') + f'{v}h{access}value={v}n; echo read({v}h{access}value);'
    main += f'echo read(maybe({v}n)); if (optional(null) !== null) {{return 2;}} echo read(new Explicit(9)); return 8;'
    make = function('make', 'Leaf', [('t', 'Token')], infer('n', 'new Leaf(5)')
                    + f'{v}n{access}hold={v}t; return {v}n;')
    make_token = function('makeToken', 'Token', [], 'return new Token();')
    fail_construction = function('failConstruction', 'void', [('t', 'Token')], infer('n', f'new Fail({v}t)'))
    api = read + view + maybe + optional + needed + accept + failure + make + make_token + fail_construction
    return locals()


def lifetime(work, output, flavor, libraries, memcheck):
    units = [output / n for n in json.loads((output / 'manifest.json').read_text())['translation_units'] if n != 'entry.cpp']
    api = next(p for p in units if 'owner_Leaf make_(' in p.read_text())
    probe = work / (flavor + '-lifetime.cpp')
    probe.write_text(f'#include "{api}"\n' + r'''
#include <cassert>
#include <cstdio>
#include <stdexcept>
int main() {
    using namespace App;
    static_assert(sizeof(owner_Node)==2*sizeof(void*));
    static_assert(sizeof(weak_Node)==2*sizeof(void*));
    for (int i=0;i<64;++i) {
        auto token=makeToken_();
        auto observer=weak_Token(token);
        try { failConstruction_(token); return 3; }
        catch (const std::runtime_error&) { assert(token.ptr->count==1); }
        auto leaf=make_(token);
        auto base=view_(leaf);
        assert(leaf.ptr==base.ptr && leaf.ptr->count==2);
        assert(base.view==static_cast<scpp_records::Node*>(leaf.view));
        auto weak_base=weak_Node(base);
        token={};
        leaf={};
        assert(observer.lock());
        {
            auto locked=weak_base.lock();
            assert(locked.ptr==base.ptr && locked.view==base.view);
            base={};
            assert(observer.lock());
            auto copied=locked;
            copied=static_cast<owner_Node&&>(copied); // Self move must retain the allocation.
            assert(copied.ptr==locked.ptr && copied->x==5);
        }
        // The derived-only Token field proves concrete destruction through the base view.
        assert(!observer.lock() && !weak_base.lock());
        assert(weak_base.ptr->count==0);
        try { needed_({}); return 1; } catch (const std::runtime_error&) {}
        try { failure_(); return 2; } catch (const std::runtime_error&) {}
    }
    std::printf("{\"handle\":%zu,\"weak\":%zu,\"control\":%zu,\"allocation\":%zu}\n",
        sizeof(owner_Node), sizeof(weak_Node), sizeof(scpp::application_control), sizeof(owner_Leaf::allocation));
}
''')
    for cc, library in libraries.items():
        binary = work / (flavor + '-lifetime-' + cc)
        result = subprocess.run([cc, '-std=c++23', '-O1', '-Wall', '-Wextra', '-Werror',
            '-I', str(ROOT / 'runtime/generated/public'), str(probe), *[str(p) for p in units if p != api],
            str(library), '-o', str(binary)], capture_output=True, text=True)
        assert result.returncode == 0, result.stderr
        result = subprocess.run([binary], capture_output=True)
        assert (result.returncode, result.stderr) == (0, b''), result
        sizes = json.loads(result.stdout)
        assert sizes['handle'] == sizes['weak'] and sizes['allocation'] > sizes['control'], sizes
        print(flavor, cc, sizes, flush=True)
        if memcheck:
            result = subprocess.run(['valgrind', '--leak-check=full', '--errors-for-leak-kinds=all',
                '--error-exitcode=99', binary], capture_output=True, text=True)
            assert result.returncode == 0 and 'All heap blocks were freed' in result.stderr, result.stderr


def prove(work, flavor, libraries, memcheck):
    s = fixture(flavor)
    app = work / flavor
    app.mkdir()
    output = work / (flavor + '-out')
    (app / 'module.json').write_text(json.dumps({'entry': 'main.' + flavor}))
    names = ['node', 'token', 'other', 'literal', 'leaf', 'alternate', 'explicit', 'fail', 'holder', 'api', 'main']
    files = {name: app / (name + '.' + flavor) for name in names}
    for name in names:
        source_edit(files[name], s[name])
    source_edit(app / ('idle.' + flavor), s['function']('idle', 'int', [], 'return 17;'))
    relation = (' : ' if flavor == 'cpp' else ' extends ')
    edits = [(None, None, None), (None, None, None),
        ('literal', s['literal'].replace(relation + 'Node', relation + 'Other'), 'Unsupported explicit conversion'),
        (None, None, 'Unsupported explicit conversion'), ('literal', s['literal'], None),
        ('node', None, 'Base must be a class'), (None, None, 'Base must be a class'), ('node', s['node'], None),
        ('node', s['node'].replace('class ', 'struct ', 1), 'Base must be a class'), ('node', s['node'], None),
        ('leaf', s['leaf'].replace('class ', 'struct ', 1), 'Base must be a struct'), ('leaf', s['leaf'], None),
        ('leaf', s['klass']('Leaf', '', 'Leaf'), 'Recursive inline layout'), ('leaf', s['leaf'], None),
        ('api', s['api'] + s['function']('down', 'Leaf', [('n', 'Node')], f'return {s["v"]}n;'), 'Unsupported explicit conversion'),
        ('api', s['api'], None),
        ('api', s['api'] + s['function']('unrelated', 'Node', [('n', 'Other')], f'return {s["v"]}n;'), 'Unsupported explicit conversion'),
        ('api', s['api'], None),
        ('literal', s['literal'].replace(relation + 'Node', relation + 'Alternate'), None),
        ('alternate', s['alternate'].replace(relation + 'Node', relation + 'Other'), 'Unsupported explicit conversion'),
        ('alternate', s['alternate'], None), ('literal', s['literal'], None),
        ('leaf', s['klass']('Leaf', s['replacement_method'], 'Literal'),
         'Inherited method redeclaration is deferred'), ('leaf', s['leaf'], None)]
    resident = service(app, output)
    try:
        for revision, (name, text, error) in enumerate(edits):
            if name:
                if text is None:
                    files[name].unlink()
                else:
                    source_edit(files[name], text)
            before = artifacts(output)
            current = invoke(resident)
            fresh_out = work / f'{flavor}-fresh-{revision}'
            fresh = service(app, fresh_out)
            try:
                comparison = invoke(fresh)
            finally:
                fresh.stdin.close()
                fresh.wait()
            if error:
                assert error in current.get('error', ''), (revision, current)
                assert current['error'] == comparison.get('error'), (current, comparison)
                assert artifacts(output) == before
                continue
            assert 'error' not in current and 'error' not in comparison, (revision, current, comparison)
            assert artifacts(output) == artifacts(fresh_out), revision
            if revision:
                assert current['preparation']['reused'] >= 1, current
                assert not any(p.endswith('/idle.' + flavor) for p in current['emitted_sources']), current
            if revision == 1:
                assert current['preparation']['prepared'] == 0 and current['emitted_sources'] == [], current
            else:
                for cc, library in libraries.items():
                    build(output, work / (flavor + '-' + cc), cc, library, b'8889')
        lifetime(work, output, flavor, libraries, memcheck)
    finally:
        resident.stdin.close()
        resident.wait()


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--memcheck', action='store_true')
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory() as directory:
        work = (args.output or Path(directory)).resolve()
        work.mkdir(exist_ok=True)
        libraries = {cc: runtime(work, cc) for cc in ['clang++', 'g++']}
        for cc in libraries:
            subprocess.run([cc, '-std=c++23', '-Wall', '-Wextra', '-Werror', '-fsyntax-only', '-x', 'c++',
                '-I', str(ROOT / 'runtime/generated/public'), '-'],
                input='#include <scpp/owner_access.hpp>\n', text=True, check=True)
        for flavor in ['cpp', 'php', 'js']:
            prove(work, flavor, libraries, args.memcheck)
            print(flavor, 'shared upcasts passed', flush=True)

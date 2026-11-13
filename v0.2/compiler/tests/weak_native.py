"""Weak observation, strong identity and incremental equivalence for PHP, C++ and JS source."""
import argparse
import json
import os
from pathlib import Path
import subprocess
import tempfile

from app_namespace_native import artifacts, invoke, service, write_build
from structs_native import build


def fixture(flavor):
    names = {'php': '$', 'cpp': '', 'js': ''}[flavor]
    access = '.' if flavor == 'js' else '->'

    def function(name, result, param, body):
        param_name, param_type = param
        param_text = f'{param_name}: {param_type}' if flavor == 'js' else f'{param_type} {names}{param_name}'
        signature = f'{result} {name}({param_text})' if flavor == 'cpp' else f'function {name}({param_text}): {result}'
        return signature + ' { ' + body + ' }\n'

    def local(name, rhs):
        prefix = {'php': '$', 'cpp': 'auto ', 'js': 'let '}[flavor]
        return f'{prefix}{name}={rhs}; '

    decl = {'php': 'public {t} ${n};', 'cpp': '{t} {n};', 'js': '{n}: {t};'}[flavor]

    def record(name, **fields):
        return 'struct ' + name + ' { ' + ' '.join(decl.format(n=n, t=t) for n, t in fields.items()) + ' }'

    parent = record('Parent', child='shared<Child>', self='weak<Parent>')
    child = record('Child', parent='weak<Parent>')
    p, c, w, held = [names + n for n in ('p', 'c', 'w', 'held')]
    api = function('observe', 'weak<Parent>', ('p', 'shared<Parent>'), f'return weak({p});')
    api += function('acquire', 'shared<Parent>', ('w', 'weak<Parent>'), f'return lock({w});')
    same = local('p', 'makeParent()') + local('c', 'makeChild()')
    same += f'{p}{access}child={c}; {p}{access}self=weak({p}); {c}{access}parent=observe({p}); '
    same += local('w', f'{c}{access}parent') + local('held', f'acquire({w})')
    same += f'if ({p} !== {held} || {p} != lock({p}{access}self)) {{ return 1; }} '
    same += f'if ({p} == makeParent() || {p} === makeParent()) {{ return 2; }} '
    same += f'{p}=null; if (!{held} || lock({w}) !== {held}) {{ return 3; }} '
    same += f'{held}=null; if (lock({w}) !== {p} || lock({c}{access}parent) != null) {{ return 4; }} '
    same += local('expired', 'weak(makeParent())') + f'if (lock({names}expired)) {{ return 5; }} '
    same += f'{held}=lock(weak(makeParent())); if (!{held}) {{ return 6; }} '
    same += f'if (lock(weak({p})) !== null) {{ return 7; }} return 8;'
    return parent, child, api, same, p, c, w, held


def native_lifetime(out, work, memcheck):
    work.mkdir()
    units = [out / f for f in json.loads((out / 'manifest.json').read_text())['translation_units'] if f != 'entry.cpp']
    entry = next(u for u in units if 'int scpp_entry_body()\n{' in u.read_text())
    probe = work / 'counts.cpp'
    probe.write_text(f'#include "{entry.resolve()}"\n' + """
#include <cassert>
#include <cstdlib>
#include <new>
void* watched[2]{};
unsigned frees;
void* operator new(std::size_t n) {
    if (auto* p = std::malloc(n)) return p;
    throw std::bad_alloc();
}
void operator delete(void* p) noexcept {
    for (auto& saved : watched) if (p && saved == p) { saved = nullptr; ++frees; }
    std::free(p);
}
int main() {
    using namespace App;
    static_assert(sizeof(weak_Parent) == sizeof(void*));
    static_assert(sizeof(owner_Parent::allocation) == sizeof(scpp_records::Parent) + 8);
    for (int i=0; i<4096; ++i) {
        unsigned start = frees;
        auto parent = makeParent_();
        auto child = makeChild_();
        watched[0] = parent.ptr;
        watched[1] = child.ptr;
        parent->child = child;
        parent->self = observe_(parent);
        child->parent = parent->self;
        auto observer = child->parent;
        auto acquired = acquire_(observer);
        assert(parent.ptr->count == 2 && parent.ptr->weak_count == 4);
        parent = {};
        acquired = {};
        // Child's count proves Parent's payload was already destroyed while observers still exist.
        assert(child.ptr->count == 1 && observer.ptr->count == 0 && observer.ptr->weak_count == 2);
        assert(!observer.lock() && frees == start);
        child = {};
        assert(frees == start+1 && observer.ptr->weak_count == 1);
        auto copy = observer;
        observer = {};
        assert(!copy.lock());
        copy = {};
        assert(frees == start+2);
        auto self = makeParent_();
        watched[0] = self.ptr;
        self->self = observe_(self);
        self = {};
        assert(frees == start+3);
    }
}
""")
    command = write_build(work, [probe, *(u for u in units if u != entry)])
    subprocess.run(command, cwd=work, capture_output=True, check=True)
    subprocess.run([work / 'program'], check=True)
    if memcheck:
        # Valgrind must see the ordinary allocator; the separate probe above counts specific frees.
        stress = work / 'stress.cpp'
        stress.write_text('namespace App { int scpp_entry_body(); }\n'
                          'int main() { for (int i=0; i<4096; ++i) if (App::scpp_entry_body()!=8) return 1; }\n')
        native = work / 'stress'
        native.mkdir()
        subprocess.run(write_build(native, [stress, *units]), cwd=native, capture_output=True, check=True)
        log = work / 'memcheck.log'
        subprocess.run(['valgrind', '--leak-check=full', '--show-leak-kinds=all', '--errors-for-leak-kinds=all',
                        '--error-exitcode=99', f'--log-file={log}', native/'program'], check=True)
        print('\n'.join(s for s in log.read_text().splitlines() if 'in use at exit' in s or 'ERROR SUMMARY' in s))


def prove(work, flavor, memcheck):
    app = work / flavor
    app.mkdir()
    entry, api, parent, child = [app / (n + '.' + flavor) for n in ('main', 'api', 'parent', 'child')]
    (app / 'module.json').write_text(json.dumps({'entry': entry.name}))
    (app / 'factory.php').write_text('function makeParent(): shared<Parent> { return new Parent(); } '
                                     'function makeChild(): shared<Child> { return new Child(); }')
    parent_text, child_text, api_text, source, p, c, w, held = fixture(flavor)
    for path, text in [(api, api_text), (parent, parent_text), (child, child_text)]:
        path.write_text(text)
    out = work / ('out-' + flavor)
    edits = [(entry, source, None), (entry, source.replace(f'acquire({w})', f'lock({p})'), 'lock() requires weak<T>'),
             (entry, source.replace(f'acquire({w})', f'weak({w})'), 'weak() requires shared<T>'),
             (entry, source.replace(f'if (!{held}', f'if (!{w}'), 'Unsupported condition type'),
             (entry, source.replace(f'{p} !== {held}', f'{p} !== {c}'), 'same shared type'),
             (entry, source, None), (child, child_text.replace('weak<Parent>', 'weak<Missing>'), 'Weak type requires'),
             (None, '', 'Weak type requires'), (child, child_text, None)]
    with service(app, out) as resident:
        for revision, (path, text, error) in enumerate(edits):
            if path:
                path.write_text(text)
                os.utime(path, (1960000000 + revision,) * 2)
            old = artifacts(out)
            result = invoke(resident)
            baseline = work / f'fresh-{flavor}-{revision}'
            with service(app, baseline) as fresh:
                other = invoke(fresh)
                fresh.stdin.close()
            if error:
                assert error in result.get('error', '') and result['error'] == other.get('error'), (revision, result, other)
                assert artifacts(out) == old
                continue
            assert 'error' not in result and 'error' not in other, (revision, result, other)
            assert artifacts(out) == artifacts(baseline)
            build(out, work / ('build-' + flavor), b'')
            build(baseline, work / f'build-fresh-{flavor}-{revision}', b'')
            if revision == 0:
                native_lifetime(out, work / ('heap-' + flavor), memcheck)
        resident.stdin.close()


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--memcheck', action='store_true')
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory() as tmp:
        work = (args.output or Path(tmp)).resolve()
        work.mkdir(exist_ok=True)
        for flavor in ('php', 'cpp', 'js'):
            prove(work, flavor, args.memcheck)
            print(flavor, 'passed', flush=True)

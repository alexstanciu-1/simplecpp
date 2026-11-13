"""Cross-unit shared reference semantics, signature edits, and optional native heap checks."""
import argparse
import json
import os
import re
from pathlib import Path
import subprocess
import tempfile

from app_namespace_native import artifacts, invoke, service, write_build
from structs_native import build


def fixture(flavor):
    sigil = '$' if flavor == 'php' else ''
    member = '.' if flavor == 'js' else '->'
    p, q = sigil + 'p', sigil + 'q'

    def function(name, params, body, result='void'):
        def param(var):
            return {'php': f'shared<Point> &${var}', 'cpp': f'shared<Point> &{var}',
                    'js': f'&{var}: shared<Point>'}[flavor]
        args = ', '.join(map(param, params))
        return (f'{result} {name}({args})' if flavor == 'cpp' else f'function {name}({args}): {result}') + ' { ' + body + ' }\n'

    def local(var, init):
        prefix = {'php': '$', 'js': 'let ', 'cpp': 'auto '}[flavor]
        return f'{prefix}{var} = {init}; '

    api = (function('replaceOwner', ['p'], f'{p}=makePoint(); {p}{member}x=9;')
           + function('clearOwner', ['p'], f'{p}=null;')
           + function('relay', ['p'], f'replaceOwner({p});')
           + function('aliasSlots', ['p', 'q'], f'relay({p}); {q}{member}x=12;')
           + function('copyFromSlot', ['p'], f'return {p};', result='shared<Point>')
           + function('failAfterReplace', ['p'], local('keep', p) + f'relay({p}); echo {p}{member}next{member}x;'))
    initial = local('p', 'makePoint()') + f'{p}{member}x=7; ' + local('q', p)
    source = (initial + f'relay({p}); echo {p}{member}x,",",{q}{member}x; '
              + f'clearOwner({p}); clearOwner({p}); if ({p}) {{ return 1; }} '
              + f'relay({p}); aliasSlots({p},{p}); echo ",",{p}{member}x,",",{q}{member}x; '
              + f'aliasSlots({p},{q}); echo ",",{p}{member}x,",",{q}{member}x; return 8;')
    foreign = local('other', 'makeOther()') + 'replaceOwner(' + sigil + 'other); return 8;'
    return api, source, foreign, initial + f'replaceOwner({p}{member}next); return 8;'


def memory_probe(output, work, memcheck):
    """Exercise the emitted ABI directly so an exception can unwind into a native catch."""
    work.mkdir()
    manifest = json.loads((output / 'manifest.json').read_text())
    units = [output / name for name in manifest['translation_units'] if name != 'entry.cpp']
    entry = next(p for p in units if 'int scpp_entry_body()\n{' in p.read_text())
    probe = work / 'probe.cpp'
    probe.write_text(f'#include "{entry.resolve()}"\n' + """
#include <cassert>
#include <cstring>
#include <stdexcept>
namespace App { void failAfterReplace_(owner_Point&); owner_Point copyFromSlot_(owner_Point&); }
int main(int argc, char**) {
    if (argc == 2) {
        try { throw std::runtime_error("abi control"); }
        catch (const std::runtime_error&) { return 0; }
    }
    using namespace App;
    for (int i = 0; i < 4096; ++i) {
        auto p = makePoint_();
        auto q = copyFromSlot_(p);
        p->x = 7;
        p->next = makePoint_();
        assert(q.ptr->count == 2);
        try {
            failAfterReplace_(p);
            return 1;
        } catch (const std::runtime_error& e) {
            assert(std::strcmp(e.what(), "ownership_empty_access") == 0);
        }
        assert(q.ptr->count == 1 && q->x == 7 && q->next);
        assert(p.ptr->count == 1 && p->x == 9 && !p->next);
        aliasSlots_(p, p);
        assert(p.ptr->count == 1 && p->x == 12);
        clearOwner_(q);  // Last owner of the original parent and its child.
        clearOwner_(q);  // Already empty.
        q = p;
        aliasSlots_(p, q);  // Distinct slots that initially share a payload.
        assert(p.ptr != q.ptr && p.ptr->count == 1 && q.ptr->count == 1);
        assert(p->x == 9 && q->x == 12);
    }
}
""")
    cmd = write_build(work, [probe, *(u for u in units if u != entry)])
    subprocess.run(cmd, cwd=work, capture_output=True, check=True)
    subprocess.run([work / 'program'], check=True)
    if memcheck:
        # Some libc++abi builds retain exception TLS for the main thread. Measure it in the same binary.
        reachable = []
        for label, args in [('abi-control', ['control']), ('stress', [])]:
            log = work / (label + '.log')
            subprocess.run(['valgrind', '--tool=memcheck', '--leak-check=full', '--show-leak-kinds=all',
                            '--errors-for-leak-kinds=definite,indirect,possible', '--error-exitcode=99',
                            f'--log-file={log}', work / 'program', *args], check=True)
            report = log.read_text()
            at_exit = re.search(r'in use at exit: ([\d,]+) bytes in ([\d,]+) blocks?', report)
            reachable.append(at_exit.groups())
            if label == 'stress':
                print('\n'.join(line for line in report.splitlines()
                                if any(term in line for term in ('in use at exit', 'total heap usage', 'ERROR SUMMARY'))))
        assert reachable[0] == reachable[1], reachable

        sanitized = work / 'sanitized'
        sanitized.mkdir()
        cmd = write_build(sanitized, [probe, *(u for u in units if u != entry)], optimization='O1')
        graph = sanitized / 'build.ninja'
        # The dynamic libc++abi exception destructor reports an ASan allocator mismatch even in the ABI control.
        graph.write_text(graph.read_text().replace(' -g0 ', ' -fsanitize=address,undefined -fno-sanitize-recover=all -g0 ')
                         .replace('--ld-path=', '-static-libstdc++ --ld-path='))
        subprocess.run(cmd, cwd=sanitized, capture_output=True, check=True)
        subprocess.run([sanitized / 'program'], check=True, env={**os.environ, 'ASAN_OPTIONS': 'detect_leaks=0'})


def prove(work, flavor, memcheck):
    app = work / flavor
    app.mkdir()
    entry = app / ('main.' + flavor)
    lib = app / ('api.' + flavor)
    (app / 'module.json').write_text(json.dumps({'entry': entry.name}))
    (app / 'model.php').write_text('struct Point { public int $x; public shared<Point> $next; } struct Other {} '
                                   'function makePoint(): shared<Point> { return new Point(); } '
                                   'function makeOther(): shared<Other> { return new Other(); }')
    api, source, wrong_type, field = fixture(flavor)
    value_api = api.replace('&', '', 1)
    temporary = 'replaceOwner(makePoint()); return 8;'
    stable = 'Reference argument requires stable variable storage'
    entry.write_text(source)
    lib.write_text(api)
    output = work / ('out-' + flavor)
    inline = {'php': '$box Point;', 'cpp': 'Point box;', 'js': 'let box: Point;'}[flavor]
    field_ref = {'php': '$box->next', 'cpp': 'box->next', 'js': 'box.next'}[flavor]
    edits = [(None, '', None), (entry, wrong_type, 'Reference argument requires exact storage type'),
             (entry, field, stable), (entry, 'replaceOwner(null); return 8;', stable),
             (entry, temporary, stable), (lib, value_api, None), (lib, api, stable),
             (None, '', stable), (entry, inline + 'replaceOwner(' + field_ref + '); return 8;', stable),
             (entry, source, None)]
    expected_stdout = b'9,7,12,7,9,12'
    with service(app, output) as resident:
        for rev, (path, content, error) in enumerate(edits):
            if path:
                path.write_text(content)
                os.utime(path, (1950000000 + rev,) * 2)
            before = artifacts(output)
            current = invoke(resident)
            baseline = work / f'fresh-{flavor}-{rev}'
            with service(app, baseline) as fresh:
                expected = invoke(fresh)
                fresh.stdin.close()
            if error:
                assert error in current.get('error', '') and current['error'] == expected.get('error'), (flavor, rev, current, expected)
                assert artifacts(output) == before
                continue
            assert 'error' not in current and 'error' not in expected, (flavor, rev, current, expected)
            assert artifacts(output) == artifacts(baseline)
            stdout = b'' if rev == 5 else expected_stdout
            build(output, work / ('build-' + flavor), stdout)
            build(baseline, work / f'fresh-build-{flavor}-{rev}', stdout)
            if rev == 0:
                memory_probe(output, work / ('heap-' + flavor), memcheck)
        resident.stdin.close()


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--memcheck', action='store_true')
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-shared-refs-') as tmp:
        work = (args.output or Path(tmp)).resolve()
        work.mkdir(exist_ok=True)
        for flavor in ('php', 'cpp', 'js'):
            prove(work, flavor, args.memcheck)
            print(flavor, 'passed', flush=True)

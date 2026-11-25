"""Check inferred vector factories, receiver bindings and retained source visibility."""
import json
from pathlib import Path
import subprocess
import sys
import tempfile

from app_namespace_native import artifacts, invoke, service

root = Path(__file__).resolve().parents[2]
cxx, linker, runtime = sys.argv[1:]
source = r'''
use function vector_filled as copies;
$names = copies(1, "a\0b");
$names->resize(128, $names[0]);
$names[0] = "new";
if (count($names) !== 128 || $names[127] !== "a\0b") { return 1; }
$calls = 0;
$names->resize(1, counted_fill($calls));
if ($calls !== 1) { return 7; }
if ($names->has(1) || $names[0] !== "new") { return 2; }
$names->compact(8);
if ($names->capacity() < 8 || $names[0] !== "new") { return 3; }
$names->compact(0);
$names->compact();
$capacity = capacity_of($names);
$names->clear();
if ($capacity < 1 || count($names) !== 0 || $names->capacity() !== $capacity) { return 4; }
$p Point;
$temporary OnlyTemporary;
if (copies(1, $temporary)->capacity() < 1) { return 8; }
$points = copies(2, $p);
$p->x = 6;
$points->resize(5, $points[0]);
$points->compact();
if ($points[4]->x !== 7 || $points[0]->x !== 7) { return 5; }
$node = new Node();
$nodes = copies(3, $node);
$node->x = 15;
if ($nodes[2]->x !== 15) { return 6; }
return cpp_check();
'''


def run_once(app, out):
    process = service(app, out)
    try:
        return invoke(process)
    finally:
        process.stdin.close()
        process.wait()


with tempfile.TemporaryDirectory(prefix='vector-capacity-') as tmp:
    work = Path(tmp)
    app, out = work / 'app', work / 'out'
    app.mkdir()
    manifest = app / 'module.json'
    manifest.write_text('{"general_api":true}')
    main = app / 'main.php'
    main.write_text(source)
    model = app / 'types.php'
    declarations = 'struct OnlyTemporary {} struct Point { public int $x = 7; } class Node { public int $x = 0; } function capacity_of(const vector<string> &$v): int { return $v->capacity(); } function counted_fill(int &$calls): string { $calls++; return "unused"; }'
    model.write_text(declarations)
    (app / 'cpp.cpp').write_text('int cpp_check() { auto values = vector_filled(0, 1); return values->capacity(); }')
    resident = service(app, out)
    try:
        def check(error=None):
            result = invoke(resident)
            if error:
                assert error in result.get('error', ''), result
            else:
                assert 'error' not in result, result

        check()
        saved = artifacts(out)
        assert any(b'::filled(' in s and b'.resize(' in s for p, s in saved.items() if p.suffix == '.cpp')
        binary = work / 'vector'
        bundle = json.loads((out / 'manifest.json').read_text())
        boundary = work / 'boundary.cpp'
        boundary.write_text("""#include <scpp/vectors.hpp>
template<class V, class N, class E> concept admits_fill = requires(N n, E e) { V::filled(n, e); };
template<class V, class N, class E> concept admits_resize = requires(V& v, N n, E e) { v.resize(n, e); };
static_assert(admits_fill<scpp::vector_int64, scpp::int64, scpp::int64>);
static_assert(!admits_fill<scpp::vector_int64, bool, scpp::int64>);
static_assert(!admits_fill<scpp::vector_int64, scpp::int64, double>);
static_assert(!admits_resize<scpp::vector_string, double, scpp::string>);
static_assert(requires(const scpp::vector_string& v) { v.capacity(); });
""")
        subprocess.run([cxx, '-std=c++23', '-stdlib=libc++', f'--ld-path={linker}', '-I', root / 'runtime/generated/public',
                        *(out / unit for unit in bundle['translation_units']), boundary, runtime, '-o', binary], check=True)
        subprocess.run([binary], check=True)
        manifest.write_text('{}')
        check('vector_filled')
        manifest.write_text('{"general_api":true}')
        model.write_text(declarations.replace('int $x = 7', 'string $text = ""'))
        check('x')
        model.write_text(declarations)
        main.write_text('$v = vector_filled(2, 1); $v->resize(3, "wrong"); return 0;')
        check('requires')
        main.write_text('vector_filled(1); return 0;')
        check('count and fill')
        main.write_text(source)
        check()
        fresh = work / 'fresh'
        assert 'error' not in run_once(app, fresh)
        assert artifacts(out) == artifacts(fresh) == saved
    finally:
        resident.stdin.close()
        resident.wait()

    # C++ sees general names without an opt-in. JS and Python use the project flag.
    for flavor, body in [('cpp', 'return vector_filled(1, 9)->capacity();'),
                         ('js', 'return vector_filled(1, 9).capacity();'),
                         ('py', 'return vector_filled(1, 9).capacity()')]:
        isolated = work / flavor
        isolated.mkdir()
        (isolated / ('main.' + flavor)).write_text(body)
        settings = isolated / 'module.json'
        settings.write_text('{}')
        result = run_once(isolated, work / (flavor + '-out'))
        if flavor != 'cpp':
            assert 'vector_filled' in result.get('error', ''), result
            settings.write_text('{"general_api":true}')
            result = run_once(isolated, work / (flavor + '-out'))
        assert 'error' not in result, result

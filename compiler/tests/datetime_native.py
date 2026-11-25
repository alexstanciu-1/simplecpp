"""Prove the selected general datetime bindings through real source, headers and archives."""
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile

from app_namespace_native import artifacts, invoke, service

root = Path(__file__).resolve().parents[2]
cxx, linker, build = sys.argv[1:]
build = Path(build)
public = root / 'runtime/generated/public'
enabled = {'dependencies': {'runtime': {'modules': ['datetime']}}, 'general_api': True}

with tempfile.TemporaryDirectory(prefix='scpp-datetime-') as tmp:
    work = Path(tmp)
    app, out = work / 'app', work / 'out'
    app.mkdir()
    main = app / 'main.php'
    main.write_text('''
$seconds int = dt_now();
$millis int = dt_now_ms();
$start int = dt_monotonic_ms();
dt_sleep_ms(0);
$us uint64 = forward_ticks(dt_monotonic_us());
$ns = forward_ticks(dt_monotonic_ns());
$epoch int = dt_parse_iso_utc(dt_format_iso_utc(0));
$bad = dt_parse_iso_utc("not a date");
if ($epoch !== 0 || isset($bad) || dt_monotonic_ms() < $start) {return 1;}
$local int = dt_parse("1970-01-01");
if ($local !== 0 || dt_format("U", $local) !== "0" || dt_format_now("x") !== "x") {return 2;}
return 0;
''')
    (app / 'forward.php').write_text('function forward_ticks(uint64 $ticks):uint64 {return $ticks;}')
    manifest = app / 'module.json'
    manifest.write_text(json.dumps(enabled))
    resident = service(app, out)
    try:
        result = invoke(resident)
        assert 'error' not in result, result
        original = artifacts(out)
        bundle = json.loads((out / 'manifest.json').read_text())
        paths = [out / unit for unit in bundle['translation_units']]
        archives = [Path(p) for p in bundle['link_inputs']]
        assert [p.name for p in archives] == ['libscpp_datetime.a', 'libscpp_runtime.a'], archives
        # Exercise exactly the archive selection and order in the manifest for this build.
        flags = [cxx, '-std=c++23', '-stdlib=libc++', '-I', public]
        link = [*flags, f'--ld-path={linker}']
        subprocess.run([*link, *paths, *(build / p.name for p in archives), '-o', work/'run'], check=True)
        subprocess.run([work/'run'], env={**os.environ, 'TZ':'UTC0'}, check=True)
        # Check the generated source ABI at a value above PHP's signed integer domain.
        header = next(out / h for h in bundle['headers'] if 'forward_ticks_' in (out/h).read_text())
        observer = work / 'unsigned.cpp'
        observer.write_text(f'#include "{header}"\n#include <limits>\n#include <type_traits>\n'
                            'static_assert(std::is_same_v<decltype(&App::forward_ticks_), '
                            'scpp::uint64(*)(scpp::uint64)>);\n'
                            'int main() { auto max = std::numeric_limits<scpp::uint64>::max(); '
                            'return App::forward_ticks_(max) == max ? 0 : 1; }')
        subprocess.run([*link, observer, header.with_suffix('.cpp'), '-o', work/'unsigned'], check=True)
        subprocess.run([work/'unsigned'], check=True)
        manifest.write_text(json.dumps({'dependencies': enabled['dependencies']}))
        assert 'Unknown function: dt_' in invoke(resident).get('error', '')
        manifest.write_text(json.dumps({'general_api': True, 'dependencies': {'runtime': {'modules': ['numeric']}}}))
        assert 'requires module runtime-datetime' in invoke(resident).get('error', '')
        manifest.write_text(json.dumps(enabled))
        assert 'error' not in invoke(resident)
        assert artifacts(out) == original
    finally:
        resident.stdin.close()
        resident.wait()
    fresh = service(app, work/'fresh')
    try:
        assert 'error' not in invoke(fresh)
        assert artifacts(work/'fresh') == original
    finally:
        fresh.stdin.close()
        fresh.wait()

    clock = work/'clock'
    clock.mkdir()
    (clock/'module.json').write_text(json.dumps({'dependencies': enabled['dependencies']}))
    (clock/'main.cpp').write_text('auto ticks = dt_monotonic_ns(); return 0;')
    proc = service(clock, work/'clock-out')
    try:
        result = invoke(proc)
        assert 'error' not in result, result
    finally:
        proc.stdin.close()
        proc.wait()
    unit = next((work/'clock-out/units').glob('*.cpp'))
    deps = subprocess.check_output([*flags, '-MM', unit], text=True)
    assert 'scpp/datetime/monotonic_nanos.hpp' in deps
    for unrelated in ['scpp/datetime.hpp', 'scpp/string/', 'scpp/carriers.hpp', 'scpp/php/', 'datetime_native.hpp']:
        assert unrelated not in deps, deps

    for flavor, source in [('js', 'return dt_now();'), ('py', 'return dt_now()')]:
        caller = work/flavor
        caller.mkdir()
        (caller/('main.' + flavor)).write_text(source)
        settings = caller/'module.json'
        settings.write_text(json.dumps({'dependencies': enabled['dependencies']}))
        proc = service(caller, work/(flavor+'-out'))
        try:
            assert 'Unknown function: dt_now' in invoke(proc).get('error', '')
            settings.write_text(json.dumps(enabled))
            result = invoke(proc)
            assert 'error' not in result, result
        finally:
            proc.stdin.close()
            proc.wait()

    # A build can publish a subset independently of project-level module activation.
    profile = json.loads((root / 'tests/datetime/publication.json').read_text())
    request = work / 'profile.json'
    generator = ['php', root/'tools/api_generator/generate.php']
    def publish():
        request.write_text(json.dumps(profile))
        for kind in ['runtime', 'compiler']:
            subprocess.run([*generator, 'emit-' + kind, root/'runtime/metadata/runtime.json',
                            request, build/'native-layout/facts.json', build/'native-layout/context.json',
                            work/kind], check=True, stdout=subprocess.DEVNULL)
        return (work/'compiler/types/registry.php').read_text()

    profile['datetime'] = {'header': 'scpp/custom/time.hpp', 'operations': ['parse_iso_utc']}
    # A signed-only datetime subset requires only the core integer type.
    profile['modules'] = []
    profile['types'].remove('uint64')
    bindings = publish()
    narrow = 'scpp/custom/time/parse_iso_utc.hpp'
    assert "general:func:dt_parse_iso_utc" in bindings and narrow in bindings
    assert 'general:func:dt_now' not in bindings and not (work/'compiler/types/uint64.php').exists()
    umbrella = work/'runtime/public/scpp/custom/time.hpp'
    assert narrow in umbrella.read_text() and 'now_unix_seconds' not in umbrella.read_text()
    assert (work/'runtime/public'/narrow).is_file()
    del profile['datetime']
    assert 'general:func:dt_' not in publish()
    assert not umbrella.exists() and not (work/'runtime/public'/narrow).exists()

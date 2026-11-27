"""Full ternary selection, native value semantics and retained edit recovery."""
import json
import argparse
import tempfile
from pathlib import Path

from app_namespace_native import artifacts, invoke, service
from classes_native import fixture
from exceptions_native import catch
from extends_native import runtime, source_edit
from inline_adapters_native import incremental_native_build
from interfaces_native import fresh
from parent_methods_native import write_app


def prove(work, flavor, library, cxx_flags=()):
    s = fixture(flavor)
    fn, v = s['function'], s['v']
    choose = fn('choose', 'int', [('flag', 'bool')], f'return {v}flag ? leftArm() : rightArm();')
    api = fn('leftArm', 'int', [], 'echo "L";return 3;')
    api += fn('rightArm', 'int', [], 'echo "R";return 7;')
    api += fn('predicate', 'bool', [('flag', 'bool')], f'echo "P";return {v}flag;')
    api += fn('missing', 'nullable<int>', [], 'return null;')
    api += fn('copy', 'string', [('x', 'string'), ('y', 'string')], f'return true ? {v}x : {v}y;')
    factory = 'Exception.make' if flavor == 'js' else 'Exception::make'
    api += fn('fail', 'int', [], f'echo "F";throw {factory}("failed");')
    handler = catch(flavor, 'Exception', 'e')
    main = 'echo choose(predicate(true)),choose(predicate(false));'
    main += 'echo true ? 1 : 8/0,false ? 8/0 : 2;'
    main += 'echo false || true ? 4 : 5,missing() ?? 0 ? 1 : 2;'
    main += 'echo false ? 8 : true ? 6 : 7;'
    main += 'echo "0" ? 1 : 0,"00" ? 1 : 0;'
    main += f'try {{echo fail() ? leftArm() : rightArm();}} {handler} {{echo "C";}}'
    main += f'try {{echo true ? fail() : rightArm();}} {handler} {{echo "C";}}'
    main += s['infer']('text', '"heap backed test string\\0tail"')
    main += s['infer']('saved', f'copy({v}text,"other")')
    main += f'{v}text="replacement";echo {v}saved;return 8;'
    expected = b'PL3PR71242601FCFCheap backed test string\0tail'
    app, out, native = work / flavor, work / (flavor + '-out'), work / (flavor + '-native')
    write_app(app, flavor, {'choose': choose, 'api': api, 'main': main})
    resident = service(app, out)
    path = app / ('choose.' + flavor)
    try:
        api_path = app / ('api.' + flavor)
        arm_error = 'Unsupported explicit conversion from string to int64' if flavor == 'php' else 'result types must be identical'
        for revision, (target, body, error) in enumerate([
            (path, choose, None), (path, choose, None), (path, choose.replace('rightArm()', 'rightArm() + 1'), None),
            (path, choose.replace('rightArm()', '"wrong"'), arm_error),
            (path, choose.replace('leftArm()', ''), 'requires a true branch'),
            (path, choose, None), (path, choose, None),
            (api_path, api.replace(fn('rightArm', 'int', [], 'echo "R";return 7;'),
                                   fn('rightArm', 'string', [], 'return "bad";')), arm_error),
            (api_path, api, None),
        ]):
            before = artifacts(out) if out.exists() else {}
            source_edit(target, body)
            result = invoke(resident)
            comparison = work / f'{flavor}-fresh-{revision}'
            clean = fresh(app, comparison)
            if error:
                assert error in result.get('error', ''), result
                assert clean.get('error') == result['error'] and artifacts(out) == before
                continue
            assert 'error' not in result and 'error' not in clean, (result, clean)
            assert artifacts(out) == artifacts(comparison)
            units = json.loads((out / 'manifest.json').read_text())['sources']
            unit = next(u for p, u in units.items() if Path(p).stem == 'choose')
            generated = (out / unit).read_text()
            assert ' ? leftArm_() : ' in generated and '[&]' not in generated, generated
            stdout = expected.replace(b'PR7', b'PR8') if revision == 2 else expected
            changed = incremental_native_build(out, native, 'clang++', library, stdout, cxx_flags=cxx_flags)
            if revision in (1, 6):
                assert result['preparation']['prepared'] == 0 and not result['emitted_sources'] and not changed, result
            if revision == 2:
                assert changed == {unit} and len(result['emitted_sources']) == 1, result
    finally:
        resident.stdin.close()
        resident.wait()
    print(flavor, 'ternary selection and incremental recovery passed', flush=True)


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--quick", action="store_true")
    quick = parser.parse_args().quick
    with tempfile.TemporaryDirectory(prefix='scpp-ternary-') as temporary:
        work = Path(temporary)
        library = Path(__file__).resolve().parents[2]/'build/clang/libscpp_runtime.a' if quick else runtime(work, 'clang++')
        for flavor in (('php',) if quick else ('cpp', 'php', 'js')):
            prove(work, flavor, library, ("-stdlib=libc++",) if quick else ())

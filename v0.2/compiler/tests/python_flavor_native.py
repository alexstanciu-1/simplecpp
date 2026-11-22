"""Compare mixed Python-flavor execution with the existing flavors and check edit recovery."""
import tempfile
from pathlib import Path

from app_namespace_native import artifacts, invoke, service
from exceptions_native import fresh_build, native
from extends_native import runtime, source_edit
from parent_methods_native import write_app


PYTHON_ENTRY = "result: int = bridge(\n    3)\nreturn result"
PYTHON_HELPER = """def helper(value: int) -> int:
    result: int = 0
    while result < value:
        result = result + 1
    if result < 3:
        return 1
    elif result == 3:
        return result--5++0
    else:
        return 2
"""
BRIDGES = {
    'cpp': 'int bridge(int x) { echo "bridge"; return helper(x); }',
    'php': 'function bridge(int $x): int { echo "bridge"; return helper($x); }',
    'js': 'function bridge(x: int): int { echo "bridge"; return helper(x); }',
}
CONTROLS = {
    'cpp': ('int result = bridge(3); return result;',
            'int helper(int x) { int n = 0; while (n < x) { n = n + 1; } '
            'if (n < 3) { return 1; } else if (n == 3) { return n + 5; } else { return 2; } }'),
    'php': ('$result int = bridge(3); return $result;',
            'function helper(int $x): int { $n int = 0; while ($n < $x) { $n = $n + 1; } '
            'if ($n < 3) { return 1; } elseif ($n == 3) { return $n + 5; } else { return 2; } }'),
    'js': ('let result:int = bridge(3); return result;',
           'function helper(x:int):int { let n:int = 0; while (n < x) { n = n + 1; } '
           'if (n < 3) { return 1; } else if (n == 3) { return n + 5; } else { return 2; } }'),
}


LOGICAL_ENTRY = """if left(False) and probe():
    return 1
if not (left(True) or probe()):
    return 2
if not (left(True) and probe()):
    return 3
if not (left(False) or probe()):
    return 4
if not 1 < 2 and True:
    return 5
if not (False or True) and True:
    return 6
if not (True or False and False) or not not False:
    return 7
selected: bool = True and falseProbe()
if selected:
    return 9
if False and skipped():
    return 10
return 8
"""
LOGICAL_CALLEES = {
    'cpp': ('bool left(bool v) { echo "L"; return v; } bool probe() { echo "R"; return true; } '
            'bool falseProbe() { echo "F"; return false; }'),
    'php': ('function left(bool $v): bool { echo "L"; return $v; } function probe(): bool { echo "R"; return true; } '
            'function falseProbe(): bool { echo "F"; return false; }'),
    'js': ('function left(v: bool): bool { echo "L"; return v; } function probe(): bool { echo "R"; return true; } '
           'function falseProbe(): bool { echo "F"; return false; }'),
}


def logical(work, flavor, library):
    app, bundle = work/'logical', work/'logical-bundle'
    write_app(app, 'py', {'main': LOGICAL_ENTRY})
    source_edit(app/f'probe.{flavor}', LOGICAL_CALLEES[flavor])
    skipped = app/f'skipped.{flavor}'
    def skipped_body(result, value):
        signature = f'{result} skipped()' if flavor == 'cpp' else f'function skipped(): {result}'
        return signature + f' {{ echo "S"; return {value}; }}'

    valid = skipped_body('bool', 'false')
    source_edit(skipped, valid)
    expected = (8, b'LLLRLRF', b'')
    resident = service(app, bundle)
    try:
        result = invoke(resident)
        assert 'error' not in result, result
        native(bundle, work/'logical-run', expected, library)
        manifest = (bundle/'manifest.json').read_bytes()
        previous = artifacts(bundle)

        source_edit(skipped, skipped_body('int', '1'))
        failed = invoke(resident)
        fresh_failure = fresh_build(app, work/'logical-failure')
        line = next(number for number, text in enumerate(LOGICAL_ENTRY.splitlines(), 1) if 'skipped()' in text)
        assert f'main.py:{line}:14: Python logical operand requires bool' in failed.get('error', ''), failed
        assert failed['error'] == fresh_failure.get('error'), (failed, fresh_failure)
        assert artifacts(bundle) == previous and (bundle/'manifest.json').read_bytes() == manifest

        source_edit(skipped, valid)
        recovered = invoke(resident)
        assert 'error' not in recovered, recovered
        fresh = work/'logical-fresh'
        assert 'error' not in fresh_build(app, fresh)
        assert artifacts(bundle) == artifacts(fresh) == previous
        native(bundle, work/'logical-recovered', expected, library)
    finally:
        resident.stdin.close()
        assert resident.wait() == 0


def prove(work, library):
    work.mkdir()
    for flavor, bridge in BRIDGES.items():
        case = work/flavor
        case.mkdir()
        app, output = case/'app', case/'out'
        write_app(app, 'py', {'main': PYTHON_ENTRY, 'helper': PYTHON_HELPER})
        source_edit(app/f'bridge.{flavor}', bridge)
        control, bundle = case/'control', case/'control-bundle'
        main, helper = CONTROLS[flavor]
        write_app(control, flavor, {'main': main, 'bridge': bridge, 'helper': helper})
        result = fresh_build(control, bundle)
        assert 'error' not in result, result
        native(bundle, case/'control-run', (8, b'bridge', b''), library)
        incremental(case, flavor, app, output, library)
        logical(case, flavor, library)


def incremental(work, flavor, app, output, library):
    helper = app/'helper.py'
    resident = service(app, output)
    try:
        initial = invoke(resident)
        assert 'error' not in initial, initial
        native(output, work/'mixed-initial', (8, b'bridge', b''), library)
        edited = PYTHON_HELPER.replace('result--5++0', 'result--6++0')
        source_edit(helper, edited)
        changed = invoke(resident)
        assert 'error' not in changed and changed['preparation']['reused'], changed
        assert [Path(path).name for path in changed['emitted_sources']] == ['helper.py'], changed
        native(output, work/'edited', (9, b'bridge', b''), library)
        compatible = edited.replace('value: int', 'value: float')
        source_edit(helper, compatible)
        changed = invoke(resident)
        assert 'error' not in changed and changed['preparation']['reused'], changed
        assert {Path(path).name for path in changed['emitted_sources']} == {f'bridge.{flavor}', 'helper.py'}, changed
        fresh = work/'compatible-fresh'
        assert 'error' not in fresh_build(app, fresh)
        assert artifacts(output) == artifacts(fresh)
        native(output, work/'compatible', (9, b'bridge', b''), library)

        manifest = (output/'manifest.json').read_bytes()
        previous = artifacts(output)

        for index, (bad, fragment) in enumerate([
            (compatible.replace('    elif', '  elif'), 'Unmatched Python dedent'),
            ('def helper(value: int, extra: int) -> int:\n    return value + extra\n', 'Argument count mismatch'),
        ]):
            source_edit(helper, bad)
            failed = invoke(resident)
            fresh_failure = fresh_build(app, work/f'failure-{index}')
            assert fragment.lower() in failed.get('error', '').lower(), failed
            assert failed['error'] == fresh_failure.get('error'), (failed, fresh_failure)
            assert artifacts(output) == previous and (output/'manifest.json').read_bytes() == manifest
            source_edit(helper, compatible)
            recovered = invoke(resident)
            assert 'error' not in recovered, recovered
            fresh = work/f'restored-{index}'
            assert 'error' not in fresh_build(app, fresh)
            assert artifacts(output) == artifacts(fresh) == previous
            native(output, work/f'recovered-{index}', (9, b'bridge', b''), library)
    finally:
        resident.stdin.close()
        assert resident.wait() == 0


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-python-flavor-') as directory:
        work = Path(directory)
        prove(work/'proof', runtime(work, 'clang++'))

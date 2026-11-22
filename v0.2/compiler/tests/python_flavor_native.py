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


BODY_ENTRY = """def completed() -> void:
    if True:
        pass
    if left(True):
        return
    mark(0)

completed()
outer: int = 0
count: int = 0
while outer < 2:
    outer = outer + 1
    inner: int = 0
    while inner < 3:
        inner = inner + 1
        if inner == 1:
            continue
        count = count + inner
        break
if count != 4:
    return 1
if (1 if True else 2 if False else 3) != 1:
    return 2
selected: int = mark(0) if left(False) else mark(6)
if selected != 6:
    return 3
return mark(8) if not False and left(True) or False else fallback()
"""
BODY_CALLEES = {
    'cpp': 'int mark(int value) { echo value; return value; }',
    'php': 'function mark(int $value): int { echo $value; return $value; }',
    'js': 'function mark(value: int): int { echo value; return value; }',
}


def everyday_bodies(work, flavor, library):
    app, bundle = work/'bodies', work/'bodies-bundle'
    write_app(app, 'py', {'main': BODY_ENTRY})
    peer = app/f'peer.{flavor}'
    peer_body = LOGICAL_CALLEES[flavor] + BODY_CALLEES[flavor]

    def with_fallback(result, value):
        signature = f'{result} fallback()' if flavor == 'cpp' else f'function fallback(): {result}'
        return peer_body + signature + f' {{ echo "F"; return {value}; }}'

    valid_peer = with_fallback('int', '0')
    source_edit(peer, valid_peer)
    entry = app/'main.py'
    resident = service(app, bundle)
    try:
        initial = invoke(resident)
        assert 'error' not in initial, initial
        native(bundle, work/'bodies-initial', (8, b'LL6L8', b''), library)

        edited = BODY_ENTRY.replace('mark(8)', 'mark(9)')
        source_edit(entry, edited)
        changed = invoke(resident)
        assert 'error' not in changed and changed['preparation']['reused'], changed
        assert [Path(path).name for path in changed['emitted_sources']] == ['main.py'], changed
        native(bundle, work/'bodies-edited', (9, b'LL6L9', b''), library)
        previous = artifacts(bundle)
        manifest = (bundle/'manifest.json').read_bytes()

        for path, invalid, valid, label in [
            (entry, edited.replace('else fallback()', 'else False'), edited, 'branch'),
            (peer, with_fallback('bool', 'false'), valid_peer, 'signature'),
        ]:
            source_edit(path, invalid)
            failure = invoke(resident)
            fresh_failure = fresh_build(app, work/f'bodies-failure-{label}')
            expected_error = f'main.py:{len(edited.splitlines())}:'
            assert expected_error in failure.get('error', '') and 'Ternary result types must be identical' in failure['error'], failure
            assert failure['error'] == fresh_failure.get('error'), (failure, fresh_failure)
            assert artifacts(bundle) == previous and (bundle/'manifest.json').read_bytes() == manifest

            source_edit(path, valid)
            recovered = invoke(resident)
            assert 'error' not in recovered, recovered
            fresh = work/f'bodies-fresh-{label}'
            assert 'error' not in fresh_build(app, fresh)
            assert artifacts(bundle) == artifacts(fresh) == previous
            native(bundle, work/f'bodies-recovered-{label}', (9, b'LL6L9', b''), library)
    finally:
        resident.stdin.close()
        assert resident.wait() == 0


TRAVERSAL_ENTRY = """def keep(last: int, enabled: bool) -> int:
    for last in items(enabled):
        pass
    return last

last: int = -1
for last in items(False):
    pass
if last != -1:
    return 1
for last in items(True):
    last += 10
    if last > 13:
        break
if last != 14:
    return 2
if keep(-2, False) != -2 or keep(-2, True) != 9:
    return 3

total: int = 2
for item in items(True):
    if item == 2:
        continue
    total += item
    if item > 5:
        break
total -= 3
total *= 2

fraction: float = 1.5
fraction += 2
fraction -= 0.5
fraction *= 2.0
if fraction != 6.0:
    return 4
return total - 8
"""


def traversal_provider(flavor, element='int'):
    v = '$' if flavor == 'php' else ''
    signature_type = 'hash<int,int>' if element == 'hash' else f'vector<{element}>'
    signature = {
        'cpp': f'{signature_type} items(bool enabled)',
        'php': f'function items(bool $enabled): {signature_type}',
        'js': f'function items(enabled: bool): {signature_type}',
    }[flavor]
    declaration = {
        'cpp': f'{signature_type} output = [];',
        'php': f'$output {signature_type} = [];',
        'js': f'let output: {signature_type} = [];',
    }[flavor]
    values = {'int': ('2', '4', '7', '9'), 'string': ('"first"', '"second"'), 'hash': ()}[element]
    fill = ''.join(f'{v}output[]={value};' for value in values)
    return signature + f' {{ echo "V"; {declaration} if ({v}enabled) {{ {fill} }} return {v}output; }}'


def typed_updates_and_traversal(work, flavor, library):
    app, bundle = work/'traversal', work/'traversal-bundle'
    write_app(app, 'py', {'main': TRAVERSAL_ENTRY})
    peer = app/f'provider.{flavor}'
    provider = traversal_provider(flavor)
    source_edit(peer, provider)
    resident = service(app, bundle)
    try:
        initial = invoke(resident)
        assert 'error' not in initial, initial
        native(bundle, work/'traversal-initial', (12, b'VVVVV', b''), library)

        edited = provider.replace('[]=7;', '[]=8;')
        source_edit(peer, edited)
        changed = invoke(resident)
        assert 'error' not in changed and changed['preparation']['reused'], changed
        assert [Path(path).name for path in changed['emitted_sources']] == [peer.name], changed
        native(bundle, work/'traversal-edited', (14, b'VVVVV', b''), library)
        previous = artifacts(bundle)
        manifest = (bundle/'manifest.json').read_bytes()

        source_edit(peer, traversal_provider(flavor, 'hash'))
        failed = invoke(resident)
        fresh_failure = fresh_build(app, work/'traversal-failure')
        assert 'Python for requires a typed vector' in failed.get('error', ''), failed
        assert failed['error'] == fresh_failure.get('error'), (failed, fresh_failure)
        assert artifacts(bundle) == previous and (bundle/'manifest.json').read_bytes() == manifest

        source_edit(peer, edited)
        recovered = invoke(resident)
        assert 'error' not in recovered, recovered
        fresh = work/'traversal-fresh'
        assert 'error' not in fresh_build(app, fresh)
        assert artifacts(bundle) == artifacts(fresh) == previous
        native(bundle, work/'traversal-recovered', (14, b'VVVVV', b''), library)
    finally:
        resident.stdin.close()
        assert resident.wait() == 0


def compatible_element_edit(work, flavor, library):
    app, bundle = work/'element-edit', work/'element-bundle'
    caller = "count: int = 0\nfor item in items(True):\n    count += 1\nreturn count"
    write_app(app, 'py', {'main': caller})
    peer = app/f'provider.{flavor}'
    source_edit(peer, traversal_provider(flavor))
    resident = service(app, bundle)
    try:
        initial = invoke(resident)
        assert 'error' not in initial, initial
        native(bundle, work/'element-initial', (4, b'V', b''), library)

        source_edit(peer, traversal_provider(flavor, 'string'))
        changed = invoke(resident)
        assert 'error' not in changed and changed['preparation']['prepared'] >= 2, changed
        assert {Path(path).name for path in changed['emitted_sources']} == {'main.py', peer.name}, changed
        fresh = work/'element-fresh'
        assert 'error' not in fresh_build(app, fresh)
        assert artifacts(bundle) == artifacts(fresh)
        native(bundle, work/'element-changed', (2, b'V', b''), library)
    finally:
        resident.stdin.close()
        assert resident.wait() == 0


def narrow_compound(work, library):
    function = """def update(delta: int) -> int:
    value: uint8 = 254
    value += delta
    if value != 255:
        return 2
    return 8
"""
    for delta, expected in [(1, (8, b'', b'')), (2, (1, b'', b'integer_cast_out_of_range\n'))]:
        app, bundle = work/f'narrow-{delta}', work/f'narrow-bundle-{delta}'
        write_app(app, 'py', {'main': function + f'return update({delta})'})
        result = fresh_build(app, bundle)
        assert 'error' not in result, result
        assert any(b'scpp::checked_int64_to_uint8(' in content for content in artifacts(bundle).values())
        native(bundle, work/f'narrow-run-{delta}', expected, library)


RECORD_SOURCE = """class Counter:
    value: int = 0
    def __init__(self, start: int):
        self.value = start

    def bump(self, delta: int) -> int:
        self.value = self.value + delta
        return self.value

class Empty:
    pass
"""
RECORD_ENTRY = """def exercise(fail: bool) -> int:
    original: Diagnostic = failure()
    try:
        try:
            if fail:
                raise original
            return 8
        except Diagnostic as inner:
            inner.marker = 9
            inner = failure()
            raise
        finally:
            cleaned()
    except Diagnostic as outer:
        if not sameFailure(original, outer) or outer.marker != 9:
            return 1
        return 9

if exercise(False) != 8 or exercise(True) != 9:
    return 2
try:
    pass
finally:
    cleaned()
index: int = 0
while index < 2:
    index += 1
    try:
        if index == 1:
            continue
        break
    finally:
        cleaned()
Counter(1)
empty: Empty = Empty()
counter: Counter = Counter(3)
alias: Counter = counter
touch(alias, 5)
if counter.value != alias.value:
    return 3
return counter.value
"""
RECORD_PEERS = {
    'cpp': ('class Diagnostic : public Exception { public: int marker = 7; }; '
            'Diagnostic failure() { return new Diagnostic(); } '
            'bool sameFailure(Diagnostic a, Diagnostic b) { return a === b; } '
            'void touch(Counter c, int n) { c->bump(n); } '
            'int guard(int divisor) { return 8 / divisor; }'),
    'php': ('class Diagnostic extends Exception { public int $marker = 7; } '
            'function failure(): Diagnostic { return new Diagnostic(); } '
            'function sameFailure(Diagnostic $a, Diagnostic $b): bool { return $a === $b; } '
            'function touch(Counter $c, int $n): void { $c->bump($n); } '
            'function guard(int $divisor): int { return 8 / $divisor; }'),
    'js': ('class Diagnostic extends Exception { marker:int = 7; } '
           'function failure(): Diagnostic { return new Diagnostic(); } '
           'function sameFailure(a: Diagnostic, b: Diagnostic): bool { return a === b; } '
           'function touch(c: Counter, n: int): void { c.bump(n); } '
           'function guard(divisor: int): int { return 8 / divisor; }'),
}
RECORD_CLEANUP = {
    'cpp': 'void cleaned() { echo "F"; }',
    'php': 'function cleaned(): void { echo "F"; }',
    'js': 'function cleaned(): void { echo "F"; }',
}
CONSTRUCTOR_COLLISIONS = {
    'cpp': 'int Counter(int start) { return start; }',
    'php': 'function Counter(int $start): int { return $start; }',
    'js': 'function Counter(start: int): int { return start; }',
}
EXCEPTION_GUARD = """try:
    return guard(0)
except Exception as missed:
    return 2
finally:
    cleaned()
"""


def records_and_exceptions(work, flavor, library):
    app, bundle = work/'records', work/'records-bundle'
    write_app(app, 'py', {'main': RECORD_ENTRY, 'records': RECORD_SOURCE})
    peer = app/f'peer.{flavor}'
    source_edit(peer, RECORD_PEERS[flavor])
    cleanup = app/f'cleanup.{flavor}'
    source_edit(cleanup, RECORD_CLEANUP[flavor])
    records = app/'records.py'
    resident = service(app, bundle)
    try:
        result = invoke(resident)
        assert 'error' not in result, result
        native(bundle, work/'records-initial', (8, b'FFFFF', b''), library)

        source_edit(cleanup, RECORD_CLEANUP[flavor].replace('echo "F"', 'echo "G"'))
        changed = invoke(resident)
        assert 'error' not in changed and changed['preparation']['reused'], changed
        assert [Path(path).name for path in changed['emitted_sources']] == [cleanup.name], changed
        native(bundle, work/'peer-edited', (8, b'GGGGG', b''), library)

        edited = RECORD_SOURCE.replace('self.value + delta', 'self.value + delta + 1')
        source_edit(records, edited)
        changed = invoke(resident)
        assert 'error' not in changed and changed['preparation']['reused'], changed
        assert {Path(path).name for path in changed['emitted_sources']} == {'main.py', peer.name, 'records.py'}, changed
        fresh_edited = work/'records-fresh-edited'
        assert 'error' not in fresh_build(app, fresh_edited)
        assert artifacts(bundle) == artifacts(fresh_edited)
        native(bundle, work/'records-edited', (9, b'GGGGG', b''), library)
        previous = artifacts(bundle)
        manifest = (bundle/'manifest.json').read_bytes()

        source_edit(records, edited.replace('self, start: int', 'self, start: int, extra: int'))
        failed = invoke(resident)
        fresh_failure = fresh_build(app, work/'records-failure')
        assert 'main.py:' in failed.get('error', '') and 'Argument count mismatch' in failed['error'], failed
        assert failed['error'] == fresh_failure.get('error'), (failed, fresh_failure)
        assert artifacts(bundle) == previous and (bundle/'manifest.json').read_bytes() == manifest

        source_edit(records, edited)
        recovered = invoke(resident)
        assert 'error' not in recovered, recovered
        fresh = work/'records-fresh'
        assert 'error' not in fresh_build(app, fresh)
        assert artifacts(bundle) == artifacts(fresh) == previous
        native(bundle, work/'records-recovered', (9, b'GGGGG', b''), library)

        collision = app/f'collision.{flavor}'
        source_edit(collision, CONSTRUCTOR_COLLISIONS[flavor])
        failed = invoke(resident)
        fresh_failure = fresh_build(app, work/'collision-failure')
        assert 'both function and class named Counter' in failed.get('error', ''), failed
        assert failed['error'] == fresh_failure.get('error'), (failed, fresh_failure)
        assert artifacts(bundle) == previous
        assert (bundle/'manifest.json').read_bytes() == manifest

        collision.unlink()
        recovered = invoke(resident)
        assert 'error' not in recovered, recovered
        assert 'error' not in fresh_build(app, work/'collision-fresh')
        assert artifacts(bundle) == artifacts(work/'collision-fresh') == previous
        native(bundle, work/'collision-recovered', (9, b'GGGGG', b''), library)

        source_edit(app/'main.py', EXCEPTION_GUARD)
        guarded = invoke(resident)
        assert 'error' not in guarded, guarded
        native(bundle, work/'native-guard', (1, b'G', b'division_by_zero\n'), library)
    finally:
        resident.stdin.close()
        assert resident.wait() == 0


def prove(work, library):
    work.mkdir()
    narrow_compound(work, library)
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
        everyday_bodies(case, flavor, library)
        typed_updates_and_traversal(case, flavor, library)
        compatible_element_edit(case, flavor, library)
        records_and_exceptions(case, flavor, library)


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

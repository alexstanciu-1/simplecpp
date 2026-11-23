"""Compare mixed Python-flavor execution with the existing flavors and check edit recovery."""
import argparse
import json
import tempfile
from pathlib import Path

from app_namespace_native import artifacts, invoke, service
from exceptions_native import fresh_build, native
from extends_native import ROOT, runtime, source_edit
from interfaces_native import forms as interface_forms
from inline_adapters_native import incremental_native_build
from parent_methods_native import syntax as ancestor_syntax, write_app
from virtual_methods_native import forms


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
    signature_type = 'int' if element == 'invalid' else f'vector<{element}>'
    signature = {
        'cpp': f'{signature_type} items(bool enabled)',
        'php': f'function items(bool $enabled): {signature_type}',
        'js': f'function items(enabled: bool): {signature_type}',
    }[flavor]
    if element == 'invalid':
        return signature + ' { return 0; }'
    declaration = {
        'cpp': f'{signature_type} output = [];',
        'php': f'$output {signature_type} = [];',
        'js': f'let output: {signature_type} = [];',
    }[flavor]
    values = {'int': ('2', '4', '7', '9'), 'string': ('"first"', '"second"')}[element]
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

        source_edit(peer, traversal_provider(flavor, 'invalid'))
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


def hash_provider(flavor, key='int'):
    v = '$' if flavor == 'php' else ''
    type_name = f'hash<int,{key}>'
    head, decl = {
        'cpp': (f'{type_name} mapping()', f'{type_name} h = [];'),
        'php': (f'function mapping(): {type_name}', f'$h {type_name} = [];'),
        'js': (f'function mapping(): {type_name}', f'let h: {type_name} = [];'),
    }[flavor]
    if key == 'invalid':
        return head.replace(type_name, 'vector<int>') + ' { ' + decl.replace(type_name, 'vector<int>') + f' return {v}h; }}'
    literal = '2' if key == 'int' else '"a"'
    return head + f' {{ echo "H"; {decl} {v}h[{literal}] = 40; return {v}h; }}'


def key_traversal(work, flavor, library):
    app, bundle = work/'keys', work/'keys-bundle'
    caller = ("empty: hash<int,int> = []\nlast: int = 7\nfor last in empty:\n    pass\n"
              "if last != 7:\n    return 10\n"
              "fixed: hash<int,int> = fixedMapping()\nfixed_total: int = 0\n"
              "for fixed_key in fixed:\n    fixed_value: int = fixed[fixed_key]\n    fixed_total += fixed_value\n"
              "if fixed_total != 17:\n    return 11\n"
              "count: int = 0\nfor key in mapping():\n    count += 1\n"
              "return count + jsKeys()")
    write_app(app, 'py', {
        'main': caller,
        'idle': 'def idle() -> int:\n    return 17',
    })
    bridge = app/'bridge.js'
    bridge_source = ('function fixedMapping(): hash<int,int> { let local: hash<int,int> = []; '
                     'local[5] = 17; return local; } '
                     'function jsKeys(): int { let count: int = 0; let h = mapping(); '
                     'for (key in h) { let value: int = h[key]; h[key] = 41; '
                     'if (value != 40 || h[key] != 41) { return 9; } '
                     'echo key; count += 1; } return count; }')
    source_edit(bridge, bridge_source)
    peer = app/f'provider.{flavor}'
    source_edit(peer, hash_provider(flavor))
    resident = service(app, bundle)
    try:
        initial = invoke(resident)
        assert 'error' not in initial, initial
        units = artifacts(bundle).values()
        emitted = b'\n'.join(unit for unit in units if b'.iteration_next_key(' in unit)
        assert emitted.count(b'.iteration_next_key(') == 4, emitted
        assert b'.iteration_next(' not in emitted and b'iteration_output' not in emitted
        assert emitted.count(b'.iteration_current_value(') == 2 and b'.at(' in emitted
        native(bundle, work/'keys-int', (2, b'HH2', b''), library)

        source_edit(peer, hash_provider(flavor, 'string'))
        changed = invoke(resident)
        assert 'error' not in changed and changed['preparation']['reused'], changed
        assert {Path(path).name for path in changed['emitted_sources']} == {
            peer.name, 'main.py', 'bridge.js',
        }, changed
        fresh = work/'keys-string-fresh'
        assert 'error' not in fresh_build(app, fresh)
        assert artifacts(bundle) == artifacts(fresh)
        native(bundle, work/'keys-string', (2, b'HHa', b''), library)
        source_edit(bridge, bridge_source.replace('let value:', 'count += 0; let value:'))
        fallback = invoke(resident)
        assert 'error' not in fallback and fallback['preparation']['reused'], fallback
        assert [Path(path).name for path in fallback['emitted_sources']] == [bridge.name], fallback
        assert sum(part.count(b'.iteration_current_value(') for part in artifacts(bundle).values()) == 1
        native(bundle, work/'keys-fallback', (2, b'HHa', b''), library)
        source_edit(bridge, bridge_source)
        restored = invoke(resident)
        assert 'error' not in restored and artifacts(bundle) == artifacts(fresh), restored
        previous = artifacts(bundle)
        manifest = (bundle/'manifest.json').read_bytes()

        for name, path, edit, recovery in [
            ('provider', peer, hash_provider(flavor, 'invalid'), hash_provider(flavor, 'string')),
            ('scope', app/'main.py', caller.replace('return count + jsKeys()', 'return key'), caller),
            ('deleted', peer, None, hash_provider(flavor, 'string')),
        ]:
            if edit is None:
                path.unlink()
            else:
                source_edit(path, edit)
            failed = invoke(resident)
            fresh_failure = fresh_build(app, work/f'keys-{name}-failure')
            assert 'error' in failed and failed['error'] == fresh_failure.get('error'), (failed, fresh_failure)
            if name == 'provider':
                assert 'JS for-in requires a typed hash' in failed['error'], failed
            assert artifacts(bundle) == previous and (bundle/'manifest.json').read_bytes() == manifest
            source_edit(path, recovery)
            recovered = invoke(resident)
            assert 'error' not in recovered, recovered
            assert artifacts(bundle) == previous
        native(bundle, work/'keys-recovered', (2, b'HHa', b''), library)
    finally:
        resident.stdin.close()
        assert resident.wait() == 0



ITEMS_ENTRY = """class Cell:
    field: int = 0

    def __init__(self, start: int):
        self.field = start

key: int = 99
total: int = 0
for key, value in makePairs().items():
    weight: int = keyWeight(key)
    if weight != valueWeight(value):
        return 1
    total += weight
if key != 99 or total != 6:
    return 2

held: int = 17
empty: hash<int,int> = {}
for k, held in empty.items():
    pass
if held != 17:
    return 3
one: hash<int,int> = {1: 8}
for k, held in one.items():
    if k != 1:
        return 4
if held != 8:
    return 5

cells: hash<Cell,int> = {1: Cell(7)}
for k, item in cells.items():
    item.field = 8
    item = Cell(9)
if cells[1].field != 8:
    return 6
return held
"""


def items_provider(flavor, key='int', value='int', trace='A', vector=False):
    v = '$' if flavor == 'php' else ''
    type_name = 'vector<int>' if vector else f'hash<{value},{key}>'
    signature, declaration = {
        'cpp': (f'{type_name} makePairs()', f'{type_name} h = [];'),
        'php': (f'function makePairs(): {type_name}', f'$h {type_name} = [];'),
        'js': (f'function makePairs(): {type_name}', f'let h: {type_name} = [];'),
    }[flavor]
    data = {'int': ('2', '4'), 'string': ('"a"', '"b"')}
    payload = {'int': ('20', '40'), 'string': ('"twenty"', '"forty"')}
    fill = '' if vector else ''.join(f'{v}h[{k}] = {x};' for k, x in zip(data[key], payload[value]))
    provider = f'{signature} {{ echo "{trace}"; {declaration} {fill} return {v}h; }}'

    def weight(name, operand, first, int_result):
        head = {
            'cpp': f'int {name}({operand} arg)',
            'php': f'function {name}({operand} $arg): int',
            'js': f'function {name}(arg: {operand}): int',
        }[flavor]
        if operand == 'int':
            return f'{head} {{ return {int_result}; }}'
        return f'{head} {{ if ({v}arg == {first}) {{ return 2; }} return 4; }}'

    return provider + weight('keyWeight', key, '"a"', v+'arg') + weight('valueWeight', value, '"twenty"', v+'arg / 10')


def paired_items(work, flavor, library):
    app, bundle = work/'items', work/'items-bundle'
    write_app(app, 'py', {'main': ITEMS_ENTRY, 'idle': 'def idle() -> int:\n    return 17'})
    peer = app/f'items-peer.{flavor}'
    source_edit(peer, items_provider(flavor))
    resident = service(app, bundle)
    try:
        result = invoke(resident)
        assert 'error' not in result, result
        native(bundle, work/'items-initial', (8, b'A', b''), library)
        cpp = b'\n'.join(artifacts(bundle).values())
        assert cpp.count(b'.iteration_next(') == 4 and b'.iteration_next_key(' not in cpp
        assert b'.iteration_current_value(' not in cpp and cpp.count(b'.at(') == 1

        source_edit(peer, items_provider(flavor, trace='B'))
        changed = invoke(resident)
        assert 'error' not in changed and changed['preparation']['reused'], changed
        assert [Path(path).name for path in changed['emitted_sources']] == [peer.name], changed
        native(bundle, work/'items-body-edit', (8, b'B', b''), library)

        for key, value in [('string', 'int'), ('string', 'string')]:
            source_edit(peer, items_provider(flavor, key, value, 'B'))
            changed = invoke(resident)
            assert 'error' not in changed and changed['preparation']['reused'], changed
            assert {Path(path).name for path in changed['emitted_sources']} == {peer.name, 'main.py'}, changed
            native(bundle, work/f'items-{key}-{value}', (8, b'B', b''), library)

        main = app/'main.py'
        typed_destination = ITEMS_ENTRY.replace('total: int = 0', 'total: int = 0\nvalue: int = 0')
        source_edit(main, typed_destination)
        provider = items_provider(flavor, 'string', 'int', 'B')
        source_edit(peer, provider)
        checked = invoke(resident)
        assert 'error' not in checked, checked
        native(bundle, work/'items-existing-destination', (8, b'B', b''), library)
        previous = artifacts(bundle)
        manifest = (bundle/'manifest.json').read_bytes()
        caller_stamp = main.stat().st_mtime_ns
        loop_line = next(i for i, line in enumerate(typed_destination.splitlines(), 1)
                         if line.startswith('for key, value'))
        for name, edit, diagnostic in [
            ('incompatible', items_provider(flavor, 'string', 'string', 'B'), 'conversion'),
            ('vector', items_provider(flavor, vector=True), 'items traversal requires a typed hash'),
            ('deleted', None, 'makePairs'),
        ]:
            if edit is None:
                peer.unlink()
            else:
                source_edit(peer, edit)
            failed = invoke(resident)
            fresh = fresh_build(app, work/f'items-{name}-failure')
            assert diagnostic in failed.get('error', '') and failed['error'] == fresh.get('error'), (failed, fresh)
            if name == 'incompatible':
                assert f'main.py:{loop_line}:' in failed['error'], failed
            assert artifacts(bundle) == previous and (bundle/'manifest.json').read_bytes() == manifest
            source_edit(peer, provider)
            recovered = invoke(resident)
            assert 'error' not in recovered and artifacts(bundle) == previous, recovered
            assert main.stat().st_mtime_ns == caller_stamp and main.read_text() == typed_destination

        fresh = work/'items-fresh'
        assert 'error' not in fresh_build(app, fresh)
        assert artifacts(bundle) == artifacts(fresh) == previous
        native(bundle, work/'items-recovered', (8, b'B', b''), library)
    finally:
        resident.stdin.close()
        assert resident.wait() == 0


COLLECTION_PEERS = {
    'cpp': ('int mark(int n) { echo n; return n; } '
            'string key(int n) { echo n; if (n == 4) { return "a"; } return "b"; }'),
    'php': ('function mark(int $n): int { echo $n; return $n; } '
            'function key(int $n): string { echo $n; if ($n == 4) { return "a"; } return "b"; }'),
    'js': ('function mark(n: int): int { echo n; return n; } '
           'function key(n: int): string { echo n; if (n == 4) { return "a"; } return "b"; }'),
}
COLLECTION_ENTRY = """class Item:
    value: int = 0

    def __init__(self, value: int):
        self.value = value

class Holder:
    values: vector<int>

values: vector<int> = [
    mark(1),
    mark(2),
]
copy: vector<int> = values
values[mark(0)] = 3
mapping: hash<int,string> = {
    key(4): mark(5),
    key(6): mark(7),
}
mapping["a"] = mark(8)
mapping["new"] = mark(9)
empty: hash<int,string> = {}
empty["insert"] = 1
vector_items: vector<Item> = [Item(10)]
vector_items[0] = Item(11)
hash_items: hash<Item,string> = {"item": vector_items[0]}
hash_items["another"] = Item(12)
holder: Holder = Holder()
field_values: vector<int> = [13]
holder.values = field_values
holder.values[0] = 14
if copy[0] != 1 or values[0] != 3 or values[1] != 2:
    return 1
if mapping["a"] != 8 or mapping["b"] != 7 or mapping["new"] != 9 or empty["insert"] != 1:
    return 2
if vector_items[0].value != 11 or hash_items["item"].value != 11 or hash_items["another"].value != 12:
    return 3
if holder.values[0] != 14:
    return 4
return 8
"""


def typed_collections(work, flavor, library):
    app, bundle = work/'collections', work/'collections-bundle'
    write_app(app, 'py', {'main': COLLECTION_ENTRY})
    main = app/'main.py'
    peer = app/f'collection-peer.{flavor}'
    provider = COLLECTION_PEERS[flavor]
    source_edit(peer, provider)
    resident = service(app, bundle)
    try:
        result = invoke(resident)
        assert 'error' not in result, result
        native(bundle, work/'collections-initial', (8, b'120456789', b''), library)

        edited = provider.replace('echo ', 'echo "X", ', 1)
        source_edit(peer, edited)
        result = invoke(resident)
        assert 'error' not in result and result['preparation']['reused'], result
        assert [Path(path).name for path in result['emitted_sources']] == [peer.name], result
        native(bundle, work/'collections-body-edit', (8, b'X1X2X04X56X7X8X9', b''), library)
        previous = artifacts(bundle)
        manifest = (bundle/'manifest.json').read_bytes()

        for name, source, diagnostic in [
            ('key-type', COLLECTION_ENTRY.replace('key(4):', '4:'), 'key'),
            ('bad-write', COLLECTION_ENTRY.replace('values[mark(0)] = 3', 'values[0] = "bad"'), 'conversion'),
        ]:
            source_edit(main, source)
            failure = invoke(resident)
            fresh = fresh_build(app, work/f'collections-{name}-failure')
            assert diagnostic in failure.get('error', '').lower() and failure['error'] == fresh.get('error'), (failure, fresh)
            assert artifacts(bundle) == previous and (bundle/'manifest.json').read_bytes() == manifest
            source_edit(main, COLLECTION_ENTRY)
            assert 'error' not in invoke(resident)

        peer.unlink()
        failure = invoke(resident)
        fresh = fresh_build(app, work/'collections-missing-peer')
        assert 'error' in failure and failure['error'] == fresh.get('error'), (failure, fresh)
        assert artifacts(bundle) == previous and (bundle/'manifest.json').read_bytes() == manifest
        source_edit(peer, edited)
        recovered = invoke(resident)
        assert 'error' not in recovered, recovered
        fresh_bundle = work/'collections-fresh'
        assert 'error' not in fresh_build(app, fresh_bundle)
        assert artifacts(bundle) == artifacts(fresh_bundle) == previous
        native(bundle, work/'collections-recovered', (8, b'X1X2X04X56X7X8X9', b''), library)
    finally:
        resident.stdin.close()
        assert resident.wait() == 0

    for label, source, expected in [
        ('negative-read', 'x: vector<int> = [1]\nreturn x[-1]', (1, b'', b'sequence_index_out_of_range\n')),
        ('past-end-write', 'x: vector<int> = [1]\nx[1] = 8\nreturn 8', (1, b'', b'sequence_index_out_of_range\n')),
        ('missing-key', 'x: hash<int,string> = {"a": 1}\nreturn x["b"]', (1, b'', b'hash_missing_key\n')),
        ('duplicate', 'x: hash<int,string> = {key(4): mark(5), key(4): mark(6), key(7): mark(8)}\nreturn 8',
         (1, b'4X54X67X8', b'duplicate_initializer_key\n')),
    ]:
        source_edit(main, source)
        output = work/f'collections-{label}'
        result = fresh_build(app, output)
        assert 'error' not in result, result
        native(output, work/f'collections-{label}-run', expected, library)


DIVISION_PEERS = {
    'cpp': ('int inputNumber() { echo "I"; return 7; } '
            'int markNumber(int n) { echo "M"; return n; } '
            'bool oldMath() { return -7 / 3 == -2 && -7 % 3 == -1; }'),
    'php': ('function inputNumber(): int { echo "I"; return 7; } '
            'function markNumber(int $n): int { echo "M"; return $n; } '
            'function oldMath(): bool { return -7 / 3 == -2 && -7 % 3 == -1; }'),
    'js': ('function inputNumber(): int { echo "I"; return 7; } '
           'function markNumber(n: int): int { echo "M"; return n; } '
           'function oldMath(): bool { return -7 / 3 == -2 && -7 % 3 == -1; }'),
}
DIVISION_ENTRY = """if not oldMath():
    return 1
if -7 // 3 != -3 or 7 // -3 != -3 or -7 // -3 != 2 or 7 // 3 != 2:
    return 2
if -7 % 3 != 2 or 7 % -3 != -2 or -7 % -3 != -1 or 7 % 3 != 1 or -6 % 3 != 0:
    return 3
if (-9223372036854775807 - 1) % -1 != 0 or (-9223372036854775807 - 1) / -1 != 9223372036854775808.0:
    return 4
if 9007199254740993 / 3 != 3002399751580331.0 or 9007199254740993 / 1 != 9007199254740992.0:
    return 5
if 7 / 2.0 != 3.5 or 7.0 / 2 != 3.5 or 7.0 / 2.0 != 3.5:
    return 6
if markNumber(7) / markNumber(2) != 3.5:
    return 7
return 8
"""


def division(work, flavor, library):
    app, bundle = work/'division', work/'division-bundle'
    caller = "q: float = inputNumber() / 2\nreturn 8 if q > 3.0 else 1"
    write_app(app, 'py', {'main': caller, 'idle': 'def idle() -> int:\n    return 17'})
    peer = app/f'division-peer.{flavor}'
    provider = DIVISION_PEERS[flavor]
    source_edit(peer, provider)
    main = app/'main.py'
    resident = service(app, bundle)
    try:
        first = invoke(resident)
        assert 'error' not in first, first
        native(bundle, work/'division-int', (8, b'I', b''), library)
        body_edit = provider.replace('echo "I"', 'echo "J"')
        source_edit(peer, body_edit)
        changed = invoke(resident)
        assert 'error' not in changed and changed['preparation']['reused'], changed
        assert [Path(path).name for path in changed['emitted_sources']] == [peer.name], changed
        native(bundle, work/'division-body-edit', (8, b'J', b''), library)

        floating = body_edit.replace('int inputNumber', 'float inputNumber').replace('inputNumber(): int', 'inputNumber(): float')
        floating = floating.replace('return 7;', 'return 7.5;', 1)
        source_edit(peer, floating)
        compatible = invoke(resident)
        assert 'error' not in compatible and compatible['preparation']['reused'], compatible
        assert {Path(path).name for path in compatible['emitted_sources']} == {peer.name, 'main.py'}, compatible
        assert any(b'scpp::float64_divide(' in part for part in artifacts(bundle).values())
        native(bundle, work/'division-float', (8, b'J', b''), library)

        for index, operator in enumerate(('//', '%')):
            floor = f'q: int = inputNumber() {operator} 2\nreturn 8'
            source_edit(peer, body_edit)
            source_edit(main, floor)
            assert 'error' not in invoke(resident)
            previous = artifacts(bundle)
            manifest = (bundle/'manifest.json').read_bytes()
            source_edit(peer, floating)
            failed = invoke(resident)
            fresh_invalid = fresh_build(app, work/f'division-bad-{index}')
            assert 'Unsupported binary operands or operator' in failed.get('error', ''), failed
            assert failed['error'] == fresh_invalid.get('error')
            assert artifacts(bundle) == previous and (bundle/'manifest.json').read_bytes() == manifest
            source_edit(peer, body_edit)
            assert 'error' not in invoke(resident)
            fresh = work/f'division-fresh-{index}'
            assert 'error' not in fresh_build(app, fresh)
            assert artifacts(bundle) == artifacts(fresh) == previous
            native(bundle, work/f'division-recovered-{index}', (8, b'J', b''), library)
    finally:
        resident.stdin.close()
        assert resident.wait() == 0

    source_edit(main, DIVISION_ENTRY)
    full = work/'division-combinations'
    assert 'error' not in fresh_build(app, full)
    native(full, work/'division-combinations-run', (8, b'MM', b''), library)

    for index, (expression, output, error) in enumerate([
        ('markNumber(7) / markNumber(0)', b'MM', b'division_by_zero'),
        ('7 // 0', b'', b'division_by_zero'),
        ('7 % 0', b'', b'modulo_by_zero'),
        ('7 / -0.0', b'', b'division_by_zero'),
        ('7.0 / 0.0', b'', b'division_by_zero'),
        ('(-9223372036854775807 - 1) // -1', b'', b'division_overflow'),
    ]):
        source_edit(main, f'q: {"float" if " / " in expression else "int"} = {expression}\nreturn markNumber(8)')
        target = work/f'division-error-{index}'
        result = fresh_build(app, target)
        assert 'error' not in result, result
        native(target, work/f'division-error-run-{index}', (1, output, error + b'\n'), library)


def hash_membership(work, flavor, library):
    syntax, _, _ = forms(flavor)
    fn = syntax['function']
    caller = """class Entry:
    pass

def hash_contains() -> bool:
    return False

h: hash<string,string> = {"": "", "one": "1"}
if "" not in h or "missing" in h:
    return 1
records: hash<Entry,string> = {}
records["one"] = Entry()
if "one" not in records or "absent" in records:
    return 4
if not (key() in mapping()):
    return 2
selected: int = 2
if selected not in replaceKey(selected) or selected != 7:
    return 5
return 8 if key() + 1 not in mapping() else 3
"""
    app, bundle = work/'membership', work/'membership-bundle'
    write_app(app, 'py', {'main': caller, 'idle': 'def idle() -> int:\n    return 17'})
    peer = app/f'provider.{flavor}'
    replace = {
        'cpp': 'hash<int,int> replaceKey(int &k) { k = 7; return mapping(); }',
        'php': 'function replaceKey(int &$k): hash<int,int> { $k = 7; return mapping(); }',
        'js': 'function replaceKey(&k: int): hash<int,int> { k = 7; return mapping(); }',
    }[flavor]

    def provider(key_type, literal):
        return (fn('key', key_type, [], f'echo "K"; return {literal};')
                + hash_provider(flavor).replace('= 40;', '= 0;') + replace)

    valid = provider('int', '2')
    source_edit(peer, valid)
    resident = service(app, bundle)
    try:
        first = invoke(resident)
        assert 'error' not in first, first
        units = json.loads((bundle/'manifest.json').read_text())['sources']
        main_unit, = (unit for path, unit in units.items() if Path(path).name == 'main.py')
        emitted = (bundle/main_unit).read_text()
        assert emitted.count('.has(') == 7, emitted
        assert all(op not in emitted for op in ('.at(', '.find(', '.iteration_next')), emitted
        native(bundle, work/'membership-hit', (8, b'KHHKH', b''), library)
        changed_body = provider('int', '7')
        source_edit(peer, changed_body)
        changed = invoke(resident)
        assert 'error' not in changed and [Path(p).name for p in changed['emitted_sources']] == [peer.name], changed
        native(bundle, work/'membership-miss', (2, b'KH', b''), library)
        previous = artifacts(bundle)
        manifest = (bundle/'manifest.json').read_bytes()
        for stage, invalid, diagnostic in [('key', provider('string', '"2"'), 'Membership requires key type'),
                                           ('deleted', None, 'Unknown function: key')]:
            if invalid is None:
                peer.unlink()
            else:
                source_edit(peer, invalid)
            failed = invoke(resident)
            assert diagnostic in failed.get('error', ''), failed
            assert failed['error'] == fresh_build(app, work/f'membership-invalid-{stage}').get('error')
            assert artifacts(bundle) == previous and (bundle/'manifest.json').read_bytes() == manifest
            source_edit(peer, changed_body)
            restored = invoke(resident)
            assert 'error' not in restored, restored
            fresh = work/f'membership-fresh-{stage}'
            assert 'error' not in fresh_build(app, fresh)
            assert artifacts(bundle) == artifacts(fresh) == previous
            native(bundle, work/f'membership-recovered-{stage}', (2, b'KH', b''), library)

        source_edit(app/'main.py', 'return 8 if key() // 0 in mapping() else 2')
        checked = invoke(resident)
        assert 'error' not in checked, checked
        native(bundle, work/'membership-key-failure', (1, b'K', b'division_by_zero\n'), library)

        # The same caller can bind to two different published hash/key signatures.
        source_edit(app/'main.py', 'return 8 if key() in mapping() else 2')
        for stage, (key_type, literal, status) in enumerate([('int', '2', 8), ('string', '"absent"', 2), ('int', '2', 8)]):
            source_edit(peer, fn('key', key_type, [], f'echo "K"; return {literal};') + hash_provider(flavor, key_type))
            changed = invoke(resident)
            assert 'error' not in changed and changed['preparation']['reused'], changed
            assert {Path(p).name for p in changed['emitted_sources']} == {peer.name, 'main.py'}, changed
            fresh = work/f'membership-compatible-{stage}'
            fresh_result = fresh_build(app, fresh)
            assert 'error' not in fresh_result, fresh_result
            assert artifacts(bundle) == artifacts(fresh)
            native(bundle, work/f'membership-compatible-run-{key_type}', (status, b'KH', b''), library)
    finally:
        resident.stdin.close()
        assert resident.wait() == 0


def comparison_chains(work, flavor, library):
    fn = forms(flavor)[0]['function']
    app, bundle = work/'chains', work/'chains-bundle'
    caller = """if not low() <= middle() < high() == 3 != 4 >= 4 > 1:
    return 1
if high() < middle() < skipped():
    return 2
if low() < high() < middle() < skipped():
    return 5
if not (1 < 2) == True:
    return 3
value: int = 2
if not 1 < value < replaceValue(value) or value != 100:
    return 4
return 8
"""
    write_app(app, 'py', {'main': caller, 'idle': 'def idle() -> int:\n    return 0'})
    replace = {
        'cpp': 'int replaceValue(int &v) { v = 100; return 3; }',
        'php': 'function replaceValue(int &$v): int { $v = 100; return 3; }',
        'js': 'function replaceValue(&v: int): int { v = 100; return 3; }',
    }[flavor]
    calls = app/f'calls.{flavor}'
    providers = replace + ''.join(
        fn(name, 'int', [], f'echo "{mark}"; return {value};')
        for name, mark, value in [('low', 'L', 1), ('middle', 'M', 2), ('high', 'H', 3)])
    source_edit(calls, providers)
    peer = app/f'last.{flavor}'
    valid = fn('skipped', 'int', [], 'echo "X"; return 4;')
    source_edit(peer, valid)
    resident = service(app, bundle)
    try:
        result = invoke(resident)
        assert 'error' not in result, result
        expected = (8, b'LMHHMLHM', b'')
        native(bundle, work/'chains-run', expected, library)
        source_edit(calls, providers.replace('echo "M"', 'echo "m"'))
        changed = invoke(resident)
        assert 'error' not in changed and changed['preparation']['reused'], changed
        assert [Path(p).name for p in changed['emitted_sources']] == [calls.name], changed
        expected = (8, b'LmHHmLHm', b'')
        native(bundle, work/'chains-body-edit', expected, library)
        previous = artifacts(bundle)
        manifest = (bundle/'manifest.json').read_bytes()
        for stage in ('signature', 'deleted'):
            if stage == 'signature':
                source_edit(peer, fn('skipped', 'string', [], 'return "wrong";'))
            else:
                peer.unlink()
            failed = invoke(resident)
            diagnostic = 'Unsupported binary' if stage == 'signature' else 'Unknown function: skipped'
            assert diagnostic in failed.get('error', ''), failed
            assert failed['error'] == fresh_build(app, work/f'bad-{stage}').get('error')
            assert artifacts(bundle) == previous and (bundle/'manifest.json').read_bytes() == manifest
            source_edit(peer, valid)
            recovered = invoke(resident)
            assert 'error' not in recovered and recovered['preparation']['reused'], recovered
            assert {Path(path).name for path in recovered['emitted_sources']} == {peer.name, 'main.py'}, recovered
            fresh = work/f'restored-{stage}'
            assert 'error' not in fresh_build(app, fresh)
            assert artifacts(bundle) == artifacts(fresh) == previous
            native(bundle, work/f'chains-{stage}', expected, library)
    finally:
        resident.stdin.close()
        assert resident.wait() == 0


def none_identity(work, flavor, library):
    syntax, evaluate, _, _, interface, implementation = interface_forms(flavor)
    fn = syntax['function']
    caller = """empty: nullable<int> = None
zero: nullable<int> = 0
flag: nullable<bool> = False
text: nullable<string> = ""
if empty is not None or zero is None or flag is None or text is None:
    return 1
if not None is None or None is not None:
    return 2
if nothing() is not None or missing() is not None or live() is None:
    return 3
if absentView() is not None or view() is None:
    return 4
if absentOwner() is not None or owner() is None:
    return 5
if findKind("missing") is not None or findKind("") is None:
    return 6
return 8 if probe() is None else 9
"""
    app, bundle = work/'none', work/'none-bundle'
    write_app(app, 'py', {'main': caller, 'idle': 'def idle() -> int:\n    return 7'})
    records = interface() + implementation('Node', evaluate('override', '1', default='1'))
    records += syntax['record']('Payload', '')
    records += fn('nothing', 'null', [], 'echo "N"; return null;')
    records += fn('missing', '?Node', [], 'echo "M"; return null;')
    records += fn('live', 'Node', [], 'echo "L"; return new Node();')
    records += fn('absentView', '?Readable', [], 'echo "A"; return null;')
    records += fn('view', 'Readable', [], 'echo "V"; return new Node();')
    records += fn('absentOwner', 'shared<Payload>', [], 'echo "E"; return null;')
    records += fn('owner', 'shared<Payload>', [], 'echo "O"; return new Payload();')
    case = 'case Empty = "";' if flavor == 'php' else 'Empty = ""'
    records += 'enum Kind:string {' + case + '}'
    separator = '.' if flavor == 'js' else '::'
    records += fn('findKind', 'nullable<Kind>', [('text', 'string')],
                  f'echo "K"; return enum_try_from_value(Kind{separator}class, {syntax["v"]}text);')
    source_edit(app/f'owners.{flavor}', records)
    peer = app/f'probe.{flavor}'
    empty = fn('probe', 'nullable<int>', [], 'echo "P"; return null;')
    present = empty.replace('return null;', 'return 0;')
    source_edit(peer, empty)
    resident = service(app, bundle)
    try:
        initial = invoke(resident)
        assert 'error' not in initial, initial
        native(bundle, work/'none-empty', (8, b'NMLAVEOKKP', b''), library)
        source_edit(peer, present)
        changed = invoke(resident)
        assert 'error' not in changed and [Path(p).name for p in changed['emitted_sources']] == [peer.name], changed
        native(bundle, work/'none-present', (9, b'NMLAVEOKKP', b''), library)

        previous = artifacts(bundle)
        optional = fn('probe', '?Node', [], 'echo "P"; return null;')
        for stage, provider, status in [('handle', optional, 8), ('nullable', present, 9)]:
            source_edit(peer, provider)
            changed = invoke(resident)
            assert 'error' not in changed and changed['preparation']['reused'], changed
            assert {Path(p).name for p in changed['emitted_sources']} == {peer.name, 'main.py'}, changed
            fresh = work/f'none-fresh-{stage}'
            assert 'error' not in fresh_build(app, fresh)
            assert artifacts(bundle) == artifacts(fresh)
            native(bundle, work/f'none-{stage}', (status, b'NMLAVEOKKP', b''), library)
        assert artifacts(bundle) == previous

        for stage, diagnostic in [('result', 'Python None test'), ('deleted', 'Unknown function: probe')]:
            manifest = (bundle/'manifest.json').read_bytes()
            if stage == 'deleted':
                peer.unlink()
            else:
                source_edit(peer, present.replace('nullable<int>', 'result<int>'))
            failed = invoke(resident)
            assert diagnostic in failed.get('error', ''), failed
            assert failed['error'] == fresh_build(app, work/f'none-invalid-{stage}').get('error')
            assert artifacts(bundle) == previous and (bundle/'manifest.json').read_bytes() == manifest

            source_edit(peer, present)
            restored = invoke(resident)
            assert 'error' not in restored and restored['preparation']['reused'], restored
            assert {Path(p).name for p in restored['emitted_sources']} == {peer.name, 'main.py'}, restored
            fresh = work/f'none-restored-{stage}'
            assert 'error' not in fresh_build(app, fresh)
            assert artifacts(bundle) == artifacts(fresh) == previous
            native(bundle, work/f'none-recovered-{stage}', (9, b'NMLAVEOKKP', b''), library)
    finally:
        resident.stdin.close()
        assert resident.wait() == 0


def integer_bitwise(work, flavor, library):
    syntax, _, _ = forms(flavor)
    function = syntax['function']
    mutate = {
        'cpp': 'int change(int &value) { value = 100; return 3; }',
        'php': 'function change(int &$value): int { $value = 100; return 3; }',
        'js': 'function change(&value: int): int { value = 100; return 3; }',
    }[flavor]
    caller = """flags: int = 12
flags ^= change(flags)
if flags != 15:
    return 3
flags &= 6
flags |= 8
small: uint8 = 255
if ~small != 0:
    return 4
small &= 7
small ^= 4
small |= mask()
if not flags & 6 == 6 or 8 | 3 ^ 6 & 2 != 9:
    return 1
if ~flags != -15 or flags != 14 or small != 11:
    return 2
return 8
"""
    app, bundle = work/'bitwise', work/'bitwise-bundle'
    write_app(app, 'py', {'main': caller, 'idle': 'def idle() -> int:\n    return 7'})
    peer = app/f'masks.{flavor}'

    def provider(result, value):
        return mutate + function('mask', result, [], f'echo "M"; return {value};')

    def verify_mask_writeback(checked):
        units = json.loads((bundle/'manifest.json').read_text())['sources']
        main_unit, = (unit for path, unit in units.items() if Path(path).name == 'main.py')
        assignment, = (line for line in (bundle/main_unit).read_text().splitlines() if 'mask_()' in line)
        assert 'var_small_' in assignment and ' | mask_()' in assignment, assignment
        assert ('scpp::checked_int64_to_uint8(' in assignment) == checked, assignment

    valid = provider('int', '8')
    source_edit(peer, valid)
    resident = service(app, bundle)
    try:
        initial = invoke(resident)
        assert 'error' not in initial, initial
        previous = artifacts(bundle)
        verify_mask_writeback(checked=True)
        native(bundle, work/'bitwise-valid', (8, b'M', b''), library)

        for result in ('uint8', 'int'):
            source_edit(peer, provider(result, '8'))
            changed = invoke(resident)
            assert 'error' not in changed and changed['preparation']['reused'], changed
            assert {Path(p).name for p in changed['emitted_sources']} == {peer.name, 'main.py'}, changed
            verify_mask_writeback(checked=result == 'int')
            fresh = work/f'bitwise-signature-{result}'
            assert 'error' not in fresh_build(app, fresh)
            assert artifacts(bundle) == artifacts(fresh)
            native(bundle, work/f'bitwise-signature-run-{result}', (8, b'M', b''), library)
        assert artifacts(bundle) == previous

        source_edit(peer, provider('int', '256'))
        changed = invoke(resident)
        assert 'error' not in changed and [Path(p).name for p in changed['emitted_sources']] == [peer.name], changed
        native(bundle, work/'bitwise-overflow', (1, b'M', b'integer_cast_out_of_range\n'), library)

        last_good = artifacts(bundle)
        manifest = (bundle/'manifest.json').read_bytes()
        source_edit(peer, provider('float', '2.5'))
        failed = invoke(resident)
        assert 'error' in failed and failed['error'] == fresh_build(app, work/'bitwise-invalid').get('error'), failed
        assert artifacts(bundle) == last_good and (bundle/'manifest.json').read_bytes() == manifest

        source_edit(peer, valid)
        restored = invoke(resident)
        assert 'error' not in restored, restored
        fresh = work/'bitwise-fresh'
        assert 'error' not in fresh_build(app, fresh)
        assert previous == artifacts(bundle) == artifacts(fresh)
        native(bundle, work/'bitwise-recovered', (8, b'M', b''), library)
    finally:
        resident.stdin.close()
        assert resident.wait() == 0

    source_edit(peer, provider('int', '256'))
    for operator, expected in [('&', (8, b'M', b'')), ('^', (1, b'M', b'integer_cast_out_of_range\n'))]:
        source_edit(app/'main.py', f'small: uint8 = 255\nsmall {operator}= mask()\nreturn 8 if small == 0 else 2')
        target = work/('bitwise-boundary-' + ('and' if operator == '&' else 'xor'))
        built = fresh_build(app, target)
        assert 'error' not in built, built
        native(target, target/'program', expected, library)


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


INHERITANCE = """class Child(Base):
    extra: int = 1

    def __init__(self, start: int):
        super().__init__(start)
        self.value = self.value + self.extra

    @override
    def evaluate(self) -> int:
        super().mark()
        return super().evaluate() + super().score(self.value) + self.extra

    @virtual
    def other(self) -> int:
        return self.value
"""
BASE_CONSTRUCTORS = {
    'cpp': 'Base(int start) { echo "B"; this->value = start; }',
    'php': 'public function __construct(int $start) { echo "B"; $this->value = $start; }',
    'js': 'constructor(start: int) { echo "B"; this.value = start; }',
}


def inheritance(work, flavor, library):
    syntax, _, klass, _, method = ancestor_syntax(flavor)
    field = {'cpp': 'public: int value = 0;', 'php': 'public int $value = 0;', 'js': 'value: int = 0;'}[flavor]
    virtual = {
        'cpp': 'virtual int evaluate() { return 0; }',
        'php': 'public virtual function evaluate(): int { return 0; }',
        'js': 'virtual evaluate(): int { return 0; }',
    }[flavor]
    inherited = {
        'cpp': 'int bonus() { return 0; }',
        'php': 'public function bonus(): int { return 0; }',
        'js': 'bonus(): int { return 0; }',
    }[flavor]
    marker = {
        'cpp': 'void mark() { echo "M"; }',
        'php': 'public function mark(): void { echo "M"; }',
        'js': 'mark(): void { echo "M"; }',
    }[flavor]
    v = syntax['v']
    parameter = {'cpp': 'int amount', 'php': 'int $amount', 'js': 'amount: int'}[flavor]
    far_score = method('score', f'return {v}amount + 20;', params=parameter, intent='virtual')
    near_score = method('score', f'return {v}amount;', params=parameter, intent='override')
    base = klass('Base', field + BASE_CONSTRUCTORS[flavor] + virtual + inherited + marker + near_score, 'Grandparent')
    access = syntax['access']
    api = syntax['function']('viaBase', 'int', [('obj', 'Base')],
                             f'return {v}obj{access}evaluate() + {v}obj{access}bonus();')
    app, bundle = work/'inheritance', work/'inheritance-bundle'
    write_app(app, 'py', {
        'main': 'child: Child = Child(3)\nreturn viaBase(child) + child.other()',
        'child': INHERITANCE,
        'idle': 'def idle() -> int:\n    return 17',
    })
    peer = app/f'base.{flavor}'
    source_edit(app/f'grandparent.{flavor}', klass('Grandparent', far_score))
    source_edit(peer, base)
    source_edit(app/f'api.{flavor}', api)
    resident = service(app, bundle)
    try:
        result = invoke(resident)
        assert 'error' not in result, result
        native_dir = work/'inheritance-native'
        incremental_native_build(bundle, native_dir, 'clang++', library, b'BM', expected_status=9)
        units = {Path(path).name: Path(unit) for path, unit in json.loads((bundle/'manifest.json').read_text())['sources'].items()}
        base_unit = units[peer.name]
        initial = artifacts(bundle)
        stamps = {path: (bundle/path).stat().st_mtime_ns for path in initial}

        edited = base.replace(virtual, virtual.replace('return 0', 'return 1')).replace(marker, marker.replace('"M"', '"E"'))
        source_edit(peer, edited)
        changed = invoke(resident)
        assert 'error' not in changed and changed['preparation']['reused'], changed
        assert {Path(path).name for path in changed['emitted_sources']} == {
            peer.name, f'api.{flavor}', 'child.py', 'main.py',
        }, changed
        previous = artifacts(bundle)
        assert previous.keys() == initial.keys()
        assert {path for path in initial if initial[path] != previous[path]} == {base_unit}
        assert {path for path in initial if stamps[path] != (bundle/path).stat().st_mtime_ns} == {base_unit}
        rebuilt = incremental_native_build(bundle, native_dir, 'clang++', library, b'BE', expected_status=10)
        assert rebuilt == {str(base_unit)}, rebuilt
        manifest = (bundle/'manifest.json').read_bytes()

        for owner, provider, status in [('Grandparent', edited.replace(near_score, ''), 30), ('Base', edited, 10)]:
            source_edit(peer, provider)
            changed = invoke(resident)
            assert 'error' not in changed, changed
            fresh = work/f'inheritance-rebind-{owner}'
            assert 'error' not in fresh_build(app, fresh)
            assert artifacts(bundle) == artifacts(fresh)
            assert f'.scpp_records::{owner}::scpp_virtual_score(' in (bundle/units['child.py']).read_text()
            incremental_native_build(bundle, native_dir, 'clang++', library, b'BE', expected_status=status)
        assert artifacts(bundle) == previous

        child = app/'child.py'
        for name, path, invalid, diagnostic in [
            ('missing-marker', child, INHERITANCE.replace('    @override\n', ''), 'requires override'),
            ('duplicate-marker', child, INHERITANCE.replace('@override', '@override\n    @override'), 'Duplicate method modifier'),
            ('multi-base', child, INHERITANCE.replace('Child(Base)', 'Child(Base, Base)'), 'single base class'),
            ('late-super', child, INHERITANCE.replace('        super().__init__(start)', '        pass\n        super().__init__(start)'), 'super'),
            ('removed-super-method', peer, edited.replace(marker.replace('"M"', '"E"'), ''), 'mark'),
            ('base-contract', peer, edited.replace('virtual ', ''), 'Nonvirtual method hiding'),
        ]:
            source_edit(path, invalid)
            failed = invoke(resident)
            fresh = fresh_build(app, work/f'inheritance-{name}')
            assert diagnostic in failed.get('error', ''), failed
            assert failed['error'] == fresh.get('error'), (failed, fresh)
            assert artifacts(bundle) == previous and (bundle/'manifest.json').read_bytes() == manifest
            source_edit(path, edited if path == peer else INHERITANCE)
            recovered = invoke(resident)
            assert 'error' not in recovered, recovered
            assert artifacts(bundle) == previous

        peer.unlink()
        failed = invoke(resident)
        fresh = fresh_build(app, work/'inheritance-deleted')
        assert 'error' in failed and failed['error'] == fresh.get('error'), (failed, fresh)
        assert artifacts(bundle) == previous and (bundle/'manifest.json').read_bytes() == manifest
        source_edit(peer, edited)
        recovered = invoke(resident)
        assert 'error' not in recovered, recovered
        fresh = work/'inheritance-fresh'
        assert 'error' not in fresh_build(app, fresh)
        assert artifacts(bundle) == artifacts(fresh) == previous
        native(bundle, work/'inheritance-recovered', (10, b'BE', b''), library)
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
        key_traversal(case, flavor, library)
        typed_collections(case, flavor, library)
        hash_membership(case, flavor, library)
        none_identity(case, flavor, library)
        integer_bitwise(case, flavor, library)
        division(case, flavor, library)
        records_and_exceptions(case, flavor, library)
        inheritance(case, flavor, library)


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
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--items', action='store_true', help='run the paired hash traversal matrix')
    parser.add_argument('--chains', action='store_true', help='run the comparison chain matrix')
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-python-flavor-') as directory:
        work = Path(directory)
        library = runtime(work, 'clang++', [ROOT/'runtime/generated/src/hash.cpp'])
        if args.items or args.chains:
            for flavor in BRIDGES:
                case = work/flavor
                case.mkdir()
                proof = paired_items if args.items else comparison_chains
                proof(case, flavor, library)
        else:
            prove(work/'proof', library)

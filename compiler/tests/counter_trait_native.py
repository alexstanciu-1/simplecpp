"""Counter trait composition: source provenance, independent consumers and incremental publication."""
import argparse
import json
import re
import tempfile
from pathlib import Path

from app_namespace_native import artifacts, invoke, service
from application_vectors_native import measure
from extends_native import source_edit, run, ROOT
from interfaces_native import fresh
from classes_native import fixture


def sources(flavor):
    s = fixture(flavor)
    receiver = s['v'] + 'this' + s['access']
    field, method = {
        'php': ('private int $count=4;', 'public function next():int'),
        'cpp': ('private: int count=4;', 'public: int next()'),
        'js': ('private count:int=4;', 'next():int'),
    }[flavor]
    members = f'{field}\n{method} {{\n{receiver}count += 1;\nreturn {receiver}count;\n}}'
    trait = f'trait Counter {{\n{members}\n}}' + (';' if flavor == 'cpp' else '')
    first = s['klass']('First', 'use Counter;')
    second = s['record']('Second', 'use Counter;')
    def entry(both):
        main = s['infer']('a', 'new First()')
        if both:
            main += s['local']('b', 'Second')
        for name in ('aba' if both else 'aa'):
            main += f'echo {s["v"]}{name}{s["access"]}next();'
        return main + 'return 8;'
    return trait, members, first, second, entry


def prove(work, flavor):
    trait, _, first, second, entry = sources(flavor)
    changed = trait.replace('+= 1', '+= 2')
    broken = changed.replace('count +=', 'missing +=')
    app = work / flavor
    app.mkdir()
    output = work / (flavor + '-out')
    (app / 'module.json').write_text(json.dumps({'entry': 'main.' + flavor}))
    proc = service(app, output)
    timings = []
    s = fixture(flavor)
    host_call = s['v'] + 'this' + s['access'] + 'step()'
    hook_trait = trait.replace('+= 1', '+= ' + host_call)
    hook, parameter = {
        'php': ('private function step():int', 'int $amount'),
        'cpp': ('private: int step()', 'int amount'),
        'js': ('private step():int', 'amount:int'),
    }[flavor]
    def consumer(declaration, signature, value):
        return declaration.replace('use Counter;', f'use Counter; {signature} {{ return {value}; }}')
    first_hook = consumer(first, hook, 1)
    second_hook = consumer(second, hook, 2)
    wrong_hook = consumer(second, hook.replace('()', '(' + parameter + ')'), 2)
    changes = [
        ({'counter': trait, 'first': first, 'main': entry(False)}, b'56'),
        ({'second': second, 'main': entry(True)}, b'556'),
        ({}, b'556'),
        ({'counter': changed}, b'668'),
        ({'counter': broken}, None),
        ({}, None),
        ({'counter': changed}, b'668'),
        ({'second': None, 'main': entry(False)}, b'68'),
        ({'second': second, 'main': entry(True)}, b'668'),
        ({'counter': None}, 'Unknown trait'),
        ({'counter': changed}, b'668'),
        ({'counter': hook_trait, 'first': first_hook, 'second': second_hook}, b'566'),
        ({'second': wrong_hook}, 'Argument count mismatch'),
        ({}, 'Argument count mismatch'),
        ({'second': consumer(second, hook, 3)}, b'576'),
    ]
    try:
        for index, (edits, expected) in enumerate(changes):
            before = artifacts(output)
            first_unit = None
            if before:
                manifest = json.loads((output / 'manifest.json').read_text())
                first_unit = next(output / unit for path, unit in manifest['sources'].items() if Path(path).name == 'first.' + flavor)
                first_stamp = first_unit.stat().st_mtime_ns
            for name, text in edits.items():
                path = app / f'{name}.{flavor}'
                if text is None:
                    path.unlink()
                else:
                    source_edit(path, text)
            current = invoke(proc)
            reference = work / f'{flavor}-fresh-{index}'
            clean = fresh(app, reference)
            if isinstance(expected, str):
                assert expected in current.get('error', '') and current['error'] == clean.get('error'), current
                if expected == 'Argument count mismatch':
                    error = current['error']
                    assert re.search(rf'counter\.{flavor}:4:\d+:', error) and 'step' in error, error
                    assert first_unit.stat().st_mtime_ns == first_stamp
                assert before == artifacts(output)
                continue
            if expected is None:
                error = current.get('error', '')
                assert error == clean.get('error') and re.search(rf'counter\.{flavor}:4:\d+:', error), error
                assert before == artifacts(output)
                continue
            assert 'error' not in current and 'error' not in clean, (flavor, index, current, clean)
            assert artifacts(output) == artifacts(reference)
            if first_unit and 'second' in edits and 'first' not in edits:
                assert first_unit.stat().st_mtime_ns == first_stamp
                assert not any(Path(path).name == 'first.' + flavor for path in current['emitted_sources']), current
            if not edits:
                assert current['preparation']['prepared'] == 0
            if not edits:
                continue
            timing = measure(output, work / f'{flavor}-native-{index}', expected)
            timings.append(dict(revision=index, php_ms=current['php_ms'], **timing))
    finally:
        proc.stdin.close()
        proc.wait()

    # Lexical trait names use the origin's namespace; the first consumer supplies its private receiver.
    sep = {'cpp': '::', 'js': '.', 'php': chr(92)}[flavor]
    def namespace(name, code):
        return f'namespace {name} {{ {code} }}'
    helper = fixture(flavor)['function']('step', 'int', [], 'return 3;')
    source_edit(app / ('counter.' + flavor), namespace('Lib', trait.replace('+= 1', '+= step()') + helper))
    for name, consumer in [('first', first), ('second', second)]:
        source_edit(app / (name + '.' + flavor), 'use Lib' + sep + 'Counter;' + consumer)
    reference = work / (flavor + '-imports')
    result = fresh(app, reference)
    assert 'error' not in result, result
    measure(reference, work / (flavor + '-imports-native'), b'7710')

    (work / (flavor + '-timings.json')).write_text(json.dumps(timings, indent=2))
    # Exercise the separate standalone preparation/publication path with a same-file trait and consumer.
    combined = work / ('single.' + flavor)
    combined.write_text(trait + first + second + entry(True))
    facts = work / 'facts.json'
    facts.write_text(json.dumps({'schema': 1, 'native_integers': {'int': {'signed': True, 'digits': 31}}}))
    cpp = work / (flavor + '-standalone.cpp')
    run(['php', ROOT / 'compiler/main.php', 'cpp', flavor, combined, facts, cpp])
    standalone = work / (flavor + '-standalone')
    standalone.mkdir()
    (standalone / 'main.cpp').write_bytes(cpp.read_bytes())
    (standalone / 'manifest.json').write_text(json.dumps({'translation_units': ['main.cpp']}))
    measure(standalone, work / (flavor + '-standalone-native'), b'556')


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-counter-trait-') as tmp:
        work = args.output or Path(tmp)
        work.mkdir(exist_ok=True)
        for flavor in ('php', 'cpp', 'js'):
            prove(work, flavor)
            print(flavor, 'Counter passed', flush=True)

"""Concrete adapter demand: cross-unit deduplication, removal, namespace changes and recovery."""
import argparse
import hashlib
import os
import json
import subprocess
from pathlib import Path
import tempfile

from app_namespace_native import artifacts, invoke, service
from classes_native import fixture
from extends_native import build, runtime, source_edit
from native_build import ninja_path, shell

ROOT = Path(__file__).resolve().parents[2]


def adapter_files(output):
    return {path: text for path, text in artifacts(output).items() if b'// Concrete inline adapter;' in text}


def prove(work, flavor, libraries):
    s = fixture(flavor)
    v, access, function = s['v'], s['access'], s['function']
    leaf = s['record']('Leaf', '', 'Node').replace('struct ', 'class ', 1)
    first = function('first', 'Node', [('n', 'Leaf')], f'return {v}n;')
    second = function('second', '?Node', [('n', '?Leaf')], f'return {v}n;')
    third = function('third', 'Node', [('n', '?Leaf')], f'return {v}n;')
    read = function('read', 'int', [('n', 'Node')], f'return {v}n{access}x;')
    main = s['infer']('n', 'new Leaf()') + f'echo read(first({v}n)),read(second({v}n)),read(third({v}n)); return 8;'
    app = work / flavor
    app.mkdir()
    manifest = app / 'module.json'
    source_edit(manifest, json.dumps({'entry': 'main.' + flavor}))
    sources = {'node': s['node'], 'leaf': leaf, 'first': first, 'second': second, 'third': third, 'read': read, 'main': main}
    paths = {name: app / (name + '.' + flavor) for name in sources}
    for name, text in sources.items():
        source_edit(paths[name], text)
    output = work / (flavor + '-out')
    edits = [(None, None, 1, None), (None, None, 1, None),
        ('first', first.replace(f'return {v}n;', 'return new Node();'), 1, None),
        ('second', second.replace(f'return {v}n;', 'return new Node();'), 1, None),
        ('third', third.replace(f'return {v}n;', 'return new Node();'), 0, None),
        ('second', second, 1, None),
        ('third', third.replace(f'return {v}n;', 'return null;'), 1, 'Required object cannot be null'),
        (None, None, 1, 'Required object cannot be null'), ('third', third, 1, None),
        ('namespace', 'acme::ledger', 1, None), ('namespace', None, 1, None), ('namespace', 'App', 1, None)]
    resident = service(app, output)
    try:
        for revision, (name, text, count, error) in enumerate(edits):
            if name == 'namespace':
                source_edit(manifest, json.dumps({'entry': 'main.' + flavor, 'cpp_namespace': text}))
            elif name:
                source_edit(paths[name], text)
            before = artifacts(output)
            old_adapters = adapter_files(output)
            current = invoke(resident)
            fresh_output = work / f'{flavor}-fresh-{revision}'
            fresh = service(app, fresh_output)
            try:
                comparison = invoke(fresh)
            finally:
                fresh.stdin.close()
                fresh.wait()
            if error:
                assert error in current.get('error', ''), current
                assert current['error'] == comparison.get('error')
                assert artifacts(output) == before
                continue
            assert 'error' not in current and 'error' not in comparison, (revision, current, comparison)
            assert artifacts(output) == artifacts(fresh_output), revision
            adapters = adapter_files(output)
            assert len(adapters) == count, (revision, adapters.keys())
            assert all(b'template<' not in text and b'application_view<' not in text for text in artifacts(output).values())
            for removed in old_adapters.keys() - adapters.keys():
                assert not (output / removed).exists(), removed
            if revision == 1:
                assert current['preparation']['prepared'] == 0 and current['emitted_sources'] == [], current
            if revision in [2, 3, 4, 5]:
                unchanged = 'third' if revision in [2, 3] else 'first'
                assert not any(p.endswith('/' + unchanged + '.' + flavor) for p in current['emitted_sources']), current
            if revision in [0, 4, 5, 8, 9, 10, 11]:
                for cc, library in libraries.items():
                    build(output, work / (flavor + '-adapter-' + cc), cc, library, b'333')
    finally:
        resident.stdin.close()
        resident.wait()


def incremental_native_build(output, native, compiler, library, expected_stdout=b'3', *, expected_status=8):
    """Persistent, compiler-specific Ninja graph; stable object names and real compiler depfiles."""
    native.mkdir(exist_ok=True)
    manifest = json.loads((output / 'manifest.json').read_text())
    objects = {unit: 'unit-' + hashlib.sha256(unit.encode()).hexdigest() + '.o'
               for unit in manifest['translation_units']}
    flags = ' '.join(map(shell, [compiler, '-std=c++23', '-O1', '-g0', '-Wall', '-Wextra',
                                '-Werror', '-I', ROOT / 'runtime/generated/public']))
    lines = ['rule compile', f'  command = {flags} -MMD -MF $out.d -c $in -o $out',
             '  depfile = $out.d', '  deps = gcc', 'rule link',
             f'  command = {shell(compiler)} $in -o $out']
    lines += [f'build {obj}: compile {ninja_path(output / unit)}' for unit, obj in objects.items()]
    lines += [f'build program: link {" ".join(objects.values())} {ninja_path(library)}', 'default program']
    graph = native / 'build.ninja'
    text = '\n'.join(lines) + '\n'
    if not graph.exists() or graph.read_text() != text:
        graph.write_text(text)
    before = {unit: (native / obj).stat().st_mtime_ns for unit, obj in objects.items() if (native / obj).exists()}
    result = subprocess.run(['ninja', '-j', '4', '-v'], cwd=native, capture_output=True, text=True)
    assert result.returncode == 0, result.stdout + result.stderr
    changed = {unit for unit, obj in objects.items() if before.get(unit) != (native / obj).stat().st_mtime_ns}
    result = subprocess.run([native / 'program'], capture_output=True)
    assert (result.returncode, result.stdout, result.stderr) == (expected_status, expected_stdout, b''), result
    return changed


def removal_and_native_reuse(work, flavor, libraries):
    """Deletion drops source demand, while surviving consumers and native objects remain reusable."""
    s = fixture(flavor)
    app, output = work / (flavor + '-removal'), work / (flavor + '-removal-out')
    app.mkdir()
    source_edit(app / 'module.json', json.dumps({'entry': 'main.' + flavor}))
    texts = {'node': s['node'], 'leaf': s['record']('Leaf', '', 'Node').replace('struct ', 'class ', 1),
             'main': s['infer']('n', 'new Leaf()') + f'echo {s["v"]}n{s["access"]}x; return 8;'}
    for name in ['first', 'second']:
        texts[name] = s['function'](name, 'Node', [('n', 'Leaf')], f'return {s["v"]}n;')
    paths = {name: app / (name + '.' + flavor) for name in texts}
    for name, text in texts.items():
        source_edit(paths[name], text)
    # Neither function is called: removing its source is a successful compilation,
    # independently of the error/recovery coverage for missing declarations elsewhere.
    steps = [('initial', None, 1), ('noop', None, 1), ('touch-adapter', None, 1),
             ('edit', 'first', 1), ('delete', 'first', 1), ('delete', 'second', 0),
             ('restore', 'second', 1), ('restore', 'first', 1), ('noop', None, 1)]
    resident = service(app, output)
    try:
        for revision, (action, name, count) in enumerate(steps):
            old = artifacts(output)
            old_adapters = adapter_files(output)
            stamps = {p: (output / p).stat().st_mtime_ns for p in old}
            if action == 'edit':
                source_edit(paths[name], texts[name].replace(f'return {s["v"]}n;', 'return new Node();'))
            elif action == 'delete':
                paths[name].unlink()
            elif action == 'restore':
                source_edit(paths[name], texts[name])
            current = invoke(resident)
            assert 'error' not in current, current
            fresh_output = work / f'{flavor}-removal-fresh-{revision}'
            fresh = service(app, fresh_output)
            try:
                comparison = invoke(fresh)
                assert 'error' not in comparison, comparison
            finally:
                fresh.stdin.close()
                fresh.wait()
            now = artifacts(output)
            assert now == artifacts(fresh_output), (flavor, action)
            assert len(adapter_files(output)) == count
            manifest = json.loads((output / 'manifest.json').read_text())
            assert set(manifest['headers']) == {str(p) for p in now if p.suffix == '.hpp'}
            for path in old.keys() - now.keys():
                assert not (output / path).exists(), path
            for path in old.keys() & now.keys():
                if old[path] == now[path]:
                    assert (output / path).stat().st_mtime_ns == stamps[path], (action, path)
            if action in ['noop', 'touch-adapter']:
                assert current['preparation']['prepared'] == 0 and current['emitted_sources'] == [], current
            if name == 'first':
                assert not any(p.endswith('/second.' + flavor) for p in current['emitted_sources']), current
            if action == 'delete':
                assert not any(p.endswith('/' + paths[name].name) for p in manifest['sources'])
            if action == 'touch-adapter':
                # Force a real header dependency rebuild without changing source or prepared facts.
                for path in old_adapters:
                    os.utime(output / path, None)
            changed_cpp = {str(p) for p in now if p.suffix == '.cpp' and old.get(p) != now[p]}
            expected = changed_cpp
            if action == 'touch-adapter':
                expected = {unit for source, unit in manifest['sources'].items()
                            if any(source.endswith('/' + paths[n].name) for n in ['first', 'second'])}
                assert len(expected) == 2
            for cc, library in libraries.items():
                native = work / (flavor + '-persistent-' + cc)
                executable = native / 'program'
                prior_link = executable.stat().st_mtime_ns if executable.exists() else None
                changed = incremental_native_build(output, native, cc, library)
                assert changed == expected, (flavor, cc, action, changed, expected)
                if action == 'noop':
                    assert executable.stat().st_mtime_ns == prior_link, 'No-op relinked executable'
                if action == 'delete':
                    assert executable.stat().st_mtime_ns != prior_link, 'Source deletion did not relink'
            print(flavor, action, name or '', 'native reuse/removal passed on Clang/GCC', flush=True)
    finally:
        resident.stdin.close()
        resident.wait()


def standalone(work, flavor, libraries):
    """Single-file output embeds concrete definitions; constructor arguments use the same demand path."""
    s = fixture(flavor)
    constructors = {
        'cpp': 'Box(Node n) { this->value=n; }',
        'php': 'public function __construct(Node $n) { $this->value=$n; }',
        'js': 'constructor(n:Node) { this.value=n; }',
    }
    leaf = s['record']('Leaf', '', 'Node').replace('struct ', 'class ', 1)
    box = s['klass']('Box', s['field']('value', 'Node') + constructors[flavor])
    main = s['infer']('b', 'new Box(new Leaf())') + f'echo {s["v"]}b{s["access"]}value{s["access"]}x; return 8;'
    source = work / (flavor + '-standalone.input')
    source.write_text(s['node'] + leaf + box + main)
    facts = work / 'facts.json'
    facts.write_text(json.dumps({'schema': 1, 'native_integers': {'int': {'signed': True, 'digits': 31}}}))
    cpp = work / (flavor + '-standalone.cpp')
    result = subprocess.run(['php', ROOT / 'compiler/main.php', 'cpp', flavor, source, facts, cpp], capture_output=True, text=True)
    assert result.returncode == 0, result.stderr
    text = cpp.read_text()
    assert '// Concrete inline adapter;' in text and 'template<' not in text and 'application_view<' not in text
    for cc, library in libraries.items():
        binary = work / (flavor + '-standalone-' + cc)
        result = subprocess.run([cc, '-std=c++23', '-Wall', '-Wextra', '-Werror', '-I',
            ROOT / 'runtime/generated/public', cpp, library, '-o', binary], capture_output=True, text=True)
        assert result.returncode == 0, result.stderr
        result = subprocess.run([binary], capture_output=True)
        assert (result.returncode, result.stdout, result.stderr) == (8, b'3', b''), result


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory() as directory:
        work = (args.output or Path(directory)).resolve()
        work.mkdir(exist_ok=True)
        libraries = {cc: runtime(work, cc) for cc in ['clang++', 'g++']}
        for flavor in ['cpp', 'php', 'js']:
            prove(work, flavor, libraries)
            standalone(work, flavor, libraries)
            removal_and_native_reuse(work, flavor, libraries)
            print(flavor, 'concrete adapter lifecycle passed', flush=True)

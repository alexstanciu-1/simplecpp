"""Render the published vector recipe natively against two independently prepared source demands."""
import io
import json
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile

from app_namespace_native import artifacts, invoke, service
from application_vectors_native import vector_files
from translated_preparation_recovery import ROOT, fresh_build, source_edit

NAMESPACE = 'Fixture'
OWNERS = ('left', 'right')


def build(bundle, exe, cxx, linker, links):
    manifest = json.loads((bundle/'manifest.json').read_text())
    subprocess.run([cxx, '-std=c++23', '-stdlib=libc++', f'--ld-path={linker}', '-I',
                    ROOT/'runtime/generated/public', *(bundle/unit for unit in manifest['translation_units']),
                    *links, '-o', exe], check=True)


def fresh(app, out):
    result = fresh_build(app, out)
    assert 'error' not in result, result
    return json.loads((out/'manifest.json').read_text())


def render(exe, edits):
    data = io.BytesIO(subprocess.check_output([exe, NAMESPACE, *edits]))
    generations = []
    while line := data.readline():
        files = {}
        for _ in range(int(line)):
            path = Path(data.readline().decode().rstrip('\n'))
            size = int(data.readline())
            payload = data.read(size)
            assert len(payload) == size and path not in files
            files[path] = payload
        generations.append(files)
    return generations


def source(owner, active):
    if not active:
        return f'function {owner}():int {{ return 0; }}'
    return (f'function read_{owner}(vector<Record> &$rows):int {{ $row = $rows[0]; return $row->n; }}'
            f'function {owner}():int {{ $rows vector<Record> = []; $row Record; $rows[] = $row; '
            f'return read_{owner}($rows); }}')


def vector_bundles(work, cxx, linker):
    fixture = ROOT/'compiler/examples/concrete_vector'
    tool = work/'tool'
    tool.mkdir()
    (tool/'module.json').write_text('{"general_api":true}')
    with (tool/'recipe.phs').open('wb') as output:
        subprocess.run(['php', fixture/'publish.php'], stdout=output, check=True)
    shutil.copyfile(fixture/'render.phs', tool/'main.phs')
    bundle = work/'tool-out'
    renderer = work/'native-renderer'
    tool_manifest = fresh(tool, bundle)
    build(bundle, renderer, cxx, linker, tool_manifest['link_inputs'])

    app, out = work/'app', work/'out'
    app.mkdir()
    (app/'module.json').write_text(json.dumps({'cpp_namespace': NAMESPACE}))
    (app/'main.phs').write_text('echo left(), right(); return 8;')
    (app/'record.phs').write_text('struct Record { public int $n = 7; }')
    for owner in OWNERS:
        source_edit(app/f'{owner}.phs', source(owner, False))
    # Header-only lowering should preserve the link plan of the vector-free application.
    links = fresh(app, work/'base')['link_inputs']
    process = service(app, out)
    edits = []
    history = []
    previous = {}
    try:
        for revision, active in enumerate((OWNERS, ('right',), ())):
            for owner in OWNERS:
                if revision == 0 or (owner in previous) != (owner in active):
                    source_edit(app/f'{owner}.phs', source(owner, owner in active))
            result = invoke(process)
            assert 'error' not in result, result
            clean = work/f'fresh-{revision}'
            manifest = fresh(app, clean)
            assert artifacts(clean) == artifacts(out)
            assert links == manifest['link_inputs'] == json.loads((out/'manifest.json').read_text())['link_inputs']
            prepared = json.loads(subprocess.check_output([
                'php', ROOT/'compiler/tests/prepared_vector_demands.php', app, NAMESPACE]))
            demands = {owner: prepared[f'read_{owner}'] for owner in active}
            assert len(demands) == len(prepared)
            fresh_edits = []
            for owner, facts in demands.items():
                request = ['request', owner, *facts]
                fresh_edits.extend(request)
                if previous.get(owner) != facts:
                    edits.extend(request)
            for owner in previous.keys() - demands.keys():
                edits.extend(['release', owner])
            edits.append('publish')
            files = vector_files(out)
            assert len(files) == int(bool(active))
            if revision == 1:
                assert files == history[0]
            history.append(files)
            native = render(renderer, edits)
            assert native == history
            assert render(renderer, [*fresh_edits, 'publish']) == [native[-1]]
            # Use only the native header for the executable, with PHP-emitted callers.
            candidate = work/f'native-{revision}'
            shutil.copytree(out, candidate, ignore=lambda folder, names: [
                name for name in names if (Path(folder)/name).relative_to(out) in files])
            for path, payload in native[-1].items():
                (candidate/path).write_bytes(payload)
            expected = ''.join('7' if owner in active else '0' for owner in OWNERS).encode()
            yield candidate, links, expected
            previous = demands
    finally:
        process.stdin.close()
        assert process.wait() == 0


def verify(work, cxx, linker):
    for revision, (candidate, links, expected) in enumerate(vector_bundles(work, cxx, linker)):
        exe = work/f'app-{revision}'
        build(candidate, exe, cxx, linker, links)
        run = subprocess.run([exe], capture_output=True)
        assert (run.returncode, run.stdout, run.stderr) == (8, expected, b''), run


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-vector-recipe-') as tmp:
        verify(Path(tmp), *sys.argv[1:])

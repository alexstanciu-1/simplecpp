"""One translated publisher instance, two artifacts, a filesystem failure, and a retry."""
import json
import os
from pathlib import Path
import shutil
import stat
import subprocess
import sys
import tempfile

ROOT = Path(__file__).resolve().parents[2]
php, cxx, linker, filesystem_archive, runtime_archive = sys.argv[1:]
facts = Path(runtime_archive).parent / 'native-layout/facts.json'
fixture = ROOT / 'compiler/examples/publisher_cleanup/proof.php.scpp'


def run(*args, cwd=None):
    result = subprocess.run(list(map(str, args)), cwd=cwd, capture_output=True, timeout=60,
                            env={**os.environ, 'XDEBUG_MODE': 'off'})
    assert result.returncode == 0, (str(args[0]), result.stdout, result.stderr)
    return result.stdout


def manifest_paths(text):
    paths = [Path(path) for path in text.decode('ascii').splitlines()]
    assert len(paths) == 2
    assert paths[0].parent == Path('bundle')
    assert all(path.name.startswith('.scpp-stage-') for path in paths)
    return paths


with tempfile.TemporaryDirectory(prefix='scpp-publisher-cleanup-') as directory:
    work = Path(directory)
    app, compiled = work / 'app', work / 'compiled'
    app.mkdir()
    (app / 'module.json').write_text('{"dependencies":{"runtime":{"modules":["filesystem"]}}}')
    shutil.copyfile(fixture, app / 'main.php')
    run(php, ROOT / 'compiler/main.php', 'project', app, facts, compiled)

    output = json.loads((compiled / 'manifest.json').read_text())
    assert [Path(path).name for path in output['link_inputs']] == ['libscpp_filesystem.a', 'libscpp_runtime.a']
    executable = work / 'publisher'
    run(cxx, '-std=c++23', '-stdlib=libc++', '-O1', f'--ld-path={linker}',
        '-I', ROOT / 'runtime/generated/public', *[compiled / unit for unit in output['translation_units']],
        filesystem_archive, runtime_archive, '-o', executable)

    bundle = work / 'bundle'
    (bundle / 'previous').mkdir(parents=True)
    (bundle / 'units').write_bytes(b'output obstruction')
    (work / 'ready-units').mkdir()
    blocked = work / 'blocked-manifest'
    (blocked / 'units').mkdir(parents=True)
    (blocked / 'manifest').mkdir()
    (blocked / 'manifest/owner').write_bytes(b'keep')
    old_paths = manifest_paths(run(executable, cwd=work))
    new_paths = manifest_paths((bundle / 'manifest').read_bytes())

    assert old_paths[1].parent == Path('bundle/previous')
    assert new_paths[1].parent == Path('bundle/units')
    expected = dict(zip(old_paths + new_paths, [b'good\0entry', b'good\0unit', b'new\0entry', b'new\0unit']))
    assert len(expected) == 4
    for path, contents in expected.items():
        artifact = work / path
        assert artifact.read_bytes() == contents
        assert stat.S_IMODE(artifact.stat().st_mode) & 0o077 == 0

    assert (work / 'obstruction').read_bytes() == b'output obstruction'
    # Every surviving stage belongs to a committed revision. No failed stage or manifest temporary remains.
    files = {path.relative_to(work) for path in bundle.rglob('*') if path.is_file()}
    assert files == set(expected) | {Path('bundle/manifest')}, files
    # Promotion failed after reserving both artifacts and the manifest itself.
    blocked_files = {path.relative_to(blocked) for path in blocked.rglob('*') if path.is_file()}
    assert blocked_files == {Path('manifest/owner')}, blocked_files
    assert (blocked / 'manifest/owner').read_bytes() == b'keep'


print('Translated publisher preserved the last good bundle and recovered in the same process.')

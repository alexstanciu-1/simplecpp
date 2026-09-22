"""Exercise project exclusion using independent PHP processes and real OS locks."""

from contextlib import contextmanager
from pathlib import Path
import shutil
import subprocess
import tempfile
import time

PROTOTYPE = Path(__file__).resolve().parents[2]
ROOT = PROTOTYPE
PHP = shutil.which('php')
PROBE = [PHP, str(PROTOTYPE / 'tests/support/project_lock_probe.php')]
CLI = [PHP, str(PROTOTYPE / 'src/main.php')]


def run(command, *, ok=True):
    result = subprocess.run(command, capture_output=True, text=True, timeout=10)
    assert (result.returncode == 0) == ok, (command, result.stdout, result.stderr)
    return result


def snapshot(project):
    return {str(p.relative_to(project)): (p.read_bytes(), p.stat().st_mtime_ns)
            for p in project.rglob('*') if p.is_file()}


@contextmanager
def held(manifest, ready):
    process = subprocess.Popen(PROBE + ['hold', str(manifest), str(ready)],
                               stdin=subprocess.PIPE, stdout=subprocess.PIPE,
                               stderr=subprocess.PIPE, text=True)
    try:
        deadline = time.monotonic() + 10
        while not ready.exists():
            assert process.poll() is None, process.communicate()
            assert time.monotonic() < deadline, 'Lock probe did not become ready'
            time.sleep(0.01)
        yield process
    finally:
        if process.poll() is None:
            process.communicate('\n', timeout=10)
        assert process.returncode in (0, -9), process.returncode


with tempfile.TemporaryDirectory(prefix='scpp_project_locks_') as temporary:
    folder = Path(temporary)
    project, edited, other = (folder / name for name in ('project', 'edited', 'other'))
    for destination in (project, edited, other):
        shutil.copytree(ROOT / 'examples/three_files', destination)
    manifest = project / 'project.json'
    alternate = project / 'alternate.json'
    shutil.copy2(manifest, alternate)
    alias = folder / 'alias'
    alias.symlink_to(project, target_is_directory=True)
    manifest_alias = folder / 'manifest-alias.json'
    manifest_alias.symlink_to(manifest)
    before = snapshot(project), snapshot(edited)
    lock_file = Path(str(project) + '.scpp-compile.lock')

    with held(manifest, folder / 'ready-first'):
        inode = lock_file.stat().st_ino
        # Direct API and CLI must agree; aliases and alternate manifests share a lock.
        for path in (manifest, project / '../project/project.json', alternate,
                     alias / 'project.json', manifest_alias):
            assert 'already being compiled' in run(PROBE + ['compile', str(path)], ok=False).stderr
        assert 'already being compiled' in run(CLI + [str(manifest)], ok=False).stderr
        run(CLI + [str(other / 'project.json')])
        assert 'already being compiled' in run(CLI + [str(manifest), '--simulate-increment', str(edited)], ok=False).stderr
        assert not Path(str(project) + '.scpp-simulation').exists()
        assert (snapshot(project), snapshot(edited)) == before
    run(PROBE + ['compile', str(manifest)])
    assert lock_file.stat().st_ino == inode, 'Release must not replace the lock file'

    # A compiler using the edited copy must also prevent the swap; acquisition of
    # the original project's lock must be rolled back when this second lock fails.
    with held(edited / 'project.json', folder / 'ready-edited'):
        assert 'already being compiled' in run(CLI + [str(manifest), '--simulate-increment', str(edited)], ok=False).stderr
        assert not Path(str(project) + '.scpp-simulation').exists()
        run(PROBE + ['compile', str(manifest)])
        assert (snapshot(project), snapshot(edited)) == before

    with held(manifest, folder / 'ready-crash') as process:
        process.kill()
        process.communicate(timeout=10)
    run(PROBE + ['compile', str(manifest)])
    assert lock_file.stat().st_ino == inode, 'OS unlock after a crash must reuse the same file'

    # Lock setup errors must stop compilation instead of silently bypassing it.
    broken = folder / 'broken'
    shutil.copytree(ROOT / 'examples/three_files', broken)
    Path(str(broken) + '.scpp-compile.lock').mkdir()
    failed = run(PROBE + ['compile', str(broken / 'project.json')], ok=False)
    assert 'project lock:' in failed.stderr

print('Project locking: cross-process/API/CLI exclusion, canonical aliases, independent projects, simulation contention, release and crash recovery passed.')

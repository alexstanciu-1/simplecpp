"""Real CLI build and folder-swap executable increment, with exact restoration."""
from pathlib import Path
import json
import os
import shutil
import subprocess
import sys
import tempfile

ROOT = Path(__file__).resolve().parents[2]
with tempfile.TemporaryDirectory(prefix="scpp_native_cli_") as temporary:
    work = Path(temporary)
    original = work / "project"
    edited = work / "project-edited"
    shutil.copytree(ROOT / "examples/three_files", original)
    shutil.copytree(original, edited)
    value = edited / "src/nested/value.phs"
    value.write_text("function value(): int { return 47; }\n")
    old = (original / "src/nested/value.phs").stat().st_mtime
    os.utime(value, (old + 2, old + 2))
    snapshots = {folder: {str(p.relative_to(folder)): (p.read_bytes(), p.stat().st_mtime_ns)
        for p in folder.rglob('*') if p.is_file()} for folder in (original, edited)}
    output = work / "native program"
    command = ['php', str(ROOT / 'src/main.php'), str(original / 'project.json'),
        '--output', str(output), '--debug=json', '--simulate-increment', str(edited)]
    result = subprocess.run(command, text=True, capture_output=True, timeout=30)
    assert result.returncode == 0, result.stderr
    first, second = json.loads(result.stdout)['runs']
    assert first['completed'] and second['completed']
    assert first['stopped_before'] is None and second['stopped_before'] is None
    assert first['inputs']['full_rebuild'] and not second['inputs']['full_rebuild']
    assert first['native']['sha256'] != second['native']['sha256']
    assert subprocess.run([str(output)], timeout=3).returncode == 47
    for folder, before in snapshots.items():
        assert before == {str(p.relative_to(folder)): (p.read_bytes(), p.stat().st_mtime_ns)
            for p in folder.rglob('*') if p.is_file()}
    assert not list(work.glob('.scpp-native-*'))
    # The same CLI builds the restored original; compiling it does not execute it.
    result = subprocess.run(command[:-2], text=True, capture_output=True, timeout=30)
    assert result.returncode == 0, result.stderr
    assert json.loads(result.stdout)['completed']
    assert subprocess.run([str(output)], timeout=3).returncode == 42
    # Exercise actual CLI warning delivery after a successful native rename.
    # This test driver runs real Clang, then leaves one unknown staging file.
    real_clang = shutil.which('clang')
    wrappers = work / 'wrappers'
    wrappers.mkdir()
    wrapper = wrappers / 'clang'
    wrapper.write_text(f"#!{sys.executable}\n" +
        "import pathlib, subprocess, sys\n" +
        f"result = subprocess.run([{real_clang!r}, *sys.argv[1:]])\n" +
        "if result.returncode == 0 and '-c' not in sys.argv:\n" +
        "    for arg in sys.argv[1:]:\n" +
        "        if arg.endswith('/program'):\n" +
        "            pathlib.Path(arg).with_name('unowned').write_text('keep')\n" +
        "sys.exit(result.returncode)\n")
    wrapper.chmod(0o700)
    environment = dict(os.environ, PATH=str(wrappers) + os.pathsep + os.environ['PATH'])
    for simulate in (False, True):
        result = subprocess.run(command if simulate else command[:-2], env=environment,
            text=True, capture_output=True, timeout=30)
        assert result.returncode == 0, result.stderr
        exported = json.loads(result.stdout)
        runs = exported['runs'] if simulate else [exported]
        warnings = [warning for run in runs for warning in run['warnings']]
        assert all(run['completed'] for run in runs)
        assert len(warnings) == len(runs) and result.stderr.splitlines() == warnings
        assert subprocess.run([str(output)], timeout=3).returncode == (47 if simulate else 42)
        for directory in work.glob('.scpp-native-*'):
            assert list(directory.iterdir()) == [directory / 'unowned']
            (directory / 'unowned').unlink()
            directory.rmdir()
print('Native CLI: executable increment, exports, folder restoration and nonfatal cleanup warnings passed.')

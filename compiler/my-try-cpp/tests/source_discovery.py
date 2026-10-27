"""Focused discovery, retained reconciliation and real worker overlap proofs."""
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile

EXE, RETAINED = sys.argv[1:]
REPO = Path(__file__).resolve().parents[3]


def main():
    with tempfile.TemporaryDirectory(prefix="source-discovery-") as temporary:
        base = Path(temporary)
        root = base / "source"
        (root / "sub").mkdir(parents=True)
        (root / "a.phs").write_text("not parsed")
        (root / "sub/b.phs").write_text("also not parsed")
        (root / "ignored.txt").write_text("ignored")
        for name in (".git", ".hg", ".svn", ".prism"):
            (root / name).mkdir()
            (root / name / "hidden.phs").write_text("ignored")
        (root / "cycle").symlink_to(root, target_is_directory=True)
        (root / "alias.phs").symlink_to(root / "a.phs")
        catalogs = base / "catalogs"
        catalogs.mkdir()
        (catalogs / "core.json").write_text(json.dumps(dict(version=1, module="core", dependencies=[], functions=[], cpp=[])))
        args = ["--catalogs", str(catalogs), "--module", "core", "--source", str(root), "--jobs", "8"]
        process = subprocess.Popen([RETAINED, *args], stdin=subprocess.PIPE, stdout=subprocess.PIPE,
                                   stderr=subprocess.PIPE, text=True)
        def ready():
            line = process.stdout.readline().strip()
            if line != "ready":
                raise AssertionError("Retained discovery test failed: " + process.stderr.read())
        def step(command):
            process.stdin.write(command)
            process.stdin.flush()
            ready()
        try:
            ready()
            step('u')
            step('u')
            (root / "a.phs").write_text("different size")
            step('c')
            step('p')
            step('u')
            stat = (root / "a.phs").stat()
            (root / "a.phs").write_text("DIFFERENT SIZE")
            # Same-size edit with restored metadata intentionally needs notification.
            assert (root / "a.phs").stat().st_size == stat.st_size
            os.utime(root / "a.phs", ns=(stat.st_atime_ns, stat.st_mtime_ns))
            step('e')
            step('n')
            step('u')
            (root / "new.phs").write_text("new")
            step('a')
            (root / "sub/b.phs").unlink()
            step('d')
            (root / "sub/b.phs").write_text("returned")
            step('r')
            if os.geteuid() != 0:
                blocked = root / "blocked"
                blocked.mkdir()
                (root / "unpublished.phs").write_text("private addition")
                blocked.chmod(0)
                try:
                    step('f')
                finally:
                    blocked.chmod(0o700)
                    blocked.rmdir()
                    (root / "unpublished.phs").unlink()
            # Missing-root failure must also preserve the previously published inventory.
            root.rename(base / "missing")
            step('f')
            (base / "missing").rename(root)
            step('u')
            process.stdin.write('q')
            process.stdin.flush()
            _, errors = process.communicate(timeout=30)
            assert process.returncode == 0, errors
            assert not any(x in errors for x in ('Sanitizer', 'runtime error:')), errors
        finally:
            if process.poll() is None:
                process.kill()
                process.communicate()
        for extra in (["--source", str(root)], ["--source", str(root / "sub")],
                      ["--source", str(base / "missing")]):
            result = subprocess.run([EXE, *args, *extra], capture_output=True, text=True)
            assert result.returncode != 0 and 'startup failed:' in result.stderr, result.stderr
        # Enough real directory IO to demonstrate overlap, not an artificial sleep.
        wide = base / "wide"
        for directory in range(32):
            folder = wide / f"d{directory:02}"
            folder.mkdir(parents=True)
            for index in range(64):
                (folder / f"f{index:03}.phs").write_text("x")
        evidence = []
        for jobs in (1, 8):
            report = json.loads(subprocess.check_output([EXE, "--catalogs", str(catalogs), "--module", "core",
                "--source", str(wide), "--jobs", str(jobs), "--timings"], text=True))
            assert report['source_files'] == 2048 and report['source_directories'] == 33, report
            assert 1 <= report['scan_peak_workers'] <= jobs, report
            if jobs == 8:
                assert report['scan_peak_workers'] > 1, report
            evidence.append(report)
        print(json.dumps(evidence))
    print("Discovery: identity, pending changes, notifications, tombstones, retry, filtering and worker overlap passed")


if __name__ == '__main__':
    main()

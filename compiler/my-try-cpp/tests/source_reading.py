"""Checked reads, revision reuse, immutable lifetimes, and batch rollback."""
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile

with tempfile.TemporaryDirectory(prefix="source-reading-") as temporary:
    base = Path(temporary)
    root = base / "sources"
    root.mkdir()
    a, b = root / "a.phs", root / "b.phs"
    a.write_bytes(b"old")
    b.write_bytes(b"")
    catalogs = base / "catalogs"
    catalogs.mkdir()
    (catalogs / "core.json").write_text(json.dumps(dict(
        version=1, module="core", dependencies=[], functions=[], cpp=[])))
    args = ["--catalogs", str(catalogs), "--module", "core", "--source", str(root), "--jobs", "8"]
    process = subprocess.Popen([sys.argv[1], *args], stdin=subprocess.PIPE,
                               stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
    def ready():
        assert process.stdout.readline().strip() == "ready", process.stderr.read()
    def step(command):
        process.stdin.write(command)
        process.stdin.flush()
        ready()
    try:
        ready()
        step("u")
        stat = a.stat()
        a.write_bytes(b"NEW")
        os.utime(a, ns=(stat.st_atime_ns, stat.st_mtime_ns))
        step("u")
        step("n")
        step("n")  # Already pending but loaded: must invalidate again.
        a.write_bytes(b"replacement")
        b.write_bytes(b"x\0y")
        step("s")
        b.unlink()  # One read can succeed, but neither buffer may publish.
        step("f")
        b.write_bytes(b"x\0y")
        step("s")
        step("r")
        step("u")
        a.write_bytes(b"changed after observation")
        step("s")
        a.write_bytes(b"another size after scan")
        step("f")
        a.write_bytes(b"replacement")
        step("s")
        step("r")
        a.unlink()
        step("s")
        step("d")
        a.write_bytes(b"replacement")
        step("s")
        step("r")
        process.stdin.write("q")
        process.stdin.flush()
        _, errors = process.communicate(timeout=20)
        assert process.returncode == 0, errors
        assert not any(x in errors for x in ("Sanitizer", "runtime error:")), errors
    finally:
        if process.poll() is None:
            process.kill()
            process.communicate()

    # CLI now tokenizes too: embedded NUL remains valid inside a quoted source token.
    b.write_bytes(b'"x\0y"')
    # Flat directories also have parallel read work; metadata discovery is serial here.
    for i in range(64):
        (root / f"wide{i}.phs").write_bytes(b"x" * 262144)
    result = subprocess.run([sys.argv[2], *args, "--timings"], capture_output=True, text=True, check=True)
    report = json.loads(result.stdout)
    assert report["source_files_read"] == 66, report
    assert report["source_bytes_read"] == 64 * 262144 + len(b"replacement") + 5, report
    assert 1 < report["read_peak_workers"] <= 8, report
    print(json.dumps(report))

"""Prove ephemeral input parity, diagnostics and absence of working-directory artifacts."""
import json
from pathlib import Path
import subprocess
import sys
import tempfile

binary = str(Path(sys.argv[1]).resolve())
with tempfile.TemporaryDirectory(prefix="ephemeral-input-") as directory:
    cwd = Path(directory)
    def run(args, content=None):
        result = subprocess.run([binary, *args], input=content, capture_output=True, cwd=cwd)
        assert not list(cwd.iterdir()), "Command created files in its working directory"
        return result

    source = b'echo "hello";\n'
    stdin = run(["-"], source)
    inline = run(["-e", source.decode()])
    assert stdin.returncode == inline.returncode == 0, (stdin.stderr, inline.stderr)
    a, b = json.loads(stdin.stdout), json.loads(inline.stdout)
    assert a.pop("source") == "<stdin>"
    assert b.pop("source") == "<command-line>"
    assert a == b == {"state": "tokenized", "execution_available": False, "bytes": len(source), "tokens": 3}
    assert json.loads(run(["-"], b"").stdout)["tokens"] == 0
    # Chunk boundaries and embedded NUL must not truncate source bytes.
    payload = b'"' + b'x' * 20000 + b'\0";'
    large = run(["-"], payload)
    assert large.returncode == 0, large.stderr
    assert json.loads(large.stdout)["bytes"] == len(payload)
    for newline in (b"\n", b"\r\n", b"\r"):
        bad = run(["-"], b"echo 1;" + newline + b"  @")
        assert bad.returncode != 0
        assert b"<stdin>" in bad.stderr and b"line 2, column 3" in bad.stderr, bad.stderr
    bad = run(["-e", "@"])
    assert bad.returncode != 0 and b"<command-line>" in bad.stderr
    for args in (["-e"], ["-e", "echo 1;", "extra"], ["-", "extra"]):
        assert run(args, b"").returncode != 0
print("Ephemeral inputs: parity, byte preservation, diagnostics, argument checks and no working-directory artifacts passed")

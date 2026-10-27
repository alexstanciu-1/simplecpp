"""Prove published dependencies work after moving the project, without rebuilding them."""
import hashlib
import json
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile

PROJECT = Path(__file__).resolve().parents[1]


def archive_hashes(root):
    return {str(p.relative_to(root)): hashlib.sha256(p.read_bytes()).hexdigest()
            for p in root.rglob("*.a")}


def main():
    dependencies = PROJECT / "dependencies"
    middleware_ref = json.loads((dependencies / "middleware.json").read_text())["package"]
    assert not Path(middleware_ref).is_absolute()
    middleware = dependencies / middleware_ref
    runtime_ref = json.loads((middleware / "manifest.json").read_text())["configuration"]["runtime"]
    assert not Path(runtime_ref).is_absolute()
    cmake = (middleware / "package.cmake").read_text()
    assert "${CMAKE_CURRENT_LIST_DIR}/../" in cmake
    assert str(PROJECT) not in cmake
    with tempfile.TemporaryDirectory(prefix="cpp-relocation-") as temporary:
        relocated = Path(temporary) / "project with spaces"
        shutil.copytree(PROJECT, relocated,
                        ignore=shutil.ignore_patterns("build", "dependencies", "__pycache__"))
        target = relocated / "dependencies"
        for reference in (middleware_ref, runtime_ref):
            shutil.copytree(dependencies / reference, target / reference)
        (target / "middleware.json").write_text(json.dumps({"package": middleware_ref}))
        (target / "runtime.json").write_text(json.dumps({"package": runtime_ref}))
        before = archive_hashes(target)
        subprocess.run([sys.executable, relocated / "tools/build.py", "test"], check=True)
        assert before == archive_hashes(target)
        assert not (relocated / "build/runtime").exists()
        assert not (relocated / "build/middleware").exists()
        commands = (relocated / "build/compiler/compile_commands.json").read_text()
        assert str(PROJECT) not in commands
        subprocess.run(["ninja", "-C", relocated / "build/compiler", "-t", "commands"],
                       check=True, capture_output=True, text=True)
    print("PASS: relocated project configured, compiled and passed tests; dependency archives reused")


if __name__ == "__main__":
    main()

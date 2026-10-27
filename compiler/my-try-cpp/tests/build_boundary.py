"""Focused integration checks; run after tools/build.py test. No dependency rebuilds."""
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import time

PROJECT = Path(__file__).resolve().parents[1]
REPO = PROJECT.parents[1]
BUILD = PROJECT / "build/compiler"


def run(args, success=True):
    started = time.monotonic()
    result = subprocess.run([str(arg) for arg in args], capture_output=True, text=True)
    assert (result.returncode == 0) == success, result.stdout + result.stderr
    return result.stdout + result.stderr, round(time.monotonic() - started, 3)


def main():
    evidence = {}
    output, evidence["unchanged_seconds"] = run(["ninja", "-C", BUILD])
    assert "no work to do" in output, output
    archives = {p: (p.stat().st_mtime_ns, p.stat().st_size)
                for p in (PROJECT / "dependencies").rglob("*.a")}
    for label, path in (
        ("runtime_source", REPO / "runtime/include/core/runtime_core.cpp"),
        ("runtime_header", REPO / "runtime/include/scpp/string_t.hpp"),
        ("runtime_public_header", REPO / "runtime/public/scpp/integers.hpp"),
        ("middleware_source", PROJECT / "middleware/src/textbuilder.cpp"),
        ("middleware_header", PROJECT / "middleware/include/scpp_compiler/textbuilder.hpp"),
        ("compiler_source", PROJECT / "src/main.cpp"),
        ("support_source", PROJECT / "support/src/token_sequence.cpp"),
        ("model_header", PROJECT / "model/include/scpp_compiler/token.hpp"),
    ):
        stat = path.stat()
        try:
            os.utime(path, None)
            output, evidence[label + "_edit_seconds"] = run(["ninja", "-C", BUILD])
            if label in ("compiler_source", "support_source", "model_header"):
                assert "Building CXX object" in output and "Linking CXX executable" in output, output
                if label == "compiler_source":
                    assert "compiler_support.dir" not in output, output
                else:
                    assert "compiler_support.dir" in output, output
                if label == "model_header":
                    assert "compiler_startup.dir" in output, output
            else:
                assert "no work to do" in output, output
        finally:
            os.utime(path, ns=(stat.st_atime_ns, stat.st_mtime_ns))
    assert archives == {p: (p.stat().st_mtime_ns, p.stat().st_size) for p in archives}
    run([BUILD / "runtime-smoke-test"])
    run([BUILD / "my-try-cpp", "--help"])
    with tempfile.TemporaryDirectory(prefix="cpp-boundary-") as temporary:
        root = Path(temporary)
        header = root / "boundary.hpp"
        source = root / "probe.cpp"
        source.write_text('#include "boundary.hpp"\nint main() { return 0; }\n')
        command = [sys.executable, PROJECT / "tools/checked_compile.py",
                   shutil.which("clang++"), "-std=c++23", "-c", source, "-o", root / "probe.o"]
        header.write_text('// template in a comment is harmless\n#if 0\ntemplate<class T> class Off;\n#endif\n')
        run(command)
        header.write_text('template<class T> class Forbidden {};\n')
        output, _ = run(command, success=False)
        assert "Compiler template boundary rejected" in output, output
        header.write_text('#define GENERIC template\nGENERIC<class T> class Forbidden {};\n')
        output, _ = run(command, success=False)
        assert "Compiler template boundary rejected" in output, output
    evidence["dependency_archives_unchanged"] = True
    evidence["transitive_and_macro_templates_rejected"] = True
    evidence["smoke_passed"] = True
    (BUILD / "boundary-evidence.json").write_text(json.dumps(evidence, indent=2) + "\n")
    print(json.dumps(evidence, indent=2))


if __name__ == "__main__":
    main()

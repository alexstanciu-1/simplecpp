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
        # Exercise the runtime package's actual restricted targets after relocation.
        client = relocated / "public-client"
        client.mkdir()
        (client / "main.cpp").write_text(
            '#include "modules/tasks/worker_pool.hpp"\n'
            '#if __has_include("scpp/runtime.hpp") || __has_include("scpp/int_t.hpp")\n'
            '#error "Public package target leaked implementation headers"\n#endif\n'
            'int main() {\n#if SCPP_HAS_TASKS\n'
            'scpp::tasks::worker_pool::configure(0);\n'
            'if (scpp::tasks::worker_pool::size() != 0) return 1;\n'
            'scpp::tasks::worker_pool::shutdown(); return 0;\n#else\n'
            'try { scpp::tasks::worker_pool::configure(1); } catch (...) { return 0; }\n'
            'return 2;\n#endif\n}\n')
        (client / "CMakeLists.txt").write_text(
            'cmake_minimum_required(VERSION 3.20)\nproject(public_client LANGUAGES CXX)\n'
            'include("${RUNTIME_PACKAGE}/package.cmake")\n'
            'add_executable(enabled main.cpp)\n'
            'target_link_libraries(enabled PRIVATE compiler_tasks_api)\n'
            'add_executable(disabled main.cpp)\n'
            'target_link_libraries(disabled PRIVATE compiler_runtime_api)\n')
        subprocess.run(["cmake", "-S", client, "-B", client / "build", "-G", "Ninja",
                        "-DCMAKE_CXX_COMPILER=clang++", f"-DRUNTIME_PACKAGE={target / runtime_ref}"], check=True)
        subprocess.run(["cmake", "--build", client / "build", "-j8"], check=True)
        for mode in ("enabled", "disabled"):
            subprocess.run([client / "build" / mode], check=True)
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

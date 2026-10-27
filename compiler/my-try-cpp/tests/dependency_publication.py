"""Exercise incremental publication and failure preservation in an isolated fixture."""
import importlib.util
import json
from pathlib import Path
import shutil
import subprocess
import tempfile

spec = importlib.util.spec_from_file_location(
    "driver", Path(__file__).resolve().parents[1] / "tools/build.py")
driver = importlib.util.module_from_spec(spec)
spec.loader.exec_module(driver)


def main():
    with tempfile.TemporaryDirectory(prefix="dependency-publication-") as temporary:
        driver.PROJECT = Path(temporary)
        driver.BUILD = driver.PROJECT / "build"
        driver.PACKAGES = driver.PROJECT / "dependencies"
        runtime = driver.PACKAGES / "runtime"
        runtime.mkdir(parents=True)
        (runtime / "manifest.json").write_text(json.dumps({"identity": "fixture-runtime"}))
        driver.select("runtime", runtime)
        (runtime / "public/scpp").mkdir(parents=True)
        (runtime / "public/scpp/integers.hpp").write_text("// fixture public runtime header\n")
        source = driver.PROJECT / "middleware"
        (source / "include").mkdir(parents=True)
        (source / "src").mkdir()
        for header in ("scalar_access.hpp", "string_state.hpp"):
            (source / "src" / header).write_text("// fixture private bridge\n")
        (source / "types.json").write_text(json.dumps({"mappings": [
            {"public_runtime_header": "scpp/integers.hpp"}]}))
        (source / "include/api.hpp").write_text("int first();\n")
        first = source / "first.cpp"
        first.write_text("int first() { return 1; }\n")
        (source / "second.cpp").write_text("int second() { return 2; }\n")
        (source / "CMakeLists.txt").write_text(
            "cmake_minimum_required(VERSION 3.20)\nproject(fixture LANGUAGES CXX)\n"
            "add_library(compiler_middleware STATIC first.cpp second.cpp)\n"
            "file(MAKE_DIRECTORY ${CMAKE_CURRENT_BINARY_DIR}/generated)\n"
            "foreach(family scalars string containers json)\n"
            "file(WRITE ${CMAKE_CURRENT_BINARY_DIR}/generated/runtime_${family}.hpp \"// fixture\")\n"
            "endforeach()\n")
        clang = shutil.which("clang++")
        driver.publish("middleware", clang, 8)
        package = driver.selected("middleware")
        assert not (package / "include/scpp").exists(), "Runtime public headers must stay runtime-owned"
        assert "compiler_runtime_api" in (package / "package.cmake").read_text()
        archive = package / "lib/libcompiler_middleware.a"
        second = driver.BUILD / "middleware/CMakeFiles/compiler_middleware.dir/second.cpp.o"
        timestamp = second.stat().st_mtime_ns
        published = archive.stat().st_mtime_ns
        (source / "__pycache__").mkdir()
        (source / "__pycache__/fixture.pyc").write_bytes(b"transient interpreter cache")
        driver.publish("middleware", clang, 8)
        assert archive.stat().st_mtime_ns == published
        first.write_text("int first() { return 3; }\n")
        driver.publish("middleware", clang, 8)
        assert second.stat().st_mtime_ns == timestamp, "Unchanged translation unit rebuilt"
        driver.check_runtime(package)
        before = {str(p.relative_to(package)): p.read_bytes()
                  for p in package.rglob("*") if p.is_file()}
        first.write_text("invalid C++\n")
        try:
            driver.publish("middleware", clang, 8)
        except subprocess.CalledProcessError:
            pass
        else:
            raise AssertionError("Invalid dependency unexpectedly built")
        assert before == {str(p.relative_to(package)): p.read_bytes()
                          for p in package.rglob("*") if p.is_file()}
        assert not list(driver.PACKAGES.glob(".publish-*"))
        (runtime / "manifest.json").write_text(json.dumps({"identity": "changed-runtime"}))
        try:
            driver.check_runtime(package)
        except SystemExit:
            pass
        else:
            raise AssertionError("Mismatched runtime accepted")
        backup = package.with_name(".middleware-previous")
        package.rename(backup)
        driver.recover_publications()
        assert package.is_dir() and not backup.exists()
    print("PASS: stable paths, incremental reuse, failed-build preservation, runtime mismatch and recovery")


if __name__ == "__main__":
    main()

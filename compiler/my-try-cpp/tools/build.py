"""Explicit dependency publication and the independent compiler build (Linux)."""
import argparse
import fcntl
import hashlib
import json
import re
from pathlib import Path
import shutil
import subprocess
import tempfile
import time

PROJECT = Path(__file__).resolve().parents[1]
REPO = PROJECT.parents[1]
BUILD = PROJECT / "build"
PACKAGES = PROJECT / "dependencies"
COMPILE_FLAGS = "-O2 -DNDEBUG"


def run(args):
    subprocess.run([str(arg) for arg in args], check=True)


def identity(root, config):
    digest = hashlib.sha256(json.dumps(config, sort_keys=True).encode())
    for path in sorted(root.rglob("*")):
        if path.is_file():
            digest.update(str(path.relative_to(root)).encode() + b"\0")
            digest.update(path.read_bytes())
    return digest.hexdigest()


def select(kind, package):
    temporary = PACKAGES / (kind + ".tmp")
    temporary.write_text(json.dumps({"package": package_reference(package)}) + "\n")
    temporary.replace(PACKAGES / (kind + ".json"))


def selected(kind):
    receipt = PACKAGES / (kind + ".json")
    if not receipt.exists():
        raise SystemExit(f"Missing {kind}: run python3 tools/build.py {kind}")
    # Absolute receipts are accepted only for migration of the initial bootstrap.
    package = PACKAGES / json.loads(receipt.read_text())["package"]
    package_reference(package)
    return package.resolve()


def package_reference(package):
    """Package references are relative to dependencies/, never the working directory."""
    return package.resolve().relative_to(PACKAGES.resolve()).as_posix()


def runtime_include(reference):
    return f'include("${{CMAKE_CURRENT_LIST_DIR}}/../{reference}/package.cmake")\n'


def manifest(package):
    return json.loads((package / "manifest.json").read_text())


def install(stage, package):
    """Replace a complete publication under the driver lock, retaining rollback."""
    backup = package.with_name("." + package.name + "-previous")
    if backup.exists():
        raise SystemExit(f"Unrecovered publication: {backup}")
    if package.exists():
        package.rename(backup)
    try:
        stage.rename(package)
    except BaseException:
        if backup.exists():
            backup.rename(package)
        raise
    if backup.exists():
        shutil.rmtree(backup)


def recover_publications():
    for kind in ("runtime", "middleware"):
        package = PACKAGES / kind
        backup = PACKAGES / ("." + kind + "-previous")
        if backup.exists():
            if package.exists():
                shutil.rmtree(backup)
            else:
                backup.rename(package)


def stable_paths():
    """Migrate selected binaries without recompilation; discard obsolete versions."""
    runtime = selected("runtime")
    middleware = selected("middleware")
    runtime_id = manifest(runtime)["identity"]
    data = manifest(middleware)
    pinned = PACKAGES / data["configuration"]["runtime"]
    if manifest(pinned)["identity"] != runtime_id:
        raise SystemExit("Selected runtime differs from middleware runtime; align selections first")
    for kind, source in (("runtime", runtime), ("middleware", middleware)):
        destination = PACKAGES / kind
        if source != destination:
            stage = Path(tempfile.mkdtemp(prefix=".publish-", dir=PACKAGES))
            try:
                shutil.copytree(source, stage, dirs_exist_ok=True)
                if kind == "middleware":
                    data["configuration"]["runtime"] = "runtime"
                    data["configuration"]["runtime_identity"] = runtime_id
                    (stage / "manifest.json").write_text(json.dumps(data, indent=2) + "\n")
                    cmake = stage / "package.cmake"
                    lines = cmake.read_text().splitlines(keepends=True)
                    cmake.write_text(runtime_include("runtime") + "".join(lines[1:]))
                install(stage, destination)
            finally:
                if stage.exists():
                    shutil.rmtree(stage)
        select(kind, destination)
        # CMake build trees cannot move: their cache and Ninja graph use absolute paths.
        build = BUILD / kind
        if build.exists():
            for child in build.iterdir():
                if child.is_dir() and re.fullmatch(r"[0-9a-f]{64}", child.name):
                    shutil.rmtree(child)
    print("Stable dependency paths installed; obsolete versions removed; no recompilation")


def check_runtime(package):
    config = manifest(package)["configuration"]
    runtime = PACKAGES / config["runtime"]
    if config.get("runtime_identity") != manifest(runtime)["identity"]:
        raise SystemExit("Runtime publication changed; explicitly rebuild middleware before building the compiler")


def configure(source, build, clang, extra=()):
    run(["cmake", "-S", source, "-B", build, "-G", "Ninja",
         f"-DCMAKE_CXX_COMPILER={clang}", "-DCMAKE_BUILD_TYPE=Release",
         "-DCMAKE_CXX_FLAGS=", f"-DCMAKE_CXX_FLAGS_RELEASE={COMPILE_FLAGS}",
         "-DCMAKE_EXPORT_COMPILE_COMMANDS=ON", *extra])


def publish(kind, clang, jobs):
    source = REPO / "runtime" if kind == "runtime" else PROJECT / "middleware"
    config = {"compiler": clang, "version": subprocess.check_output(
        [clang, "--version"], text=True), "profile": COMPILE_FLAGS + "/C++23/static",
        "recipe": hashlib.sha256(Path(__file__).read_bytes()).hexdigest()}
    runtime = None if kind == "runtime" else selected("runtime")
    if runtime:
        config["runtime"] = package_reference(runtime)
        config["runtime_identity"] = manifest(runtime)["identity"]
    key = identity(source, config)
    package = PACKAGES / kind
    if (package / "manifest.json").exists() and manifest(package)["identity"] == key:
        select(kind, package)
        print(f"Reusing {kind} package {key[:12]}")
        return
    build = BUILD / kind
    if kind == "runtime":
        extra = ["-DBUILD_SHARED_LIBS=OFF", "-DSCPP_LANGUAGE_TARGET_PHP=OFF"]
        extra += [f"-DSCPP_WITH_{name}=OFF" for name in
                  ("MYSQLI", "REGEX", "PROCESS", "CURL", "UI")]
        targets = ["scpp_runtime_core", "scpp_json", "scpp_filesystem", "scpp_datetime", "scpp_tasks"]
    else:
        extra = [f"-DRUNTIME_PACKAGE={runtime}"]
        targets = ["compiler_middleware"]
    configure(source, build, clang, extra)
    run(["ninja", "-C", build, f"-j{jobs}", *targets])
    package.parent.mkdir(parents=True, exist_ok=True)
    stage = Path(tempfile.mkdtemp(prefix=".publish-", dir=package.parent))
    try:
        shutil.copytree(source / "include", stage / "include")
        if kind == "middleware":
            # Support alone consumes this private bridge, paired with this publication.
            (stage / "detail").mkdir()
            for header in ("scalar_access.hpp", "string_state.hpp"):
                shutil.copy2(source / "src" / header, stage / "detail" / header)
            shutil.copy2(build / "generated/runtime_bindings.hpp", stage / "detail/runtime_bindings.hpp")
        (stage / "lib").mkdir()
        for target in targets:
            shutil.copy2(build / f"lib{target}.a", stage / "lib")
        if identity(source, config) != key:
            raise SystemExit("Sources changed during dependency build; retry publication")
        if kind == "runtime":
            cmake = '''add_library(compiler_runtime_core STATIC IMPORTED)
set_target_properties(compiler_runtime_core PROPERTIES
  IMPORTED_LOCATION "${CMAKE_CURRENT_LIST_DIR}/lib/libscpp_runtime_core.a")
add_library(compiler_runtime_json STATIC IMPORTED)
set_target_properties(compiler_runtime_json PROPERTIES
  IMPORTED_LOCATION "${CMAKE_CURRENT_LIST_DIR}/lib/libscpp_json.a")
add_library(compiler_tasks STATIC IMPORTED)
set_target_properties(compiler_tasks PROPERTIES
  IMPORTED_LOCATION "${CMAKE_CURRENT_LIST_DIR}/lib/libscpp_tasks.a")
add_library(compiler_runtime INTERFACE IMPORTED)
add_library(compiler_filesystem STATIC IMPORTED)
set_target_properties(compiler_filesystem PROPERTIES
  IMPORTED_LOCATION "${CMAKE_CURRENT_LIST_DIR}/lib/libscpp_filesystem.a")
add_library(compiler_datetime STATIC IMPORTED)
set_target_properties(compiler_datetime PROPERTIES
  IMPORTED_LOCATION "${CMAKE_CURRENT_LIST_DIR}/lib/libscpp_datetime.a")
set_target_properties(compiler_runtime PROPERTIES
  INTERFACE_INCLUDE_DIRECTORIES "${CMAKE_CURRENT_LIST_DIR}/include"
  INTERFACE_COMPILE_DEFINITIONS "SCPP_HAS_REGEX=0;SCPP_HAS_CURL=0;SCPP_HAS_UI=0;SCPP_HAS_MYSQLI=0;SCPP_HAS_TASKS=1"
  INTERFACE_LINK_LIBRARIES "compiler_tasks;compiler_filesystem;compiler_datetime;compiler_runtime_core;compiler_runtime_json;compiler_runtime_core")
'''
        else:
            cmake = runtime_include(package_reference(runtime)) + '''add_library(compiler_middleware STATIC IMPORTED)
set_target_properties(compiler_middleware PROPERTIES
  IMPORTED_LOCATION "${CMAKE_CURRENT_LIST_DIR}/lib/libcompiler_middleware.a"
  INTERFACE_INCLUDE_DIRECTORIES "${CMAKE_CURRENT_LIST_DIR}/include"
  INTERFACE_LINK_LIBRARIES "$<LINK_ONLY:compiler_runtime>")
'''
        (stage / "package.cmake").write_text(cmake)
        (stage / "manifest.json").write_text(json.dumps(
            {"identity": key, "configuration": config}, indent=2) + "\n")
        install(stage, package)
        select(kind, package)
    finally:
        if stage.exists():
            shutil.rmtree(stage)
    print(f"Published {kind} package {key[:12]}")


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("action", choices=("runtime", "middleware", "configure", "build", "test", "stable-paths"))
    parser.add_argument("--jobs", type=int, default=8, help="parallel Ninja jobs (default: 8)")
    args = parser.parse_args()
    if args.jobs < 1:
        parser.error("--jobs must be positive")
    clang = shutil.which("clang++")
    for tool in ("clang++", "cmake", "ninja", "mold"):
        if not shutil.which(tool):
            raise SystemExit(f"Required tool not found: {tool}")
    BUILD.mkdir(exist_ok=True)
    PACKAGES.mkdir(exist_ok=True)
    started = time.monotonic()
    # Serialize publication/selection; a failed build never replaces a package.
    with (BUILD / "driver.lock").open("w") as lock:
        fcntl.flock(lock, fcntl.LOCK_EX)
        recover_publications()
        if args.action == "stable-paths":
            stable_paths()
        elif args.action in ("runtime", "middleware"):
            publish(args.action, clang, args.jobs)
        else:
            package = selected("middleware")
            check_runtime(package)
            build = BUILD / "compiler"
            selection = build / "selected-middleware.txt"
            if args.action == "configure" or not selection.exists() or selection.read_text() != str(package):
                configure(PROJECT, build, clang, [f"-DMIDDLEWARE_PACKAGE={package}"])
                selection.write_text(str(package))
            if args.action != "configure":
                run(["ninja", "-C", build, f"-j{args.jobs}"])
            if args.action == "test":
                run(["ctest", "--test-dir", build, "--output-on-failure"])
    print(f"{args.action}: {time.monotonic() - started:.3f}s")


if __name__ == "__main__":
    main()

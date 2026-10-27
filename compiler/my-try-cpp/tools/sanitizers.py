"""Build isolated sanitizer dependencies and run the focused compiler tests."""
import argparse
import fcntl
import hashlib
import os
from pathlib import Path
import shutil
import subprocess
import time

import build as driver


def fingerprints(root):
    return {str(p.relative_to(root)): hashlib.sha256(p.read_bytes()).hexdigest()
            for p in root.rglob("*") if p.is_file()}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--jobs", type=int, default=8)
    parser.add_argument("--mode", choices=("address", "thread"), default="address")
    args = parser.parse_args()
    if args.jobs < 1:
        parser.error("--jobs must be positive")
    normal_packages = driver.PACKAGES
    before = fingerprints(normal_packages)
    thread_mode = args.mode == "thread"
    label = "ThreadSanitizer" if thread_mode else "ASan/UBSan"
    driver.BUILD = driver.PROJECT / ("build/thread-sanitizer" if thread_mode else "build/sanitizers")
    driver.PACKAGES = driver.BUILD / "dependencies"
    driver.COMPILE_FLAGS = (
        f"-O1 -g -DNDEBUG -fsanitize={'thread' if thread_mode else 'address,undefined'} "
        "-fno-omit-frame-pointer -fno-sanitize-recover=all")
    driver.BUILD.mkdir(parents=True, exist_ok=True)
    driver.PACKAGES.mkdir(exist_ok=True)
    clang = shutil.which("clang++")
    started = time.monotonic()
    with (driver.BUILD / "driver.lock").open("w") as lock:
        fcntl.flock(lock, fcntl.LOCK_EX)
        driver.recover_publications()
        driver.publish("runtime", clang, args.jobs)
        driver.publish("middleware", clang, args.jobs)
        build = driver.BUILD / "compiler"
        driver.configure(driver.PROJECT, build, clang,
                         [f"-DMIDDLEWARE_PACKAGE={driver.selected('middleware')}"])
        targets = (["my-try-cpp", "runtime-smoke-test", "concrete-types-test",
                    "startup-lifecycle-test", "source-discovery-test", "source-reading-test",
                    "tokenizer-test", "token-lifecycle-test", "tokenization-profile"]
                   if thread_mode else [])
        # The scalar allocation test replaces new/delete, conflicting with TSan interceptors.
        driver.run(["ninja", "-C", build, f"-j{args.jobs}", *targets])
        environment = dict(os.environ)
        if thread_mode:
            environment["TSAN_OPTIONS"] = "halt_on_error=1:exitcode=66"
        else:
            environment["ASAN_OPTIONS"] = "detect_leaks=1:halt_on_error=1"
            environment["UBSAN_OPTIONS"] = "print_stacktrace=1:halt_on_error=1"
        test_filter = ["-E", "^scalar_runtime_contract$"] if thread_mode else []
        subprocess.run(["ctest", "--test-dir", build, "--output-on-failure", *test_filter],
                       env=environment, check=True)
        subprocess.run([build / "my-try-cpp", "--catalogs",
                        driver.REPO / "runtime/metadata/modules", "--timings"],
                       env=environment, check=True)
    assert fingerprints(normal_packages) == before, "Normal dependency publications changed"
    print(f"{label} checks passed; normal dependencies unchanged ({time.monotonic()-started:.3f}s)")


if __name__ == "__main__":
    main()

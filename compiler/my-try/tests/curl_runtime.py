"""Curl runtime and existing HTTP fixture checks; run on dev002, not locally."""
import argparse
from contextlib import contextmanager
import json
from pathlib import Path
import shutil
import socket
import subprocess
import sys
import time
import urllib.request

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / "tools"))
from test_runner import CommandRunner, Task, run_tasks


@contextmanager
def fixture_server(folder):
    """Serve original curl fixtures on loopback; retain log and always reap PHP."""
    fixtures = ROOT / "tests/php/curl/strict_level_01"
    document = folder / "http"
    shutil.copytree(fixtures / "http_server/public", document)
    shutil.copy2(fixtures / "fixtures/http_root/payload.txt", document)
    with socket.socket() as reservation:
        reservation.bind(("127.0.0.1", 0))
        port = reservation.getsockname()[1]
    base = f"http://127.0.0.1:{port}"
    command = ["php", "-S", f"127.0.0.1:{port}", "-t", str(document),
               str(fixtures / "http_server/router.php")]
    (folder / "server-command.json").write_text(json.dumps(command) + "\n")
    with (folder / "server.log").open("wb") as log:
        server = subprocess.Popen(command, stdout=log, stderr=log)
        try:
            deadline = time.monotonic() + 10
            while True:
                if server.poll() is not None:
                    raise RuntimeError("Fixture server exited; see server.log")
                try:
                    with urllib.request.urlopen(base + "/payload.txt", timeout=1) as reply:
                        if reply.read() != b"curl-http\n":
                            raise RuntimeError("Fixture server returned unexpected payload")
                    break
                except OSError:
                    if time.monotonic() >= deadline:
                        raise RuntimeError("Fixture server readiness timeout")
                    time.sleep(0.05)
            yield base
        finally:
            server.terminate()
            try:
                server.wait(timeout=5)
            except subprocess.TimeoutExpired:
                server.kill()
                server.wait()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--results", required=True, type=Path)
    args = parser.parse_args()
    out = args.results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    with CommandRunner(out / "logs", ROOT, jobs=12) as runner:
        def check(name, source, http=False):
            folder = out / name
            folder.mkdir()
            program = folder / "probe"
            runner.run(name + "-build", [
                "clang++", "-std=c++23", "-DSCPP_HAS_CURL=1",
                "-DSCPP_LANGUAGE_TARGET_PHP=1", "-fsanitize=address,undefined",
                "-fno-sanitize-recover=undefined", "-I", ROOT / "runtime/include",
                source, ROOT / "runtime/include/modules/curl/curl.cpp",
                ROOT / "runtime/include/core/runtime.cpp", "-lcurl", "-pthread",
                "-o", program], timeout=300)
            command = ["env", "ASAN_OPTIONS=detect_leaks=0", program]
            if http:
                with fixture_server(folder) as base:
                    runner.run(name + "-execute", [*command, base], timeout=60)
            else:
                runner.run(name + "-execute", command, timeout=60)
        run_tasks([
            Task("existing", lambda: check("existing", ROOT / "tests/runtime/native/test_curl.cpp")),
            Task("http", lambda: check("http", APP / "tests/curl_runtime.cpp", True)),
        ])
    (out / "summary.json").write_text(json.dumps({
        "passed": True, "scope": "runtime only; no compiler compatibility claim",
        "cases": ["existing file/error/reset", "local HTTP GET/binary POST/JSON/states/lifetime"],
    }, indent=2) + "\n")


if __name__ == "__main__":
    main()

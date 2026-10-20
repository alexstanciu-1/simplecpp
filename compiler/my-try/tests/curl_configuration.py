"""Verify opt-in curl provider discovery without changing installed packages."""
import argparse
from pathlib import Path
import json
import sys

ROOT = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(ROOT / "compiler/my-try/tools"))
from test_runner import CommandRunner, Task, run_tasks


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--results", type=Path, required=True)
    out = parser.parse_args().results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    with CommandRunner(out / "logs", ROOT, jobs=12) as runner:
        def check(name, enabled, unavailable=False):
            command = ["cmake", "-S", ROOT / "runtime", "-B", out / name,
                       "-DSCPP_WITH_MYSQLI=OFF", "-DSCPP_WITH_REGEX=OFF",
                       "-DSCPP_WITH_CURL=" + ("ON" if enabled else "OFF")]
            if unavailable:
                command += ["-DPKG_CONFIG_EXECUTABLE=/nonexistent/curl-probe-pkg-config",
                            "-DCMAKE_DISABLE_FIND_PACKAGE_CURL=TRUE"]
            runner.run(name, command, expected=1 if enabled and unavailable else 0, timeout=120)
            if enabled and unavailable:
                assert "SCPP_WITH_CURL=ON but no supported libcurl development package" in (
                    out / "logs" / (name + ".stderr")).read_text()
        run_tasks([
            Task("enabled", lambda: check("enabled", True)),
            Task("missing", lambda: check("missing", True, True)),
            Task("disabled", lambda: check("disabled", False, True)),
        ])
    (out / "summary.json").write_text(json.dumps({"passed": True,
        "checks": ["enabled", "missing", "disabled"]}, indent=2) + "\n")


if __name__ == "__main__":
    main()

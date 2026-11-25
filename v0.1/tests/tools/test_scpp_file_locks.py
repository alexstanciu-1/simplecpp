#!/usr/bin/env python3
"""Strict file-lock surface: project build/run and output-type rejection."""
import json
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile


def main():
    if sys.platform != "linux":
        print("SKIP: file locks require Linux")
        return
    repo = Path(__file__).resolve().parents[2]
    cli = ["php", str(repo / "bin/scpp.php")]
    with tempfile.TemporaryDirectory(prefix="scpp-lock-surface-") as directory:
        project = Path(directory)
        config = {
            "entrypoint": "main.phs",
            "build": {"mode": "debug"},
            "runtime": {"languages": {"php": {"profile": "strict"}}, "modules": ["filesystem"]},
        }
        source = project / "main.phs"
        shutil.copyfile(repo / "tests/tools/fixtures/file_locks/main.phs", source)
        (project / "prism.json").write_text(json.dumps(config))

        def run(arguments):
            return subprocess.run(arguments, cwd=project, text=True, capture_output=True, timeout=180)

        build = run(cli + ["build", "--build-runtime"])
        assert build.returncode == 0, build.stdout + build.stderr
        for _ in range(2):
            result = run(cli + ["run"])
            assert result.returncode == 0 and "lock-ok\n" in result.stdout, result.stdout + result.stderr
            assert "lock-failed" not in result.stdout
        assert (project / "strict_file_lock_test.lock").exists(), "release unlinked the lock file"

        source.write_text('$wrong string = "";\nfs_lock_try($wrong, "bad.lock");\n')
        wrong_type = run(cli + ["build"])
        assert wrong_type.returncode != 0, "string accepted as lock output"
        assert not (project / "bad.lock").exists()
    print("PASS: strict file locks (build/run, output type)")


if __name__ == "__main__":
    main()

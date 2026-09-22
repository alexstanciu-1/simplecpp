"""Build and run one compiler fixture in an isolated strict-PHP++ project."""

import argparse
import json
from pathlib import Path
import shutil
import subprocess
import tempfile


root = Path(__file__).resolve().parents[1]
configuration = json.loads((root / "tools/toolchain.json").read_text())
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument("--scpp", help="Override the custom toolchain selected in tools/toolchain.json")
parser.add_argument("--no-stan", action="store_true", help="Native check only: STAN does not recognize throw-only skeleton returns")
parser.add_argument("--fixture", choices=("frontend_storage", "project_manifest", "manifest_recovery", "manifest_export", "source_discovery", "toolchain_json"), default="frontend_storage")
args = parser.parse_args()
compiler = args.scpp or str(root / configuration["scpp"])
if args.scpp is None and not Path(compiler).is_file():
    parser.error("Configured custom toolchain is missing; update tools/toolchain.json or pass --scpp")
if Path(compiler).exists():
    compiler = str(Path(compiler).resolve())
toolchain = ["php", compiler] if compiler.endswith(".php") else [compiler]
print("Using Simple C++: " + compiler, flush=True)
command = toolchain + ["run"]
if args.no_stan:
    command += ["--no-stan"]
with tempfile.TemporaryDirectory(prefix="scpp_compiler_check_") as temporary:
    project = Path(temporary) / "compiler"
    project.mkdir()
    shutil.copytree(root / "src", project / "src", ignore=shutil.ignore_patterns(".prism"))
    # Each fixture supplies its own entry point and build project.
    (project / "src/main.phs").unlink()
    (project / "src/prism.json").unlink()
    shutil.copy2(root / "tests" / (args.fixture + ".phs"), project / "main.phs")
    # Inputs are outside the host toolchain's source tree; read as data, never
    # compiled by the bootstrap compiler to substitute for our own pipeline.
    shutil.copytree(root / "examples/three_files", Path(temporary) / "fixtures/three_files")
    (project / "prism.json").write_text(json.dumps({
        "runtime": {"languages": {"php": {"profile": "strict"}}},
        "build": {"cxx": "clang++"},
        "entrypoint": "main.phs",
    }, indent=2) + "\n")
    result = subprocess.run(command, cwd=project)
    if result.returncode != 0:
        # Collect source-level diagnostics before the temporary project is removed.
        subprocess.run(toolchain + ["error"], cwd=project, check=False)
        subprocess.run(toolchain + ["full-error"], cwd=project, check=False)
        raise SystemExit(result.returncode)
    headers = "\n".join(
        path.read_text() for path in (project / ".prism/generated").rglob("*.hpp")
    )
    for row in ("token", "syntax_node"):
        if f"vector_t<{row}>" not in headers or f"shared_p<{row}>" in headers:
            raise AssertionError(f"{row} must be stored inline in its vector")
    print("Generated C++ confirms inline token and syntax-node vector elements.")

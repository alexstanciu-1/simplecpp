"""Build src/main.phs with the repository's configured custom Simple C++ instance."""

import json
from pathlib import Path
import subprocess
import sys


root = Path(__file__).resolve().parents[1]
configuration = json.loads((root / "tools/toolchain.json").read_text())
compiler = (root / configuration["scpp"]).resolve()
if not compiler.is_file():
    raise SystemExit("Configured custom toolchain is missing; update tools/toolchain.json")
command = ["php", str(compiler)] if compiler.suffix == ".php" else [str(compiler)]
print("Using Simple C++: " + str(compiler), flush=True)
raise SystemExit(subprocess.run(command + ["build", *sys.argv[1:]], cwd=root / "src").returncode)

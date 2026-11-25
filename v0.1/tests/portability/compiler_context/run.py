"""Forward compiler readiness validation to the active rewrite-stage proof owner."""
from pathlib import Path
import subprocess
import sys

if __name__ == '__main__':
    root = Path(__file__).resolve().parents[3]
    sys.exit(subprocess.call([sys.executable, str(root / 'compiler/tests/run.py'), *sys.argv[1:]], cwd=root))

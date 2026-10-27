"""Check generation and the complete public integer include closure."""
import argparse
from pathlib import Path
import re
import subprocess
import sys

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--compiler', default='clang++')
args = parser.parse_args()
root = Path(__file__).resolve().parents[3]
subprocess.run([sys.executable, str(root / 'runtime/tools/concrete_integers.py'), '--check'], check=True)
source = '#include "scpp/integers.hpp"\nstatic_assert(sizeof(scpp::int64) == 8);\n'
command = [args.compiler, '-std=c++23', '-I'+str(root / 'runtime/include'), '-x', 'c++', '-']
result = subprocess.run([*command, '-E', '-P'], input=source, text=True, capture_output=True, check=True)
assert not re.search(r'\b(template|typename)\b', result.stdout)
subprocess.run([*command, '-fsyntax-only'], input=source, text=True, check=True)
print(f'Concrete integer header: {len(result.stdout.encode())} preprocessed bytes, no templates')

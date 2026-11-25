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
public = root / 'runtime/public'
command = [args.compiler, '-std=c++23', '-I'+str(public), '-x', 'c++', '-']
for header in sorted(public.rglob('*.hpp')):
    source = f'#include "{header.relative_to(public)}"\n'
    for enabled in (0, 1):
        flags = [*command, f'-DSCPP_HAS_TASKS={enabled}']
        result = subprocess.run([*flags, '-E', '-P'], input=source, text=True, capture_output=True, check=True)
        assert not re.search(r'\b(template|typename)\b', result.stdout), header
        dependencies = subprocess.run([*flags, '-M'], input=source, text=True, capture_output=True, check=True)
        assert '/runtime/include/' not in dependencies.stdout, dependencies.stdout
        subprocess.run([*flags, '-fsyntax-only'], input=source, text=True, check=True)
for forbidden in ('scpp/int_t.hpp', 'scpp/runtime.hpp', 'scpp/runtime_error.hpp', 'modules/tasks/tasks.hpp'):
    source = f'#include "{forbidden}"\n'
    result = subprocess.run([*command, '-fsyntax-only'], input=source, text=True, capture_output=True)
    assert result.returncode != 0 and 'file not found' in result.stderr, forbidden
print('All public headers standalone and template-free in both modes; private headers unavailable')

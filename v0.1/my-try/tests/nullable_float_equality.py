"""Nullable canonical-float equality: 12 state-preserving eager signatures."""
import argparse
import json
from pathlib import Path
import sys
APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--results', required=True, type=Path)
out = parser.parse_args().results.resolve()
out.mkdir(parents=True, exist_ok=False)
with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
    def proof():
        runner.run('prepare', ['php', APP / 'tests/nullable_float_equality.php', out])
        signatures = json.loads((out / 'signatures.json').read_text())
        expected = {(a, op, b, 'bool') for a, b in [('?float', '?float'), ('?float', 'float'), ('float', '?float')] for op in ['==', '!=', '===', '!==']}
        assert len(signatures) == 12 and set(map(tuple, signatures)) == expected
        cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True)
        cpp.compile('native-build', out / 'wrapper_equality.cpp', out / 'native')
        runner.run('native-execute', [out / 'native'])
    run_tasks([Task('nullable-float-equality', proof)])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'binary_signatures': 12,
    'recovery_sequences': 12, 'full_matrix_run': False, 'native_compiler_rebuilt': False}, indent=2) + '\n')

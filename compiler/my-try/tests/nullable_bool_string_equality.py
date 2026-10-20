"""Nullable bool/string equality: 24 state-preserving eager signatures."""
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
    cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True)
    def proof(payload):
        target = out / payload
        target.mkdir()
        runner.run(payload + '-prepare', ['php', APP / 'tests/nullable_bool_string_equality.php', target, payload])
        signatures = json.loads((target / 'signatures.json').read_text())
        expected = {(a, op, b, 'bool') for a, b in [('?' + payload, '?' + payload), ('?' + payload, payload), (payload, '?' + payload)] for op in ['==', '!=', '===', '!==']}
        assert len(signatures) == 12 and set(map(tuple, signatures)) == expected
        cpp.compile(payload + '-native-build', target / 'wrapper_equality.cpp', target / 'native')
        runner.run(payload + '-native-execute', [target / 'native'])
    run_tasks([Task('nullable-' + payload + '-equality', lambda payload=payload: proof(payload)) for payload in ['bool', 'string']])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'binary_signatures': 24,
    'recovery_sequences': 24, 'full_matrix_run': False, 'native_compiler_rebuilt': False}, indent=2) + '\n')

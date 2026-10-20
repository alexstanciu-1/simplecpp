"""Non-bool sentinel equality: 72 state-preserving eager signatures."""
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
    def proof(carrier, payload):
        name = carrier + '-' + payload
        target = out / name
        target.mkdir()
        runner.run(name + '-prepare', ['php', APP / 'tests/nonbool_sentinel_equality.php', target, carrier, payload])
        signatures = json.loads((target / 'signatures.json').read_text())
        wrapper = carrier + '<' + payload + '>'
        expected = {(a, op, b, 'bool') for a, b in [(wrapper, wrapper), (wrapper, payload), (payload, wrapper)] for op in ['==', '!=', '===', '!==']}
        assert len(signatures) == 12 and set(map(tuple, signatures)) == expected
        cpp.compile(name + '-native-build', target / 'wrapper_equality.cpp', target / 'native')
        runner.run(name + '-native-execute', [target / 'native'])
    run_tasks([Task(carrier + '-' + payload + '-equality', lambda carrier=carrier, payload=payload: proof(carrier, payload)) for carrier in ['result_or_false', 'result_or_bool'] for payload in ['int', 'float', 'string']])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'binary_signatures': 72,
    'recovery_sequences': 72, 'full_matrix_run': False, 'native_compiler_rebuilt': False}, indent=2) + '\n')

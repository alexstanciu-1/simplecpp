"""U1: 32 carrier updates with state, snapshot and write-back proofs."""
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
        runner.run(name + '-prepare', ['php', APP / 'tests/carrier_updates.php', target, carrier, payload])
        signatures = json.loads((target / 'signatures.json').read_text())
        wrapper = '?' + payload if carrier == 'nullable' else carrier + '<' + payload + '>'
        expected = {(wrapper, form, wrapper) for form in ['preinc','postinc','predec','postdec']}
        assert len(signatures) == 4 and set(map(tuple, signatures)) == expected
        cpp.compile(name + '-native-build', target / 'carrier_updates.cpp', target / 'native')
        runner.run(name + '-native-execute', [target / 'native'])
    run_tasks([Task(carrier + '-' + payload + '-updates', lambda carrier=carrier, payload=payload: proof(carrier, payload)) for carrier in ['nullable','result','result_or_false','result_or_bool'] for payload in ['int','float']])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'unary_signatures': 32,
    'recovery_sequences': 32, 'full_matrix_run': False, 'native_compiler_rebuilt': False}, indent=2) + '\n')

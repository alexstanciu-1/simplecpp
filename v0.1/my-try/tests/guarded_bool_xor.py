"""Guarded bool XOR: 24 eager signatures, errors, snapshots and recovery."""
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
        runner.run('prepare', ['php', APP / 'tests/guarded_bool.php', out, '--xor'])
        types = ['bool', '?bool', 'result<bool>', 'result_or_false<bool>', 'result_or_bool<bool>']
        manifest = json.loads((out / 'cross-signatures.json').read_text())
        expected = {(a, 'xor', b, 'bool') for a in types for b in types if (a, b) != ('bool', 'bool')}
        assert set(map(tuple, manifest['signatures'])) == expected and len(manifest['signatures']) == 24
        assert manifest['recovery_sequences'] == 24
        cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True)
        cpp.compile('native-build', out / 'guarded_bool.cpp', out / 'native')
        runner.run('native-execute', [out / 'native'])
    run_tasks([Task('guarded-bool-xor', proof)])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'carriers': 4, 'binary_forms': 1, 'binary_signatures': 24, 'unary_signatures': 4, 'full_matrix_run': False, 'native_compiler_rebuilt': False}, indent=2) + '\n')

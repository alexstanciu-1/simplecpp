"""Numeric XOR: 100 scalar/guarded signatures, eager truth conversion and snapshots."""
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
        runner.run('prepare', ['php', APP / 'tests/guarded_mixed.php', out, '--numeric-xor'])
        types = [fmt % payload for fmt in ['%s', '?%s', 'result<%s>', 'result_or_false<%s>', 'result_or_bool<%s>'] for payload in ['int', 'float']]
        manifest = json.loads((out / 'cross-signatures.json').read_text())
        expected = {(a, 'xor', b, 'bool') for a in types for b in types}
        assert set(map(tuple, manifest['signatures'])) == expected and len(manifest['signatures']) == 100
        assert manifest['recovery_sequences'] == 100
        cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True)
        cpp.compile('native-build', out / 'guarded_mixed.cpp', out / 'native')
        runner.run('native-execute', [out / 'native'])
    run_tasks([Task('numeric-xor', proof)])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'carriers': 4, 'binary_forms': 1, 'binary_signatures': 100, 'unary_signatures': 0, 'full_matrix_run': False, 'native_compiler_rebuilt': False}, indent=2) + '\n')

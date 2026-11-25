"""Guarded spaceship: 48 canonical-int/string signatures, errors and snapshots."""
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
counts = {'operators': 24, 'string': 24}
with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
    cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True)
    def proof(domain, count):
        target = out / domain
        target.mkdir()
        runner.run(domain + '-prepare', ['php', APP / f'tests/guarded_{domain}.php', target, '--spaceship'])
        manifest = json.loads((target / 'cross-signatures.json').read_text())
        signatures = manifest['signatures']
        payload = 'int' if domain == 'operators' else 'string'
        types = [payload, '?' + payload, f'result<{payload}>', f'result_or_false<{payload}>', f'result_or_bool<{payload}>']
        expected = {(a, '<=>', b, 'int') for a in types for b in types if (a, b) != (payload, payload)}
        assert set(map(tuple, signatures)) == expected and len(signatures) == count
        assert manifest['recovery_sequences'] == 24
        cpp.compile(domain + '-native-build', target / f'guarded_{domain}.cpp', target / 'native')
        runner.run(domain + '-native-execute', [target / 'native'])
    run_tasks([Task('spaceship-' + domain, lambda d=domain, n=count: proof(d, n)) for domain, count in counts.items()])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'ordered_family_pairs': 12,
    'binary_signatures': sum(counts.values()), 'domains': counts, 'full_matrix_run': False,
    'native_compiler_rebuilt': False}, indent=2) + '\n')

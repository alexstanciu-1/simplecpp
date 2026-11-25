"""Cross-family guarded operators: all 660 approved signatures, states and snapshots."""
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
counts = {'operators': 228, 'float': 144, 'mixed': 192, 'bool': 48, 'string': 48}
with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
    cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True)
    def proof(domain, count):
        target = out / domain
        target.mkdir()
        runner.run(domain + '-prepare', ['php', APP / f'tests/guarded_{domain}.php', target, '--cross'])
        manifest = json.loads((target / 'cross-signatures.json').read_text())
        signatures = manifest['signatures']
        assert len(signatures) == len({tuple(s) for s in signatures}) == count
        cpp.compile(domain + '-native-build', target / f'guarded_{domain}.cpp', target / 'native')
        runner.run(domain + '-native-execute', [target / 'native'])
    run_tasks([Task('cross-' + domain, lambda d=domain, n=count: proof(d, n)) for domain, count in counts.items()])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'ordered_family_pairs': 12,
    'binary_signatures': sum(counts.values()), 'domains': counts, 'full_matrix_run': False,
    'native_compiler_rebuilt': False}, indent=2) + '\n')

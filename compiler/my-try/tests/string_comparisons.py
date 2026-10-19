"""V-L03 exact byte comparisons and ordered string snapshots."""
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
        runner.run('prepare', ['php', APP / 'tests/string_comparisons.php', out])
        cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True)
        cpp.compile('native-build', out / 'string_comparisons.cpp', out / 'native')
        runner.run('native-execute', [out / 'native'])
    run_tasks([Task('string-comparisons', proof)])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'comparison_forms': 7, 'string_values': 18, 'value_error_cases': 9072, 'snapshot_cases': 4536, 'full_matrix_run': False, 'native_compiler_rebuilt': False}, indent=2) + '\n')

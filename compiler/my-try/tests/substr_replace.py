"""S09 replacement signatures, original source, effects and recovery."""
import argparse, json, sys
from pathlib import Path
APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler
p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--results', type=Path, required=True)
p.add_argument('--baseline', action='store_true')
a = p.parse_args()
out = a.results.resolve()
out.mkdir(parents=True, exist_ok=False)
with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
    run_tasks([Task('prepare', lambda: runner.run('prepare', ['php', APP / 'tests/substr_replace.php', out] + (['--baseline'] if a.baseline else [])))])
    checks = json.loads((out / ('baseline.json' if a.baseline else 'checks.json')).read_text())
    executed = []
    if not a.baseline:
        cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True)
        cpp.flags.extend(['-fsanitize=address,undefined', '-fno-sanitize-recover=undefined', '-fno-omit-frame-pointer'])
        programs = {'behavior': b'replace_checks=3312\n'}
        if checks['original']['compiled']:
            info = json.loads((ROOT / checks['original']['path']).with_suffix('.test-info.json').read_text())
            programs['original'] = info['expect']['run']['stdout'].encode()
        def execute(name, expected):
            cpp.compile(name + '-build', out / (name + '.cpp'), out / name)
            actual = runner.run(name + '-execute', ['env', 'ASAN_OPTIONS=detect_leaks=0', out / name])
            assert actual == expected, (name, actual, expected)
            assert (out / 'logs' / (name + '-execute.stderr')).read_bytes() == b''
        run_tasks([Task(n, lambda n=n, wanted=w: execute(n, wanted)) for n, w in programs.items()])
        executed = list(programs)
(out / 'summary.json').write_text(json.dumps({'passed': True, 'baseline': a.baseline, 'checks': checks,
    'executed': executed, 'full_matrix_run': False}, indent=2) + '\n')

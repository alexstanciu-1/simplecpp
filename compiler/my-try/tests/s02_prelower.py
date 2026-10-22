"""Replay the six declaration-only S02 companions through default production builds."""
import argparse
import hashlib
import json
from pathlib import Path
import re
import sys

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, TaskFailures, run_tasks

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--results', required=True, type=Path)
out = parser.parse_args().results.resolve()
out.mkdir(parents=True, exist_ok=False)
manifest = json.loads((APP / 'tests/legacy_compatibility_strict_s02.json').read_text())


def normalized(data, comparison, stream):
    if comparison.get('normalize_' + stream + '_newlines', False):
        data = data.replace(b'\r\n', b'\n').replace(b'\r', b'\n')
    if comparison.get('trim_final_' + stream + '_newline', False):
        data = data.removesuffix(b'\n')
    return data


with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
    def check(case):
        info = json.loads((ROOT / case['metadata']).read_text())
        name = info['id']
        original = (ROOT / case['source']).read_bytes()
        companion = (ROOT / case['source_variant']).read_bytes()
        migrated, count = re.subn(rb'/\*\* ([A-Za-z_][A-Za-z_0-9<>]*) \*/', rb'\1', original)
        assert migrated == companion, 'Companion contains changes beyond declaration spelling'
        folder = out / name
        source = folder / 'source'
        source.mkdir(parents=True)
        (source / 'main.phs').write_bytes(companion)
        published = folder / 'published'
        runner.run(name + '-publish', ['php', APP / 'tests/s02_publish.php', folder])
        assert json.loads((published / '.scpp-output.manifest').read_text())['application_templates'] == 'forbidden'
        expected = info['expect']['run']
        builds = {}
        for phase in ['cold', 'warm']:
            label = name + '-' + phase
            build = json.loads(runner.run(label + '-build', ['php', APP / 'tools/build_cpp.php',
                published, folder / 'cache'], timeout=600))
            work = folder / phase
            work.mkdir()
            stdout = runner.run(label + '-run', [build['executable']], cwd=work, expected=expected['exit_code'])
            stderr = (out / 'logs' / (label + '-run.stderr')).read_bytes()
            for stream, actual in [('stdout', stdout), ('stderr', stderr)]:
                assert normalized(actual, info['compare'], stream) == normalized(
                    expected[stream].encode(), info['compare'], stream), (name, stream, actual)
            assert not list(work.iterdir()), 'Fixture left filesystem resources'
            if phase == 'warm':
                assert build['compiled'] == 0 and not build['linked'], build
            builds[phase] = build
        result = dict(case=case, passed=True, declaration_replacements=count, builds=builds,
            original_sha256=hashlib.sha256(original).hexdigest(),
            companion_sha256=hashlib.sha256(companion).hexdigest(),
            metadata_sha256=hashlib.sha256((ROOT / case['metadata']).read_bytes()).hexdigest())
        (folder / 'result.json').write_text(json.dumps(result, indent=2) + '\n')
        return result

    try:
        outcomes = run_tasks([Task(Path(case['source']).stem, lambda c=case: check(c)) for case in manifest['cases']])
    except TaskFailures as failure:
        (out / 'summary.json').write_text(json.dumps(dict(passed=False, failures=[
            dict(name=row.name, error=row.error) for row in failure.outcomes]), indent=2) + '\n')
        raise
(out / 'summary.json').write_text(json.dumps(dict(passed=True, legacy_syntax='deferred',
    default_prelowering=True, publication='compiler API with explicit metadata modules; not project configuration',
    cases=[row.value for row in outcomes]), indent=2) + '\n')

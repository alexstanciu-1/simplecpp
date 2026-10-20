#!/usr/bin/env python3
"""Complete template-closure proof for generated null output using the native builder."""
import argparse
import json
from pathlib import Path
import shutil
import sys

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    out = parser.parse_args().results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    source = out / 'source'
    source.mkdir()
    # Side effects around each null, all in the same echo statement.
    (source / 'main.phs').write_text('function next_value(int &$n): int { $n++; echo "p"; return $n; } '
        '$n = 0; echo null, "[", next_value($n), null, ":", next_value($n), null, "]", $n;')
    published = out / 'published'
    recorder = out / 'timed_clang.py'
    shutil.copyfile(APP / 'tests/fixtures/timed_clang.py', recorder)
    recorder.chmod(0o700)
    with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
        def proof():
            runner.run('publish', ['php', APP / 'tests/fixtures/prelower_null_output.php', source, published])
            command = ['php', APP / 'tests/fixtures/native_cache_driver.php', published,
                       out / 'cache', ROOT / 'runtime/include', recorder, '--no-pch']
            built = json.loads(runner.run('build', command, cli_reason='Native build'))
            assert built['pch_built'] == 0, built
            assert runner.run('execute', [built['executable']]) == b'[p1:p2]2'
            warm = json.loads(runner.run('warm', command, cli_reason='Native build'))
            assert warm['compiled'] == warm['linked'] == 0, warm
            runner.run('closure', [sys.executable, APP / 'tests/prelower_template_closure.py',
                '--published', published, '--commands', recorder.with_suffix('.jsonl'),
                '--results', out / 'closure'])
            (out / 'result.json').write_text(json.dumps(dict(build=built, warm=warm,
                expected_output='[p1:p2]2'), indent=2) + '\n')
        run_tasks([Task('null-output', proof)])


if __name__ == '__main__':
    main()

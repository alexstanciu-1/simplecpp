#!/usr/bin/env python3
"""Public CLI/API parity and complete closure proof for generated null output."""
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
            api_manifest = (published / '.scpp-output.manifest').read_bytes()
            assert json.loads(api_manifest)['application_templates'] == 'forbidden'
            cli_published = out / 'cli-published'
            cli = ['php', APP / 'main.php', '--s2s', source, '--output', cli_published]
            runner.run('cli-publish', [*cli, '--prelower-templates'], cli_reason='Public CLI')
            assert (cli_published / '.scpp-output.manifest').read_bytes() == api_manifest
            command = ['php', APP / 'tests/fixtures/native_cache_driver.php', cli_published,
                       out / 'cache', ROOT / 'runtime/include', recorder, '--no-pch']
            runner.run('cli-pch-reject', command[:-1], expected=1, cli_reason='Native build')
            assert 'runtime PCH is incompatible' in (out / 'logs/cli-pch-reject.stderr').read_text()
            assert (out / 'cache/.scpp-native.status').read_text() == 'pending\n'
            built = json.loads(runner.run('build', command, cli_reason='Native build'))
            assert built['pch_built'] == 0, built
            assert runner.run('execute', [built['executable']]) == b'[p1:p2]2'
            warm = json.loads(runner.run('warm', command, cli_reason='Native build'))
            assert warm['compiled'] == warm['linked'] == 0, warm
            runner.run('closure', [sys.executable, APP / 'tests/prelower_template_closure.py',
                '--published', cli_published, '--commands', recorder.with_suffix('.jsonl'),
                '--results', out / 'closure'])
            original = (source / 'main.phs').read_text()
            (source / 'main.phs').write_text('$f function<int()> = fn(): int => 1; return $f();')
            runner.run('cli-unsupported', [*cli, '--prelower-templates'], expected=1, cli_reason='Public CLI')
            assert 'callable storage requires a concrete callback transport' in (
                out / 'logs/cli-unsupported.stderr').read_text()
            assert not (cli_published / '.scpp-output.status').read_text().startswith('scpp-output-v1:ready:')
            runner.run('cli-ordinary', cli, cli_reason='Public CLI')
            ordinary = json.loads((cli_published / '.scpp-output.manifest').read_text())
            assert ordinary['application_templates'] == 'allowed'
            (source / 'main.phs').write_text(original)
            runner.run('cli-repair', [*cli, '--prelower-templates'], cli_reason='Public CLI')
            assert (cli_published / '.scpp-output.manifest').read_bytes() == api_manifest
            (out / 'result.json').write_text(json.dumps(dict(build=built, warm=warm,
                expected_output='[p1:p2]2'), indent=2) + '\n')
        run_tasks([Task('null-output-public-options', proof),
                   Task('mode-lifecycle', lambda: runner.run('mode-lifecycle',
                       ['php', APP / 'tests/fixtures/prelower_templates.php', out / 'lifecycle-source']))])


if __name__ == '__main__':
    main()

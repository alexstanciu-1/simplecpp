#!/usr/bin/env python3
"""Run my-try suites and generated programs through the shared bounded task pool."""
import argparse
import json
from pathlib import Path
import sys
import tempfile
import time

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'tools'))
from test_runner import CommandRunner, DEFAULT_JOBS, Task, TaskFailures, positive_jobs, run_tasks


def verify_php(root, output, runner, jobs):
    """Run PHP suites independently, each with private temporary files and logs."""
    def suite(source):
        command = ['php', str(source)]
        if source.stem in ('calls', 'llvm', 's2s'):
            directory = output / source.stem
            directory.mkdir()
            command.append(str(directory))
        if source.stem == 'incremental_smoke':
            command.append('--restore')
        return runner.run('php-' + source.stem, command, timeout=120,
                          cli_reason='Tests PHP_BINARY child invocation' if source.stem == 'native' else None)

    tasks = [Task(source.name, lambda source=source: suite(source))
             for source in sorted((root / 'tests').glob('*.php'))]
    try:
        outcomes = run_tasks(tasks, jobs)
    except TaskFailures as failure:
        outcomes = failure.outcomes
        raise
    finally:
        if 'outcomes' in locals():
            summary = {'jobs': jobs, 'tests': [dict(test=row.name, passed=row.error is None,
                       error=row.error, seconds=round(row.seconds, 3)) for row in outcomes],
                       'passed': sum(row.error is None for row in outcomes)}
            (output / 'php-summary.json').write_text(json.dumps(summary, indent=2) + '\n')


def verify_programs(root, output, runner, jobs):
    clang = json.loads((root / '06_native/toolchain.json').read_text())['clang']
    tasks = []
    counts = {}
    def program(suite, fixture):
        source = Path(fixture['path'])
        executable = source.with_suffix('.program')
        name = suite + '-' + source.stem
        if suite == 'llvm':
            command = [clang, '-Wno-override-module', '-x', 'ir', source, '-o', executable]
        else:
            command = ['clang++', '-std=c++20', '-I', root.parents[1] / 'runtime/include',
                       source, '-o', executable]
        runner.run(name + '-compile', command)
        runner.run(name + '-execute', [executable], expected=fixture['exit_code'])

    for suite in ('llvm', 's2s'):
        fixtures = json.loads((output / suite / 'executions.json').read_text())
        counts[suite + '_executions'] = len(fixtures)
        tasks.extend(Task(suite + '-' + Path(fixture['path']).stem,
                          lambda suite=suite, fixture=fixture: program(suite, fixture))
                     for fixture in fixtures)
    run_tasks(tasks, jobs)
    return counts


def verify_cli(root, output, runner):
    def run(*args, **kwargs):
        return runner.run(*args, **kwargs, cli_reason='Tests the PHP CLI entrypoint contract')

    cli_source = output / 'cli-source'
    cli_source.mkdir()
    (cli_source / 'main.phs').write_text('$a = 10; return $a;')
    cli = run('cli-s2s', ['php', root / 'main.php', '--s2s', cli_source])
    if cli != (output / 's2s/value.cpp').read_bytes() or (runner.logs / 'cli-s2s.stderr').read_bytes():
        raise RuntimeError('Host S2S mode differs from direct generation')
    for index, arguments in enumerate((['--s2s'], ['--unknown'], ['--s2s', str(cli_source), 'extra'])):
        name = 'cli-usage-' + str(index)
        stdout = run(name, ['php', root / 'main.php', *arguments], expected=1)
        if stdout or not (runner.logs / (name + '.stderr')).read_bytes().startswith(b'Usage:'):
            raise RuntimeError('Host usage failure did not stay on stderr')
    stdout = run('cli-missing', ['php', root / 'main.php', '--s2s', output / 'missing-source'], expected=1)
    if stdout or not (runner.logs / 'cli-missing.stderr').read_bytes().startswith(b'S2S generation failed:'):
        raise RuntimeError('Host S2S failure did not stay on stderr')
    sample = run('sample', ['php', root / 'main.php'])
    if b'Native build: exit 0\n' not in sample or b'Executable exit code: 9\n' not in sample:
        raise RuntimeError('Sample did not compile and execute with its expected exit code 9')


def verify(root, output, jobs=DEFAULT_JOBS, php_only=False, skip_style=False, php_executor='auto'):
    started = time.monotonic()
    runner = CommandRunner(output / 'logs', root.parents[1], journals=[output / 'commands.json'],
                           jobs=jobs, php_executor=php_executor)
    summary = dict(jobs=jobs, passed=False)
    try:
        if not php_only:
            php_files = sorted(p for p in root.rglob('*.php') if 'build' not in p.relative_to(root).parts)
            tasks = [Task('lint-' + str(index), lambda source=source, index=index:
                          runner.run('lint-' + str(index), ['php', '-l', source]))
                     for index, source in enumerate(php_files)]
            tasks.extend(Task(source.stem, lambda source=source:
                              runner.run(source.stem, [sys.executable, source]))
                         for source in sorted((root / 'tests').glob('*_test.py')))
            if not skip_style:
                tasks.append(Task('style', lambda: runner.run('style', [sys.executable, root / 'tools/style_check.py'])))
            run_tasks(tasks, jobs)
            summary['php_lint_files'] = len(php_files)
        verify_php(root, output, runner, jobs)
        if not php_only:
            summary.update(verify_programs(root, output, runner, jobs))
            verify_cli(root, output, runner)
            summary['sample_exit'] = 9
            summary['call_executions'] = (runner.logs / 'php-calls.stdout').read_text().count('dependencies verified, native exit')
        summary['passed'] = True
    finally:
        runner.close()
        summary['php_executor'] = php_executor
        summary['wall_seconds'] = round(time.monotonic() - started, 3)
        summary['style_skipped'] = php_only or skip_style
        (output / 'summary.json').write_text(json.dumps(summary, indent=2) + '\n')
    print(json.dumps(summary))


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, help='New directory in which to retain evidence')
    parser.add_argument('--jobs', type=positive_jobs, default=DEFAULT_JOBS, help='Maximum concurrent tasks (default: 12)')
    parser.add_argument('--php-executor', choices=('auto', 'fpm', 'cli'), default='auto',
                        help='PHP backend: auto prefers FPM with CLI fallback (default)')
    parser.add_argument('--php-only', action='store_true', help='Run PHP suites without lint/style/native fixture sweep')
    parser.add_argument('--skip-style', action='store_true', help='Explicitly skip repository style gate; record in summary')
    arguments = parser.parse_args()
    root = Path(__file__).resolve().parents[1]
    if arguments.results:
        output = arguments.results.resolve()
        output.mkdir(parents=True, exist_ok=False)
        verify(root, output, arguments.jobs, arguments.php_only, arguments.skip_style, arguments.php_executor)
    else:
        with tempfile.TemporaryDirectory(prefix='scpp-my-try-proof-') as directory:
            verify(root, Path(directory), arguments.jobs, arguments.php_only, arguments.skip_style, arguments.php_executor)

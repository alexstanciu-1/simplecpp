"""Actual compiler query bodies through PHP, v0.1, and both v0.2 modes."""
import argparse
import json
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
APP = ROOT / 'compiler/my-try'
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--results', type=Path, required=True)
out = parser.parse_args().results.resolve()
(out / 'source').mkdir(parents=True, exist_ok=False)
expected = b'000:111:000:110:000:000:000:000:\n'
with CommandRunner(out / 'logs', ROOT) as runner:
    def portable():
        runner.run('actual-records', ['php8.5', APP / 'tests/collected_query_dispatch.php', out / 'source/main.php'])
        assert runner.run('php', ['php8.5', out / 'source/main.php']) == expected
        runner.run('convert', ['php8.5', ROOT / 'tools/php_portability/convert.php', out / 'source', out / 'legacy'])
        runner.run('init', ['php8.5', ROOT / 'bin/scpp.php', 'init', '--php-profile=strict'], out / 'legacy', cli_reason='CLI fixture setup')
        config = json.loads((out / 'legacy/prism.json').read_text())
        config['build']['cxx'] = 'clang++-18'
        (out / 'legacy/prism.json').write_text(json.dumps(config))
        runner.run('legacy-build', ['php8.5', ROOT / 'bin/scpp.php', 'build', '--build-runtime', '--no-stan'], out / 'legacy', timeout=240, cli_reason='CLI fixture build')
        assert runner.run('legacy-run', [out / 'legacy/.prism/build/main']) == expected
    run_tasks([Task('portable', portable)])

    flags = out / 'flags'
    flags.mkdir()
    run_tasks([Task('flags-projection', lambda: runner.run('flags-projection',
        ['php8.5', APP / 'tests/collected_query_dispatch.php', flags / 'main.php', 'flags-only']))])
    run_tasks([Task('flags-convert', lambda: runner.run('flags-convert',
        ['php8.5', ROOT / 'tools/php_portability/convert.php', flags, out / 'flags-phs']))])

    def native(mode):
        folder = out / mode
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_bytes((out / 'legacy/main.phs').read_bytes())
        runner.run(mode + '-blocked', ['php8.5', APP / 'tests/async_tasks_publish.php', folder, ROOT / 'runtime/metadata/modules', 'core', mode], expected=255)
        assert 'S2S ownership boundary requires matching handles' in (out / 'logs' / (mode + '-blocked.stderr')).read_text()
        (folder / 'source/main.phs').write_bytes((out / 'flags-phs/main.phs').read_bytes())
        runner.run(mode + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder, ROOT / 'runtime/metadata/modules', 'core', mode])
        manifest = json.loads((folder / 'published/.scpp-output.manifest').read_text())
        assert manifest['application_templates'] == ('allowed' if mode == 'ordinary' else 'forbidden')
        build = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache', '-O0'], timeout=240))
        assert runner.run(mode + '-run', [build['executable']]) == b'00:11:00:11:00:00:00:00:\n'
    run_tasks([Task(mode, lambda mode=mode: native(mode)) for mode in ['ordinary', 'concrete']], jobs=12)
(out / 'result.json').write_text(json.dumps({'boolean_dispatch_passed': True, 'php_and_v01_all_queries_passed': True, 'v02_owner_query_blocker': 'raw null return ownership boundary', 'native_compiler_rebuilt': False}, indent=2))

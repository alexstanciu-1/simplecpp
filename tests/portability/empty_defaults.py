"""Typed empty parameter defaults through PHP, v0.1, and both v0.2 modes."""
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
expected = b'1:1:3:2:0:0'
(out / 'source/main.php').write_text('''<?php
class Bag {
    public function __construct(public array $items /** vector<int> */ = []) {}
    public function grow(array $values /** vector<int> */ = []): int {
        $values[] = 7;
        return q_count($values);
    }
}
$bag = new Bag();
$other = new Bag();
$values /** vector<int> */ = [1, 2];
echo $bag->grow(), ':', $bag->grow(), ':', $bag->grow($values), ':', q_count($values), ':', q_count($bag->items), ':', q_count($other->items);
''')
with CommandRunner(out / 'logs', ROOT) as runner:
    def portable():
        runner.run('imports', ['php8.5', ROOT / 'tools/php_portability/sync_imports.php', out / 'source'])
        assert runner.run('php', ['php8.5', '-r', 'require $argv[1]; require $argv[2];', ROOT / 'tools/php_portability/runtime/bootstrap.php', out / 'source/main.php']) == expected
        rejected = out / 'rejected'
        rejected.mkdir()
        for index, body in enumerate([
            'class Bad { public function f(array $items = []): void {} }',
            'class Bad { public function f(array $items /** vector<int> */ = [1]): void {} }',
            'class Bad { public function __construct(public array $items /** vector<int> */ = [1]) {} }',
        ]):
            (rejected / 'main.php').write_text('<?php\n' + body)
            runner.run('reject-' + str(index), ['php8.5', ROOT / 'tools/php_portability/check.php', rejected], expected=1)
        runner.run('convert', ['php8.5', ROOT / 'tools/php_portability/convert.php', out / 'source', out / 'legacy'])
        runner.run('init', ['php8.5', ROOT / 'bin/scpp.php', 'init', '--php-profile=strict'], out / 'legacy', cli_reason='CLI fixture setup')
        config = json.loads((out / 'legacy/prism.json').read_text())
        config['build']['cxx'] = 'clang++-18'
        (out / 'legacy/prism.json').write_text(json.dumps(config))
        runner.run('legacy-build', ['php8.5', ROOT / 'bin/scpp.php', 'build', '--build-runtime', '--no-stan'], out / 'legacy', timeout=240, cli_reason='CLI fixture build')
        assert runner.run('legacy-run', [out / 'legacy/.prism/build/main']) == expected
    run_tasks([Task('portable', portable)])

    def native(mode):
        folder = out / mode
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_bytes((out / 'legacy/main.phs').read_bytes())
        runner.run(mode + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder, ROOT / 'runtime/metadata/modules', 'core', mode])
        manifest = json.loads((folder / 'published/.scpp-output.manifest').read_text())
        assert manifest['application_templates'] == ('allowed' if mode == 'ordinary' else 'forbidden')
        build = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache', '-O0'], timeout=240))
        assert runner.run(mode + '-run', [build['executable']]) == expected
    run_tasks([Task(mode, lambda mode=mode: native(mode)) for mode in ['ordinary', 'concrete']], jobs=12)
(out / 'result.json').write_text(json.dumps({'passed': True, 'native_compiler_rebuilt': False}, indent=2))

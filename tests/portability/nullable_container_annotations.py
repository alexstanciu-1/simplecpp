"""Complete nullable array annotation conversion and route boundary evidence."""
import argparse
import json
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
APP = ROOT / 'compiler/my-try'
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks

p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--results', type=Path, required=True)
a = p.parse_args()
out = a.results.resolve()
out.mkdir(parents=True, exist_ok=False)
(out / 'source').mkdir()
(out / 'source/main.php').write_text('''<?php
final class Lists {
 public ?array $extra /** ?hash<vector<int>> */ = null;
 public function __construct(public ?array $items /** ?vector<int> */ = null) {}
 public function present(): bool { return $this->items !== null; }
 public static function absent(?array $items /** ?vector<int> */ = null): ?array /** ?vector<int> */ { return $items; }
}
$empty /** vector<int> */ = [];
$absent = new Lists();
$present = new Lists($empty);
echo $absent->present() ? 'bad' : 'absent', ':', $present->present() ? 'present' : 'bad', ':', Lists::absent() === null ? 'null' : 'bad', ':', Lists::absent($empty) === null ? 'bad' : 'present';
''')
with CommandRunner(out / 'logs', ROOT) as runner:
    def annotations():
        runner.run('annotations', ['php8.5', ROOT / 'tests/portability/nullable_container_annotations.php'])
    def portable():
        assert runner.run('php', ['php8.5', out / 'source/main.php']) == b'absent:present:null:present'
        runner.run('imports', ['php8.5', ROOT / 'tools/php_portability/sync_imports.php', out / 'source'])
        runner.run('convert', ['php8.5', ROOT / 'tools/php_portability/convert.php', out / 'source', out / 'legacy'])
        runner.run('init', ['php8.5', ROOT / 'bin/scpp.php', 'init', '--php-profile=strict'], out / 'legacy', cli_reason='CLI fixture setup')
        config = json.loads((out / 'legacy/prism.json').read_text())
        config['build']['cxx'] = 'clang++-18'
        (out / 'legacy/prism.json').write_text(json.dumps(config))
        runner.run('legacy-build', ['php8.5', ROOT / 'bin/scpp.php', 'build', '--build-runtime', '--no-stan'],
                   out / 'legacy', timeout=240, cli_reason='CLI fixture build')
        assert runner.run('legacy-run', [out / 'legacy/.prism/build/main']) == b'absent:present:null:present'
    run_tasks([Task('annotations', annotations), Task('portable', portable)])
    runner.run('convert-v02', ['php8.5', ROOT / 'tools/php_portability/convert.php', out / 'source', out / 'v02', '--target=v0.2'])
    def native(mode):
        folder = out / mode
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_bytes((out / 'v02/main.phs').read_bytes())
        # NULL-01/02 own payload admission. Retain the actual diagnostic as evidence;
        # this fixture must become an execution proof when those entries are implemented.
        runner.run(mode + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                                       ROOT / 'runtime/metadata/modules', 'core', mode], expected=255)
        error = (out / 'logs' / (mode + '-publish.stderr')).read_text()
        assert 'S2S nullable value boundary requires a scalar or inline record payload' in error, error
    run_tasks([Task(mode, lambda mode=mode: native(mode)) for mode in ['ordinary', 'concrete']])
(out / 'summary.json').write_text(json.dumps({'conversion': 'passed', 'php_legacy': 'passed',
                                            'v02_execution': 'blocked_pending_NULL_01_02'}, indent=2) + '\n')

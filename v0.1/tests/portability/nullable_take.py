"""Portable take_nullable preserves array values and class identity across routes."""
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
class Node { public int $value = 7; }
final class Values { public static function run(?array $source /** ?vector<int> */): void {
 $out /** vector<int> */ = [9];
 if (take_nullable($out, $source)) {
  $out[0] = 9;
  $saved /** vector<int> */ = $source;
  echo $saved[0], ':', $out[0];
 } else { echo ':empty'; }
}
}
$value /** vector<int> */ = [3, 7];
Values::run($value);
Values::run(null);
$local_present /** ?vector<int> */ = $value;
Values::run($local_present);
$local_absent /** nullable<vector<int>> */ = null;
Values::run($local_absent);
$nested /** hash<vector<int>, string> */ = [];
$nested['row'] = $value;
$optional_nested /** ?hash<vector<int>, string> */ = $nested;
$extracted /** hash<vector<int>, string> */ = [];
if (take_nullable($extracted, $optional_nested)) {
 $row /** vector<int> */ = $extracted['row'];
 echo ':', $row[1];
}
$node = new Node();
$present /** nullable<Node> */ = $node;
$target = new Node();
echo take_nullable($target, $present) ? ':present' : ':bad';
$target->value = 8;
echo ':', $node->value;
$absent /** nullable<Node> */ = null;
echo take_nullable($target, $absent) ? ':bad' : ':empty';
echo ':', $target->value;
''')
expected = b'3:9:empty3:9:empty:7:present:8:empty:8'
with CommandRunner(out / 'logs', ROOT) as runner:
    def portable():
        assert runner.run('php', ['php8.5', '-r', 'require $argv[1]; require $argv[2];', ROOT / 'tools/php_portability/runtime/bootstrap.php', out / 'source/main.php']) == expected
        runner.run('imports', ['php8.5', ROOT / 'tools/php_portability/sync_imports.php', out / 'source'])
        runner.run('convert', ['php8.5', ROOT / 'tools/php_portability/convert.php', out / 'source', out / 'legacy'])
        runner.run('init', ['php8.5', ROOT / 'bin/scpp.php', 'init', '--php-profile=strict'], out / 'legacy', cli_reason='CLI fixture setup')
        config = json.loads((out / 'legacy/prism.json').read_text())
        config['build']['cxx'] = 'clang++-18'
        (out / 'legacy/prism.json').write_text(json.dumps(config))
        runner.run('legacy-build', ['php8.5', ROOT / 'bin/scpp.php', 'build', '--build-runtime', '--no-stan'],
                   out / 'legacy', timeout=240, cli_reason='CLI fixture build')
        assert runner.run('legacy-run', [out / 'legacy/.prism/build/main']) == expected
    run_tasks([Task('portable', portable)])
    runner.run('convert-v02', ['php8.5', ROOT / 'tools/php_portability/convert.php', out / 'source', out / 'v02', '--target=v0.2'])
    def native(mode):
        folder = out / mode
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_bytes((out / 'v02/main.phs').read_bytes())
        runner.run(mode + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                                       ROOT / 'runtime/metadata/modules', 'core', mode])
        build = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php',
                                                       folder / 'published', folder / 'cache'], timeout=300))
        assert runner.run(mode + '-execute', [build['executable']]) == expected
    run_tasks([Task(mode, lambda mode=mode: native(mode)) for mode in ['ordinary', 'concrete']])
(out / 'summary.json').write_text(json.dumps({'conversion': 'passed', 'php_legacy': 'passed',
                                            'v02_execution': 'passed_both_modes'}, indent=2) + '\n')

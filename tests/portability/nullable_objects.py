"""Portable nullable class values preserve identity across PHP and both native routes."""
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
class Base {
 public int $value = 7;
 public function get(): int { return $this->value; }
}
class Derived extends Base {}
class Values {
 public static function absent(): ?Base { return null; }
 public static function identity(?Base $value = null): ?Base { return $value; }
 public static function promote(?Derived $value): Base { return $value; }
 public static function widen(?Derived $value): ?Base { return $value; }
}
$derived = new Derived();
$maybe /** nullable<Derived> */ = $derived;
$base /** nullable<Base> */ = Values::widen($maybe);
$required = Values::promote($maybe);
$required->value = 9;
echo $base->get(), ':', Values::absent() === null ? 'absent' : 'bad', ':', Values::identity() === null ? 'default' : 'bad';
$chosen = true ? Values::widen($maybe) : $required;
echo ':', $chosen === $derived ? 'same' : 'bad';
$none /** nullable<Derived> */ = null;
$missing = true ? Values::widen($none) : $required;
echo ':', $missing === null ? 'absent' : 'bad';
$reverse = false ? $required : Values::widen($maybe);
echo ':', $reverse === $derived ? 'reverse' : 'bad';
''')
expected = b'9:absent:default:same:absent:reverse'
with CommandRunner(out / 'logs', ROOT) as runner:
    def portable():
        assert runner.run('php', ['php8.5', out / 'source/main.php']) == expected
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

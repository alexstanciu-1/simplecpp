"""Portable explicit virtual defaults and override intent across routes."""
import argparse
import json
import shutil
import sys
from pathlib import Path
ROOT = Path(__file__).resolve().parents[2]
APP = ROOT / 'compiler/my-try'
TOOLS = ROOT / 'tools/php_portability'
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--results', type=Path, required=True)
out = p.parse_args().results.resolve()
(out / 'source').mkdir(parents=True, exist_ok=False)
source = r'''<?php
class Base_Record {
 #[\Virtual]
 public function values(array $items /** vector<int> */): array /** vector<int> */ { return $items; }
 #[\Virtual]
 protected function score(): int { return 3; }
 public function read_score(): int { return $this->score(); }
}
class Default_Record extends Base_Record {}
trait Values {
 #[\Override]
 public function values(array $items /** vector<int> */): array /** vector<int> */ {
  $out /** vector<int> */ = [9];
  return $out;
 }
}
class Derived_Record extends Base_Record {
 use Values;
 #[\Override]
 public final function score(): int { return parent::score() + 4; }
}
$fallback /** Base_Record */ = new Default_Record();
$derived = new Derived_Record();
$base /** Base_Record */ = $derived;
$input /** vector<int> */ = [5];
$a = $fallback->values($input);
$b = $base->values($input);
echo $a[0], ':', $b[0], ':', $base->read_score(), ':', $fallback->read_score();
'''
(out / 'source/main.php').write_text(source)
expected = b'5:9:7:3'
with CommandRunner(out / 'logs', ROOT) as runner:
    assert runner.run('host', ['php8.5', '-r', 'require $argv[1]; require $argv[2];', TOOLS / 'runtime/bootstrap.php', out / 'source/main.php']) == expected
    for route in ['v0.1', 'v0.2']:
        runner.run(route + '-convert', ['php8.5', TOOLS / 'convert.php', out / 'source', out / route, '--target=' + route])
    def markers():
        reflection = r'require $argv[1]; $a = new ReflectionClass("Virtual"); $m = $a->getAttributes(Attribute::class)[0]->newInstance(); if ($m->flags !== Attribute::TARGET_METHOD) { throw new Exception("marker target"); }'
        runner.run('marker-reflection', ['php8.5', '-r', reflection, TOOLS / 'runtime/bootstrap.php'])
        bad = out / 'bad'
        bad.mkdir()
        cases = [
            (r'class B { #[\Virtual] private function f(): int { return 1; } }', 'Virtual requires'),
            (r'class B { #[\Virtual] public static function f(): int { return 1; } }', 'ordinary instance'),
            (r'class B { #[\Virtual] public final function f(): int { return 1; } }', 'Virtual requires'),
            (r'class B { #[\Virtual] public int $x = 0; }', 'ordinary instance'),
            (r'class B { #[\Virtual] public function __construct() {} }', 'ordinary instance'),
            (r'class B { #[\Virtual(1)] public function f(): int { return 1; } }', 'zero-argument'),
            (r'class B { #[Virtual] public function f(): int { return 1; } }', 'zero-argument'),
            (r'class B { #[\Virtual, \Override] public function f(): int { return 1; } }', 'zero-argument'),
            (r'class B { #[\Virtual] #[\Virtual] public function f(): int { return 1; } }', 'duplicate method marker'),
            (r'trait A { #[\Override] public function f(): int { return 1; } } class B { use A; public function f(): int { return 2; } }', 'collision'),
        ]
        for i, (body, diagnostic) in enumerate(cases):
            (bad / 'main.php').write_text('<?php\n' + body)
            name = 'invalid-' + str(i)
            runner.run(name, ['php8.5', TOOLS / 'convert.php', bad, out / 'bad-output', '--target=v0.2'], expected=1)
            assert diagnostic in (out / 'logs' / (name + '.stderr')).read_text()
        for route in ['v0.1', 'v0.2']:
            before = (out / route / 'main.phs').read_bytes()
            runner.run(route + '-warm', ['php8.5', TOOLS / 'convert.php', out / 'source', out / route, '--target=' + route])
            assert (out / route / 'main.phs').read_bytes() == before
    def check(mode):
        if mode == 'legacy':
            project = out / 'v0.1'
            runner.run('legacy-init', ['php8.5', ROOT / 'bin/scpp.php', 'init', '--php-profile=strict'], project, cli_reason='CLI fixture setup')
            config = json.loads((project / 'prism.json').read_text())
            config['build']['cxx'] = 'clang++-18'
            (project / 'prism.json').write_text(json.dumps(config))
            runner.run('legacy-build', ['php8.5', ROOT / 'bin/scpp.php', 'build', '--build-runtime', '--no-stan'], project, timeout=300, cli_reason='CLI fixture build')
            executable = project / '.prism/build/main'
        else:
            folder = out / mode
            (folder / 'source').mkdir(parents=True)
            shutil.copyfile(out / 'v0.2/main.phs', folder / 'source/main.phs')
            runner.run(mode + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                                           ROOT / 'runtime/metadata/modules', 'core', mode])
            manifest = json.loads((folder / 'published/.scpp-output.manifest').read_text())
            assert manifest['application_templates'] == ('allowed' if mode == 'ordinary' else 'forbidden')
            build = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
            executable = build['executable']
        assert runner.run(mode + '-run', [executable]) == expected
    results = run_tasks([Task("markers", markers)] + [Task(mode, lambda mode=mode: check(mode)) for mode in ['legacy', 'ordinary', 'concrete']])
(out / 'summary.json').write_text(json.dumps([{'name':r.name, 'seconds':r.seconds, 'passed':r.error is None} for r in results], indent=2) + '\n')

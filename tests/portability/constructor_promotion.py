"""Portable promoted inputs use v0.2 set parameters and preserve constructor behavior."""
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
source = '''<?php
class Node { public int $value = 13; }
class Parent_Holder {
 public int $base = 0;
 public function __construct(int $base) { $this->base = $base; }
}
class Holder extends Parent_Holder {
 public function __construct(public Node $node) {
  // Parent does not inspect child state or publish this.
  parent::__construct(7);
 }
}
$object = new Node();
$holder = new Holder($object);
$object->value = 17;
echo $holder->node->value, ':', $holder->base, ':';
enum Choice { case first; case second; }
final class Record {
 public int $computed = 10;
 public Choice $state = Choice::first;
 public function __construct(public Choice $choice, int $delta, private int $seed = 3) {
  echo $this->choice === Choice::second ? 'second' : 'first', ':', $this->computed, ':';
  $choice = Choice::first;
  $this->computed = $delta + 1;
  $this->state = Choice::second;
 }
 public function seed_value(): int { return $this->seed; }
 public static function argument(int $number): int { echo 'arg', $number, ':'; return $number; }
}
final class Default_Choice {
 public function __construct(public Choice $choice = Choice::first) {}
}
$record = new Record(Choice::second, Record::argument(5), Record::argument(7));
echo $record->computed, ':', $record->seed_value(), ':', $record->choice === Choice::second ? 'unchanged' : 'bad', ':', $record->state === Choice::second ? 'assigned' : 'bad', ':';
$chosen_default = new Default_Choice();
echo $chosen_default->choice === Choice::first ? 'default' : 'bad', ':';
'''
(out / 'source/main.php').write_text(source)
expected = b'17:7:arg5:arg7:second:10:6:7:unchanged:assigned:default:'
with CommandRunner(out / 'logs', ROOT) as runner:
    def portable():
        assert runner.run('php', ['php8.5', out / 'source/main.php']) == expected
        runner.run('convert-legacy', ['php8.5', TOOLS / 'convert.php', out / 'source', out / 'legacy'])
        emitted = (out / 'legacy/main.phs').read_text()
        assert 'set:$choice' not in emitted and '$this->choice = $choice;' in emitted
        assert emitted.index('parent::__construct(7)') < emitted.index('$this->node = $node;')
        runner.run('init', ['php8.5', ROOT / 'bin/scpp.php', 'init', '--php-profile=strict'], out / 'legacy', cli_reason='CLI fixture setup')
        config = json.loads((out / 'legacy/prism.json').read_text())
        config['runtime']['modules'] = ['compiler']
        config['build']['cxx'] = 'clang++-18'
        (out / 'legacy/prism.json').write_text(json.dumps(config))
        runner.run('legacy-build', ['php8.5', ROOT / 'bin/scpp.php', 'build', '--build-runtime', '--no-stan'], out / 'legacy', timeout=300, cli_reason='CLI fixture build')
        assert runner.run('legacy-run', [out / 'legacy/.prism/build/main']) == expected

    def structural_order():
        controls = out / 'order-source'
        controls.mkdir()
        (controls / 'main.php').write_text("""<?php
class Base { public function __construct(int $value) {} }
class Later extends Base {
 public function __construct(public int $value) { echo 'first'; parent::__construct($value); }
}
class Conditional extends Base {
 public function __construct(public int $value) { if ($value > 0) { parent::__construct($value); } }
}
""")
        runner.run('order-convert', ['php8.5', TOOLS / 'convert.php', controls, out / 'order-legacy'])
        text = (out / 'order-legacy/main.phs').read_text()
        later = text[text.index('class Later'):text.index('class Conditional')]
        assert later.index('$this->value = $value;') < later.index('echo') < later.index('parent::__construct')
        conditional = text[text.index('class Conditional'):]
        assert conditional.index('$this->value = $value;') < conditional.index('if') < conditional.index('parent::__construct')

    def actual_source():
        assert runner.run('switch-label-php', ['php8.5', '-r',
            'require $argv[1]; $label = new scpp\\compiler\\switch_label(scpp\\compiler\\switch_label_kind::fallback); '
            'echo ($label->label_kind === scpp\\compiler\\switch_label_kind::fallback && $label->value === null) ? "ok" : "bad";', APP / 'boot.php']) == b'ok'
    run_tasks([Task('portable', portable), Task('actual-source', actual_source), Task('structural-order', structural_order)])
    runner.run('convert-v02', ['php8.5', TOOLS / 'convert.php', out / 'source', out / 'v02', '--target=v0.2'])
    emitted = (out / 'v02/main.phs').read_text()
    assert 'set:$choice' in emitted and 'set:$seed = 3' in emitted
    assert '$this->choice = $choice;' not in emitted
    assert '$this->computed = $delta + 1;' in emitted
    def native(mode):
        folder = out / mode
        (folder / 'source').mkdir(parents=True)
        shutil.copyfile(out / 'v02/main.phs', folder / 'source/main.phs')
        runner.run(mode + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                                       ROOT / 'runtime/metadata/modules', 'compiler', mode])
        build = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php',
                                                       folder / 'published', folder / 'cache'], timeout=300))
        assert runner.run(mode + '-run', [build['executable']]) == expected
    run_tasks([Task(mode, lambda mode=mode: native(mode)) for mode in ['ordinary', 'concrete']])
    failure_source = out / 'failure-source'
    failure_source.mkdir()
    (failure_source / 'main.php').write_text('''<?php
enum Choice { case first; case second; }
final class Failing_Record {
 public int $value = 11;
 public function __construct(public Choice $choice, int $divisor) {
  echo $this->choice === Choice::second ? 'ready' : 'bad', ':', $this->value, ':';
  $this->value = (int)(10 / $divisor);
  echo 'bad-after-division';
 }
}
$failed = new Failing_Record(Choice::second, 0);
echo 'bad-after-construction';
''')
    failure_prefix = b'ready:11:'
    assert runner.run('failure-php', ['php8.5', failure_source / 'main.php'], expected=255) == failure_prefix
    for target in ['v0.1', 'v0.2']:
        runner.run('failure-convert-' + target, ['php8.5', TOOLS / 'convert.php', failure_source,
                                               out / ('failure-' + target), '--target=' + target])
    def failure_native(mode):
        label = 'failure-' + mode
        if mode == 'legacy':
            folder = out / 'failure-v0.1'
            runner.run(label + '-init', ['php8.5', ROOT / 'bin/scpp.php', 'init', '--php-profile=strict'], folder, cli_reason='CLI fixture setup')
            config = json.loads((folder / 'prism.json').read_text())
            config['build']['cxx'] = 'clang++-18'
            (folder / 'prism.json').write_text(json.dumps(config))
            runner.run(label + '-build', ['php8.5', ROOT / 'bin/scpp.php', 'build', '--build-runtime', '--no-stan'], folder, timeout=300, cli_reason='CLI fixture build')
            executable = folder / '.prism/build/main'
        else:
            folder = out / label
            (folder / 'source').mkdir(parents=True)
            shutil.copyfile(out / 'failure-v0.2/main.phs', folder / 'source/main.phs')
            runner.run(label + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                                           ROOT / 'runtime/metadata/modules', 'compiler', mode])
            build = json.loads(runner.run(label + '-build', ['php8.5', APP / 'tools/build_cpp.php',
                                                           folder / 'published', folder / 'cache'], timeout=300))
            executable = build['executable']
        assert runner.run(label + '-run', [executable], expected=1) == failure_prefix
    run_tasks([Task('failure-' + mode, lambda mode=mode: failure_native(mode))
               for mode in ['legacy', 'ordinary', 'concrete']])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'expected': expected.decode(),
                                            'native_compiler_rebuilt': False}, indent=2) + '\n')

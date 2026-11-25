"""Extract the production snapshot helper and exercise its shared hash access concurrently."""
import argparse
import json
import re
import shutil
import sys
from pathlib import Path
ROOT = Path(__file__).resolve().parents[2]
APP = ROOT / 'compiler/my-try'
TOOLS = ROOT / 'tools/php_portability'
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, TaskFailures, run_tasks
p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--results', type=Path, required=True)
p.add_argument('--sanitize', action='store_true', help='Instrument legacy application lookup/registration code with ThreadSanitizer')
p.add_argument('--mode', action='append', choices=['legacy', 'ordinary', 'concrete'])
args = p.parse_args()
out = args.results.resolve()
(out / 'source').mkdir(parents=True, exist_ok=False)
def declaration(path, label):
    text = path.read_text()
    start = text.index(label)
    opening = text.index('{', start)
    depth = 1
    end = opening + 1
    while depth:
        depth += (text[end] == '{') - (text[end] == '}')
        end += 1
    return text[start:end]
source = r'''<?php
namespace scpp\compiler;
class collected_type_definition { public int $identity = 7; }
class scope {
 public array $associations /** hash<collected_type_definition, int> */ = [];
 public function source_types_named(string $name): array /** vector<collected_type_definition> */ {
  if ($name === 'fail') { throw new \RuntimeException('lookup failure'); }
  $key = $name === 'record' ? 0 : 1;
  $entry = $this->associations[$key];
  $result /** vector<collected_type_definition> */ = [$entry];
  return $result;
 }
 public function add(int $name, collected_type_definition $entry): void { $this->associations[$name] = $entry; }
}
'''
source += declaration(APP / '03_parse/collect/structures.php', 'final class source_type_snapshot') + '\n'
source += 'final class Lookup { public function __construct(private ?scope $global) {}\n'
source += declaration(APP / '03_parse/collect/collect.php', 'private function retained_types') + '\n'
source += r'''
 public function read(scope $local, string $name): array /** vector<collected_type_definition> */ {
  return $this->retained_types($local, $name);
 }
}
class Lookup_Work {
 public static function run(int $worker, Lookup $lookup, scope $index, collected_type_definition $original): int {
  $failed = false;
  try { $lookup->read($index, 'fail'); }
  catch (\RuntimeException $error) { $failed = true; }
  if (!$failed) { throw new \RuntimeException('missing lookup failure'); }
  for ($step = 0; $step < 1000; $step++) {
   if ($worker % 2 === 0) {
    $record = $lookup->read($index, 'record'); $enumeration = $lookup->read($index, 'enum');
    if ($record[0] !== $original) { throw new \RuntimeException('record identity'); }
    if ($enumeration[0] !== $original) { throw new \RuntimeException('enum identity'); }
   } else {
    $key = $worker * 1000 + $step + 2;
    task_synchronize(function () use ($index, $original, $key): void { $index->add($key, $original); });
   }
  }
  return 1000;
 }
}
class Totals { public int $published = 0; }
$index = new scope(); $original = new collected_type_definition();
$index->add(0, $original); $index->add(1, $original);
$standalone = new Lookup(null);
$before = $standalone->read($index, 'record');
echo $before[0] === $original ? 'standalone:' : 'bad:';
$lookup = new Lookup($index); $totals = new Totals();
$items /** vector<int> */ = [0, 1, 2, 3];
$completed = task_run_publish_unordered($items, 4,
 function (int $worker) use ($lookup, $index, $original): int {
  return Lookup_Work::run($worker, $lookup, $index, $original);
 },
 function (int $amount) use ($totals): void { $totals->published += $amount; });
echo $completed, ':', $totals->published, ':';
$after = $standalone->read($index, 'record');
echo $after[0] === $original ? 'retained:' : 'bad:';
echo q_count($index->associations);
'''
(out / 'source/main.php').write_text(source)
expected = b'standalone:4:4000:retained:2002'
with CommandRunner(out / 'logs', ROOT) as runner:
    assert runner.run('host', ['php8.5', '-r', 'require $argv[1]; require $argv[2];', TOOLS / 'runtime/bootstrap.php', out / 'source/main.php']) == expected
    for route in ['v0.1', 'v0.2']:
        runner.run(route + '-convert', ['php8.5', TOOLS / 'convert.php', out / 'source', out / route, '--target=' + route])
        runner.run(route + '-framework', ['php8.5', TOOLS / 'install_native_runtime.php', out / route])
    def check(mode):
        if mode == 'legacy':
            project = out / 'v0.1'
            runner.run('legacy-init', ['php8.5', ROOT / 'bin/scpp.php', 'init', '--php-profile=strict'], project, cli_reason='CLI fixture setup')
            config = json.loads((project / 'prism.json').read_text())
            config['runtime']['modules'] = ['tasks']
            config['build']['cxx'] = 'clang++-18'
            (project / 'prism.json').write_text(json.dumps(config))
            runner.run('legacy-build', ['php8.5', ROOT / 'bin/scpp.php', 'build', '--build-runtime', '--no-stan'], project, timeout=300, cli_reason='CLI fixture build')
            executable = project / '.prism/build/main'
        else:
            folder = out / mode
            (folder / 'source').mkdir(parents=True)
            shutil.copyfile(out / 'v0.2/main.phs', folder / 'source/main.phs')
            shutil.copyfile(out / 'v0.2/scpp_framework/exceptions.phs', folder / 'source/exceptions.phs')
            runner.run(mode + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                                           ROOT / 'runtime/metadata/modules', 'tasks', mode])
            build = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
            executable = build['executable']
        assert runner.run(mode + '-run', [executable]) == expected
        if mode == 'legacy':
            # Supplemental scheduling instrumentation only: keep production helper unchanged.
            # All four callbacks must be resident before any reader/writer starts its loop.
            generated = project / '.prism/generated/main.cpp'
            text = generated.read_text()
            marker = r'(SCPP_CALL_DEPTH_GUARD\("Lookup_Work::run"[^\n]+\n)'
            text, matches = re.subn(marker, r'\1\tlookup_test_gate.arrive_and_wait();\n', text)
            assert matches == 1, 'Missing production work entry for overlap instrumentation'
            generated.write_text('#include <barrier>\nstatic std::barrier<> lookup_test_gate(4);\n' + text)
            runner.run('legacy-overlap-build', ['ninja', '-C', project / '.prism/build'], timeout=240)
            assert runner.run('legacy-overlap-run', [executable], timeout=30) == expected
            if args.sanitize:
                ninja = project / '.prism/build/build.ninja'
                lines = ninja.read_text().splitlines()
                for index, line in enumerate(lines):
                    if line.startswith('cxxflags = ') or line.startswith('ldflags = '):
                        lines[index] += ' -fsanitize=thread -fno-omit-frame-pointer'
                ninja.write_text('\n'.join(lines) + '\n')
                runner.run('legacy-tsan-build', ['ninja', '-C', project / '.prism/build'], timeout=300)
                assert runner.run('legacy-tsan-run', [executable], timeout=60) == expected
                # Negative control: removing only reader synchronization must expose the race.
                original = generated.read_text()
                locked = 'tasks::synchronize([scope, name, snapshot]() mutable -> void {\n\tsnapshot->declarations = scope->source_types_named(name);\n});'
                unlocked = '([scope, name, snapshot]() mutable -> void {\n\tsnapshot->declarations = scope->source_types_named(name);\n})();'
                assert original.count(locked) == 1
                fixed_executable = executable.with_suffix('.fixed')
                shutil.copyfile(executable, fixed_executable)
                try:
                    generated.write_text(original.replace(locked, unlocked))
                    runner.run('legacy-unlocked-build', ['ninja', '-C', project / '.prism/build'], timeout=240)
                    runner.run('legacy-unlocked-run', ['env', 'TSAN_OPTIONS=halt_on_error=1', executable], expected=66, timeout=30)
                    assert 'data race' in (out / 'logs/legacy-unlocked-run.stderr').read_text()
                finally:
                    generated.write_text(original)
                    shutil.copyfile(fixed_executable, executable)



    failure = None
    try:
        results = run_tasks([Task(mode, lambda mode=mode: check(mode)) for mode in (args.mode or ['legacy', 'ordinary', 'concrete'])])
    except TaskFailures as error:
        results = error.outcomes
        failure = error
(out / 'summary.json').write_text(json.dumps([{'name':r.name, 'seconds':r.seconds, 'passed':r.error is None, 'error':r.error} for r in results], indent=2) + '\n')

if failure is not None:
    raise failure

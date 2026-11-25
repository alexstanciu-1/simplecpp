"""Marked staged class fields use converter-owned checked storage across routes."""
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
class Payload { public int $value = 7; }
class Staged {
 public int $attempts = 0;
 public function read(): Payload { return $this->item; }
 public function assign(Payload $value): void { $this->item = $value; }
 public function assign_nullable(?Payload $value): void { $this->item = $value; }
 public function copy_from_self(): void { $this->item = $this->item; }
 public function fail_value(): Payload { $this->attempts = $this->attempts + 1; throw new \LogicException('rhs'); }
 public function fail_assignment(): void { $this->item = $this->fail_value(); }
 public Payload $item /** _uninitialized_ */;
}
$owner = new Staged();
try { $owner->read(); echo 'unexpected'; }
catch (\Throwable $error) { echo 'early:'; }
$value = new Payload(); $owner->assign($value);
$held = $owner->read(); $held->value = 9;
$owner->copy_from_self();
try { $owner->assign_nullable(null); echo 'unexpected'; }
catch (\Throwable $error) { echo 'null:'; }
$held = $owner->read(); echo $held->value, ':';
try { $owner->fail_assignment(); echo 'unexpected'; }
catch (\Throwable $error) { echo 'rhs:'; }
$held = $owner->read(); echo $held->value, ':', $owner->attempts, ':';
$replacement = new Payload(); $owner->assign($replacement);
$held = $owner->read(); echo $held->value;
'''
(out / 'source/main.php').write_text(source)
expected = b'early:null:9:rhs:9:1:7'
with CommandRunner(out / 'logs', ROOT) as runner:
    assert runner.run('host', ['php8.5', '-r', 'foreach(array_slice($argv,1) as $path){require $path;}',
                             TOOLS / 'runtime/bootstrap.php', out / 'source/main.php']) == expected
    for route in ['v0.1', 'v0.2']:
        runner.run(route + '-convert', ['php8.5', TOOLS / 'convert.php', out / 'source', out / route, '--target=' + route])
        artifact = out / route / 'main.phs'
        before = artifact.read_bytes(), artifact.stat().st_mtime_ns
        runner.run(route + '-incremental', ['php8.5', TOOLS / 'convert.php', out / 'source', out / route, '--target=' + route])
        assert (artifact.read_bytes(), artifact.stat().st_mtime_ns) == before
    runner.run('rejections', ['php8.5', ROOT / 'tests/portability/staged_marker_rejections.php'])
    for route in ['v0.1', 'v0.2']:
        runner.run(route + '-framework', ['php8.5', TOOLS / 'install_native_runtime.php', out / route])
    def check(mode):
        if mode == 'legacy':
            project = out / 'v0.1'
            runner.run('legacy-init', ['php8.5', ROOT / 'bin/scpp.php', 'init', '--php-profile=strict'], project, cli_reason='CLI fixture setup')
            config = json.loads((project / 'prism.json').read_text())
            config['runtime']['modules'] = ['compiler']
            config['build']['cxx'] = 'clang++-18'
            (project / 'prism.json').write_text(json.dumps(config))
            runner.run('legacy-build', ['php8.5', ROOT / 'bin/scpp.php', 'build', '--build-runtime', '--no-stan'], project, timeout=300, cli_reason='CLI fixture build')
            executable = project / '.prism/build/main'
        else:
            folder = out / mode
            (folder / 'source').mkdir(parents=True)
            shutil.copyfile(out / 'v0.2/main.phs', folder / 'source/main.phs')
            # Include the full exception framework, including its identity helper.
            shutil.copyfile(out / 'v0.2/scpp_framework/exceptions.phs', folder / 'source/exceptions.phs')
            runner.run(mode + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                                           ROOT / 'runtime/metadata/modules', 'compiler', mode])
            manifest = json.loads((folder / 'published/.scpp-output.manifest').read_text())
            assert manifest['application_templates'] == ('allowed' if mode == 'ordinary' else 'forbidden')
            build = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
            executable = build['executable']
        assert runner.run(mode + '-run', [executable]) == expected
    results = run_tasks([Task(mode, lambda mode=mode: check(mode)) for mode in ['legacy', 'ordinary', 'concrete']])
(out / 'summary.json').write_text(json.dumps([{'name':r.name, 'seconds':r.seconds, 'passed':r.error is None} for r in results], indent=2) + '\n')

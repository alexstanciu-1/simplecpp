"""Production type identity allocators at uint32 exhaustion across routes."""
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
model = (APP / '03_parse/types/model.php').read_text()
def extract(label, text=model):
    start = text.index(label)
    opening = text.index('{', start)
    end, depth = opening + 1, 1
    while depth:
        depth += (text[end] == '{') - (text[end] == '}')
        end += 1
    return text[start:end]
source = '<?php\n' + extract('final class Type_Identity') + '\n'
source += '''class Identity_Proof {
 private int $next_definition_id /** uint32 */ = 4294967294;
 private int $next_type_id /** uint32 */ = 4294967294;
 private bool $definition_ids_exhausted = false;
 private bool $type_ids_exhausted = false;
 public function definition(): int { return $this->allocate_definition_id(); }
 public function concrete(): int { return $this->allocate_type_id(); }
'''
source += extract('private function allocate_definition_id(') + '\n'
source += extract('private function allocate_type_id(') + '\n}\n'
source += '''class source_record { public int $revision /** uint32 */ = 17; }
class module {
 public int $revision /** uint32 */ = 19;
 public array $sources /** hash<source_record> */ = [];
}
class Model {
 public static int $revision /** uint32 */ = 4294967295;
 public static array $modules /** hash<module> */ = [];
}
class Lifecycle_Proof {
'''
source += extract('public static function next_revision(', (APP / 'compiler/lifecycle.php').read_text())
source += '\n}\n'
source += r'''$ids = new Identity_Proof();
echo $ids->definition(), ':', $ids->definition(), ':';
try { echo $ids->definition(); }
catch (\OverflowException $error) { echo 'exhausted:'; }
echo $ids->concrete(), ':', $ids->concrete(), ':';
try { echo $ids->concrete(); }
catch (\OverflowException $error) { echo 'exhausted'; }
$module_value = new module(); $source_value = new source_record();
$sources /** hash<source_record> */ = [];
$sources['file'] = $source_value;
$module_value->sources = $sources;
$modules /** hash<module> */ = [];
$modules['module'] = $module_value;
Model::$modules = $modules;
echo ':', Lifecycle_Proof::next_revision(), ':', $module_value->revision, ':', $source_value->revision;
'''
(out / 'source/main.php').write_text(source)
expected = b'4294967294:4294967295:exhausted:4294967294:4294967295:exhausted'
expected += b':1:0:0'
with CommandRunner(out / 'logs', ROOT) as runner:
    assert runner.run('host', ['php8.5', '-r', 'foreach(array_slice($argv,1) as $path){require $path;}',
                             TOOLS / 'runtime/bootstrap.php', out / 'source/main.php']) == expected
    for route in ['v0.1', 'v0.2']:
        runner.run(route + '-convert', ['php8.5', TOOLS / 'convert.php', out / 'source', out / route, '--target=' + route])
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

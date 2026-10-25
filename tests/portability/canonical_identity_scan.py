"""Production type identity and directory scan boundaries, including real scan failure, across routes."""
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
start = model.index('final class Type_Identity')
identity = model[start:model.index('interface type_definition_i', start)]
start = model.index('final class canonical_type_use')
type_use = model[start:model.index('/** Exact ordered application index', start)]
production = (APP / '06_native/ninja_build.php').read_text()
start = production.index('$scan = fs_scan($this->root);')
scan = production[start:production.index('\n\t\tforeach ($entries', start)]
(out / 'empty').mkdir()
(out / 'populated').mkdir()
(out / 'populated/entry.txt').write_text('entry')
source = '<?php\nnamespace scpp\\compiler;\n' + identity + type_use + r"""
final class Scan_Proof {
 public function __construct(private string $root) {}
 public function count_entries(): int {
""" + scan + r"""
  return q_count($entries);
 }
}
$first = new canonical_type_use(1);
$same = new canonical_type_use(1);
$other = new canonical_type_use(2);
$by_value = new canonical_type_use(1, true);
$maximum = new canonical_type_use(4294967295);
$maximum_copy = new canonical_type_use(4294967295);
echo $first->matches($same) && !$first->matches($other) && !$first->matches($by_value)
 && $maximum->matches($maximum_copy) && !$first->matches($maximum) ? 'ids:' : 'bad:';
"""
for name in ['empty', 'populated']:
    source += f"$probe_{name} = new Scan_Proof('{out / name}'); echo $probe_{name}->count_entries(), ':';\n"
source += f"$missing = new Scan_Proof('{out / 'missing'}');\n"
source += r"""
try { $missing->count_entries(); echo 'bad'; }
catch (\RuntimeException $error) { echo $error->getMessage(); }
"""
source += f"\n$existing_path = '{out / 'populated/entry.txt'}'; $missing_path = '{out / 'missing'}';\n"
source += r"""
$size = 0;
$modified = 0;
echo take_false($size, fs_size($existing_path)) && $size === 5 ? ':size' : ':bad';
echo take_false($modified, fs_mtime($existing_path)) && $modified > 0 ? ':mtime' : ':bad';
echo !take_false($size, fs_size($missing_path)) && $size === 5 ? ':size-failed' : ':bad';
echo !take_false($modified, fs_mtime($missing_path)) && $modified > 0 ? ':mtime-failed' : ':bad';
"""
(out / 'source/main.php').write_text(source)
expected = b'ids:0:1:Cannot inspect Ninja directory:size:mtime:size-failed:mtime-failed'
with CommandRunner(out / 'logs', ROOT) as runner:
    publisher = out / 'publish.php'
    publisher.write_text((APP / 'tests/async_tasks_publish.php').read_text()
                         .replace("dirname(__DIR__) . '/boot.php'", repr(str(APP / 'boot.php')))
                         .replace('[$argv[3]]', "[$argv[3], 'strings', 'filesystem']"))
    assert runner.run('host', ['php8.5', '-r', 'foreach(array_slice($argv,1) as $path){require $path;}',
                             TOOLS / 'runtime/bootstrap.php', APP / 'helpers/storage_abstract.php',
                             APP / 'helpers/storage.php', out / 'source/main.php']) == expected
    for route in ['v0.1', 'v0.2']:
        runner.run(route + '-convert', ['php8.5', TOOLS / 'convert.php', out / 'source', out / route, '--target=' + route])
    for route in ['v0.1', 'v0.2']:
        runner.run(route + '-framework', ['php8.5', TOOLS / 'install_native_runtime.php', out / route, '--filesystem'])
    def check(mode):
        if mode == 'legacy':
            project = out / 'v0.1'
            runner.run('legacy-init', ['php8.5', ROOT / 'bin/scpp.php', 'init', '--php-profile=strict'], project, cli_reason='CLI fixture setup')
            config = json.loads((project / 'prism.json').read_text())
            config['runtime']['modules'] = ['compiler', 'filesystem']
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
            framework = (out / 'v0.2/scpp_framework/filesystem.phs').read_text()
            bindings = ''
            for operation in ['scan', 'size', 'mtime']:
                begin = framework.index('function scpp_portability_fs_' + operation + '(')
                end = framework.index('\nfunction ', begin + 1)
                bindings += framework[begin:end] + '\n'
            (folder / 'source/scan.phs').write_text(bindings)
            runner.run(mode + '-publish', ['php8.5', publisher, folder,
                                           ROOT / 'runtime/metadata/modules', 'compiler', mode])
            manifest = json.loads((folder / 'published/.scpp-output.manifest').read_text())
            assert manifest['application_templates'] == ('allowed' if mode == 'ordinary' else 'forbidden')
            build = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
            executable = build['executable']
        assert runner.run(mode + '-run', [executable]) == expected
    results = run_tasks([Task(mode, lambda mode=mode: check(mode)) for mode in ['legacy', 'ordinary', 'concrete']])
(out / 'summary.json').write_text(json.dumps([{'name':r.name, 'seconds':r.seconds, 'passed':r.error is None} for r in results], indent=2) + '\n')

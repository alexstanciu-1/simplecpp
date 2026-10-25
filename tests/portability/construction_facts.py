"""Production construction slots preserve identity, replacement and checked access across routes."""
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
production = (APP / '03_parse/structures/structures_specialization.php').read_text()
start = production.index('private ?prepared_record_construction $record_facts', production.index('final class data_construction_node'))
methods = production[start:production.index('\n\tpublic ?type_node $target_type', start)]
source = r"""<?php
namespace scpp\compiler;
abstract class prepared_expression { public bool $suspends = false; }
abstract class prepared_data_construction extends prepared_expression {}
final class prepared_record_construction extends prepared_data_construction {}
final class prepared_sequence_construction extends prepared_data_construction {}
final class prepared_empty_construction extends prepared_data_construction {}
final class Fact_Slots {
""" + methods + r"""
}
$node = new Fact_Slots();
echo $node->preparation() === null ? 'empty:' : 'bad:';
$record_facts = new prepared_record_construction();
$node->set_record_preparation($record_facts);
$generic = $node->require_preparation();
$generic->suspends = true;
if ($node->require_data_preparation() !== $record_facts) { throw new \LogicException('Different generic identity'); }
echo $node->require_record_preparation() === $record_facts && $record_facts->suspends ? 'record:' : 'bad:';
$empty = new prepared_empty_construction();
$node->set_empty_preparation($empty);
echo $node->require_empty_preparation() === $empty && $node->require_preparation() === $empty
 && $node->optional_record_preparation() === null ? 'empty:' : 'bad:';
try { $node->require_record_preparation(); echo 'bad:'; }
catch (\LogicException $error) { echo 'checked:'; }
$sequence = new prepared_sequence_construction();
$node->set_sequence_preparation($sequence);
echo $node->require_sequence_preparation() === $sequence && $node->require_preparation() === $sequence
 && $node->optional_empty_preparation() === null ? 'sequence:' : 'bad:';
$node->clear_preparation();
echo $node->preparation() === null && $node->optional_record_preparation() === null
 && $node->optional_empty_preparation() === null && $node->optional_sequence_preparation() === null ? 'cleared:' : 'bad:';
try { $node->require_preparation(); echo 'bad:'; }
catch (\LogicException $empty_error) { echo 'checked:'; }
$node->set_record_preparation($record_facts);
echo $node->require_record_preparation() === $record_facts && $record_facts->suspends ? 'retained' : 'bad';
"""
(out / 'source/main.php').write_text(source)
expected = b'empty:record:empty:checked:sequence:cleared:checked:retained'
with CommandRunner(out / 'logs', ROOT) as runner:
    publisher = out / 'publish.php'
    publisher.write_text((APP / 'tests/async_tasks_publish.php').read_text()
                         .replace("dirname(__DIR__) . '/boot.php'", repr(str(APP / 'boot.php')))
                         .replace('[$argv[3]]', "[$argv[3], 'strings']"))
    assert runner.run('host', ['php8.5', '-r', 'foreach(array_slice($argv,1) as $path){require $path;}',
                             TOOLS / 'runtime/bootstrap.php', APP / 'helpers/storage_abstract.php',
                             APP / 'helpers/storage.php', out / 'source/main.php']) == expected
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
            runner.run(mode + '-publish', ['php8.5', publisher, folder,
                                           ROOT / 'runtime/metadata/modules', 'compiler', mode])
            manifest = json.loads((folder / 'published/.scpp-output.manifest').read_text())
            assert manifest['application_templates'] == ('allowed' if mode == 'ordinary' else 'forbidden')
            build = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
            executable = build['executable']
        assert runner.run(mode + '-run', [executable]) == expected
    results = run_tasks([Task(mode, lambda mode=mode: check(mode)) for mode in ['legacy', 'ordinary', 'concrete']])
(out / 'summary.json').write_text(json.dumps([{'name':r.name, 'seconds':r.seconds, 'passed':r.error is None} for r in results], indent=2) + '\n')

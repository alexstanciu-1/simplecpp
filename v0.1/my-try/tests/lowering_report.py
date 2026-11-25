"""Prove optional lowering diagnostics preserve outputs and expose truthful provenance."""
import argparse
import json
import sys
from pathlib import Path

APP = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    out = parser.parse_args().results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    source = out / 'source'
    source.mkdir()
    (source / 'types.phs').write_text('''class Item { public int $number = 7; }
class Bag { public $items hash<Item> = []; public function size(): int { return count($this->items); } }
class Authored { public $items hash<Item> = []; public function __construct() {} }
function read_item(Item $item): int { return $item->number; }
''')
    (source / 'main.phs').write_text('''$bag = new Bag(); $item = new Item();
if ($bag->size() !== 0 || read_item($item) !== 7) { return 1; }
return 0;
''')
    with CommandRunner(out / 'logs', APP.parents[1]) as runner:
        def check(level):
            root = out / str(level)
            root.mkdir()
            command = ['php', APP / 'main.php', '--s2s', source, '--no-prelower-templates', '--entry', 'main.phs', '--optimization', str(level)]
            baseline = runner.run(f'baseline-{level}', command)
            report_path, explain_path = root / 'report.json', root / 'explain.json'
            observed = runner.run(f'report-{level}', [*command, '--lowering-report', report_path, '--lowering-explain', explain_path])
            assert baseline == observed, 'Reports changed artifact transport'
            artifacts = json.loads(baseline)['artifacts']
            report = json.loads(report_path.read_text())
            explain = json.loads(explain_path.read_text())
            assert report['format'] == 'scpp-lowering-report-v1'
            assert report['optimization'] == level
            assert report['operation_reasons_available'] is False
            assert report['artifacts'] == explain['artifacts']
            indexed = {a['path']: a for a in artifacts}
            for row in report['artifacts']:
                artifact = indexed[row['path']]
                assert row['bytes'] == len(artifact['text'].encode())
                assert row['direct_includes'] == artifact['includes']
                assert row['direct_generated_consumers'] == [a['path'] for a in artifacts if row['path'] in a['includes']]
            bag = next(f for f in explain['fragments'] if f['owner'] == 'Bag' and 'default_constructor_decision' in f)
            assert bag['default_constructor_decision'] == ('outlined' if level < 2 else 'execution-policy')
            assert bag['shared_allocation_outlined'] == (level < 2)
            authored = next(f for f in explain['fragments'] if f['owner'] == 'Authored' and 'default_constructor_decision' in f)
            assert authored['default_constructor_decision'] == ('constructor-base-or-data-initialization' if level < 2 else 'execution-policy')
            body = next(f for f in explain['fragments'] if f['owner'] == 'read_item' and f['kind'] == 'function_body')
            assert body['implementation_path'] in indexed
            assert body['fragment_bytes'] > 0
            assert any(h.startswith('types/') for h in body['fragment_headers'])
            assert all('fragment_headers' not in f for f in report['fragments'])
            # Repeat diagnostics must be deterministic, and never overwrite an existing path.
            second = root / 'again.json'
            runner.run(f'again-{level}', [*command, '--lowering-explain', second])
            assert second.read_bytes() == explain_path.read_bytes()
            runner.run(f'reject-overwrite-{level}', [*command, '--lowering-explain', second], expected=1)
            assert second.read_bytes() == explain_path.read_bytes()
        def retained():
            driver = out / 'retained.php'
            driver.write_text(r'''<?php
namespace scpp\compiler;
require BOOT;
$compiler = new Compiler(new cpp_lowering_options(false)); $compiler->init([SOURCE]); $compiler->select_entry('main', 'main.phs');
$policy = new cpp_lowering_policy(0); $compiler->exec_cpp(null, $policy);
$before = CPP_Output::encode(Model::$cpp_files);
$report = CPP_Lowering_Report::encode(Model::$cpp_files, Model::$cpp_output_program, $policy, true);
if (CPP_Output::encode(Model::$cpp_files) !== $before) { throw new \Exception('Reporting mutated artifacts'); }
$compiler->cpp(null, $policy);
if (CPP_Output::encode(Model::$cpp_files) !== $before) { throw new \Exception('Reporting disturbed retained generation'); }
if (CPP_Lowering_Report::encode(Model::$cpp_files, Model::$cpp_output_program, $policy, true) !== $report) { throw new \Exception('Retained report changed'); }
$rejected = false;
try { CPP_Lowering_Report::encode(Model::$cpp_files, Model::$cpp_output_program, new cpp_lowering_policy(2)); }
catch (\LogicException $error) { $rejected = true; }
if (!$rejected) { throw new \Exception('Mismatched report policy accepted'); }
echo "PASS\n";
'''.replace('BOOT', repr(str(APP / 'boot.php'))).replace('SOURCE', repr(str(source))))
            runner.run('retained', ['php', driver])
        run_tasks([Task(f'optimization-{level}', lambda level=level: check(level)) for level in [0, 1, 2]] + [Task('retained', retained)])
    (out / 'summary.json').write_text(json.dumps({'passed': True, 'optimization_levels': [0, 1, 2], 'native_compiler_tested': False}, indent=2) + '\n')


if __name__ == '__main__':
    main()

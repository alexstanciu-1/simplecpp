"""Production definite-assignment composition across PHP and native routes."""
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
enum control_transfer_kind { case break_construct; case continue_loop; }
class prepared_source_storage {}
class preparation_context {
 public array $pending_locals /** vector<prepared_source_storage> */ = [];
 public bool $assignment_path_reachable = true;
}
''' + declaration(APP / '04_analyze/prepare/data/structures.php', 'final class statement_completion') + '\n'
source += (APP / '04_analyze/prepare/semantics/completion.php').read_text().split('namespace scpp\\compiler;', 1)[1]
source += r'''
$a = new prepared_source_storage(); $b = new prepared_source_storage();
$context = new preparation_context();
$pending /** vector<prepared_source_storage> */ = [$a, $b];
$context->pending_locals = $pending;
$entry = Local_Assignment::normal($context);
Local_Assignment::assigned($a, $context);
Local_Assignment::require_read($a, 'a', $context);
$taken = Local_Assignment::normal($context);
$joined = Completion_Preparation::alternatives($entry, $taken);
echo Local_Assignment::contains($entry->normal_pending, $a) ? '1:' : '0:';
echo Local_Assignment::contains($taken->normal_pending, $a) ? '0:' : '1:';
echo Local_Assignment::contains($joined->normal_pending, $a) ? '1:' : '0:';
$transfer = Completion_Preparation::transfer(control_transfer_kind::break_construct, $context);
Local_Assignment::assigned($b, $context);
$finalizer = Local_Assignment::normal($context);
$resumed = Completion_Preparation::finalized($transfer, $finalizer);
echo $resumed->can_break ? '1:' : '0:';
echo Local_Assignment::contains($resumed->break_pending, $b) ? 'bad:' : '0:';
$single /** vector<prepared_source_storage> */ = [$a];
$context->pending_locals = $single;
$context->assignment_path_reachable = false;
Local_Assignment::require_read($a, 'unreachable', $context);
echo Local_Assignment::contains($transfer->break_pending, $b) ? '1' : '0';
'''
(out / 'source/main.php').write_text(source)
expected = b'1:1:1:1:0:1'
with CommandRunner(out / 'logs', ROOT) as runner:
    assert runner.run('host', ['php8.5', '-r', 'require $argv[1]; require $argv[2];', TOOLS / 'runtime/bootstrap.php', out / 'source/main.php']) == expected
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

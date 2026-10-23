"""Shared JSON reader factory, state, ownership and errors across both routes."""
import argparse
import json
import sys
from pathlib import Path

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks

p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--results', type=Path, required=True)
out = p.parse_args().results.resolve()
out.mkdir(parents=True, exist_ok=False)
source = r'''function exercise(): int {
$reader json_pull_reader = json_pull_reader_create();
$alias json_pull_reader = $reader;
$problem error;
$text string = "";
$ok bool = false;
$value int = 0;
if (take($text, $problem, $reader->kind())) { return 1; }
$input string = "{\"items\":[7,1.5,true,null,\"text\"]}";
if (!take($ok, $problem, $reader->start($input, "fixture", 128))) { return 2; }
$input = "changed";
if (!take($text, $problem, $alias->kind())) { return 3; }
if ($text !== "object") { return 4; }
if (!take($ok, $problem, $alias->begin_object())) { return 5; }
if (!take($ok, $problem, $reader->next_property())) { return 6; }
if (!$ok) { return 7; }
if (!take($text, $problem, $reader->property_name())) { return 8; }
if ($text !== "items") { return 9; }
if (!take($ok, $problem, $reader->begin_array())) { return 10; }
if (!take($ok, $problem, $reader->next_element())) { return 11; }
if (!$ok) { return 12; }
if (!take($value, $problem, $alias->read_integer())) { return 13; }
if ($value !== 7) { return 14; }
if (!take($ok, $problem, $reader->next_element())) { return 15; }
if (!take($text, $problem, $reader->read_number())) { return 16; }
if ($text !== "1.5") { return 17; }
if (!take($ok, $problem, $reader->next_element())) { return 18; }
if (!take($ok, $problem, $reader->read_boolean())) { return 19; }
if (!$ok) { return 20; }
if (!take($ok, $problem, $reader->next_element())) { return 21; }
if (!take($ok, $problem, $reader->read_null())) { return 22; }
if (!take($ok, $problem, $reader->next_element())) { return 23; }
if (!take($text, $problem, $reader->read_string())) { return 24; }
if ($text !== "text") { return 25; }
if (!take($ok, $problem, $reader->next_element())) { return 26; }
if ($ok) { return 27; }
if (!take($ok, $problem, $reader->next_property())) { return 28; }
if ($ok) { return 29; }
if (!take($ok, $problem, $reader->finish_document())) { return 30; }
if (take($ok, $problem, $alias->start("0", "restart", 128))) { return 31; }
$fresh json_pull_reader = json_pull_reader_create();
if (!take($ok, $problem, $fresh->start("9", "fresh", 128))) { return 32; }
if (!take($value, $problem, $fresh->read_integer())) { return 33; }
if ($value !== 9) { return 34; }
if (!take($ok, $problem, $fresh->finish_document())) { return 35; }
$failed json_pull_reader = json_pull_reader_create();
if (take($ok, $problem, $failed->start("0", "bad-limit", 0))) { return 36; }
if (take($ok, $problem, $failed->start("0", "retry", 128))) { return 37; }
if (take($text, $problem, $failed->kind())) { return 38; }
$rejected json_pull_reader = json_pull_reader_create();
if (!take($ok, $problem, $rejected->start("0", "reject", 128))) { return 39; }
if (take($ok, $problem, $rejected->reject("consumer failure"))) { return 40; }
if (take($value, $problem, $rejected->read_integer())) { return 41; }
$protocol json_pull_reader = json_pull_reader_create();
if (!take($ok, $problem, $protocol->start("[]", "protocol", 128))) { return 42; }
if (take($ok, $problem, $protocol->next_element())) { return 43; }
if (take($text, $problem, $protocol->kind())) { return 44; }
return 0;
}
echo exercise();
'''
with CommandRunner(out / 'logs', ROOT) as runner:
    def check(mode):
        folder = out / mode
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(source)
        if mode == 'legacy':
            project = folder / 'source'
            runner.run(mode + '-init', ['php8.5', ROOT / 'bin/scpp.php', 'init', '--php-profile=strict'], project, cli_reason='CLI fixture setup')
            config = json.loads((project / 'prism.json').read_text())
            config['build']['cxx'] = 'clang++-18'
            config['runtime']['modules'] = ['json']
            (project / 'prism.json').write_text(json.dumps(config))
            runner.run(mode + '-build', ['php8.5', ROOT / 'bin/scpp.php', 'build', '--build-runtime', '--no-stan'], project, timeout=300, cli_reason='CLI fixture build')
            executable = project / '.prism/build/main'
        else:
            runner.run(mode + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                                           ROOT / 'runtime/metadata/modules', 'json', mode])
            manifest = json.loads((folder / 'published/.scpp-output.manifest').read_text())
            assert manifest['application_templates'] == ('allowed' if mode == 'ordinary' else 'forbidden')
            build = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php',
                                                           folder / 'published', folder / 'cache'], timeout=300))
            executable = build['executable']
        assert runner.run(mode + '-run', [executable]) == b'0'
    run_tasks([Task(mode, lambda mode=mode: check(mode)) for mode in ['legacy', 'ordinary', 'concrete']])
(out / 'summary.json').write_text(json.dumps({'legacy': 'executed', 'ordinary': 'executed', 'concrete': 'executed'}, indent=2) + '\n')

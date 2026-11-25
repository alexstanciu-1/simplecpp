"""Production token input promotion across PHP and native routes."""
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

def method(text, name):
    start = text.index(name)
    opening = text.index('{', start)
    depth, end = 1, opening + 1
    while depth:
        depth += (text[end] == '{') - (text[end] == '}')
        end += 1
    return text[start:end]

source = r"""<?php
namespace scpp\compiler;
class file {}
class scope {}
class file_node { public int $marker = 0; }
class anonymous_callable_identity {}
class preparation_owner {}
class collected_name {}
"""
for cls in ['token', 'retained_token_range', 'token_list']:
    source += declaration(APP / '02_tokenize/structures.php', 'final class ' + cls) + '\n'
source += declaration(APP / '02_tokenize/buffer.php', 'final class Token_Buffer') + '\n'
source += 'class parsed_file {\n' + method(declaration(APP / '03_parse/structures/structures.php', 'final class parsed_file'), 'public function __construct') + '\n}\n'
cleanup = declaration(APP / '03_parse/token_cleanup.php', 'final class Token_Cleanup')
source += 'final class Token_Cleanup {\n'
for name in ['public function __construct', 'private function delta', 'public function token_index']:
    source += method(cleanup, name) + '\n'
# Stand-in for AST-wide cleanup: real cleanup/recovery is exercised by host tests.
source += r"""
public static int $calls = 0;
public static function file(parsed_file $parsed): void { self::$calls++; }
}
"""
parser = declaration(APP / '03_parse/parser.php', 'final class Parser_Run')
constructor = method(parser, 'public function __construct')
# Exact production prefix owns token normalization and initial cursor placement.
# Later scope/collection construction has independent outstanding bootstrap gates.
cut = constructor.index('\n\t\t$this->scopes =')
source += 'final class Parser_Run { private int $position = 0;\n' + constructor[:cut] + '\n}\n'
source += 'public function snapshot(): token_list { return $this->tokens; }\n'
source += 'public function current_position(): int { return $this->position; }\n}\n'
collection = declaration(APP / '03_parse/collect/structures.php', 'final class collected_file')
source += r"""
final class collected_file {
 public Storage $anonymous_callables /** Storage<anonymous_callable_identity> */;
 public Storage $retired_scopes /** Storage<scope> */;
 public \SplObjectStorage $preparation_changes /** hash<bool, preparation_owner> */;
 public Storage $entries /** Storage<collected_name> */;
"""
for name in ['public function __construct', 'public function set_tokens', 'public function token_snapshot']:
    source += method(collection, name) + '\n'
source += '}\n'
source += r"""
$input = new file();
$old = new token_list($input, 'ab');
$old_rows /** Storage<token> */ = $old->tokens;
$first = new token(0, 2, 'ab'); $old_rows->append($first); $old->end_token = 1;
$current = new token_list($input, 'cd');
$current_rows /** Storage<token> */ = $current->tokens;
$second = new token(0, 2, 'cd'); $current_rows->append($second); $current->end_token = 1;
$root = new file_node();
$prior_collection = new collected_file($old, $root);
$prior = new parsed_file($old, $root, $prior_collection, new Storage /** Storage<scope> */());
$run = new Parser_Run($current, null, $prior);
echo $run->snapshot() === $current ? 'same' : 'bad';
echo ':', $run->current_position(), ':', $current->content, ':', Token_Cleanup::$calls;
$joined /** Storage<token> */ = $current->tokens;
echo ':', $joined[0] === $first ? 'old' : 'bad';
echo ':', $joined[1] === $second ? 'new' : 'bad';
echo ':', $second->offset, ':', q_count($current->retained_ranges);
$collection = new collected_file($current, $root);
echo ':', $collection->token_snapshot() === $current ? 'collection' : 'bad';
$collection->set_tokens($old);
echo ':', $collection->token_snapshot() === $old ? 'rebound' : 'bad';
$ranges /** Storage<retained_token_range> */ = $current->retained_ranges;
$ranges->append(new retained_token_range(0, 1, 1));
$cleanup = new Token_Cleanup($current);
echo ':', $cleanup->token_index(0), ':', $cleanup->token_index(1);
$current->first_token = 2;
echo ':', $cleanup->token_index(1);
$fresh = new token_list($input, '');
$fresh_run = new Parser_Run($fresh);
echo ':', $fresh_run->snapshot() === $fresh ? 'fresh' : 'bad', ':', $fresh_run->current_position();
echo ':', $collection->root === $prior->root ? 'shared-root' : 'bad';
$root->marker = 17;
echo ':', $collection->root->marker, ':', $prior->root->marker;
$revived = new file_node();
$prior->root = $revived;
$prior_collection->root = $revived;
echo ':', $prior->root === $prior_collection->root ? 'revived' : 'bad';
echo ':', $collection->root === $root ? 'old-root' : 'bad';
"""
(out / 'source/main.php').write_text(source)
expected = b'same:1:abcd:1:old:new:2:0:collection:rebound:0:0:-1:fresh:0:shared-root:17:17:revived:old-root'
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

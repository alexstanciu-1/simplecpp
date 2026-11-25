"""Provider layout requirements, recursive handles, metadata recovery and complete-value controls."""
import argparse
import json
import shutil
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
source = '''struct Inline { public int $value = 3; }
class Node {
 public int $marker = 7;
 public $children Storage<Node> = [];
 public $named Keyed_Storage<Node> = [];
 public $multiple Key_Storage_List<Node> = [];
 public $values vector<Node> = [];
 public $seen hash<int,Node> = [];
 public $parent weak<Node>;
}
$inline = new Inline();
$inline_values vector<Inline> = [$inline];
$root = new Node(); $child = new Node();
$child->parent = weak($root);
$children = $root->children; $children->append($child);
$named = $root->named; $named->add("child", $child);
$multiple = $root->multiple; $multiple->add("child", $child);
$values vector<Node> = [$child];
$seen hash<int,Node> = [$child => 9];
$retained = $children[0];
$parent Node = lock($retained->parent);
$members = $multiple->items();
$inline_value = $inline_values[0];
echo $parent->marker, ":", $retained->marker, ":", $seen[$values[0]], ":", $named->count(), ":", count($members), ":", $inline_value->value;
'''
modules = out / 'conservative-modules'
shutil.copytree(ROOT / 'runtime/metadata/modules', modules)
catalog = json.loads((modules / 'compiler.json').read_text())
for row in catalog['cpp_types']:
    if row['id'] == 'compiler.Storage':
        del row['arguments'][0]['layout']
(modules / 'compiler.json').write_text(json.dumps(catalog))
with CommandRunner(out / 'logs', ROOT) as runner:
    def check(mode):
        folder = out / mode
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(source)
        driver = folder / 'publish.php'
        driver.write_text((APP / 'tests/async_tasks_publish.php').read_text()
            .replace("dirname(__DIR__) . '/boot.php'", repr(str(APP / 'boot.php')))
            .replace('} finally { $publication->close(); }', '''
    $warm = CPP_Output::encode(Model::$cpp_files);
    $compiler->update_cpp([]);
    if (CPP_Output::encode(Model::$cpp_files) !== $warm) { throw new \\LogicException('Warm dependency replay changed output'); }
    Compiler_Lifecycle::reset_cpp();
    $compiler->cpp();
    if (CPP_Output::encode(Model::$cpp_files) !== $warm) { throw new \\LogicException('Fresh dependency replay changed output'); }
} finally { $publication->close(); }'''))
        runner.run(mode + '-publish', ['php8.5', driver, folder, ROOT / 'runtime/metadata/modules', 'compiler', mode])
        build = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
        assert runner.run(mode + '-run', [build['executable']]) == b'7:7:9:1:1:3'
    def native():
        runner.run('native-build', ['clang++-18', '-std=c++23', '-DSCPP_LANGUAGE_TARGET_PHP=1', '-I', ROOT / 'runtime/include', APP / 'tests/fixtures/layout_argument_native.cpp', '-o', out / 'native'], timeout=120)
        runner.run('native-run', [out / 'native'])
    def conservative():
        folder = out / 'conservative'
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text('class Node { public $children Storage<Node>; } $node = new Node();')
        runner.run('conservative', ['php8.5', APP / 'tests/async_tasks_publish.php', folder, modules, 'compiler', 'concrete'], expected=255)
        assert 'generated header cycle' in (out / 'logs/conservative.stderr').read_text()
    results = run_tasks([Task(mode, lambda mode=mode: check(mode)) for mode in ['ordinary', 'concrete']] + [
        Task('native', native), Task('conservative', conservative),
        Task('metadata', lambda: runner.run('metadata', ['php8.5', APP / 'tests/runtime_type_bindings.php']))])
(out / 'summary.json').write_text(json.dumps([{'name':r.name, 'error':r.error, 'seconds':r.seconds} for r in results], indent=2) + '\n')

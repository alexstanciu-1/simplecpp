"""Build and run the duplicate-key wrapper against an explicit native toolchain."""
import argparse
import json
from pathlib import Path
import subprocess

ROOT = Path(__file__).resolve().parents[2]
SOURCE = '''<?php
namespace scpp\\compiler;
final class Row { public int $value = 0; }
final class Proof {
    public static function run(): int {
        $first = new Row();
        $second = new Row();
        $groups /** Key_Storage_List<Row> */ = new Key_Storage_List();
        $groups->add('1', $first);
        $groups->add('01', $second);
        $groups->add('1', $first);
        $all /** vector<Row> */ = $groups->items();
        $named /** vector<Row> */ = $groups->named('1');
        $other /** vector<Row> */ = $groups->named('01');
        $missing /** vector<Row> */ = $groups->named('missing');
        if ((q_count($all) !== 3) || (q_count($named) !== 2) || (q_count($other) !== 1) || (q_count($missing) !== 0)) { return 1; }
        if (($all[0] !== $first) || ($all[1] !== $second) || ($all[2] !== $first) || ($named[1] !== $first) || ($other[0] !== $second)) { return 2; }
        $all[] = $second;
        $alias /** Key_Storage_List<Row> */ = $groups;
        $alias->add('last', $second);
        $after /** vector<Row> */ = $groups->items();
        if ((q_count($after) !== 4) || ($after[3] !== $second) || $groups->is_empty()) { return 3; }
        return 0;
    }
}
echo Proof::run(), "\\n";
'''


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--target-checkout', required=True, type=Path)
    parser.add_argument('--results', required=True, type=Path)
    args = parser.parse_args()
    results = args.results.resolve()
    results.mkdir(parents=True)
    source, project = results / 'source', results / 'phs'
    source.mkdir()
    (source / 'main.php').write_text(SOURCE)

    def run(name, command, cwd=ROOT):
        completed = subprocess.run(list(map(str, command)), cwd=cwd, capture_output=True, text=True)
        (results / (name + '.stdout')).write_text(completed.stdout)
        (results / (name + '.stderr')).write_text(completed.stderr)
        assert completed.returncode == 0, (name, completed.stderr)
        return completed.stdout

    tools = ROOT / 'tools/php_portability'
    run('convert', ['php', tools / 'convert.php', source, project])
    run('framework', ['php', tools / 'install_native_runtime.php', project])
    cli = args.target_checkout.resolve() / 'bin/scpp.php'
    run('init', ['php', cli, 'init', '--php-profile=strict'], project)
    config = json.loads((project / 'prism.json').read_text())
    config['runtime']['modules'] = ['compiler']
    (project / 'prism.json').write_text(json.dumps(config, indent=2) + '\n')
    run('build', ['php', cli, 'build', '--build-runtime'], project)
    assert run('execute', [project / '.prism/build/main']) == '0\n'
    print('Key_Storage_List native: typed construction, duplicates, ordering, identity, aliases and snapshots passed')


if __name__ == '__main__':
    main()

"""Explicit container method returns and nonpublic state preserve value and object semantics."""
import argparse
import json
from pathlib import Path
import subprocess

ROOT = Path(__file__).resolve().parents[2]
TOOLS = ROOT / 'tools/php_portability'


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--target-checkout', type=Path)
    parser.add_argument('--results', type=Path, required=True)
    args = parser.parse_args()
    results = args.results.resolve()
    results.mkdir(parents=True, exist_ok=False)
    source, output = results / 'php', results / 'phpp'
    source.mkdir()
    events = []

    def run(command, ok=True, cwd=ROOT):
        p = subprocess.run(list(map(str, command)), cwd=cwd, capture_output=True, text=True)
        events.append({'command': list(map(str, command)), 'exit': p.returncode,
                       'stdout': p.stdout, 'stderr': p.stderr})
        (results / 'commands.json').write_text(json.dumps(events, indent=2) + '\n')
        assert (p.returncode == 0) == ok, (command, p.stdout, p.stderr)
        return p.stdout

    bodies = {
        'store.php': r'''namespace samples;
class Row { public int $value = 1; }
trait Exports {
    public function get_ids(): array /** vector<int> */ { return $this->ids; }
    public function get_positions(): array /** hash<int, int> */ { return $this->positions; }
    public function get_groups(): array /** vector<vector<int>> */ { return $this->groups; }
    public function get_rows(): array /** vector<Row> */ { return $this->rows; }
}
class Store {
    use Exports;
    private int $entry = 0;
    protected bool $ready = false;
    private string $label = "initial";
    private array $ids /** vector<int> */ = [];
    private array $positions /** hash<int, int> */ = [];
    private array $groups /** vector<vector<int>> */ = [];
    private array $rows /** vector<Row> */ = [];
    public function initialize(Row $row): void {
        $this->entry = 37;
        $this->ready = true;
        $this->label = "prepared";
        $this->ids[] = 12;
        $this->ids[] = 37;
        $this->positions[37] = 1;
        $this->groups[] = $this->ids;
        $this->rows[] = $row;
    }
    public function get_entry(): int { return $this->entry; }
    public function get_ready(): bool { return $this->ready; }
    public function get_label(): string { return $this->label; }
    public static function empty_ids(): array /** vector<int> */ {
        $empty /** vector<int> */ = [];
        return $empty;
    }
}''',
        'main.php': r'''$store = new \samples\Store();
echo $store->get_entry(), ":", $store->get_ready() ? "ready" : "pending", ":", $store->get_label(), "\n";
$row = new \samples\Row();
$store->initialize($row);
$ids /** vector<int> */ = $store->get_ids();
$ids[0] = 99;
$original /** vector<int> */ = $store->get_ids();
$positions /** hash<int, int> */ = $store->get_positions();
$positions[37] = 8;
$original_positions /** hash<int, int> */ = $store->get_positions();
$groups /** vector<vector<int>> */ = $store->get_groups();
$groups[0][0] = 104;
$original_groups /** vector<vector<int>> */ = $store->get_groups();
echo $original[0], ":", $ids[0], ":", $original_positions[37], ":", $positions[37], ":", $original_groups[0][0], ":", $groups[0][0], "\n";
$rows /** vector<\samples\Row> */ = $store->get_rows();
$rows[0]->value = 9;
echo $row->value, ":", $store->get_entry(), ":", $store->get_ready() ? "ready" : "bad", ":", $store->get_label(), "\n";
$empty /** vector<int> */ = \samples\Store::empty_ids();
echo count($empty), "\n";
''',
    }
    for name, body in bodies.items():
        (source / name).write_text('<?php\n' + body + '\n')
    run(['php', TOOLS / 'sync_imports.php', source])
    run(['php', TOOLS / 'check.php', source])
    run(['php', TOOLS / 'convert.php', source, output])
    expected = '0:pending:initial\n12:99:1:8:12:104\n9:37:ready:prepared\n0\n'
    php = run(['php', '-r', 'foreach (array_slice($argv,1) as $path) { require $path; }',
               TOOLS / 'runtime/bootstrap.php', *[source / name for name in bodies]])
    assert php == expected, php
    run(['php', TOOLS / 'install_native_runtime.php', output])
    if args.target_checkout:
        target = json.loads((ROOT / 'compiler/tools/portability_target.json').read_text())
        checkout = args.target_checkout.resolve()
        assert run(['git', '-C', checkout, 'rev-parse', 'HEAD']).strip() == target['verified_commit']
        assert run(['git', '-C', checkout, 'status', '--porcelain']).strip() == ''
        cli = checkout / target['cli']
        run(['php', cli, 'init', '--php-profile=strict'], cwd=output)
        config_path = output / 'prism.json'
        config = json.loads(config_path.read_text())
        config['build']['cxx'] = 'clang++-18'
        config['runtime']['modules'] = []
        config_path.write_text(json.dumps(config, indent=2) + '\n')
        native = run(['php', cli, 'run', '--build-runtime'], cwd=output)
        assert native.endswith(expected), native
    (results / 'summary.json').write_text(json.dumps({'passed': True, 'native': bool(args.target_checkout),
                                                    'expected': expected,
                                                    'target_revision': target['verified_commit'] if args.target_checkout else None}, indent=2) + '\n')
    print('Container returns and private/protected scalar field proofs passed.')


if __name__ == '__main__':
    main()

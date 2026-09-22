"""PHP/native collection adapters against the immutable #231/#232 candidate."""
import argparse
import json
from pathlib import Path
import subprocess

ROOT = Path(__file__).resolve().parents[2]
TOOLS = ROOT / 'tools/php_portability'
REVISION = '08c8206aae914240d8b25fd2d1cd49c7149dd417'
BODY = r'''$input /** vector<int> */ = [10, 20, 30];
$selected /** vector<int> */ = sequence_filter($input, static function (int $x): bool { return $x > 10; });
foreach ($selected as $k => $v) { echo $k, ":", $v, "\n"; }
$keyed /** hash<int, int> */ = [];
$keyed[0] = 10; $keyed[1] = 20; $keyed[2] = 30;
$kept /** hash<int, int> */ = keyed_filter($keyed, function (int $x): bool { return $x > 10; });
foreach ($kept as $k => $v) { echo $k, ":", $v, "\n"; }
$suffix = "!";
$labels /** vector<string> */ = sequence_map($input, function (int $x) use ($suffix): string { return "item" . $suffix; });
echo $labels[0], ":", q_count($labels), "\n";
$words /** hash<string> */ = [];
$words["b"] = "two"; $words["04"] = "four";
$mapped /** hash<int> */ = keyed_map($words, function (string $x): int { return q_strlen($x); });
foreach ($mapped as $k => $v) { echo $k, ":", $v, "\n"; }
$empty /** vector<int> */ = [];
$none /** vector<int> */ = sequence_filter($input, function (int $x): bool { return false; });
$all /** vector<int> */ = sequence_filter($input, function (int $x): bool { return true; });
$zero /** vector<int> */ = sequence_map($empty, function (int $x): int { throw new \Exception("must not run"); });
echo q_count($none), ":", q_count($all), ":", q_count($zero), "\n";
$nested /** vector<int> */ = sequence_map(sequence_filter($input, function (int $x): bool { return $x > 10; }), function (int $x): int { return $x + 1; });
echo $nested[0], ":", $nested[1], "\n";
try { sequence_map($input, function (int $x): int { throw new \Exception("callback error"); }); }
catch (\Exception $error) { echo $error->getMessage(), "\n"; }
class Groups {
    private array $items /** vector<vector<int>> */ = [];
    public function initialize(): void { $row /** vector<int> */ = [42]; $this->items[] = $row; }
    public function first(): int { return $this->items[0][0]; }
}
$groups = new Groups(); $groups->initialize(); echo $groups->first(), "\n";
class Row { public int $value = 7; }
$row = new Row();
$rows /** vector<Row> */ = []; $rows[] = $row;
$kept_rows /** vector<Row> */ = sequence_filter($rows, function (Row $item): bool { return $item->value > 0; });
echo $kept_rows[0] === $row ? "shared" : "bad", "\n";
$kept_rows[] = new Row(); echo q_count($rows), ":", q_count($kept_rows), "\n";
$empty_hash /** hash<int,int> */ = [];
$empty_mapped /** hash<string,int> */ = keyed_map($empty_hash, function (int $x): string { throw new \Exception("must not run"); });
$all_keys /** hash<int,int> */ = keyed_filter($keyed, function (int $x): bool { return true; });
$no_keys /** hash<int,int> */ = keyed_filter($keyed, function (int $x): bool { return false; });
echo q_count($empty_mapped), ":", q_count($all_keys), ":", q_count($no_keys), "\n";

'''
EXPECTED = '0:20\n1:30\n1:20\n2:30\nitem!:3\nb:3\n04:4\n0:3:0\n21:31\ncallback error\n42\nshared\n1:2\n0:3:0\n'


def main():
    p = argparse.ArgumentParser(description=__doc__)
    p.add_argument('--results', type=Path, required=True)
    p.add_argument('--target-checkout', type=Path, required=True)
    p.add_argument('--candidate-revision', help='Full immutable revision for pre-adoption validation')
    args = p.parse_args()
    revision = args.candidate_revision or REVISION
    if len(revision) != 40 or any(c not in '0123456789abcdef' for c in revision):
        p.error('Candidate revision must be a full lowercase commit hash')
    out = args.results.resolve(); out.mkdir(parents=True, exist_ok=False)
    source = out / 'php'; source.mkdir()
    project = out / 'phpp'
    events = []
    def run(command, ok=True, cwd=ROOT):
        result = subprocess.run(list(map(str, command)), cwd=cwd, capture_output=True, text=True)
        events.append(dict(command=list(map(str,command)), exit=result.returncode, stdout=result.stdout, stderr=result.stderr))
        (out/'commands.json').write_text(json.dumps(events,indent=2)+'\n')
        assert (result.returncode == 0) == ok, (command,result.stdout,result.stderr)
        return result.stdout
    checkout = args.target_checkout.resolve()
    assert run(['git','-C',checkout,'rev-parse','HEAD']).strip() == revision
    assert not run(['git','-C',checkout,'status','--porcelain']).strip()
    path = source/'main.php'
    def convert(body, ok=True):
        path.write_text('<?php\ndeclare(strict_types=1);\n'+body)
        run(['php',TOOLS/'sync_imports.php',source])
        return run(['php',TOOLS/'convert.php',source,project],ok=ok)
    convert(BODY)
    assert run(['php','-r','require $argv[1]; require $argv[2];',TOOLS/'runtime/bootstrap.php',path]) == EXPECTED
    run(['php',TOOLS/'install_native_runtime.php',project])
    cli = checkout/'bin/scpp.php'
    run(['php',cli,'init','--php-profile=strict'],cwd=project)
    config_path = project/'prism.json'; config=json.loads(config_path.read_text())
    config['build']['cxx']='clang++-18';config['runtime']['modules']=[]
    config_path.write_text(json.dumps(config,indent=2)+'\n')
    assert run(['php',cli,'run','--build-runtime'],cwd=project).endswith(EXPECTED)
    for body in [
        '$v /** hash<int,int> */ = []; sequence_map($v, function (int $x): int { return $x; });',
        '$v /** vector<int> */ = []; keyed_filter($v, function (int $x): bool { return true; });',
        '$v /** vector<int> */ = []; sequence_filter($v, function (int $x): int { return $x; });',
        '$v /** vector<int> */ = []; sequence_map($v, function (string $x): string { return $x; });',
    ]:
        convert(body);run(['php',cli,'build','--build-runtime'],cwd=project,ok=False)
    for callback in ['function ($x): int { return 1; }','function (int &$x): int { return $x; }',
                     'function (int $x = 1): int { return $x; }','function (int $x) use (&$n): int { return $x; }',
                     'function (int $x): void {}','fn(int $x): int => $x']:
        convert('$n = 1; $v /** vector<int> */ = []; sequence_map($v, '+callback+');',ok=False)
    convert(BODY)
    assert not run(['git','-C',checkout,'status','--porcelain']).strip()
    (out/'summary.json').write_text(json.dumps(dict(passed=True,target_revision=revision,expected=EXPECTED),indent=2)+'\n')
    print('Collection parity, carrier/signature rejection and chained field read passed.')

if __name__ == '__main__': main()

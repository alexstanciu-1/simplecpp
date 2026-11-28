"""PHP diagnostic output through the published structural Encoder, with JSON optional."""
from pathlib import Path
import json
import subprocess
from tempfile import TemporaryDirectory

from app_namespace_native import service, invoke, artifacts, write_build


with TemporaryDirectory() as directory:
    work = Path(directory)
    app, out = work / 'app', work / 'out'
    app.mkdir()
    (app / 'module.json').write_text('{"dependencies":{"runtime":{"modules":["numeric"]}}}')
    model = app / 'model.phs'
    source = ('struct Plain { public int $x; }data struct Point { public int $x; }data struct Snapshot { public int $count; }'
              'data struct Boxes { public mixed $m; public dynamic $d; }'
              'data class Node { public int $id; public ?Node $next=null; }'
              'data struct Pair { public Node $a; public Node $b; public vector<int> $xs; }')
    model.write_text(source)
    (app / 'unsigned.phs').write_text('function show_unsigned(uint64 $v): void { var_dump($v); }')
    entry = app / 'main.phs'
    entry.write_text(r'''
function showSnapshot(Snapshot $v): void { var_dump($v); }

function forward_unsigned(uint64 $v): void { show_unsigned($v); }
function once(): int { echo "!"; return 7; }

var_dump([]); var_dump(once()); var_dump(null); var_dump(false); var_dump("a\0b"); var_dump(1.25);
$x ?int=null; var_dump($x); $x=0; var_dump($x);
$m mixed=["hello"=>false]; var_dump($m);
$d dynamic={a:2}; var_dump($d);
$v vector<int>=[3]; var_dump($v);
$pt Point={x:5}; $points vector<Point>=[$pt]; var_dump($points);
$table hash<Point,string>=[]; $table["z"]=$pt; $pt->x=9; $table["01"]=$pt; var_dump($table);
$numbers hash<int,int>=[]; $numbers[19]=7; var_dump($numbers);
$boxes Boxes={m:$m,d:$d}; var_dump($boxes);
$wrapped ?vector<string>=null; var_dump($wrapped); $words vector<string>=["ok"]; $wrapped=$words; var_dump($wrapped);
$n Node={id:4}; $n->next=$n;
$p Pair={a:$n,b:$n,xs:[8]}; var_dump($p);
$n->next=null;
return 0;
''')
    proc = service(app, out)
    try:
        def native():
            manifest = json.loads((out / 'manifest.json').read_text())
            assert all('scpp_json' not in item for item in manifest['link_inputs'])
            units = [out / unit for unit in manifest['translation_units']]
            header = next(out / h for h in manifest['headers'] if 'show_unsigned_' in (out / h).read_text())
            observer = work / 'observer.cpp'
            entry = out / 'entry.cpp'
            observer.write_text(f'#include "{header}"\n#include <limits>\n'
                                'namespace App { int scpp_entry_body(); }\n'
                                'int main() { App::show_unsigned_(std::numeric_limits<scpp::uint64>::max()); '
                                'return App::scpp_entry_body(); }')
            units = [observer if unit == entry else unit for unit in units]
            subprocess.run(write_build(work, units, optimization='O0'), cwd=work, check=True)
            return subprocess.check_output([work/'program'])
        result = invoke(proc)
        assert 'error' not in result, result
        node = b'  object(Node)(2) {\n    ["id"]=>\n    int(4)\n    ["next"]=>\n    *RECURSION*\n  }\n'
        expected = (b'int(18446744073709551615)\narray(0) {\n}\n!int(7)\nNULL\nbool(false)\nstring(3) "a\0b"\nfloat(1.25)\nNULL\nint(0)\n'
                    b'array(1) {\n  ["hello"]=>\n  bool(false)\n}\n'
                    b'object(dynamic)(1) {\n  ["a"]=>\n  int(2)\n}\narray(1) {\n  [0]=>\n  int(3)\n}\n'
                    b'array(1) {\n  [0]=>\n  object(Point)(1) {\n    ["x"]=>\n    int(5)\n  }\n}\n'
                    b'array(2) {\n  ["z"]=>\n  object(Point)(1) {\n    ["x"]=>\n    int(5)\n  }\n'
                    b'  ["01"]=>\n  object(Point)(1) {\n    ["x"]=>\n    int(9)\n  }\n}\n'
                    b'array(1) {\n  [19]=>\n  int(7)\n}\n'
                    b'object(Boxes)(2) {\n  ["m"]=>\n  array(1) {\n    ["hello"]=>\n    bool(false)\n  }\n'
                    b'  ["d"]=>\n  object(dynamic)(1) {\n    ["a"]=>\n    int(2)\n  }\n}\n'
                    b'NULL\narray(1) {\n  [0]=>\n  string(2) "ok"\n}\n'
                    b'object(Pair)(3) {\n  ["a"]=>\n' + node + b'  ["b"]=>\n' + node +
                    b'  ["xs"]=>\n  array(1) {\n    [0]=>\n    int(8)\n  }\n}\n')
        actual = native()
        assert actual == expected, actual
        baseline = artifacts(out)
        model.write_text(source.replace('int $count;', 'Plain $count;'))
        error = invoke(proc).get('error', '')
        assert 'No var_dump encoder for Snapshot::$count' in error, error
        model.write_text(source)
        assert 'error' not in invoke(proc)
        assert artifacts(out) == baseline
        fresh = work / 'fresh'
        clean = service(app, fresh)
        try:
            assert 'error' not in invoke(clean) and artifacts(fresh) == baseline
        finally:
            clean.stdin.close()
            clean.wait()
    finally:
        proc.stdin.close()
        proc.wait()

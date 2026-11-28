"""PHP indexed reads use the provider's exact key domain and yield one value per lookup."""
import json
from pathlib import Path
import subprocess
import tempfile

from app_namespace_native import invoke, service
from native_build import write_build


source = '''
function rhs(int $n):int {echo "S";return $n;}
function numbers():vector<string> { echo "V"; return ["vector"]; }
function labels():hash<int,string> { echo "H"; $h hash<int,string>=["05"=>37]; return $h; }
function pos(int $i):int { echo "I"; return $i; }
function rowPos(int $i):int { echo "R"; return $i; }
function keyText(string $s):string { echo "K"; return $s; }
$rows fixed_array<fixed_array<byte,1>,1> = [[29]];
if(cli_argc()===2){ $v=numbers()[pos(1)]; return 99; }
if(cli_argc()===3){ $h=labels()[keyText("5")]; return 99; }
if(cli_argc()===4){ $b=$rows[rowPos(1)][pos(0)]; return 99; }
if(cli_argc()===5){ $b=$rows[rowPos(0)][pos(1)]; return 99; }
$values vector<int>=[1];
if(cli_argc()===6){$values[pos(1)]=rhs(9);return 99;}
$values[pos(0)]=rhs(8);
$table hash<int,string>=[];
$table[keyText("05")]=rhs(5);
$table[keyText("5")]=rhs(7);
$table[keyText("05")]=rhs(9);
if($values[0]!==8 || count($table)!==2 || $table["05"]!==9 || $table["5"]!==7){return 96;}
$v string=numbers()[pos(0)];
$h int=labels()[keyText("05")];
$b byte=$rows[rowPos(0)][pos(0)];
function tracedKey(string $mark,string $key):string {echo $mark;return $key;}
struct Item {public int $n; public function __construct(int $n){$this->n=$n;}}
$left Item(5); $right Item(7);
$items vector<Item>=[$left]; $items[rowPos(0)]=$right;
$named hash<Item,string>=[tracedKey("A","05")=>$left,tracedKey("B","5")=>$right];
$named[tracedKey("W","5")]=$items[pos(0)];
$found=$named[tracedKey("C","05")] ?? throw new Exception("missing");
if($found->n!==5 || $named[tracedKey("D","5")]->n!==7){return 97;}
if($v!=="vector" || $h!==37 || (int)$b!==29){return 98;}
return 0;
'''

with tempfile.TemporaryDirectory(prefix='indexed-entry-reads-') as tmp:
    work = Path(tmp)
    app, out = work / 'app', work / 'out'
    app.mkdir()
    (app / 'module.json').write_text('{}')
    main = app / 'main.phs'
    compiler = service(app, out)
    try:
        for declaration, message in [
            ('$a vector<int>=[0];', 'Vector index requires int64'),
            ('$a hash<int,string>=[];', 'Hash key requires string'),
            ('$a fixed_array<byte,1>=[0];', 'Fixed-array index requires int64'),
        ]:
            main.write_text(declaration + '\nreturn (int)$a[false];')
            result = invoke(compiler)
            assert f'main.phs:2:16: {message}' in result.get('error', ''), result
        main.write_text(source)
        result = invoke(compiler)
        assert 'error' not in result, result
    finally:
        compiler.stdin.close()
        compiler.wait()

    manifest = json.loads((out / 'manifest.json').read_text())
    units = [out / name for name in manifest['translation_units']]
    subprocess.run(write_build(work, units), cwd=work, check=True)
    for argc, trace, error in [(1, b'IS' + b'KS' * 3 + b'VIHKRIRABWICD', b''), (2, b'VI', b'sequence_index_out_of_range\n'),
                               (3, b'HK', b'hash_missing_key\n'), (4, b'R', b'sequence_index_out_of_range\n'),
                               (5, b'RI', b'sequence_index_out_of_range\n'),
                               (6, b'IS', b'sequence_index_out_of_range\n')]:
        result = subprocess.run([work / 'program', *(['x'] * (argc - 1))], capture_output=True)
        assert (result.returncode, result.stdout, result.stderr) == (int(bool(error)), trace, error), result

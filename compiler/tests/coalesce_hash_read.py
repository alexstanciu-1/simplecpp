"""Direct PHP hash coalescing owns the selected value and evaluates only one arm."""
import json
from pathlib import Path
import subprocess
import sys
from tempfile import TemporaryDirectory

from app_namespace_native import ROOT, invoke, service

source = '''
function keyName(string $name):string { echo "K"; return $name; }
function fallback(hash<int,string> &$h):int {
    echo "F";
    $h=["miss"=>91];
    return 42;
}
function readNumber(hash<int,string> &$h,string $name):int {
    return $h[keyName($name)] ?? fallback($h);
}
class Person { public int $id=7; }
function other():Person { echo "P"; return new Person(); }
class Holder { public hash<int,string> $items; }
function switchHolder(Holder &$h):string { $h=new Holder(); echo "K"; return "k"; }
struct Row { public int $n; public function __construct(int $n){$this->n=$n;} }
function makeRows(Row $r):hash<Row,string> {echo "R"; $rows hash<Row,string>=["a"=>$r];return $rows;}
function people():hash<?Person,string> {
    $h hash<?Person,string>=[]; $h["nil"]=null; $h["live"]=new Person(); return $h;
}
$h hash<int,string>=["hit"=>0];
echo readNumber($h,"hit"),count($h),readNumber($h,"miss"),$h["miss"];
$s hash<string,int>=[5=>""]; echo "[",$s[5] ?? throw new Exception("hit"),"]";
$b hash<bool,string>=["x"=>false]; echo ($b["x"] ?? true) ? "T" : "F";
echo ($b["missing"] ?? true) ? "T" : "F", count($b);
$p=people();
$a=$p["live"] ?? other(); $a->id=8;
echo $p["live"]->id;
$c=$p["nil"] ?? other(); echo $c->id;
$d=$p["missing"] ?? other(); echo $d->id,count($p);
$holder=new Holder(); $holder->items["k"]=13;
echo $holder->items[switchHolder($holder)] ?? 99;
$row Row(5); $r=makeRows($row)[keyName("a")] ?? throw new Exception("lost"); echo $r->n;
try {$u=$s[9] ?? throw new Exception("M");} catch(Exception $e) {echo $e->get_message();}
'''
with TemporaryDirectory(prefix='scpp-coalescing-read-') as directory:
    work = Path(directory)
    app, out = work/'app', work/'out'
    app.mkdir()
    (app/'module.json').write_text('{}')
    (app/'main.php').write_text(source)
    proc = service(app, out)
    try:
        got = invoke(proc)
        assert 'error' not in got, got
        manifest = json.loads((out/'manifest.json').read_text())
        units = [out/u for u in manifest['translation_units']]
        cpp = '\n'.join(u.read_text() for u in units)
        read = next(line for line in cpp.splitlines() if '.lookup_entry(' in line and 'keyName_' in line)
        assert read.count('.lookup_entry(') == read.count('keyName_(') == read.count('fallback_(') == 1, read
        assert '.has(' not in read and '.at(' not in read and read.index('entry.found') < read.index('fallback_('), read
        definition = (ROOT/'runtime/generated/src/hash.cpp').read_text().split('hash_int64_string::lookup_entry(',1)[1].split('\n}',1)[0]
        assert definition.count('storage_->find(key)') == 1
        subprocess.run([sys.argv[1], '-std=c++23', '-stdlib=libc++', '-I', ROOT/'runtime/generated/public',
                        *units, *manifest['link_inputs'], '-o', work/'run'], check=True)
        assert (actual := subprocess.check_output([work/'run'])) == b'K01KF4291[]FT18P7P72K13RK5M', actual
    finally:
        proc.stdin.close()
        proc.wait()

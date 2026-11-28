"""Typed collection helper execution, source visibility and retained edit recovery."""
import json
import subprocess
import sys
import tempfile
from pathlib import Path

from app_namespace_native import artifacts, invoke, service

root = Path(__file__).resolve().parents[2]
cxx, linker, runtime = sys.argv[1:]
main_source = r'''
use function sequence_map as labels;
$v vector<int> = [1,2,3];
$native = array_map(label(...),$v);
$gaps = array_filter($v,fn($x)=>$x!==2);
if($native[1]!=="two" || count($gaps)!==2 || $gaps[2]!==3 || !in_array("two",$native,true)){return 20;}
$truth vector<string>=["","0","ok"]; $keptTruth=array_filter($truth);
if(count($keptTruth)!==1 || $keptTruth[2]!=="ok"){return 21;}
$names = labels($v, label(...));
if (count($names)!==3 || $names[1]!=="two") {return 1;}
$seq = sequence_filter($v, fn($x)=>$x!==2);
if (count($seq)!==2 || $seq[1]!==3) {return 2;}
$h hash<int,int> = [];
$h[19]=1; $h[2]=2; $h[8]=3;
$phpWords=array_map(label(...),$h);
$phpKept=array_filter($phpWords,fn($s)=>$s==="two");
if(count($phpKept)!==1 || $phpKept[2]!=="two" || in_array("absent",$phpKept)){return 22;}
$words = keyed_map($h,label(...));
$kept = keyed_filter($words, fn($x)=>$x!=="two");
foreach ($kept as $k=>$word) {echo $k,";";}
$suffix="!";
$op function<string(int)> = fn($x)=>label($x).$suffix;
$owned=collection_map($v,$op);
if ($owned[1]!=="two!") {return 3;}
$none function<bool(int)>;
$empty vector<int> = [];
$r=collection_filter($empty,$none);
if (count($r)!==0) {return 4;}
$boxes=collection_map($v,make_box(...));
$keptBoxes=collection_filter($boxes,fn($box)=>$box->n===2);
if(count($keptBoxes)!==1 || $keptBoxes[0]->n!==2){return 5;}
$bag = new Bag(); $bag->numbers = $v;
$calls=0;
$changed = collection_map($bag->numbers, function(int $n) use (&$bag,&$calls):int {
    $calls++; $bag = new Bag(); return $n;
});
if ($calls!==3 || $changed[2]!==3 || count($bag->numbers)!==0) {return 6;}
try { $seq = collection_map($v,may_throw(...)); return 7; }
catch (Exception $e) { if (count($seq)!==2 || $seq[1]!==3) { return 8; } }
$stringKeys hash<int,string> = [];
$stringKeys["05"] = 1; $stringKeys["5"] = 2;
$typed = collection_map($stringKeys,fn($x):int=>$x+10);
if(count($typed)!==2 || $typed["05"]!==11 || $typed["5"]!==12){return 9;}
$table mixed = []; $table[19]=1; $table["a\0b"]=2; $table[8]=3;
$mapped = collection_map($table,fn(mixed $x):mixed=>$x+1);
$m = collection_filter($mapped,fn(mixed $x):bool=>(int)$x!==3);
if(count($m)!==2 || (int)$m[19]!==2 || (int)$m[8]!==4 || (int)$mapped["a\0b"]!==3){return 10;}
foreach ($mapped as $k=>$value) {echo (int)$value,";";}
$mixInner mixed = []; $mixInner["n"]=1; $table["inner"]=$mixInner;
$mcopy=collection_map($table,fn(mixed $x):mixed=>$x);
$child=$mcopy["inner"]; $child["n"]=9;
$sourceChild=$table["inner"];
if((int)$sourceChild["n"]!==1){return 13;}
$d dynamic = {}; $inner dynamic = {}; $inner["n"]=1;
$d["inner"]=$inner;
$copy=collection_map($d,fn(dynamic $x):dynamic=>$x);
$keep=collection_filter($copy,fn(dynamic $x):bool=>true);
$nested=$keep["inner"]; $nested["n"]=9;
$keep->clear();
if(count($d)!==1 || count($copy)!==1 || (int)$inner["n"]!==9){return 11;}
$noneBox function<bool(dynamic)>;
$blank dynamic = {}; $noneKept=collection_filter($blank,$noneBox);
if(count($noneKept)!==0){return 12;}
return 0;
'''
models = '''class Bag { public vector<int> $numbers; }
function void_op(int $n):void {}
function may_throw(int $n):int {if($n===2){throw new Exception("stop");} return $n;}
struct Box { public int $n; public function __construct(int $n){$this->n=$n;} }
function make_box(int $n): Box {$b Box($n);return $b;}
function label(int $x):string {return $x===2 ? "two":"other";}
'''


def fresh(app, output):
    child = service(app, output)
    try:
        return invoke(child)
    finally:
        child.stdin.close()
        child.wait()


with tempfile.TemporaryDirectory(prefix='collection-transforms-') as tmp:
    work = Path(tmp)
    app, out = work / 'app', work / 'out'
    app.mkdir()
    manifest = app / 'module.json'
    manifest.write_text('{"general_api":true}')
    main = app / 'main.php'
    main.write_text(main_source)
    model = app / 'model.php'
    model.write_text(models)
    resident = service(app, out)
    try:
        def check(error=None):
            result = invoke(resident)
            if error:
                assert error.lower() in result.get('error', '').lower(), result
            else:
                assert 'error' not in result, result

        check()
        baseline = artifacts(out)
        assert any(b'iteration_next' in code and b'&label_' in code for code in baseline.values())
        bundle = json.loads((out / 'manifest.json').read_text())
        binary = work / 'run'
        subprocess.run([cxx, '-std=c++23', '-stdlib=libc++', f'--ld-path={linker}', '-I', root/'runtime/generated/public',
                        *(out/unit for unit in bundle['translation_units']), runtime, '-o', binary], check=True)
        result = subprocess.run([binary], capture_output=True, check=True)
        assert result.stdout == b'19;8;2;3;4;', result.stdout
        manifest.write_text('{}')
        check('sequence_map')
        manifest.write_text('{"general_api":true}')
        for expression, error in [('keyed_map($v,label(...))', 'Unsupported collection'),
                                  ('sequence_filter($v,label(...))', 'Invalid callback'),
                                  ('collection_map($v,fn(string $x):int=>1)', 'Invalid callback'),
                                  ('collection_filter($v,fn(int &$x):bool=>true)', 'Invalid callback'),
                                  ('sequence_map($v,fn(int $x=0):int=>$x)', 'Invalid callback'),
                                  ('sequence_map($v,fn($x)=>$x)', 'explicit result type'),
                                  ('collection_map($v,void_op(...))', 'Invalid callback'),
                                  ('collection_map($b,fn(mixed $x):int=>1)', 'Invalid callback'),
                                  ('collection_filter($b,fn(mixed $x):mixed=>$x)', 'Invalid callback'),
                                  ('sequence_map($b,fn(mixed $x):mixed=>$x)', 'Unsupported collection'),
                                  ('keyed_map($b,fn(mixed $x):mixed=>$x)', 'Unsupported collection')]:
            main.write_text('$v vector<int> = [1]; $b mixed = []; '+expression+'; return 0;')
            check(error)
        main.write_text(main_source)
        model.write_text(models.replace('label(int $x):string', 'label(int $x):int').replace('$x===2 ? "two":"other"', '$x'))
        check('matching comparable scalar needle')
        model.write_text(models)
        check()
        assert 'error' not in fresh(app, work/'fresh')
        assert artifacts(work/'fresh') == artifacts(out) == baseline
    finally:
        resident.stdin.close()
        resident.wait()

    # Keep the caller identical across flavors by obtaining a typed callable from a source function.
    for flavor in ('cpp', 'js', 'py'):
        app = work / flavor
        app.mkdir()
        (app / 'calls.php').write_text('function values():vector<int>{return [1];} '
                                       'function op():function<int(int)>{return fn($n)=>$n+1;} '
                                       'function boxed():mixed{$x mixed=[]; return $x;} '
                                       'function identity():function<mixed(mixed)>{return fn($x)=>$x;}')
        terminator = '' if flavor == 'py' else ';'
        (app / ('main.' + flavor)).write_text('return count(sequence_map(values(),op())) + count(collection_map(boxed(),identity()))' + terminator)
        settings = app / 'module.json'
        settings.write_text('{}')
        result = fresh(app, work / (flavor + '-out'))
        if flavor != 'cpp':
            assert 'sequence_map' in result.get('error', ''), result
            settings.write_text('{"general_api":true}')
            result = fresh(app, work / (flavor + '-out'))
        assert 'error' not in result, result

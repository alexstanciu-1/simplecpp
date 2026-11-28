"""Run isolated PHP-flavor probes. Expectations record observations at the audited revision."""
import argparse
from dataclasses import dataclass
import json
import os
from pathlib import Path
import subprocess
import tempfile

from check_apps import build


@dataclass
class Probe:
    name: str
    code: str
    output: str = ''
    reject: str = ''
    runtime_error: str = ''
    modules: tuple = ()
    general: bool = False


probes = [
    Probe('opening-tag', '<?php\necho "hello"; return 0;', output='hello'),
    Probe('nullable-zero', '$limit ?int=0; echo $limit ?? 20; return 0;', '0'),
    Probe('result-false-payload', '''$r=json_decode("false");
if (!isset($r)) { return 1; } $value dynamic=$r;
$enabled bool=$value; if (!$enabled) { echo "present false"; } return 0;''',
          'present false', modules=('json',)),
    Probe('json-associative', '$r=json_decode("{}", true); return 0;',
          reject='Argument count mismatch', modules=('json',)),
    Probe('json-disabled', '$r=json_decode("1"); return 0;', reject='requires module runtime-json'),
    Probe('query-global', '$page=$_GET["page"] ?? "1"; echo $page; return 0;', reject='_GET'),
    Probe('empty-string', '''function observed(string $s, int &$calls): string { $calls += 1; return $s; }
$calls=0;
$words vector<string> = ["", "0", " ", "a\\0b"];
foreach ($words as $s) {
    $m mixed=$s; $d dynamic=$s;
    echo (int)empty(observed($s,$calls)), (int)empty($m), (int)empty($d);
}
echo "|", $calls; return 0;''', '111000000000|4'),
    Probe('empty-boxed-number', '$n mixed=0; echo (int)empty($n); return 0;', runtime_error='invalid_empty_operand'),
    Probe('empty-boxed-null', '$n dynamic=null; echo (int)empty($n); return 0;', runtime_error='invalid_empty_operand'),
    Probe('empty-boxed-table', '$v mixed=[]; echo (int)empty($v); $v[]=null; echo (int)empty($v); return 0;', '10'),
    Probe('count-string', 'echo count("abc"); return 0;', reject='count requires a type that supports counting'),
    Probe('empty-number', '$n=0; if (empty($n)) { return 1; } return 0;',
          reject='empty requires a string or collection'),
    Probe('echo-boxed', '$h=[]; $h["jobs"]=1; echo $h["jobs"]; return 0;',
          reject='Echo requires a printable scalar or Stringable value; received mixed'),
    Probe('extract-boxed', '$h=[]; $h["jobs"]=1; $n int=$h["jobs"]; echo $n; return 0;', '1'),
    Probe('copies', '''$a mixed=["n"=>1]; $b=$a; $b["n"]=2; $x int=$a["n"]; echo $x;
$c dynamic=["n"=>1]; $d=$c; $d["n"]=2; $y int=$c["n"]; echo $y; return 0;''', '12'),
    Probe('box-cross', '$x mixed=3; $d dynamic=$x; return 0;', reject='Unsupported boxed conversion'),
    Probe('strict-sentinel-hit', '$p=strpos("abc","a"); if ($p !== false) { $n int=$p; echo $n; } return 0;', '0'),
    Probe('strict-sentinel-miss', '$p=strpos("abc","x"); if ($p !== false) { echo "hit"; } return 0;', output=''),
    Probe('strict-sentinel-file', '$r=file_get_contents("missing"); if ($r === false) { echo "missing"; } return 0;',
          modules=('filesystem',), output='missing'),
    Probe('guard-search', '''$words vector<string> = ["a","x"]; foreach ($words as $word) { $p=strpos("abc",$word);
if (isset($p)) { $n int=$p; echo $n; } else { echo "miss"; } } return 0;''', '0miss'),
    Probe('hash-presence', '''function insert_key(hash<int,string> &$h,int &$calls):string {
    $calls+=1; $h["paid"]=0; return "paid";
}
$h hash<int,string> = []; $calls=0;
echo (int)isset($h["missing"]), count($h), (int)isset($h[insert_key($h,$calls)]), $calls, count($h);
$labels hash<string,int> = [7=>""]; echo (int)isset($labels[7]), (int)isset($labels[8]);
return 0;''', '0011110'),
    Probe('hash-nullable-presence', '''class User {public int $id=1;}
$users hash<?User,string> = []; $users["absent"]=null; $users["live"]=new User();
echo (int)isset($users["missing"]), (int)isset($users["absent"]), (int)isset($users["live"]), count($users);
return 0;''', '0012'),
    Probe('hash-helper', '$h hash<int,string> =["x"=>0]; echo (int)hash_contains($h,"x"); return 0;', '1'),
    Probe('boxed-foreach-key', '''$a=cli_args(); foreach ($a as $i=>$arg) {
if ($i === 0) { echo "first"; } } return 0;''', reject='Unsupported boxed binary'),
    Probe('form-int', '$n=(int)"42"; echo $n; return 0;', output='42'),
    Probe('adjacent-generics', '$v vector<int>=[]; return 0;', output=''),
    Probe('mixed-list-context', '''foreach (["a","b"] as $word) { echo strlen($word); } return 0;''', output='11'),
    Probe('payment-division', 'echo (string)(7 / 2), "|", (string)(7 / 2.0); return 0;', output='3|3.5'),
    Probe('form-truth', '''$fields vector<string> = ["","0","00","false","1"];
foreach ($fields as $f) { echo ($f ? "Y" : "N"); } return 0;''', output='NNYYY'),
    Probe('loose-form-id', 'echo (int)("01" == "1"), (int)("10" < "2"); return 0;', output='01'),
    Probe('compare-user-input', '$input="2"; echo (int)($input == 2); return 0;', reject='Unsupported binary operands or operator'),
    Probe('truthy-default', '$label="0"; echo $label ?: "anon"; return 0;', output='anon'),
    Probe('nullable-update', '$n ?int=null; $n ??= 0; $n ??= 2; echo $n ?? 9; return 0;', output='0'),
    Probe('map-default', '$counts hash<int,string> = []; echo $counts["missing"] ?? 0; return 0;', output='0'),
    Probe('map-init-key', '$counts hash<int,string> = []; $counts["x"] ??= 0; $counts["x"] ??= 9; echo $counts["x"]; return 0;', output='0'),
    Probe('map-find', '$h hash<int,string> = ["yes"=>0]; echo hash_find($h,"yes") ?? 9, hash_find($h,"no") ?? 9; return 0;', output='09'),
    Probe('keys-stay-strings', '''$h hash<int,string> = ["01"=>3, "1"=>4];
foreach ($h as $k=>$n) { echo $k, "=", $n, "|"; } return 0;''', output='01=3|1=4|'),
    Probe('missing-vector-pos', '$v vector<int> = [10]; echo $v[2]; return 0;', runtime_error='sequence_index_out_of_range'),
    Probe('unset-index', '$v vector<int> = [0,1,2]; unset($v[1]); echo count($v); return 0;', reject='Expected ) after a single whole-variable unset operand'),
    Probe('remove-index', '$v vector<int> = [0,1,2]; $v->remove(1); echo $v[1],count($v); return 0;', output='22'),
    Probe('mutate-foreach', '''$v vector<int> = [10,20]; foreach ($v as &$amount) { $amount+=1; }
echo $v[0],":",$v[1]; return 0;''', output='11:21'),
    Probe('lingering-ref', '''$v vector<int> = [10,20]; foreach ($v as &$amount) { $amount+=1; }
$amount=900; echo $v[1]; return 0;''', output='21'),
    Probe('mutate-argument', '''function adjust(int &$fee):void { $fee+=1; }
$v vector<int> = [10]; adjust($v[0]); echo $v[0]; return 0;''', reject='Reference argument requires stable variable storage: a named local or parameter'),
    Probe('mutate-collection-argument', '''function adjust(vector<int> &$fees):void { $fees[0]+=1; }
$v vector<int> = [10]; adjust($v); echo $v[0]; return 0;''', output='11'),
    Probe('copy-collection-argument', '''function adjust(vector<int> $fees):void { $fees[0]+=1; }
$v vector<int> = [10]; adjust($v); echo $v[0]; return 0;''', output='10'),
    Probe('scope-after-foreach', '$v vector<int> = [4,5]; foreach ($v as $n) {} echo $n; return 0;', reject='Unknown or not-yet-initialized local/global: n'),
    Probe('typed-empty-loop', '''$n=9; $v vector<int> = []; foreach ($v as $n) {} echo $n; return 0;''', output='9'),
    Probe('eager-fallback', '''function fallback(int &$calls): int { $calls+=1; return 8; }
$n ?int=0; $calls=0; echo $n ?? fallback($calls), $calls; return 0;''', output='00'),
    Probe('template-fields', '$row mixed=["name"=>"Ada"]; echo "Hello {$row[\'name\']}"; return 0;', output='Hello Ada'),
    Probe('template-local', '$name="Ada"; $ok=true; echo "Hello $name {$ok}"; return 0;', output='Hello Ada true'),
    Probe('bool-output', 'echo true,"/",false; return 0;', output='1/'),
    Probe('typed-ternary', '$ok=true; $v=$ok ? 404 : "absent"; return 0;', reject='Ternary result types must be identical'),
    Probe('underspecified-function', 'function greet($name) { return $name; } echo greet("Ada"); return 0;', reject='Expected explicit parameter type before parameter name'),
    Probe('named-user-args', '''function label(string $prefix,int $n=1):string {return "$prefix$n";}
echo label(n: 2, prefix: "p"); return 0;''', output='p2'),
    Probe('unpack-for-sum', '''function sum(int ...$items):int { $s=0; foreach ($items as $n) { $s+=$n; } return $s; }
$v vector<int> = [4,5]; echo sum(...$v),":",sum(); return 0;''', output='9:0'),
    Probe('branch-local', 'if (true) { $message="ok"; } echo $message; return 0;', reject='Unknown or not-yet-initialized local/global: message'),
    Probe('closure-capture', '$rate=2; $fee=fn(int $n):int => $rate*$n; $rate=4; echo $fee(3); return 0;', output='6'),
    Probe('match-default', '$method="PATCH"; $s=match($method) {"POST"=>201,default=>405}; echo $s; return 0;', output='405'),
    Probe('array-map', '$v vector<int> = [1,2]; $r=array_map(fn(int $n):int=>$n+1,$v); echo $r[0],$r[1]; return 0;', output='23'),
    Probe('map-helper', '$v vector<int> = [1,2]; $r=collection_map($v,fn(int $n):int=>$n+1); echo $r[0],$r[1]; return 0;', general=True, output='23'),
    Probe('byte-label', '$s="é!"; echo strlen($s),":",strpos($s,"!")+0,":",substr($s,0,2); return 0;', output='3:2:é'),
    Probe('byte-index', '$s="ABC"; echo $s[0]; return 0;', output='65'),
    Probe('replace-count', '$n=90; echo str_replace("-","/","a-b-c",$n),"|",$n,"|",str_replace("z","/","a",$n),"|",$n; return 0;', output='a/b/c|2|a|0'),
    Probe('empty-getenv', '$s=getenv("SCPP_QOS_UNUSED"); if(!isset($s)){echo "missing";} return 0;', output='missing'),
    Probe('bare-getenv', '$all=getenv(); return 0;', reject='Argument count mismatch for function: getenv'),
    Probe('html-escape', '$s=htmlspecialchars("A & B"); echo $s; return 0;', output='A &amp; B'),
    Probe('url-query', '$pairs=[]; parse_str("a=1&b=2",$pairs); echo (string)$pairs["b"];', output='2'),
    Probe('strict-types', 'declare(strict_types=1); echo "ok"; return 0;', reject='Remove declare(strict_types)'),
    Probe('php-array-param', 'function limit(array $args): int {return count($args);} echo limit([1]); return 0;', reject='Unknown or unsupported parameter type: array'),
    Probe('null-inference', '$next=null; $next=3; echo $next; return 0;', reject='Cannot infer a local type from null; write an explicit type'),
    Probe('destructure', '[$host,$port]=explode(":","db:80"); echo $host; return 0;', reject='Expected variable name'),
    Probe('inline-array-index', 'echo (string)["name"=>"Ana"]["name"]; return 0;', reject='Expected ]'),
    Probe('arrow-untyped', '$op=fn($n)=>$n+1; echo $op(1); return 0;', reject='Lambda requires an explicit result type without a callback context'),
    Probe('new-dynamic-property', 'class User {} $u=new User(); $u->name="Ana"; return 0;', reject='Unknown field User.name'),
    Probe('promoted-property', 'class User {public function __construct(public string $name) {}} $u=new User("A"); echo $u->name; return 0;', reject='Declare the field in the class body'),
    Probe('nullsafe', 'class User {public string $name="Ada";} $u ?User=null; echo $u?->name ?? "guest"; return 0;', output='guest'),
    Probe('class-presence', 'class User {} $u ?User=null; echo isset($u)?"Y":"N"; return 0;', reject='This isset slice requires a carrier or owned callable'),
    Probe('class-null-check', 'class User {} $u ?User=null; echo ($u === null)?"absent":"present"; return 0;', output='absent'),
    Probe('enum-php-value', 'enum Status:string {case Open="open";} echo Status::Open->value; return 0;', reject='Member reads are not supported in this slice for type Status'),
    Probe('enum-general-value', 'enum Status:string {case Open="open";} echo enum_value(Status::Open); return 0;', output='open'),
    Probe('enum-from-string', 'enum Status:string {case Open="open";} $status=Status::tryFrom("open"); return 0;', reject='Unknown static method: Status::tryFrom'),
    Probe('object-identity', 'class User {public int $id=1;} $a=new User(); $b=$a; $b->id=2; echo $a->id; return 0;', output='2'),
    Probe('boxed-record', 'class User {public int $id=1;} $u=new User(); $record mixed=$u; return 0;', reject='Unsupported boxing into mixed'),
    Probe('boxed-vector', '$tags vector<string> = ["a"]; $tree mixed=$tags; return 0;', reject='Unsupported boxing into mixed'),
    Probe('json-typed-vector', '$rows vector<int> = [1,2]; $text string=json_encode($rows); echo $text; return 0;', modules=('json',), reject='Unsupported first argument type for json_encode'),
    Probe('json-class', 'class User {public int $id=1;} $u=new User(); $text=json_encode($u); return 0;', modules=('json',), reject='Unsupported first argument type for json_encode'),
    Probe('json-shape', '''$r=json_decode("{}"); $v dynamic=$r; $out string=json_encode($v); echo $out; return 0;''', modules=('json',), output='[]'),
    Probe('json-number-spelling', '''$r=json_decode("1.0"); $v dynamic=$r; $out string=json_encode($v); echo $out; return 0;''', modules=('json',), output='1'),
    Probe('json-duplicate', '''$r=json_decode('{"count":1,"count":2}'); echo isset($r)?"ok":"bad"; return 0;''', modules=('json',), output='bad'),
    Probe('json-pointer', '''$r=json_decode('{"user":{"name":"Ana"}}'); $v dynamic=$r; $name string=$v->user->name; echo $name; return 0;''', modules=('json',), output='Ana'),
    Probe('json-index-path', '''$r=json_decode('{"user":{"name":"Ana"}}'); $v dynamic=$r; $name string=$v["user"]["name"]; echo $name; return 0;''', modules=('json',), output='Ana'),
    Probe('json-direct-chain', '''$diag ?json_parse_error=null; $doc=json_document_parse("[]",$diag);
$kind string=json_node_kind(json_document_root($doc)); echo $kind; return 0;''', general=True, modules=('json',), output='array'),
    Probe('builtin-carried-aliases', '''$s result<string> =" /docs/name "; $v vector<string> = ["a","b"]; $rows result<vector<string>> = $v;
echo basename(trim($s)),":",implode(",",$rows); return 0;''', modules=('filesystem',), output='name:a,b'),
    Probe('json-exact-or-carried', '''$none ?int=null; $number result<int> =7;
$a string=json_encode($none); $b string=json_encode($number); echo $a,":",$b; return 0;''',
          modules=('json',), output='null:7'),
    Probe('regex-carried-inputs', '''$s result<string> = "ab"; $p result<string> = "/a/"; $m vector<string> = [];
$n int=preg_match($p,$s,$m); $out string=preg_replace_callback($p,fn(vector<string> $row)=>"!",$s);
echo $n,":",$m[0],":",$out; return 0;''', modules=('regex',), output='1:a:!b'),
    Probe('regex-carried-failure', '''$s=hex2bin("x");
$out=preg_replace_callback("/a/",fn(vector<string> $row)=>"!",$s); echo "after"; return 0;''',
          modules=('regex',), runtime_error='carrier_no_value'),
    Probe('json-failed-chain', '''$diag ?json_parse_error=null;
$doc=json_document_parse("{",$diag); $root=json_document_root($doc); echo "after"; return 0;''',
          general=True, modules=('json',), runtime_error='carrier_no_value'),
    Probe('predicate-family', '''$r=json_decode("true"); $v dynamic=$r; echo is_bool($v)?"bool":"other"; return 0;''', modules=('json',), output='bool'),
    Probe('missing-or-null', '''$record mixed=["limit"=>null]; echo (int)$record->has("limit"),(int)$record->has("absent"); return 0;''', output='10'),
    Probe('try-message', '''try { throw new Exception("declined"); } catch (Exception $e) { echo $e->getMessage(); } return 0;''', reject='Member calls: unknown method getMessage on Exception'),
    Probe('try-accessor', '''try { throw new Exception("declined"); } catch (Exception $e) { echo $e->get_message(); } return 0;''', output='declined'),
    Probe('catch-throwable', '''try { throw new Exception("declined"); } catch (Throwable $e) { echo "caught"; } return 0;''', reject='Throw and catch require core Exception or a shared descendant'),
    Probe('catch-range', '''$v vector<int> = []; try {echo $v[0];} catch (Exception $e) {echo "caught";} finally {echo "cleanup";} return 0;''', runtime_error="sequence_index_out_of_range; stdout=b'cleanup'"),
    Probe('require-once', '''require_once "settings.php"; return 0;''', reject='select project sources in module.json'),
    Probe('header-function', '''header("Content-Type: application/json"); return 0;''', reject='Unknown function: header'),
    Probe('preg-unbound-output', '''$found=preg_match("/(ok)/","ok",$matches); return 0;''', modules=('regex',), reject='Unknown or not-yet-initialized local/global: matches'),
    Probe('preg-output', '''$matches mixed=[]; $r=preg_match("/(ok)/","ok",$matches); $found int=$r;
$word string=$matches[1]; echo $found,":",$word; return 0;''', modules=('regex',), output='1:ok'),
    Probe('date-natural', 'echo date("Y",0); return 0;', modules=('datetime',), output='1970'),
    Probe('date-general', 'echo dt_format_iso_utc(0); return 0;', general=True, modules=('datetime',), output='1970-01-01T00:00:00Z'),
    Probe('file-basename', 'echo basename("/tmp/docs/"),"|",dirname("leaf"); return 0;', modules=('filesystem',), output='|'),
    Probe('neutral-file-name', '$open=fs_get("settings.json"); return 0;', modules=('filesystem',), reject='Unknown function: fs_get'),
    Probe('php-query-builder', '$s=http_build_query(["page"=>1]); echo $s;', output='page=1'),
    Probe('string-byte-value', '$s="ABC"; echo (int)$s[0]; return 0;', output='65'),
    Probe('utf8-width', '$s="é😀"; echo strlen($s),"|",string_utf8_codepoint_count($s); return 0;', general=True, output='6|2'),
    Probe('json-intermediate', '''$r=json_decode('{"user":{"name":"Ana"}}'); $v dynamic=$r; $user dynamic=$v["user"];
$name string=$user["name"]; echo $name; return 0;''', modules=('json',), output='Ana'),
    Probe('boxed-nested-write', '''$row mixed=["user"=>["name"=>"Ana"]]; $row["user"]["name"]="Jo"; $child=$row["user"]; echo (string)$child["name"]; return 0;''', output='Jo'),
    Probe('mixed-writeback', '''$base mixed=["user"=>["name"=>"Ana"]]; $edit=$base; $child mixed=$edit["user"];
$child["name"]="Jo"; $edit["user"]=$child; $old mixed=$base["user"]; $name string=$old["name"]; echo $name; return 0;''', output='Ana'),
    Probe('mixed-key-types', '''$row mixed=["n"=>1]; $row[0]="zero"; echo (int)$row->has("0"),(int)$row->has(0); return 0;''', output='11'),
    Probe('decoded-shape', '''$err ?json_parse_error=null; $doc json_document=json_document_parse("{}",$err);
$node json_node=json_document_root($doc); $kind string=json_node_kind($node); echo $kind; return 0;''', modules=('json',), general=True, output='object'),
    Probe('null-dynamic-write', '$v dynamic=null; $v["x"]=1; return 0;', runtime_error='dynamic_requires_container'),
    Probe('null-mixed-write', '$v mixed=null; $v["x"]=1; $i int=$v["x"]; echo $i; return 0;', output='1'),
    Probe('is-string', '$v mixed="ok"; echo is_string($v)?"text":"other"; return 0;', output='text'),
    Probe('is-array', '$v mixed=[]; echo is_array($v)?"array":"other"; return 0;', output='array'),
    Probe('explicit-box-int', '$v mixed="42"; echo (int)$v; return 0;', runtime_error='boxed_scalar_kind'),
    Probe('typed-box-int', '$v mixed="42"; $n int=$v; echo $n; return 0;', runtime_error='boxed_scalar_kind'),
    Probe('native-intval', 'echo intval("42"); return 0;', reject='Unknown function: intval'),
    Probe('numeric-validation', 'echo is_numeric("42")?"Y":"N"; return 0;', reject='Unknown function: is_numeric'),
    Probe('preg-isset-miss', '''$m mixed=[]; $r=preg_match("/[0-9]+/","abc",$m); $n int=$r; echo (int)isset($r),":",$n,":",count($m); return 0;''', modules=('regex',), output='1:0:0'),
    Probe('preg-invalid', '''$m mixed=["old"]; $r=preg_match("/[/","abc",$m); echo (int)isset($r),":",count($m); return 0;''', modules=('regex',), output='0:0'),
    Probe('exclusive-write-flag', '$r=file_put_contents("x","line",FILE_APPEND); return 0;', modules=('filesystem',), reject='Argument count mismatch for function: file_put_contents'),
    Probe('namespace-import', '''namespace Billing {function cents(int $n): int {return $n*100;}}
namespace {use function Billing\\cents as money; echo money(2); return 0;}''', reject='Only one namespace body per file is supported'),
    Probe('dir-constant', 'echo __FILE__ === __DIR__ . "/main.php" ? "ok" : "bad";', output='ok'),
    Probe('switch-status', '$s=405; switch($s){case 405: echo "method"; break; default: echo "other";} return 0;', output='method'),
    Probe('throw-expression', '''function required(?string $s): string { return $s ?? throw new Exception("required"); }
try {echo required(null);} catch(Exception $e) {echo $e->get_message();} return 0;''', output='required'),
    Probe('enum-lookup', '''enum Mode:string {case In="in"; case Out="out";} $r=enum_try_from_value(Mode::class,"in");
if(isset($r)){ $m Mode=$r; echo enum_name($m); } return 0;''', output='In'),
    Probe('class-json-projection', '''class User {public int $id=7;} $u=new User(); $row mixed=["id"=>$u->id];
$text string=json_encode($row); echo $text; return 0;''', modules=('json',), output='{"id":7}'),
    Probe('catch-invalid-json', '''try { $r=json_decode("{"); echo "returned"; } catch(Exception $e) { echo "caught"; } return 0;''', modules=('json',), output='returned'),
    Probe('foreach-array-mutator', '''$v vector<int> = [1,2]; foreach ($v as &$n) { $v[]=9; } return 0;''', reject='Cannot structurally mutate or borrow the collection during writable foreach'),
    Probe('loose-comparison-bool', 'echo (int)(false == 0); return 0;', reject='Unsupported binary operands or operator'),
    Probe('missing-string-at', '$s=""; echo (int)$s[0]; return 0;', runtime_error='sequence_index_out_of_range'),
    Probe('class-interface', '''interface Label {public function title():string;}
class Page implements Label {public function title():string{return "Hello";}}
function render(Label $view): string{return $view->title();} echo render(new Page()); return 0;''', reject='Interface implementation requires override: Page::title'),
    Probe('typed-property-default', 'class User {public string $name;} $u=new User(); echo "[",$u->name,"]"; return 0;', output='[]'),
    Probe('nonlocal-default', 'function page(int $n=1+2):int{return $n;} echo page(); return 0;', reject='Parameter defaults require literals'),
    Probe('object-as-array', 'class User {public int $id=1;} $u=new User(); echo $u["id"]; return 0;', reject='Indexed read requires a vector, hash, boxed container or readable string'),
    Probe('named-duplicate', 'function page(int $size=1):int{return $size;} echo page(2,size:3); return 0;', reject='Parameter supplied twice: size'),
    Probe('unpack-typed-list', '''$v vector<int> = [1,2]; $w vector<int> = [0,...$v]; echo count($w); return 0;''', reject='Expected variable name'),
    Probe('foreach-value-scope', '''$n=9; $v vector<int> = [1,2]; foreach ($v as $n) {} echo $n; return 0;''', output='2'),
    Probe('http-cookie', 'setcookie("theme","dark"); return 0;', reject='Unknown function: setcookie'),
    Probe('php-session', 'session_start(); return 0;', reject='Unknown function: session_start'),
    Probe('stream-input', '$r=file_get_contents("php://input"); echo isset($r)?"present":"missing"; return 0;', modules=('filesystem',), output='missing'),
    Probe('query-charset', 'echo urlencode("a b"); return 0;', output='a+b'),
    Probe('php-trim-set', 'echo trim("--key--","-"); return 0;', output='key'),
    Probe('php-mb-len', 'echo mb_strlen("é"); return 0;', reject='Unknown function: mb_strlen'),
    Probe('time-natural', '$t int = time(); echo $t > 0 ? "unix" : "invalid";', modules=('datetime',), output='unix'),
    Probe('cstring-split-limit', '$parts=explode("=","key=a=b",2); echo count($parts),":",$parts[1]; return 0;', output='2:a=b'),
    Probe('array-membership', '$items vector<string> = ["red","green"]; echo in_array("green",$items,true)?"Y":"N"; return 0;', output='Y'),
    Probe('hello-minimal', 'echo "hello";', output='hello'),
    Probe('exit-status', 'echo "bad input"; exit(2);', reject='Unknown function: exit'),
    Probe('bare-return', 'echo "ok"; return;', reject='Entry requires a value return'),
    Probe('use-before-init', '$page int; echo $page; return 0;', reject='A local without an initializer requires a struct type'),
    Probe('int-annotation-implicit', '$n int=8.75; echo $n; return 0;', output='8'),
    Probe('annotate-inference', '$n=1; $n="1"; return 0;', reject='Unsupported implicit conversion from string to int64'),
    Probe('isset-unbound', 'echo isset($page)?"yes":"no"; return 0;', reject='Unknown or not-yet-initialized local/global: page'),
    Probe('return-missing', 'function label(bool $yes):string {if ($yes) {return "ok";}} return 0;', reject='Non-void function requires a value return'),
    Probe('double-use-ref', '''function adjust(int &$n):void {$n+=1;}
$n=2; adjust($n); echo $n; return 0;''', output='3'),
    Probe('interface-override', '''interface Label {public function title():string;}
class Page implements Label {public override function title():string{return "Hello";}}
function render(Label $view): string{return $view->title();} echo render(new Page()); return 0;''', output='Hello'),
    Probe('nullable-field', 'class User {public ?int $limit=10;} $u=new User(); echo $u->limit ?? 20; return 0;', output='10'),
    Probe('nullable-text-field', 'class User {public ?string $name=null;} $u=new User(); echo $u->name ?? "guest"; return 0;', output='guest'),
    Probe('nullable-by-ref', '''function reset_limit(?int &$n):void {$n=null;}
$limit ?int=0; reset_limit($limit); echo $limit ?? 10; return 0;''', output='10'),
    Probe('generator-php', 'function pages(): Generator {yield "a";} foreach(pages() as $page) {echo $page;} return 0;', reject='Unknown or unsupported return type: Generator'),
    Probe('generator-typed', '''function pages(): Iterator<string> {yield "a"; yield "b";}
$cursor=pages(); foreach ($cursor as $page) {echo $page; break;}
foreach ($cursor as $page) {echo $page;} return 0;''', output='ab'),
    Probe('generator-delegate', '''function rows(): Iterator<string> {yield "a";}
function page(): Iterator<string> {yield from rows();}
foreach(page() as $row){echo $row;} return 0;''', reject='Expected ;'),
    Probe('generator-key', '''function rows(): Iterator<int,string> {yield "a"=>2; yield "b"=>3;}
foreach(rows() as $label=>$n){echo $label,$n;} return 0;''', output='a2b3'),
    Probe('custom-exception', '''class Rejected extends Exception {}
try {throw new Rejected();} catch(Rejected $e) {echo "specific";} return 0;''', output='specific'),
    Probe('routine-void', '''function log_event(string $text):void {echo $text; return;}
log_event("ok"); return 0;''', output='ok'),
    Probe('closure-ref', '''$n=1; $add=function() use (&$n):void {$n+=2;}; $add(); echo $n; return 0;''', output='3'),
    Probe('datetime-natural-epoch', 'echo (int)strtotime("1970-01-01T00:00:00Z"); return 0;', modules=('datetime',), output='0'),
    Probe('fputcsv', '$f file_stream=fopen("export.csv","wb"); echo (int)fputcsv($f,["a","b"]); return 0;', modules=('io',), output='4'),
    Probe('hash-array-value', '$v hash<int,string> = ["a"=>1]; $plain=array_values($v); return 0;', reject='Unknown function: array_values'),
    Probe('php-curl-status', '$r curl_handle=curl_init(); echo CURLINFO_HTTP_CODE === CURLINFO_RESPONSE_CODE ? "same":"different"; return 0;', modules=('curl',), output='same'),
    Probe('php-json-flags', '$s=json_encode("a/b",JSON_UNESCAPED_SLASHES); return 0;', modules=('json',), reject='Argument count mismatch for function: json_encode'),
    Probe('php-mysqli-bind-temp', '''$db=new mysqli(); $s mysqli_stmt=$db->prepare("SELECT ?"); $s->bind_param("s","open"); return 0;''', modules=('mysqli',), reject='Retaining call requires a stable typed slot'),
    Probe('php-mysqli-bind-escape', '''$db=new mysqli(); $s mysqli_stmt=$db->prepare("SELECT ?"); $alias=$s;
$state="open"; $s->bind_param("s",$state); return 0;''', modules=('mysqli',), reject='Bound owner must stay confined'),
    Probe('empty-typed-vector', '$v vector<int> = []; echo empty($v)?"empty":"full"; return 0;', output='empty'),
    Probe('empty-typed-hash', '$h hash<int,string> = ["a"=>0]; echo empty($h)?"empty":"full"; return 0;', output='full'),
    Probe('codepoint-slice', 'echo string_utf8_slice_codepoints("aé😀",1,2); return 0;', general=True, output='é😀'),
    Probe('float-render', 'echo (string)0.5; return 0;', output='0.5'),
    Probe('text-position-fallback', '$word="abc"; echo strpos($word,"z") ?? -1; return 0;', reject='Coalescing requires a published nullable carrier'),
    Probe('mysqli-guard-bind', '''function read(mysqli $db,string $state):void {
$maybe=$db->prepare("SELECT ?"); if (!isset($maybe)) {return;} $s mysqli_stmt=$maybe;
$s->bind_param("s",$state); } return 0;''', modules=('mysqli',)),
    Probe('mysqli-immediate-args', '''function read(mysqli $db,string $state):void {
$maybe=$db->prepare("SELECT ?"); if (!isset($maybe)) {return;} $s mysqli_stmt=$maybe;
$values dynamic=[$state]; db_execute($s,"s",$values); } return 0;''', general=True, modules=('mysqli',)),
    Probe('mysqli-fetch-loop', '''function consume(mysqli_result $r):void {while ($row=$r->fetch_assoc()) {echo "row";}} return 0;''', modules=('mysqli',), reject='Expected )'),
    Probe('isset-json-handle', '''$r=json_decode("null"); if (!isset($r)){return 1;}
$v dynamic=$r; echo ($v===null)?"present null":"other"; return 0;''', modules=('json',), output='present null'),
    Probe('php-miss-member', '$row mixed=[]; $x mixed=$row["none"]; echo ($x===null)?"missing":"other"; return 0;', output='missing'),
    Probe('php-match-exhaustive', '$verb="POST"; $n=match($verb){"GET"=>1}; echo $n; return 0;', reject='Match requires a default arm'),
    Probe('uppercase-builtin', 'echo STRLEN("abc"); return 0;', reject='Unknown function: STRLEN'),
    Probe('uppercase-bool', '$flag=TRUE; if ($flag) { echo "yes"; } return 0;', reject='Unknown constant: TRUE'),
    Probe('case-method', 'class User {public function name():string {return "Ada";}} $u=new User(); echo $u->NAME(); return 0;', reject='Unknown method User::NAME'),
    Probe('json-bool-text', '$v mixed=false; echo (string)$v; return 0;', output='false'),
    Probe('default-arg-null', 'function page(?int $n=null):int {return $n ?? 1;} echo page(); return 0;', output='1'),
    Probe('clone-object', 'class User {public int $id=1;} $u=new User(); $v=clone $u; $v->id=2; echo $u->id; return 0;', reject='Expected ;'),
    Probe('inline-struct-copy', 'struct Row {public int $id=1;} $a Row; $b=$a; $b->id=2; echo $a->id,$b->id; return 0;', output='12'),
    Probe('array-spread-call', '''function join_names(string ...$parts):string{return implode(",",$parts);}
$v vector<string> = ["a","b"]; echo join_names(...$v); return 0;''', output='a,b'),
    Probe('case-insensitive-search', '$r=stripos("GET /Health","health"); if(isset($r)) { $n int=$r; echo $n; } return 0;', output='5'),
    Probe('replace-literal', '$s=str_replace(".","-","1.2.3"); echo $s; return 0;', output='1-2-3'),
    Probe('csv-shape', '$s=str_getcsv(\'1,"A,B"\'); echo $s[1]; return 0;', output='A,B'),
    Probe('sprintf-template', 'echo sprintf("%s=%d","hits",2); return 0;', reject='Unknown function: sprintf'),
    Probe('money-format', 'echo number_format(1234.5,2,".",","); return 0;', output='1,234.50'),
    Probe('path-join', 'echo DIRECTORY_SEPARATOR; return 0;', reject='Unknown constant: DIRECTORY_SEPARATOR'),
    Probe('normalize-tags', '''$tags=explode(","," Blue,0,  ,blue"); $out vector<string> = [];
foreach ($tags as $t){$tag=strtolower(trim($t)); if ($tag!==""){ $out[]=$tag; }}
echo implode("|",$out); return 0;''', output='blue|0|blue'),
    Probe('http-etag', 'echo hash("sha256","catalog"); return 0;', reject='Unknown function: hash'),
    Probe('base64-text', 'echo base64_encode("tag"); return 0;', reject='Unknown function: base64_encode'),
    Probe('error-log', 'error_log("qos"); return 0;', reject='Unknown function: error_log'),
    Probe('on-empty-scan', 'mkdir("inbox"); $r=scandir("inbox"); if(!isset($r)){return 1;} $files vector<string> = $r; echo count($files); return 0;', modules=('filesystem',), output='0'),
    Probe('url-decode', 'echo urldecode("A+B%2BC"); return 0;', output='A B+C'),
    Probe('raw-url-decode', 'echo rawurldecode("A+B%2BC"); return 0;', output='A+B+C'),
    Probe('ascii-chr', 'echo chr(65); return 0;', output='A'),
    Probe('ascii-ord', 'echo ord("A"); return 0;', output='65'),
    Probe('byte-construction', 'echo string_byte_from_int(65); return 0;', general=True, output='A'),
    Probe('utf8-validation', '$s=string_byte_from_int(255); $ok=preg_match("//u",$s); echo isset($ok)?"valid":"bad bytes"; return 0;', general=True, modules=('regex',), output='bad bytes'),
    Probe('nullable-sentinel-literal', '$p=strpos("abc","z"); echo ($p === false_sentinel)?"miss":"value"; return 0;', reject='Unsupported binary operands or operator'),
    Probe('reversed-sentinel', '$p=strpos("abc","z"); echo (false === $p)?"miss":"value"; return 0;', output='miss'),
    Probe('true-status-is-set', '''$r result_or_bool<bool> = true_sentinel;
if (isset($r)) { $payload bool=$r; echo $payload?"true":"false"; } return 0;''',
          runtime_error='carrier_no_value'),
    Probe('false-payload-sentinel', '$value result_or_false<bool> = false; $miss result_or_false<bool> = false_sentinel; echo (int)isset($value),(int)isset($miss); return 0;', output='10'),
    Probe('missing-dynamic-read', '$v dynamic=[]; $raw dynamic=$v["missing"]; echo $raw===null?"absent":"value"; return 0;', output='absent'),
    Probe('box-clear-alias', '$a dynamic=["a"=>1]; $b=$a; $b->clear(); echo count($a); return 0;', output='0'),
    Probe('box-clean-binding', '$a dynamic=["a"=>1]; $b=$a; clean($b); echo count($a),":",($b===null?"null":"live"); return 0;', output='1:null'),
    Probe('append-snapshot', '$v vector<int> = [1]; $copy=$v; $copy[]=2; echo count($v),count($copy); return 0;', output='12'),
    Probe('hash-insertion-order', '$h hash<int,string> = ["a"=>1,"b"=>2]; $h->remove("a"); $h["a"]=3; foreach($h as $k=>$v){echo $k,$v;} return 0;', output='b2a3'),
    Probe('enum-field-default', 'enum Mode:string{case On="on";} class Settings{public Mode $mode=Mode::On;} $s=new Settings(); echo enum_value($s->mode); return 0;', output='on'),
    Probe('closure-callback-filter', '$v vector<int> = [0,1,2]; $cut=1; $w=collection_filter($v,fn(int $n):bool=>$n>=$cut); echo count($w),$w[0],$w[1]; return 0;', general=True, output='212'),
    Probe('array-filter-php', '$v vector<int> = [0,1,2]; $w=array_filter($v); foreach($w as $k=>$n){echo $k,$n;} return 0;', output='1122'),
    Probe('local-null-unwrap', '$value ?string=null; $s string=$value; echo $s; return 0;', runtime_error='carrier_no_value'),
    Probe('unhandled-exception', 'throw new Exception("declined");', runtime_error='declined'),
    Probe('function-container-return', 'function labels(): vector<string>{ $v vector<string> = ["x","y"]; return $v; } $items=labels(); echo implode(",",$items); return 0;', output='x,y'),
    Probe('user-string-from-box', '''function length_of(string $label):int{return strlen($label);}
$x mixed="Ada"; echo length_of($x); return 0;''', output='3'),
    Probe('builtin-string-from-box', '$x mixed="Ada"; echo strlen($x); return 0;', output='3'),
    Probe('user-string-from-result', '''function length_of(string $label):int{return strlen($label);}
$r=json_encode(10); echo length_of($r); return 0;''', modules=('json',), output='2'),
    Probe('builtin-string-from-result', '$r=json_encode(10); echo strlen($r); return 0;', modules=('json',), output='2'),
    Probe('user-doc-from-result', '''function kind_of(json_document $d):string { $n json_node=json_document_root($d); return json_node_kind($n); }
$diag ?json_parse_error=null; $r=json_document_parse("[]",$diag); echo kind_of($r); return 0;''', modules=('json',), general=True, output='array'),
    Probe('predicate-plain', 'echo is_int(1)?"yes":"no"; return 0;', output='yes'),
    Probe('guard-no-narrowing', '$field mixed=true; if (is_bool($field)) { echo $field?"yes":"no"; } return 0;', reject='Unsupported condition type: mixed'),
    Probe('guard-then-typed', '$field mixed=true; if (is_bool($field)) { $flag bool=$field; echo $flag?"yes":"no"; } return 0;', output='yes'),
    Probe('file-result-forward', '''function read_text(string $path):result<string>{ return fs_get($path); }
$r=read_text("absent"); echo isset($r)?"read":"missing"; return 0;''', modules=('filesystem',), general=True, output='missing'),
    Probe('file-result-message', '''$r=fs_get("absent"); if (!isset($r)) { echo $r->error()->get_message(); } return 0;''', modules=('filesystem',), general=True, reject='Member calls: unknown method error on result<string>'),
    Probe('falseable-forward', '''function offset(string $s):result_or_false<int> {return strpos($s,"?");}
$r=offset("plain"); echo isset($r)?"yes":"miss"; return 0;''', output='miss'),
    Probe('typed-callback-no-arg-type', '''$v vector<int> = [1,2]; $f function<int(int)> = fn($n)=>$n+3;
$out=collection_map($v,$f); echo $out[0]; return 0;''', general=True, output='4'),
    Probe('php-object-default-zero', 'class C {public int $count;} $c=new C(); echo $c->count; return 0;', output='0'),
    Probe('boxed-string-to-bool', '$s mixed="false"; $flag bool=$s; echo $flag?"true":"false"; return 0;', runtime_error='boxed_scalar_kind'),
    Probe('nullable-required-param', '''class User {public int $id=1;} function id(User $u):int{return $u->id;}
$u ?User=null; echo id($u); return 0;''', reject='Required object cannot be null: User'),
    Probe('present-empty-result', '''$n=file_put_contents("empty",""); $r=file_get_contents("empty");
if (isset($n) && isset($r)) {$len int=$n; $data string=$r; echo $len,":",strlen($data);} return 0;''',
          modules=('filesystem',), output='0:0'),
]


def exercise(probe, work):
    app = work/'app'
    app.mkdir()
    (app/'main.php').write_text(probe.code+'\n')
    (app/'module.json').write_text(json.dumps({'general_api': probe.general,
                                               'dependencies': {'runtime': {'modules': probe.modules}}}))
    try:
        exe = build(app, work)
    except RuntimeError as error:
        return ('build error', str(error).replace(str(app)+'/', '').strip())
    env = os.environ.copy()
    env.pop("SCPP_QOS_UNUSED", None)
    run = subprocess.run([exe], cwd=work, capture_output=True, timeout=8, env=env)
    if run.returncode or run.stderr:
        return ('runtime error', f'exit {run.returncode}: '+run.stderr.decode(errors='replace').strip()
                + ('; stdout='+repr(run.stdout) if run.stdout else ''))
    return ('output', run.stdout.decode())


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('names', nargs='*', help='default: all probes')
    parser.add_argument('--show', action='store_true', help='print the source and project settings without building')
    args = parser.parse_args()
    names = set(args.names)
    if names - {p.name for p in probes}:
        parser.error('unknown probe name')
    errors = 0
    for probe in probes:
        if names and probe.name not in names:
            continue
        if args.show:
            print(f'{probe.name}  modules={list(probe.modules)}  general_api={probe.general}\n{probe.code}\n')
            continue
        with tempfile.TemporaryDirectory(prefix='php-qos-') as directory:
            stage, observed = exercise(probe, Path(directory))
        if probe.reject:
            matched = stage == 'build error' and probe.reject in observed
        elif probe.runtime_error:
            matched = stage == 'runtime error' and probe.runtime_error in observed
        else:
            matched = stage == 'output' and observed == probe.output
        print(f'{probe.name}: {stage}: {observed!r}', flush=True)
        errors += not matched
        if not matched:
            print('  differs from the recorded observation', flush=True)
    return int(errors != 0)


if __name__ == '__main__':
    raise SystemExit(main())

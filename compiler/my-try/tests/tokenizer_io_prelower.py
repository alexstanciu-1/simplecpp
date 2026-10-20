"""Default prelowering acceptance for tokenizer/IO and prepared take transport."""
import argparse
import json
import shutil
from pathlib import Path
import sys

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / "tools"))
from test_runner import CommandRunner, Task, run_tasks

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument("--results", type=Path, required=True)
parser.add_argument("--case", action="append", dest="selected")
args = parser.parse_args()
out = args.results.resolve()
out.mkdir(parents=True, exist_ok=False)
cases = {}
for name, source, info in [
    ("tokenizer", "tests/php/tokenizer/strict_level_01/strict_tokenizer_001_typed_buffer_accessors.phs",
     "tests/php/tokenizer/strict_level_01/strict_tokenizer_001_typed_buffer_accessors.test-info.json"),
    ("io_surface", "compiler/my-try/tests/compatibility_variants/s02/strict_io_001_io_surface.phs",
     "tests/php/io/strict_level_01/strict_io_001_io_surface.test-info.json"),
    ("io_failure", "compiler/my-try/tests/compatibility_variants/s02/strict_io_002_io_open_failure.phs",
     "tests/php/io/strict_level_01/strict_io_002_io_open_failure.test-info.json"),
]:
    cases[name] = ((ROOT / source).read_text(),
                   json.loads((ROOT / info).read_text())["expect"]["run"]["stdout"])
cases["take_states"] = ("""
$v=91;$r ?int=null;
if(take($v,$r)||$v!==91){return 1;}
$r=7;if(!take($v,$r)||$v!==7){return 2;}
$f result_or_false<int> =false_sentinel;
if(take($v,$f)||$v!==7){return 3;}
$f=12;if(!take($v,$f)||$v!==12){return 4;}
$b result_or_bool<int> =true_sentinel;$flag=false;
if(!take($v,$flag,$b)||!$flag||$v!==12){return 5;}
$b=false_sentinel;
if(take($v,$flag,$b)||$flag||$v!==12){return 6;}
$b=18;
if(!take($v,$flag,$b)||$flag||$v!==18){return 7;}
function once(int &$out,int &$trace):?int{$out=44;$trace++;$value=5;$r ?int=$value;return $r;}
$trace=0;
if(!take($v,once($v,$trace))||$v!==5||$trace!==1){return 8;}
$p result_or_bool<bool> =true;$value=false;$flag=false;
if(!take($value,$flag,$p)||!$value||$flag){return 9;}
$p=false;$value=true;
if(!take($value,$flag,$p)||$value||$flag){return 10;}
$p=true_sentinel;
if(!take($value,$flag,$p)||$value||!$flag){return 11;}
echo "take states\\n";
""", "take states\n")
cases["take_error"] = ("""
$v=91;$e=error::make("old",3,"old");$s result<int> =24;
if(!take($v,$e,$s)||$v!==24){return 1;}
$s=error::make("kept",17,"origin");
if(take($v,$e,$s)||$v!==24||$e->get_line()!==17||$e->get_message()!=="kept"){return 2;}
$text="unchanged";$missing=fs_get("missing-file");
if(take($text,$e,$missing)||$text!=="unchanged"){return 3;}
echo "take error\\n";
""", "take error\n")
cases["io_states"] = ("""
function replace(resource_handle &$h,int &$trace):string{
    $trace++;take($h,io_open("second.bin","wb+"));return "a\\0b\\nlast";
}
function fallback(int &$trace):int{$trace++;return 99;}
$h resource_handle;
if(!take($h,io_open("first.bin","wb+"))){return 1;}
$old=$h;$trace=0;$written=0;
if(!take($written,io_write($h,replace($h,$trace)))||$written!==8||$trace!==1){return 2;}
io_flush($old);io_rewind($old);
$line="";if(!take($line,io_read_line($old,5))||$line!=="a\\0b\\n"){return 3;}
$pos=0;if(!take($pos,io_tell($old))||$pos!==4){return 4;}
$seek=io_seek($old,0);$trace=0;
if(($seek??fallback($trace))!==0||$trace!==0){return 5;}
$seek=io_seek($old,-1);
if(($seek??fallback($trace))!==99||$trace!==1){return 6;}
io_close($old);io_close($h);$missing=io_open("absent.bin","rb");
if(take($h,$missing)){return 7;}
fs_remove("first.bin");fs_remove("second.bin");
echo "IO states and snapshots\\n";
""", "IO states and snapshots\n")
cases["tokenizer_forms"] = ("""
$a=phs_tokenize("foo 12");$b=phs_tokenize_buffer("foo 12");
$c=jss_tokenize("foo 12");$d=jss_tokenize_buffer("foo 12");
if(token_buffer_count($a)!==3||token_buffer_count($b)!==3||
   token_buffer_count($c)!==3||token_buffer_count($d)!==3){return 1;}
if(token_buffer_kind_id($a,1)!==3||token_buffer_start_offset($a,1)!==4||token_buffer_length($a,1)!==2||
   token_buffer_line($a,1)!==1||token_buffer_column($a,1)!==5||
   token_buffer_flags($a,1)!==1){return 2;}
$readable=token_buffer_to_mixed($a);
echo "tokenizer forms\\n";
""", "tokenizer forms\n")
cases["provider_roundtrip"] = ("""
$v=91;$n ?int=null;
if(take($v,probe_nullable($n))||$v!==91){return 1;}
$n=7;if(!take($v,probe_nullable($n))||$v!==7){return 2;}
$f result_or_false<int> =false_sentinel;
if(take($v,probe_false($f))||$v!==7){return 3;}
$b result_or_bool<int> =true_sentinel;$flag=false;
if(!take($v,$flag,probe_bool($b))||!$flag||$v!==7){return 4;}
$b=false_sentinel;if(take($v,$flag,probe_bool($b))||$flag){return 5;}
$b=12;if(!take($v,$flag,probe_bool($b))||$v!==12){return 6;}
$r result<int> =error::make("preserved",42,"provider");$e error;
if(take($v,$e,probe_result($r))||$v!==12||$e->get_line()!==42){return 7;}
$r=19;if(!take($v,$e,probe_result($r))||$v!==19){return 8;}
$original=io_open("roundtrip.bin","wb+");$returned=probe_handle($original);
$first resource_handle;$second resource_handle;
if(!take($first,$original)||!take($second,$returned)){return 9;}
$written=0;take($written,io_write($first,"shared"));$pos=0;take($pos,io_tell($second));
if($written!==6||$pos!==6){return 10;}
io_close($second);fs_remove("roundtrip.bin");
echo "provider states and shared handle\\n";
""", "provider states and shared handle\n")
if args.selected:
    cases = {name: cases[name] for name in args.selected}

with CommandRunner(out / "logs", ROOT, jobs=12) as runner:
    def verify(name, source, expected):
        folder = out / name
        (folder / "source").mkdir(parents=True)
        (folder / "source/main.phs").write_text(source)
        publish = ["php", APP / "tests/tokenizer_io.php", folder, "--publish"]
        runtime = ROOT / "runtime/include"
        if name == "provider_roundtrip":
            catalog = folder / "catalog"
            shutil.copytree(ROOT / "runtime/metadata/modules", catalog)
            runtime = folder / "runtime"
            shutil.copytree(ROOT / "runtime/include", runtime)
            functions, bindings, native = [], [], []
            for label, family, payload, native_payload in [
                ("nullable", "nullable", "int", "scpp::int_t<>"),
                ("false", "result_or_false", "int", "scpp::int_t<>"),
                ("bool", "result_or_bool", "int", "scpp::int_t<>"),
                ("result", "result", "int", "scpp::int_t<>"),
                ("handle", "result_or_false", "resource_handle", "scpp::resource_handle_t"),
            ]:
                type_ref = {"name": family, "arguments": [payload]}
                identity = "transport_probe." + label
                functions.append({"id": identity, "name": "probe_" + label,
                    "parameters": [{"name": "value", "type": type_ref, "passing": "value"}],
                    "returns": type_ref})
                bindings.append({"id": identity, "symbol": "transport_probe::probe_" + label,
                                 "header": "transport_probe.hpp"})
                cpp_type = "scpp::" + family + "<" + native_payload + ">"
                native.append(f"inline {cpp_type} probe_{label}({cpp_type} value) {{ return value; }}")
            functions.append({"id": "transport_probe.mutable", "name": "probe_mutable",
                "parameters": [{"name": "value", "type": {"name": "nullable", "arguments": ["int"]},
                                "passing": "reference"}],
                "returns": {"name": "nullable", "arguments": ["int"]}})
            bindings.append({"id": "transport_probe.mutable", "symbol": "transport_probe::probe_mutable",
                             "header": "transport_probe.hpp"})
            native.append("inline scpp::nullable<scpp::int_t<>> probe_mutable(scpp::nullable<scpp::int_t<>>& value) { return value; }")
            (catalog / "transport_probe.json").write_text(json.dumps({
                "version": 1, "module": "transport_probe", "dependencies": ["core", "io"],
                "functions": functions, "cpp": bindings}))
            (runtime / "transport_probe.hpp").write_text(
                '#pragma once\n#include "scpp/io.hpp"\n#include "scpp/result.hpp"\n'
                '#include "scpp/result_or_bool.hpp"\nnamespace transport_probe {\n'
                + "\n".join(native) + "\n}\n")
            publish.append(catalog)
            (folder / "source/main.phs").write_text('$r ?int=1;$x=probe_mutable($r);')
            runner.run(name + "-mutable-reject", publish, expected=255,
                       cli_reason="Reject unimplemented mutable provider write-back")
            diagnostic = (out / "logs" / (name + "-mutable-reject.stderr")).read_text()
            assert "mutable provider wrapper arguments require write-back transport" in diagnostic
            (folder / "source/main.phs").write_text(source)
        runner.run(name + "-publish",
                   publish,
                   cli_reason="Default concrete publication")
        work = folder / "cwd"
        work.mkdir()
        builds = {}
        for phase in ["cold", "warm"]:
            build = json.loads(runner.run(name + "-" + phase,
                ["php", APP / "tests/fixtures/native_cache_driver.php",
                 folder / "published", folder / "cache", runtime,
                 "/usr/bin/clang++", "--no-pch"], timeout=300,
                cli_reason="Default concrete native build"))
            actual = runner.run(name + "-" + phase + "-execute", [build["executable"]], cwd=work)
            assert actual == expected.encode(), (name, actual, expected)
            assert not list(work.iterdir()), (name, "filesystem effects remain")
            if phase == "warm":
                assert build["compiled"] == build["linked"] == 0, build
            builds[phase] = build
        (folder / "result.json").write_text(json.dumps(builds, indent=2) + "\n")
    run_tasks([Task(name, lambda name=name, pair=pair: verify(name, *pair))
               for name, pair in cases.items()])
(out / "summary.json").write_text(json.dumps({"passed": True, "cases": list(cases)}, indent=2) + "\n")

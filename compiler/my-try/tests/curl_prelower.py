"""Strict curl production acceptance with explicit, separately recorded v0.2 migrations."""
import argparse
import hashlib
import json
from pathlib import Path
import re
import sys

from curl_runtime import fixture_server

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / "tools"))
from test_runner import CommandRunner, Task, run_tasks


def cases():
    result = {}
    for path in sorted((ROOT / "tests/php/curl/strict_level_01").glob("*.phs")):
        info = json.loads(path.with_suffix(".test-info.json").read_text())
        original = path.read_text()
        source = re.sub(r"(\$[A-Za-z_][A-Za-z_0-9]*)\s*/\*\*\s*([^*]+?)\s*\*/",
                        r"\1 \2", original)
        changes = ["comment declarations to strict declarations"]
        if "$argv" in source:
            source = "$argv=cli_args();\n" + source
            changes.append("explicit argv harness setup")
        if "github_raw" in path.stem:
            source = source.replace("$url = $argv[1];", "$url string = $argv[1];")
            changes.append("explicit string URL stabilization")
        if "json_decode(" in source:
            source = source.replace("json_decode(", "json_decode_mixed(")
            changes.append("explicit mixed JSON family under v0.2")
        result[path.stem] = dict(source=source, expected=info["expect"]["run"]["stdout"],
            provenance=str(path.relative_to(ROOT)), sha256=hashlib.sha256(path.read_bytes()).hexdigest(),
            changes=changes, http="http_server" in info.get("build", {}),
            external="github_raw" in path.stem)
    result["properties_snapshots"] = dict(source=r'''
function replace(curl_response &$slot,curl_response $other,int &$trace):string {
    $trace++;$slot=$other;return "written";
}
$h curl_handle;$e error;$r curl_response;$path string="";
if(!take($path,$e,fs_realpath("payload.txt"))){return 1;}
if(!take($h,$e,curl_init("file://" . $path))){return 1;}
if(!take($r,$e,curl_exec($h))){return 2;}
$other curl_response;if(!take($other,$e,curl_exec($h))){return 3;}
$old=$r;$trace=0;
$r->body=replace($r,$other,$trace);
if($trace!==1||$old->body!=="written"||$r->body!=="curl-http\n"){return 4;}
$old->status_code=201;$old->effective_url="kept";$old->content_type="bytes";
$old->total_time_ms=17;$old->header_size=18;$old->request_size=19;$old->redirect_count=20;
$headers vector<string> =["first","second"];$old->headers=$headers;
$copy=$old->headers;$copy[]="third";
if(count($old->headers)!==2||count($copy)!==3){return 5;}
if($old->headers[0]!=="first"||$old->headers[1]!=="second"){return 11;}
if($old->status_code!==201||$old->effective_url!=="kept"||$old->content_type!=="bytes"||
   $old->total_time_ms!==17||$old->header_size!==18||$old->request_size!==19||$old->redirect_count!==20){return 6;}
$m mixed;$s mixed;
if(!take($m,$e,curl_getinfo($h,CURLINFO_RESPONSE_CODE))||!take($s,$e,curl_getinfo($h,CURLINFO_EFFECTIVE_URL))){return 8;}
$n int=$m;$text string=$s;
if($n!==0||$text!=="file://" . $path){return 9;}
$number_text string=$m;if($number_text!=="0"){return 10;}
curl_reset($h);curl_close($h);
if($old->body!=="written"||$r->body!=="curl-http\n"){return 7;}
echo "provider properties, snapshots and checked extraction\n";
''', expected="provider properties, snapshots and checked extraction\n",
        changes=[], http=False, external=False)
    for name, source, diagnostic in [
        ("null_read", '$r curl_response;echo $r->body;', "operator->() requires a present shared pointer value"),
        ("null_write", 'function rhs():string{echo "RHS MUST NOT RUN";return "x";}$r curl_response;$r->body=rhs();',
         "operator->() requires a present shared pointer value"),
        ("mixed_null_int", '$m mixed;$n int=$m;echo "UNREACHABLE";', "null cannot satisfy a required typed boundary"),
        ("mixed_null_string", '$m mixed;$n string=$m;echo "UNREACHABLE";', "null cannot satisfy a required typed boundary"),
    ]:
        result[name] = dict(source=source, expected="", exit=1, diagnostic=diagnostic,
                            changes=[], http=False, external=False)
    result["mixed_kind_int"] = dict(source=r'''
$h curl_handle;$r curl_response;$e error;$path string="";$m mixed;
if(!take($path,$e,fs_realpath("payload.txt"))){return 2;}
if(!take($h,$e,curl_init("file://" . $path))||!take($r,$e,curl_exec($h))){return 3;}
if(!take($m,$e,curl_getinfo($h,CURLINFO_EFFECTIVE_URL))){return 4;}
$n int=$m;echo "UNREACHABLE";
''', expected="", exit=1, diagnostic="runtime kind cannot satisfy a required typed boundary",
        changes=[], http=False, external=False)
    result["mixed_index_transport"] = dict(source=r'''
$h curl_handle;$r curl_response;$e error;$path string="";$key mixed;
if(!take($path,$e,fs_realpath("payload.txt"))){return 2;}
if(!take($h,$e,curl_init("file://" . $path))||!take($r,$e,curl_exec($h))){return 3;}
if(!take($key,$e,curl_getinfo($h,CURLINFO_RESPONSE_CODE))){return 4;}
$args=cli_args();$text string=$args[$key];
if(strlen($text)===0){return 5;}echo "mixed indexing\n";
''', expected="mixed indexing\n", changes=[], http=False, external=False)
    return result


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--results", type=Path, required=True)
    parser.add_argument("--case", action="append", dest="selected")
    args = parser.parse_args()
    out = args.results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    selected = {name: row for name, row in cases().items()
                if args.selected is None or name in args.selected}
    if not selected:
        raise ValueError("No selected cases")
    (out / "cases.json").write_text(json.dumps(selected, indent=2) + "\n")
    with CommandRunner(out / "logs", ROOT, jobs=12) as runner:
        def verify(name, case):
            folder = out / name
            (folder / "source").mkdir(parents=True)
            (folder / "source/main.phs").write_text(case["source"])
            runner.run(name + "-publish", ["php", APP / "tests/curl_publish.php", folder],
                       cli_reason="Default production curl publication")
            work = folder / "cwd"
            work.mkdir()
            (work / "payload.txt").write_text("curl-http\n")
            builds = {}
            for phase in ["cold", "warm"]:
                build = json.loads(runner.run(name + "-" + phase, [
                    "php", APP / "tests/fixtures/native_cache_driver.php", folder / "published",
                    folder / "cache", ROOT / "runtime/include", "/usr/bin/clang++", "--no-pch"],
                    timeout=300, cli_reason="Strict concrete curl native build"))
                if phase == "warm":
                    assert build["compiled"] == build["linked"] == 0, build
                builds[phase] = build
                if case["external"]:
                    continue  # Compilation is not external HTTPS execution evidence.
                if case["http"]:
                    server_folder = folder / ("server-" + phase)
                    server_folder.mkdir()
                    with fixture_server(server_folder) as base:
                        actual = runner.run(name + "-" + phase + "-execute",
                                            [build["executable"], base], cwd=work, timeout=60)
                else:
                    actual = runner.run(name + "-" + phase + "-execute",
                                        [build["executable"]], cwd=work, timeout=60,
                                        expected=case.get("exit", 0))
                assert actual == case["expected"].encode(), (name, actual, case["expected"])
                if "diagnostic" in case:
                    stderr = (out / "logs" / (name + "-" + phase + "-execute.stderr")).read_text()
                    assert case["diagnostic"] in stderr, stderr
                assert sorted(p.name for p in work.iterdir()) == ["payload.txt"]
            (folder / "result.json").write_text(json.dumps({
                "builds": builds, "execution": "skipped-external-opt-in" if case["external"] else "passed",
            }, indent=2) + "\n")
        run_tasks([Task(name, lambda name=name, case=case: verify(name, case))
                   for name, case in selected.items()])
    (out / "summary.json").write_text(json.dumps({
        "passed": True, "cases": list(selected),
        "external_execution": "not requested; compile-only",
    }, indent=2) + "\n")


if __name__ == "__main__":
    main()

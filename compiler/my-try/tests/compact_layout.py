"""Focused compact-layout source/native proofs; no operator matrix."""
import argparse
import json
import sys
from pathlib import Path

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / "tools"))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler

parser = argparse.ArgumentParser()
parser.add_argument("--results", type=Path, required=True)
args = parser.parse_args()
out = args.results.resolve()
out.mkdir(parents=True, exist_ok=False)
with CommandRunner(out / "logs", ROOT, jobs=12) as runner:
    run_tasks([Task("prepare", lambda: runner.run("prepare",
               ["php", APP / "tests/compact_layout.php", out]))])
    cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True)
    cpp.flags.extend(["-fsanitize=address,undefined", "-fno-sanitize-recover=undefined"])

    def check(name, expected):
        cpp.compile(name + "-build", out / (name + ".cpp"), out / name)
        actual = runner.run(name + "-run", ["env", "ASAN_OPTIONS=detect_leaks=0", out / name])
        assert actual == expected, actual

    run_tasks([Task("union", lambda: check("union", b"7\n11:17\n1\nTwo\n13\n11\n")),
               Task("layout", lambda: check("layout", b"")),
               Task("writes", lambda: check("writes", b"")),
               Task("widths", lambda: check("widths", b"7 7 7 7 7 7 7 7 7  1\n")),
               Task("shadow", lambda: check("shadow", b"11\n")),
               Task("imported", lambda: check("imported", b"0:1:1:1\n")),
               Task("changed", lambda: check("changed", b"0::1:1\n"))])
    source = out / "production-source"
    source.mkdir()
    (source / "main.phs").write_text(
        'union Payload { int32 $word; bool $flag; }'
        '$u=new Payload();$u->word=(int32)7;'
        'echo $u->word,":",layout_offsetof(Payload,word)===0,"\\n";')
    published, cache = out / "published", out / "cache"
    runner.run("publish", ["php", APP / "tests/fixtures/runtime_build_publish.php",
               ROOT / "runtime/metadata/modules", source, published, "core"],
               cli_reason="Production compact-layout publication")
    for name in ["cold", "warm"]:
        build = json.loads(runner.run(name + "-build",
            ["php", APP / "tests/fixtures/native_cache_driver.php", published, cache,
             ROOT / "runtime/include", "/usr/bin/clang++"], timeout=300,
            cli_reason="Production native build"))
        assert runner.run(name + "-execute", [build["executable"]]) == b"7:1\n"
        if name == "warm":
            assert build["compiled"] == 0 and build["linked"] == 0, build
        (out / (name + ".json")).write_text(json.dumps(build, indent=2) + "\n")
(out / "summary.json").write_text(json.dumps({"passed": True, "programs": 7,
    "checks": json.loads((out / "checks.json").read_text())}, indent=2) + "\n")

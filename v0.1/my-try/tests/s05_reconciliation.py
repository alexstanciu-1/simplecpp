"""Borrowed closure policy and concrete adaptation; run on dev002."""
import argparse
import hashlib
import json
from pathlib import Path
import sys

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / "tools"))
from test_runner import CommandRunner, Task, run_tasks


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--results", type=Path, required=True)
    out = parser.parse_args().results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    original_path = ROOT / "tests/php/closures/strict_level_02/closures_004_use_reference_capture.phs"
    original = original_path.read_text()
    signature_error = "Callable boundary requires an exact parameter and return signature"
    cases = {
        "original": dict(source=original, reject=signature_error),
        "concrete_adaptation": dict(source=original.replace("$f function<int()> =", "$f ="), expected="2\n"),
        "concrete_owned": dict(source=original.replace("$f function<int()> =", "$f =").replace("use (&$a)", "use ($a)"), expected="1\n"),
        "concrete_mutation": dict(source='''$a=1;
$f=function() use (&$a):int {$a++;return $a;};
$copy=$f;
echo $f(), ":", $copy(), ":", $a, "\\n";
''', expected="2:3:3\n"),
        "nested_borrow": dict(source='''$a=1;
$inner=function() use (&$a):int{return $a;};
$f function<int()> = function() use ($inner):int{return $inner();};
echo $f();''', reject=signature_error),
        "owned_erased_transport_gap": dict(source=original.replace("use (&$a)", "use ($a)"),
            reject="prelower_templates: callable storage requires a concrete callback transport",
            disposition="Known separate backend gap, not a positive execution proof"),
    }
    (out / "cases.json").write_text(json.dumps(dict(original=str(original_path.relative_to(ROOT)),
        original_sha256=hashlib.sha256(original_path.read_bytes()).hexdigest(), cases=cases), indent=2) + "\n")
    with CommandRunner(out / "logs", ROOT, jobs=12) as runner:
        def verify(name, case):
            folder = out / name
            (folder / "source").mkdir(parents=True)
            (folder / "source/main.phs").write_text(case["source"])
            runner.run(name + "-publish", ["php", APP / "tests/scalar_debug_publish.php", folder],
                       expected=255 if "reject" in case else 0, cli_reason="Default prelowering closure boundary")
            if "reject" in case:
                assert case["reject"] in (out / "logs" / (name + "-publish.stderr")).read_text()
                (folder / "result.json").write_text(json.dumps(dict(status="expected-rejection",
                    diagnostic=case["reject"], disposition=case.get("disposition", "Borrowed boundary preserved"))) + "\n")
                return
            builds = {}
            for phase in ["cold", "warm"]:
                build = json.loads(runner.run(name + "-" + phase, ["php",
                    APP / "tests/fixtures/native_cache_driver.php", folder / "published", folder / "cache",
                    ROOT / "runtime/include", "/usr/bin/clang++", "--no-pch"], timeout=300,
                    cli_reason="Default prelowering concrete closure execution"))
                if phase == "warm":
                    assert build["compiled"] == build["linked"] == 0
                assert runner.run(name + "-" + phase + "-run", [build["executable"]]) == case["expected"].encode()
                builds[phase] = build
            (folder / "result.json").write_text(json.dumps(dict(status="executed", builds=builds), indent=2) + "\n")
        run_tasks([Task(name, lambda name=name, case=case: verify(name, case)) for name, case in cases.items()])
    (out / "summary.json").write_text(json.dumps(dict(passed=True, executed=3,
        borrowed_rejections=2, separate_backend_gaps=1,
        scope="Concrete closure adaptation and restrictions; no erased callable execution claim"), indent=2) + "\n")


if __name__ == "__main__":
    main()

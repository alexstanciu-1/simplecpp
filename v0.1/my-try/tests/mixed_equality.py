"""Bounded int/float ordinary equality: carrier states, numeric promotion and source snapshots."""
import itertools
import argparse
import json
from pathlib import Path
import sys

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / "tools"))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument("--results", required=True, type=Path)
parser.add_argument("--runtime-only", action="store_true")
parser.add_argument("--group", choices=["nullable-result", "sentinel", "cross"], default="nullable-result")
parser.add_argument("--probe-runtime", action="store_true", help="Record baseline failures without admitting compiler support.")
args = parser.parse_args()
if args.probe_runtime and not args.runtime_only:
    parser.error("--probe-runtime requires --runtime-only")
out = args.results.resolve()
out.mkdir(parents=True, exist_ok=False)
pairs = [("int", "float"), ("float", "int")]
if args.group == "cross":
    arrangements = list(itertools.permutations(["nullable", "result", "result_or_false", "result_or_bool"], 2))
else:
    arrangements = [("plain", "plain")]
    for family in (["nullable", "result"] if args.group == "nullable-result" else ["result_or_false", "result_or_bool"]):
        arrangements += [(family, family), (family, "plain"), ("plain", family)]
families = {"nullable": 0, "result": 1, "result_or_false": 2, "result_or_bool": 3, "plain": 4}
payloads = {"int": ("scpp::int_t<>", "std::int64_t"), "float": ("scpp::float_t", "double")}

def source_type(family, payload):
    return payload if family == "plain" else "?" + payload if family == "nullable" else family + "<" + payload + ">"

with CommandRunner(out / "logs", ROOT, jobs=12) as runner:
    cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True)
    runtime_results = {}

    def runtime(a, b, left, right):
        v, p = payloads[left]
        w, q = payloads[right]
        name = f"{a}-{b}-{left}-{right}"
        source = out / (name + ".cpp")
        source.write_text('#include "' + str(APP / "tests/mixed_equality_support.hpp") + '"\n'
            + "int main() { long n=0;\n"
            + f"n+=mixed_equality_probe::runtime_pair<{families[a]},{families[b]},{v},{p},{w},{q}>();\n"
            + 'std::cout<<"runtime_cases="<<n<<"\\n";}\n')
        phase = "build"
        try:
            cpp.compile(name + "-runtime-build", source, out / (name + "-runtime"))
            phase = "execute"
            runner.run(name + "-runtime-execute", [out / (name + "-runtime")])
            runtime_results[name] = {"passed": True}
        except RuntimeError as error:
            runtime_results[name] = {"passed": False, "phase": phase, "error": str(error)}
            if not args.probe_runtime:
                raise

    run_tasks([Task(f"{a}-{b}-{p}-{q}-runtime", lambda a=a,b=b,p=p,q=q: runtime(a,b,p,q))
        for a,b in arrangements for p,q in pairs])

    if not args.runtime_only:
        def proof(a, b, p, q):
            name = f"{a}-{b}-{p}-{q}"
            target = out / name
            target.mkdir()
            runner.run(name + "-prepare", ["php", APP / "tests/cross_identity.php", target, a, b, p, q, "--ordinary-numeric"])
            signatures = json.loads((target / "signatures.json").read_text())
            assert len(signatures) == 2 and set(map(tuple, signatures)) == {
                (source_type(a, p), op, source_type(b, q), "bool") for op in ["==", "!="]}
            cpp.compile(name + "-build", target / "cross_identity.cpp", target / "native")
            runner.run(name + "-execute", [target / "native"])

        run_tasks([Task(f"{a}-{b}-{p}-{q}", lambda a=a, b=b, p=p, q=q: proof(a, b, p, q))
            for a, b in arrangements for p, q in pairs])

(out / "summary.json").write_text(json.dumps({
    "passed": all(x["passed"] for x in runtime_results.values()), "group": args.group,
    "runtime_signatures": len(arrangements) * 4, "runtime_results": runtime_results,
    "scalar_prerequisites": 0 if args.runtime_only or args.group == "cross" else 4,
    "carrier_signatures": 0 if args.runtime_only else (48 if args.group == "cross" else 24),
    "full_matrix_run": False,
}, indent=2) + "\n")

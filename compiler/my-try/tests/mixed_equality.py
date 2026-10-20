"""Bounded int/float ordinary equality: nullable/Result states and scalar prerequisites."""
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
args = parser.parse_args()
out = args.results.resolve()
out.mkdir(parents=True, exist_ok=False)
pairs = [("int", "float"), ("float", "int")]
arrangements = [("plain", "plain")]
for family in ["nullable", "result"]:
    arrangements += [(family, family), (family, "plain"), ("plain", family)]
families = {"nullable": 0, "result": 1, "plain": 4}
payloads = {"int": ("scpp::int_t<>", "std::int64_t"), "float": ("scpp::float_t", "double")}

def source_type(family, payload):
    return payload if family == "plain" else "?" + payload if family == "nullable" else family + "<" + payload + ">"

with CommandRunner(out / "logs", ROOT, jobs=12) as runner:
    cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True)

    def runtime(left, right):
        v, p = payloads[left]
        w, q = payloads[right]
        name = left + "-" + right
        source = out / (name + ".cpp")
        source.write_text('#include "' + str(APP / "tests/mixed_equality_support.hpp") + '"\n'
            + "int main() { long n=0;\n"
            + "".join(f"n+=mixed_equality_probe::runtime_pair<{families[a]},{families[b]},{v},{p},{w},{q}>();\n"
                for a, b in arrangements)
            + 'std::cout<<"runtime_cases="<<n<<"\\n";}\n')
        cpp.compile(name + "-runtime-build", source, out / name)
        runner.run(name + "-runtime-execute", [out / name])

    run_tasks([Task(p + "-" + q + "-runtime", lambda p=p, q=q: runtime(p, q)) for p, q in pairs])

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
    "passed": True, "runtime_signatures": 28,
    "scalar_prerequisites": 0 if args.runtime_only else 4,
    "carrier_signatures": 0 if args.runtime_only else 24,
    "full_matrix_run": False,
}, indent=2) + "\n")

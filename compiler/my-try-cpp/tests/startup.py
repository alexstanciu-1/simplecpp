"""Bounded CLI, dependency-graph and retained-model startup proofs."""
import copy
import json
from pathlib import Path
import subprocess
import sys
import tempfile

EXE, LIFECYCLE = sys.argv[1:]


def invoke(arguments, success=True):
    result = subprocess.run([EXE, *map(str, arguments)], capture_output=True, text=True)
    assert not any(marker in result.stderr for marker in
                   ("AddressSanitizer", "LeakSanitizer", "UndefinedBehaviorSanitizer", "runtime error:")), result.stderr
    assert (result.returncode == 0) == success, result.stdout + result.stderr
    if not success:
        assert not result.stdout, result.stdout
        assert "startup failed:" in result.stderr, result.stderr
    return result


def module(name, dependencies=()):
    return {"version": 1, "module": name, "dependencies": list(dependencies), "functions": [], "cpp": []}


def main():
    with tempfile.TemporaryDirectory(prefix="startup-proof-") as temp:
        root = Path(temp)
        good = root / "good"
        good.mkdir()
        rows = {"base": module("base"), "left": module("left", ["base"]),
                "right": module("right", ["base"])}
        for name, row in rows.items():
            (good / (name + ".json")).write_text(json.dumps(row))
        result = invoke(["--catalogs", good, "--module", "left", "--module", "right", "--jobs", 4, "--timings"])
        report = json.loads(result.stdout)
        assert report["state"] == "catalog_metadata_ready" and report["modules"] == report["files_read"] == 3
        assert report["jobs"] == 4 and report["bytes_read"] > 0
        assert all(report[key] >= 0 for key in ["configuration_us", "loading_us", "validation_us", "publication_us"])
        assert "Usage:" in invoke(["--help", "--catalogs", root / "missing"]).stdout
        for args in [[], ["--jobs", "0"], ["--jobs", "99999999999999999999999"], ["--unknown"],
                     ["--catalogs", good, "--module", "../base"],
                     ["--catalogs", good, "--module", "left", "--module", "left"],
                     ["--catalogs", root / "missing"], ["--catalogs", good, "--module", "missing"]]:
            invoke(args, False)
        bad = root / "bad"
        bad.mkdir()
        # Fail after a valid dependency has already entered the private inventory.
        (bad / "left.json").write_text(json.dumps(module("left", ["base", "broken"])))
        (bad / "base.json").write_text(json.dumps(module("base")))
        (bad / "broken.json").write_text('{"version":1')
        subprocess.run([LIFECYCLE, str(good), str(bad)], check=True)
        failures = []
        row = module("wrong"); failures.append(row)
        row = module("left"); row["version"] = 2; failures.append(row)
        row = module("left"); row["extra"] = True; failures.append(row)
        row = module("left", ["base", "base"]); failures.append(row)
        row = module("left", ["left"]); failures.append(row)
        row = module("left"); row["dependencies"] = "base"; failures.append(row)
        row = module("left"); del row["cpp"]; failures.append(row)
        function = {"id": "left.f", "name": "f", "parameters": [], "returns": "int"}
        binding = {"id": "left.f", "symbol": "native_f", "header": "test.hpp"}
        row = module("left"); row["functions"] = [function]; failures.append(row)
        row = module("left"); row["cpp"] = [binding]; failures.append(row)
        row = module("left"); row["functions"] = [function, function]; row["cpp"] = [binding]; failures.append(row)
        row = module("left"); row["functions"] = [copy.deepcopy(function)]; row["cpp"] = [binding]
        row["functions"][0]["returns"] = {"name": "vector"}; failures.append(row)
        row = module("left"); row["constants"] = [{"name": "LIMIT", "type": "int", "value": 1.5}]; failures.append(row)
        for row in failures:
            (bad / "left.json").write_text(json.dumps(row))
            invoke(["--catalogs", bad, "--module", "left"], False)
        # Diagnostics retain catalog, row and nested field context.
        diagnostic_cases = []
        row = module("left"); row["functions"] = [copy.deepcopy(function)]; row["cpp"] = [binding]
        row["functions"][0]["parameters"] = [{"name": "arg", "type": "int", "passing": "value", "variadic": 7}]
        diagnostic_cases.append((row, ["left.json", "functions[0]", "parameters[0]", "variadic"]))
        row = module("left"); row["functions"] = [copy.deepcopy(function)]; row["cpp"] = [binding]
        row["functions"][0]["returns"] = {"name": "vector", "arguments": [False]}
        diagnostic_cases.append((row, ["left.json", "functions[0]", "returns", "arguments[0]"]))
        row = module("left"); row["constants"] = [{"name": "LIMIT", "type": "int", "value": 1.5}]
        diagnostic_cases.append((row, ["left.json", "constants[0]", "value"]))
        row = module("left"); row["properties"] = [{"id": "p", "name": "p", "owner": "T"}]
        diagnostic_cases.append((row, ["left.json", "properties[0]", "type", "Missing catalog field"]))
        row = module("left"); row["cpp_build"] = {"sources": 7}
        diagnostic_cases.append((row, ["left.json", "cpp_build", "sources"]))
        row = module("left"); row["cpp_properties"] = [{"id": "p", "symbol": "p"}]
        diagnostic_cases.append((row, ["left.json", "cpp_properties[0]", "header"]))
        for row, context in diagnostic_cases:
            (bad / "left.json").write_text(json.dumps(row))
            message = invoke(["--catalogs", bad, "--module", "left"], False).stderr
            assert all(part in message for part in context), message
        (bad / "left.json").write_text('{"version":1,"version":1,"module":"left","dependencies":[],"functions":[],"cpp":[]}')
        invoke(["--catalogs", bad, "--module", "left"], False)
        # Cross-module identity conflicts and a genuine two-module cycle.
        for name in ["left", "base"]:
            row = module(name, ["base"] if name == "left" else [])
            row["functions"] = [function]; row["cpp"] = [binding]
            (bad / (name + ".json")).write_text(json.dumps(row))
        invoke(["--catalogs", bad, "--module", "left"], False)
        (bad / "left.json").write_text(json.dumps(module("left", ["base"])))
        (bad / "base.json").write_text(json.dumps(module("base", ["left"])))
        invoke(["--catalogs", bad, "--module", "left"], False)
    print("Startup configuration, graph, binding, failure/retry and model publication checks passed")


if __name__ == "__main__":
    main()

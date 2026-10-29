#!/usr/bin/env python3
"""Frozen G1 artifacts and Rust publication lifecycle. Never writes production output."""
import argparse
import hashlib
import json
from pathlib import Path
import subprocess
import tempfile

from corpus import cases


def require(ok, message):
    if not ok:
        raise AssertionError(message)


def digest(data):
    return hashlib.sha256(data).hexdigest()


def tree(directory):
    return {str(p.relative_to(directory)): p.read_bytes()
            for p in sorted(directory.rglob("*")) if p.is_file()}


def json_digest(data):
    # Only JSON object key ordering/whitespace is normalized here. Arrays and types survive.
    value = json.loads(data)
    return digest(json.dumps(value, sort_keys=True, separators=(",", ":"), ensure_ascii=False).encode())


def fingerprint(files):
    return {
        "files": {name: digest(data) for name, data in files.items()},
        "prepared_model": json_digest(files["prepared.json"]),
    }


def compare(expected, actual, context):
    require(expected.keys() == actual.keys(), f"{context}: artifact set differs")
    for name in expected:
        require(expected[name] == actual[name], f"{context}: artifact differs: {name}")


def invoke(generator, arguments, code=0, marker=""):
    run = subprocess.run([str(generator), *map(str, arguments)], capture_output=True, timeout=30)
    require(run.returncode == code,
            f"{generator.name}: expected exit {code}, got {run.returncode}: {run.stderr.decode(errors='replace')}")
    if code:
        require(not run.stdout and run.stderr, "failure must have stderr and no success stdout")
        require(marker.lower() in run.stderr.decode(errors="replace").lower(),
                f"missing rejection fragment {marker!r}: {run.stderr!r}")
    else:
        require(not run.stderr, f"unexpected stderr: {run.stderr!r}")
    return run.stdout.decode()


def target_facts(prepared, owner_prepared):
    layouts = {}
    for record in owner_prepared["ownership"]["records"]:
        for family in record["families"]:
            if family["family"] != "value":
                key = family["family"] + ":" + family["pointee"]
                value = [family["size"], family["alignment"]]
                require(key not in layouts or layouts[key] == value, "inconsistent concrete ownership layouts")
                layouts[key] = value
    return {"native_inputs": prepared["native_inputs"], "owner_size_alignment": layouts}


def reordered(data):
    """Object order is irrelevant; known set-like publication lists are tested separately."""
    def visit(value):
        if isinstance(value, dict):
            return {key: visit(item) for key, item in reversed(list(value.items()))}
        if isinstance(value, list):
            return [visit(item) for item in value]
        return value
    return visit(json.loads(data))


def publish(generator, case, work):
    work.mkdir(parents=True)
    metadata, publication, output = work / "runtime.json", work / "publication.json", work / "out"
    metadata.write_bytes(case.metadata)
    publication.write_bytes(case.publication)
    args = [metadata, publication, output]
    if case.rejection:
        invoke(generator, args, 1)
        require(not output.exists(), f"{case.name}: invalid input wrote output")
        return {"rejection_fragment": case.rejection}, None
    stdout = invoke(generator, args)
    first = tree(output)
    inventory = json.loads(first["inventory.json"])
    require(inventory == sorted(set(first) - {"inventory.json"}), f"{case.name}: inventory mismatch")
    timestamps = {p: p.stat().st_mtime_ns for p in output.rglob("*") if p.is_file()}
    checked = invoke(generator, args + ["--check"])
    require(json.loads(checked) == {**json.loads(stdout), "status": "verified"}, "check summary mismatch")
    compare(first, tree(output), case.name + " read-only check")
    require(all(p.stat().st_mtime_ns == mtime for p, mtime in timestamps.items()), "--check rewrote an artifact")
    invoke(generator, [metadata, publication, work / "repeat"])
    compare(first, tree(work / "repeat"), case.name + " repeated generation")
    m, p = reordered(case.metadata), reordered(case.publication)
    for field in ["types", "values", "short_aliases", "operators"]:
        if field in p:
            p[field].reverse()
    for field in ["vectors", "carriers"]:
        if field in p:
            p[field]["specializations"].reverse()
    if "ownership" in p:
        p["ownership"]["records"].reverse()
        for r in p["ownership"]["records"]:
            for field in ["families", "const_families", "constructors"]:
                if field in r:
                    r[field].reverse()
    metadata.write_text(json.dumps(m))
    publication.write_text(json.dumps(p))
    invoke(generator, [metadata, publication, work / "reordered"])
    compare(first, tree(work / "reordered"), case.name + " reordered inputs")
    return {"summary": stdout.strip(), **fingerprint(first)}, first


def cli_contract(generator, case, root):
    """Check filesystem behavior independently for every supplied generator."""
    root.mkdir()
    metadata, publication, output = root / "runtime.json", root / "publication.json", root / "out"
    metadata.write_bytes(case.metadata)
    publication.write_bytes(case.publication)
    args = [metadata, publication, output]
    invoke(generator, [], 1, "Usage:")
    invoke(generator, args + ["--unknown"], 1, "Unknown generator option")
    invoke(generator, [root / "missing.json", publication, output], 1, "Cannot read")
    invoke(generator, args + ["--check"], 1, "Cannot read")
    require(not output.exists(), "failed --check created output")
    invoke(generator, args)
    original = tree(output)
    chosen = output / "public/scpp/numeric.hpp"
    chosen.write_bytes(chosen.read_bytes() + b"// stale\n")
    stale = tree(output)
    invoke(generator, args + ["--check"], 1, "Stale generated file")
    compare(stale, tree(output), "stale check must not change output")
    invoke(generator, args)
    compare(original, tree(output), "ordinary generation repairs stale content")
    chosen.unlink()
    invoke(generator, args + ["--check"], 1, "Cannot read")
    require(not chosen.exists(), "missing output check rewrote file")
    invoke(generator, args)
    inventory = output / "inventory.json"
    for old, marker in [(b'{}', "Invalid old generated inventory"),
                        (b'["obsolete.cpp"]', "Obsolete generated outputs"),
                        (b'[', "byte")]:
        inventory.write_bytes(old)
        before = tree(output)
        for check in [[], ["--check"]]:
            invoke(generator, args + check, 1, marker)
            compare(before, tree(output), "invalid/obsolete inventory must not mutate output")
    # Current inventory tracks known artifacts, not arbitrary directory contents.
    inventory.write_bytes(original["inventory.json"])
    (output / "unlisted.txt").write_text("leave me alone")
    invoke(generator, args + ["--check"])
    invoke(generator, args)
    require((output / "unlisted.txt").read_text() == "leave me alone", "unlisted file changed")
    # An empty prior inventory is accepted; check remains based on expected outputs.
    inventory.write_text("[]")
    invoke(generator, args + ["--check"], 1, "Stale generated file")
    invoke(generator, args)
    require(inventory.read_bytes() == original["inventory.json"], "inventory repair failed")
    # A filesystem obstruction fails normally. Writes are not promised transactional.
    blocked = root / "blocked"
    blocked.write_text("not a directory")
    invoke(generator, [metadata, publication, blocked], 1)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--reference", type=Path, required=True)
    parser.add_argument("--candidate", type=Path)
    parser.add_argument("--source", type=Path, default=Path(__file__).resolve().parents[2])
    parser.add_argument("--work", type=Path, required=True)
    parser.add_argument("--capture", type=Path, help="explicitly write a new reviewed contract snapshot; never used by CTest")
    args = parser.parse_args()
    args.work.mkdir(parents=True, exist_ok=True)
    corpus = cases(args.source.resolve())
    require(len({case.name for case in corpus}) == len(corpus), "duplicate case name")
    snapshot = {"format": 1, "cases": {}}
    with tempfile.TemporaryDirectory(prefix="parity-", dir=args.work) as temporary:
        root = Path(temporary)
        prepared = {}
        for case in corpus:
            actual, files = publish(args.reference.resolve(), case, root / "reference" / case.name)
            actual["input_sha256"] = digest(case.metadata + b'\0' + case.publication)
            snapshot["cases"][case.name] = actual
            if case.name in {"default", "ownership"}:
                prepared[case.name] = json.loads(files["prepared.json"])
            if args.candidate:
                candidate, candidate_files = publish(args.candidate.resolve(), case, root / "candidate" / case.name)
                require({k: v for k, v in actual.items() if k != "input_sha256"} == candidate,
                        f"{case.name}: candidate summary, rejection or artifact fingerprints differ")
                if files is not None:
                    compare(files, candidate_files, case.name + " candidate")
        snapshot["target_facts"] = target_facts(prepared["default"], prepared["ownership"])
        cli_contract(args.reference.resolve(), corpus[0], root / "reference-cli")
        if args.candidate:
            cli_contract(args.candidate.resolve(), corpus[0], root / "candidate-cli")
    if args.capture:
        args.capture.write_text(json.dumps(snapshot, indent=2, sort_keys=True) + "\n")
    else:
        expected = json.loads(Path(__file__).with_name("baseline.json").read_text())
        require(expected["target_facts"] == snapshot["target_facts"],
                "native facts differ from the G1 fixture; use a reviewed target-specific baseline, never silently recapture")
        require(expected["cases"].keys() == snapshot["cases"].keys(), "parity corpus changed without baseline review")
        for name, case in snapshot["cases"].items():
            # CLI summaries are now structured JSON. Diagnostics retain failure status,
            # not C++ wording. Artifact bytes and prepared facts are unchanged.
            without_summary = lambda item: {k: v for k, v in item.items() if k != "summary"}
            require(without_summary(case) == without_summary(expected["cases"][name]), f"G1 baseline drift: {name}")
    summary = {"accepted": sum(not c.rejection for c in corpus),
               "rejected": sum(bool(c.rejection) for c in corpus),
               "candidate_compared": args.candidate is not None,
               "cli_contract": "passed", "target_facts": snapshot["target_facts"]}
    (args.work / "summary.json").write_text(json.dumps(summary, indent=2) + "\n")
    print(f"Generator contract passed: {summary['accepted']} accepted, {summary['rejected']} rejected; CLI lifecycle passed")


if __name__ == "__main__":
    main()

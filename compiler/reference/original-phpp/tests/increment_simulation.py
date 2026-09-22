"""Verify the built CLI and kill a native swap probe at each rename boundary."""

import argparse
import json
from pathlib import Path
import shutil
import subprocess
import tempfile
import time


ROOT = Path(__file__).resolve().parents[1]
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument("--no-stan", action="store_true")
args = parser.parse_args()
binary = ROOT / "src/.prism/build/main"
configuration = json.loads((ROOT / "tools/toolchain.json").read_text())
compiler = (ROOT / configuration["scpp"]).resolve()
toolchain = ["php", str(compiler)]


def fixture(parent):
    original, edited = parent / "project", parent / "project-edited"
    shutil.copytree(ROOT / "examples/three_files", original)
    shutil.copytree(original, edited, copy_function=shutil.copy2)
    return original, edited


def snapshot(folder):
    return {
        str(p.relative_to(folder)): (p.read_bytes(), p.stat().st_mtime_ns)
        for p in folder.rglob("*") if p.is_file()
    }


def invoke(original, edited, *, ok=True, debug=True, cwd=None):
    command = [str(binary), str(original / "project.json"), "--simulate-increment", str(edited)]
    if debug:
        command.append("--debug=json")
    result = subprocess.run(command, capture_output=True, text=True, cwd=cwd, timeout=20)
    assert (result.returncode == 0) == ok, (result.returncode, result.stdout, result.stderr)
    return result


def recover_manually(journal):
    # Execute the note's documented rules, never overwrite or remove project data.
    note = json.loads(journal.read_text().splitlines()[0])
    project, edited, backup = (Path(note[key]) for key in ("project", "edited", "backup"))
    assert "Never overwrite" in note["recovery"]
    if backup.exists() and not edited.exists():
        assert project.is_dir()
        project.rename(edited)
    if backup.exists() and not project.exists():
        backup.rename(project)
    assert project.is_dir() and edited.is_dir() and not backup.exists()
    journal.unlink()
    journal.parent.rmdir()


with tempfile.TemporaryDirectory(prefix="scpp_simulation_checks_") as temporary:
    workspace = Path(temporary)
    original, edited = fixture(workspace / "success with spaces")
    changed = edited / "src/nested/value.phs"
    changed.write_text(changed.read_text().replace("42", "420"))
    before = snapshot(original), snapshot(edited)
    result = invoke(original, edited, cwd=original)
    first, second = json.loads(result.stdout)["runs"]
    assert first["full_rebuild"] and not second["full_rebuild"]
    initial_rows = {row["relative_path"]: row for row in first["sources"]["files"]}
    for row in second["sources"]["files"]:
        assert row["id"] == initial_rows[row["relative_path"]]["id"]
        assert row["change_state"] == ("changed" if row["relative_path"] == "nested/value.phs" else "unchanged")
    assert (snapshot(original), snapshot(edited)) == before
    assert not Path(str(original) + ".scpp-simulation").exists()
    assert invoke(original, edited, debug=False).stdout == ""
    # Manifest changes and removals use the common early full-rebuild decisions.
    manifest = edited / "project.json"
    manifest.write_text(manifest.read_text() + "\n")
    assert json.loads(invoke(original, edited).stdout)["runs"][1]["full_rebuild"]
    shutil.copy2(original / "project.json", manifest)
    (edited / "src/answer.phs").unlink()
    removed = json.loads(invoke(original, edited).stdout)["runs"][1]
    assert removed["full_rebuild"] and len(removed["sources"]["removed_file_ids"]) == 1
    (edited / "src/new.phs").write_text("return 9;\n")
    assert any(row["change_state"] == "added" for row in json.loads(invoke(original, edited).stdout)["runs"][1]["sources"]["files"])
    # A catchable second-run failure restores byte content and metadata exactly.
    manifest.write_text('{"source_folders":["missing"],"entry":"src/main.phs"}')
    before = snapshot(original), snapshot(edited)
    assert "Cannot resolve source path" in invoke(original, edited, ok=False).stderr
    assert (snapshot(original), snapshot(edited)) == before
    assert not Path(str(original) + ".scpp-simulation").exists()
    # Malformed JSON must fail at the reader boundary and still restore the swap.
    manifest.write_text('{"entry":"\\q"}')
    before = snapshot(original), snapshot(edited)
    assert "json error at byte" in invoke(original, edited, ok=False).stderr
    assert (snapshot(original), snapshot(edited)) == before
    assert not Path(str(original) + ".scpp-simulation").exists()
    shutil.copy2(original / "project.json", manifest)
    invoke(original, edited)
    invoke(original, original, ok=False)
    nested = original / "nested-project"
    shutil.copytree(edited, nested)
    invoke(original, nested, ok=False)
    assert not Path(str(original) + ".scpp-simulation").exists()
    print("CLI simulation: two real refreshes, stable IDs, rebuild decisions, exact restoration, and error handling passed.", flush=True)

    # Isolated test binary: production swap implementation, test-only pause points.
    probe = workspace / "probe"
    shutil.copytree(ROOT / "src", probe, ignore=shutil.ignore_patterns(".prism"))
    shutil.copy2(ROOT / "tests/simulation_swap.phs", probe / "main.phs")
    command = toolchain + ["build"] + (["--no-stan"] if args.no_stan else [])
    build = subprocess.run(command, cwd=probe, capture_output=True, text=True)
    if build.returncode:
        print(build.stdout, build.stderr)
        subprocess.run(toolchain + ["error"], cwd=probe)
        subprocess.run(toolchain + ["full-error"], cwd=probe)
        raise SystemExit(build.returncode)
    probe_binary = probe / ".prism/build/main"
    stages = ("prepared", "original_parked", "edited_installed", "edited_returned", "original_restored")
    for stage in (*stages, "collision"):
        original, edited = fixture(workspace / stage)
        (edited / "src/main.phs").write_text("return 7;\n")
        before = snapshot(original), snapshot(edited)
        journal = Path(str(original) + ".scpp-simulation") / "journal.jsonl"
        requested = "edited_installed" if stage == "collision" else stage
        process = subprocess.Popen([str(probe_binary), str(original / "project.json"), str(edited), requested], stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
        try:
            deadline = time.monotonic() + 15
            while True:
                if journal.exists() and '"stage":"test_paused"' in journal.read_text():
                    break
                assert process.poll() is None, process.communicate()
                assert time.monotonic() < deadline, stage
                time.sleep(0.01)
            entries = [json.loads(line) for line in journal.read_text().splitlines()]
            assert entries[-2]["stage"] == requested
            if stage == "collision":
                edited.mkdir()
                marker = edited / "do-not-overwrite.txt"
                marker.write_text("unrelated content")
                Path(str(journal) + ".continue").touch()
                _, stderr = process.communicate(timeout=10)
                assert process.returncode != 0 and "without overwriting" in stderr
                assert marker.read_text() == "unrelated content"
                marker.unlink()
                edited.rmdir()
            else:
                process.kill()
                process.communicate(timeout=10)
                assert process.returncode != 0
            note_before = journal.read_bytes()
            assert "Pending simulation" in invoke(original, edited, ok=False).stderr
            ordinary = subprocess.run([str(binary), str(original / "project.json")], capture_output=True, text=True, timeout=10)
            assert ordinary.returncode != 0 and "Pending simulation" in ordinary.stderr
            assert journal.read_bytes() == note_before
            recover_manually(journal)
            assert (snapshot(original), snapshot(edited)) == before
        finally:
            if process.poll() is None:
                process.kill()
                process.communicate()
    print("Crash checks: killed native process at all five boundaries; journal-guided recovery restored both trees. Restore collision preserved unrelated data.")

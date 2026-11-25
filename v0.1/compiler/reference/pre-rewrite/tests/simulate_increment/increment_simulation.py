"""Verify the PHP CLI and kill its swap probe at each rename boundary."""

import json
from pathlib import Path
import shutil
import subprocess
import tempfile
import time


PROTOTYPE = Path(__file__).resolve().parents[2]
ROOT = PROTOTYPE
PHP = shutil.which("php")
if PHP is None:
    raise SystemExit("PHP 8.1+ CLI is required")
command_prefix = [PHP, str(PROTOTYPE / "src/main.php")]


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
    command = command_prefix + [ str(original / "project.json"), "--simulate-increment", str(edited)]
    if debug:
        command.append("--debug=json")
    result = subprocess.run(command, capture_output=True, text=True, cwd=cwd, timeout=20)
    assert (result.returncode == 0) == ok, (result.returncode, result.stdout, result.stderr)
    if ok and debug:
        for run in json.loads(result.stdout)["runs"]:
            assert run["completed"] is False and run["stopped_before"] == "build_native"
            assert {body["callable_id"] for body in run["bodies"]} == {signature["callable_id"] for signature in run["types"]["signatures"]}
            backend = run["backend"]
            assert backend["configuration"]["target_triple"]
            assert backend["configuration"]["data_layout"]
            assert backend["native_entry_adapter"] == "separate_module_plan"
            assert {binding["callable_id"] for binding in backend["callables"]} == {body["callable_id"] for body in run["bodies"]}
            assert all(binding["linkage"] == "external" and binding["calling_convention"] == "ccc" for binding in backend["callables"])
            lifetimes = {body["callable_id"]: body for body in run["lifetimes"]}
            assert lifetimes.keys() == {body["callable_id"] for body in run["bodies"]}
            lowered = {body["callable_id"]: body for body in run["lowered"]}
            assert lowered.keys() == lifetimes.keys()
            for plan in lowered.values():
                assert plan["entry_block_id"] == 1 and len(plan["blocks"]) == 1
                block = plan["blocks"][0]
                assert block["instruction_start"] == 0
                assert block["instruction_count"] == len(plan["instructions"])
                produced = set()
                for instruction in plan["instructions"]:
                    value_id = instruction["result_value_id"]
                    if value_id:
                        assert 1 <= value_id <= len(plan["values"]) and value_id not in produced
                        produced.add(value_id)
                    else:
                        assert instruction["kind"] == "call"
                    if instruction["kind"] == "call":
                        assert instruction["payload"]["callable_id"] in lowered
                assert len(produced) == len(plan["values"])
                assert block["terminator"]["value_id"] == 0 or block["terminator"]["value_id"] in produced
            for body in run["bodies"]:
                assert body["signature_dependencies"]
                analysis = lifetimes[body["callable_id"]]
                reachable = next((index + 1 for index, statement in enumerate(body["statements"]) if statement["kind"] == "return"), len(body["statements"]))
                assert analysis["reachable_statement_count"] == reachable
                assert analysis["falls_through"] == body["falls_through"]
                assert "values" not in analysis
                for value in analysis["lifetimes"]:
                    assert 1 <= value["statement_id"] <= reachable
                    assert 1 <= value["value_id"] <= len(body["values"])
                    statement = body["statements"][value["statement_id"] - 1]
                    assert value["end"] == ("return_copy" if statement["kind"] == "return" and statement["value_id"] == value["value_id"] else "discard")
                for statement in body["statements"]:
                    if statement["kind"] == "return" and statement["value_id"]:
                        assert body["values"][statement["value_id"] - 1]["kind"] != "conversion"
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
    ordinary = subprocess.run(command_prefix + [str(original / "project.json"), "--debug=json"], capture_output=True, text=True, check=True)
    ordinary_result = json.loads(ordinary.stdout)
    assert ordinary_result["completed"] is False and ordinary_result["stopped_before"] == "build_native"
    result = invoke(original, edited, cwd=original)
    first_run, second_run = json.loads(result.stdout)["runs"]
    first_symbols = first_run["symbols"]["current"]["rows"]
    second_symbols = second_run["symbols"]["current"]["rows"]
    assert len(first_run["resolutions"]) == len(first_symbols)
    assert len(second_run["resolutions"]) == len(second_symbols)
    answer_id = next(row["symbol_id"] for row in first_symbols if row["name"] == "answer")
    value_id = next(row["symbol_id"] for row in first_symbols if row["name"] == "value")
    for run in (ordinary_result, first_run, second_run):
        resolved = run["types"]
        signatures = resolved["signatures"]
        assert len(signatures) == 3
        entry_id = resolved["entry_symbol_id"]
        assert next(row for row in signatures if row["symbol_id"] == entry_id)["return_annotation_id"] == 0
        assert any(body["symbol_id"] == entry_id for body in run["bodies"])
        return_ids = {row["return_type_id"] for row in signatures}
        assert len(return_ids) == 1
        type_id = next(iter(return_ids))
        type_row = resolved["types"]["types"][type_id - 1]
        shape = resolved["types"]["representations"][type_row["representation_id"] - 1]
        assert type_row["name"] == "int" and shape["payload"]["bit_width"] == 64
    answer_result = next(row for row in second_run["resolutions"] if row["symbol_id"] == answer_id)
    assert [row["target_symbol_id"] for row in answer_result["bindings"]] == [value_id]
    first_value_symbol = next(row for row in first_symbols if row["name"] == "value")
    second_value_symbol = next(row for row in second_symbols if row["name"] == "value")
    assert first_value_symbol["symbol_id"] == second_value_symbol["symbol_id"]
    value_change = next(row for row in second_run["symbols"]["changes"]
                        if row["current"] and row["current"]["name"] == "value")
    assert value_change["own_status"] == "unchanged" and value_change["children_changed"] is True
    first, second = (run["inputs"] for run in json.loads(result.stdout)["runs"])
    assert ordinary_result["inputs"] == first
    assert first["full_rebuild"] and not second["full_rebuild"]
    initial_rows = {row["relative_path"]: row for row in first["sources"]["files"]}
    for row in second["sources"]["files"]:
        assert row["id"] == initial_rows[row["relative_path"]]["id"]
        assert row["change_state"] == ("changed" if row["relative_path"] == "nested/value.phs" else "unchanged")
    value_id = initial_rows["nested/value.phs"]["id"]
    first_value = next(item for item in first["tokens"] if item["source_file_id"] == value_id)
    second_value = next(item for item in second["tokens"] if item["source_file_id"] == value_id)
    assert [t["text"] for t in first_value["tokens"] if t["kind"] == "integer_literal"] == ["42"]
    assert [t["text"] for t in second_value["tokens"] if t["kind"] == "integer_literal"] == ["420"]
    first_ast = next(item for item in first["frontends"] if item["source_file_id"] == value_id)
    second_ast = next(item for item in second["frontends"] if item["source_file_id"] == value_id)
    assert len(first_ast["defined_entities"]) == len(second_ast["defined_entities"]) == 1
    assert [n["text"] for n in first_ast["nodes"] if n["kind"] == "integer_literal"] == ["42"]
    assert [n["text"] for n in second_ast["nodes"] if n["kind"] == "integer_literal"] == ["420"]
    assert (snapshot(original), snapshot(edited)) == before
    assert not Path(str(original) + ".scpp-simulation").exists()
    assert invoke(original, edited, debug=False).stdout == ""
    # Manifest changes and removals use the common early full-rebuild decisions.
    manifest = edited / "project.json"
    manifest.write_text(manifest.read_text() + "\n")
    assert json.loads(invoke(original, edited).stdout)["runs"][1]["inputs"]["full_rebuild"]
    shutil.copy2(original / "project.json", manifest)
    (edited / "src/answer.phs").unlink()
    before_deletion_error = snapshot(original), snapshot(edited)
    assert "Unknown function 'answer'" in invoke(original, edited, ok=False).stderr
    assert (snapshot(original), snapshot(edited)) == before_deletion_error
    assert not Path(str(original) + ".scpp-simulation").exists()
    # Remove its call as part of the successful deletion fixture.
    (edited / "src/main.phs").write_text("return 9;\n")
    removed = json.loads(invoke(original, edited).stdout)["runs"][1]["inputs"]
    assert removed["full_rebuild"] and len(removed["sources"]["removed_file_ids"]) == 1
    removed_id = removed["sources"]["removed_file_ids"][0]
    assert all(item["source_file_id"] != removed_id for item in removed["tokens"])
    assert all(item["source_file_id"] != removed_id for item in removed["frontends"])
    (edited / "src/new.phs").write_text("function extra(): int { return 9; }\n")
    assert any(row["change_state"] == "added" for row in json.loads(invoke(original, edited).stdout)["runs"][1]["inputs"]["sources"]["files"])
    # A lexical failure after the swap must preserve both directories exactly.
    new_source = edited / "src/new.phs"
    new_source.write_text("return @;")
    before = snapshot(original), snapshot(edited)
    assert "byte 7" in invoke(original, edited, ok=False).stderr
    assert (snapshot(original), snapshot(edited)) == before
    assert not Path(str(original) + ".scpp-simulation").exists()
    # Lexically valid but syntactically invalid edited source also restores both.
    new_source.write_text("return 9")
    before = snapshot(original), snapshot(edited)
    assert "Expected ';'" in invoke(original, edited, ok=False).stderr
    assert (snapshot(original), snapshot(edited)) == before
    assert not Path(str(original) + ".scpp-simulation").exists()
    new_source.write_text("function extra(): int { return 9; }\n")
    # A project-level duplicate after successful parsing also restores both trees.
    new_source.write_text("function value(): int { return 7; }")
    before = snapshot(original), snapshot(edited)
    assert "Duplicate" in invoke(original, edited, ok=False).stderr
    assert (snapshot(original), snapshot(edited)) == before
    assert not Path(str(original) + ".scpp-simulation").exists()
    new_source.write_text("function extra(): int { return 9; }\n")
    # A catchable second-run failure restores byte content and metadata exactly.
    manifest.write_text('{"source_folders":["missing"],"entry":"src/main.phs"}')
    before = snapshot(original), snapshot(edited)
    assert "Cannot resolve source path" in invoke(original, edited, ok=False).stderr
    assert (snapshot(original), snapshot(edited)) == before
    assert not Path(str(original) + ".scpp-simulation").exists()
    # Malformed JSON must fail at the reader boundary and still restore the swap.
    manifest.write_text('{"entry":"\\q"}')
    before = snapshot(original), snapshot(edited)
    assert "Invalid JSON:" in invoke(original, edited, ok=False).stderr
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

    # Test-only pause points invoke the production owner in a PHP subprocess.
    probe_command = [PHP, str(PROTOTYPE / "tests/support/simulation_swap.php")]
    stages = ("prepared", "original_parked", "edited_installed", "edited_returned", "original_restored")
    for stage in (*stages, "collision"):
        original, edited = fixture(workspace / stage)
        (edited / "src/main.phs").write_text("return 7;\n")
        before = snapshot(original), snapshot(edited)
        journal = Path(str(original) + ".scpp-simulation") / "journal.jsonl"
        requested = "edited_installed" if stage == "collision" else stage
        process = subprocess.Popen(probe_command + [ str(original / "project.json"), str(edited), requested], stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
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
            # The swap's production owner holds the lock even after the edited
            # directory replaces the original pathname. Bypass the CLI's journal
            # check here to prove the public compile API enforces the OS lock.
            if (original / "project.json").is_file():
                contender = subprocess.run([PHP, str(PROTOTYPE / "tests/support/project_lock_probe.php"),
                                            "compile", str(original / "project.json")],
                                           capture_output=True, text=True, timeout=10)
                assert contender.returncode != 0 and "already being compiled" in contender.stderr
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
            ordinary = subprocess.run(command_prefix + [ str(original / "project.json")], capture_output=True, text=True, timeout=10)
            assert ordinary.returncode != 0 and "Pending simulation" in ordinary.stderr
            assert journal.read_bytes() == note_before
            recover_manually(journal)
            assert (snapshot(original), snapshot(edited)) == before
        finally:
            if process.poll() is None:
                process.kill()
                process.communicate()
    print("Crash checks: killed PHP process at all five boundaries; journal-guided recovery restored both trees. Restore collision preserved unrelated data.")

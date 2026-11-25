"""Single source CLI defaults, exact membership, output protection and manifest compatibility."""
from pathlib import Path
import json
import shutil
import subprocess
import tempfile

ROOT = Path(__file__).resolve().parents[2]
CLI = ROOT / "src/main.php"

with tempfile.TemporaryDirectory(prefix="scpp_single_cli_") as temporary:
    work = Path(temporary)
    project = work / "one file"
    project.mkdir()
    source = project / "main.phs"
    source.write_text("return 42;\n")
    neighbor = project / "neighbor.phs"
    neighbor.write_text("not valid PHS!")
    manifest = project / "project.json"
    manifest.write_text("not valid JSON!")
    (project / "unrelated-link").symlink_to(work / "missing")
    originals = {p.name: p.read_bytes() for p in (source, neighbor, manifest)}
    before = set(project.iterdir())

    def compile_file(*options, input_path="main.phs", cwd=project):
        return subprocess.run(["php", str(CLI), str(input_path), *options], cwd=cwd,
                              capture_output=True, text=True, timeout=30)

    checked = compile_file("--check", "--debug=json")
    assert checked.returncode == 0, checked.stderr
    data = json.loads(checked.stdout)
    assert not data["completed"] and data["stopped_before"] == "build_native"
    assert set(project.iterdir()) == before
    assert data["inputs"]["manifest"]["content"] is None
    assert data["inputs"]["manifest"]["source_file_paths"] == ["main.phs"]

    # Default build stays quiet, does not run the program, and emits beside the input.
    built = compile_file()
    assert built.returncode == 0 and built.stdout == "" and built.stderr == "", built.stderr
    output = project / "main"
    assert set(project.iterdir()) == before | {output}
    assert subprocess.run([str(output)], timeout=3).returncode == 42
    for name, content in originals.items():
        assert (project / name).read_bytes() == content

    # Relative paths from another working directory still name output beside the source.
    elsewhere = compile_file("--debug=json", input_path="one file/main.phs", cwd=work)
    assert elsewhere.returncode == 0, elsewhere.stderr
    assert json.loads(elsewhere.stdout)["native"]["path"] == str(output)
    assert not (work / "main").exists()
    absolute = compile_file("--check", input_path=source, cwd=work)
    assert absolute.returncode == 0, absolute.stderr

    # Explicit names remain exact, including spaces and extensions.
    custom = project / "custom result.bin"
    explicit = compile_file("--output", custom.name, "--debug=json")
    assert explicit.returncode == 0, explicit.stderr
    assert json.loads(explicit.stdout)["native"]["path"] == str(custom)
    assert subprocess.run([str(custom)], timeout=3).returncode == 42

    for name in ("other.name.phs", ".hidden.phs"):
        named = project / name
        named.write_bytes(originals["main.phs"])
        result = compile_file(input_path=named)
        assert result.returncode == 0, result.stderr
        assert subprocess.run([str(named.with_suffix(""))], timeout=3).returncode == 42

    # Invalid requests must preserve inputs and already published executables.
    saved_output = output.read_bytes()
    protected = compile_file("--output", str(source))
    assert protected.returncode != 0 and "replace compiler input" in protected.stderr
    alias = project / "output-alias"
    alias.symlink_to(source)
    protected_alias = compile_file("--output", str(alias))
    assert protected_alias.returncode != 0 and "regular file path" in protected_alias.stderr
    conflict = compile_file("--check", "--output", str(custom))
    assert conflict.returncode != 0 and "cannot be combined" in conflict.stderr
    simulation = compile_file("--simulate-increment", str(work / "edited"))
    assert simulation.returncode != 0 and "requires a project manifest" in simulation.stderr
    missing = compile_file(input_path="missing.phs")
    assert missing.returncode != 0 and "Cannot locate project input" in missing.stderr
    (project / "directory.phs").mkdir()
    directory = compile_file(input_path="directory.phs")
    assert directory.returncode != 0 and "not a regular file" in directory.stderr
    source.write_text("return missing();\n")
    invalid = compile_file()
    assert invalid.returncode != 0 and "Unknown function" in invalid.stderr
    assert output.read_bytes() == saved_output
    source.write_bytes(originals["main.phs"])

    # Existing manifests still inspect by default and require --output to build.
    old_project = work / "manifest project"
    shutil.copytree(ROOT / "examples/three_files", old_project)
    old_before = {p.relative_to(old_project) for p in old_project.rglob("*")}
    inspected = compile_file("--debug=json", input_path=old_project / "project.json")
    assert inspected.returncode == 0, inspected.stderr
    assert not json.loads(inspected.stdout)["completed"]
    assert {p.relative_to(old_project) for p in old_project.rglob("*")} == old_before
    old_output = work / "manifest-program"
    linked = compile_file("--output", str(old_output), input_path=old_project / "project.json")
    assert linked.returncode == 0, linked.stderr
    assert subprocess.run([str(old_output)], timeout=3).returncode == 42
    rejected = compile_file("--output", str(old_project / "src/program"),
                            input_path=old_project / "project.json")
    assert rejected.returncode != 0 and "outside participating source folders" in rejected.stderr

print("Single-file CLI: defaults, membership, paths, protection, diagnostics and manifest compatibility passed.")

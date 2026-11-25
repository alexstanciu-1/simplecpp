"""Generate deterministic, reachable source workloads for the PHP prototype."""

import hashlib
import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[2]
DESTINATION = ROOT / "examples/scalability"
SIZES = {"64kb": 64 * 1024, "128kb": 128 * 1024, "256kb": 256 * 1024,
         "512kb": 512 * 1024, "1mb": 1024 * 1024,
         "5mb": 5 * 1024 * 1024, "10mb": 10 * 1024 * 1024}
FUNCTIONS_PER_FILE = 32
HELPERS = "function seed(): int { return 42; }\nfunction visit(): void { return; }\n"


def function(index):
    return f"""function sample_{index:06d}(): int {{
    visit();
    $value int = seed();
    $copy int = $value;
    {{
        $value int = 7;
        $value = $value;
        $copy = $copy;
    }}
    $value = $copy;
    return $value;
}}
"""


def generate():
    inventory = []
    for label, target in SIZES.items():
        files = {"src/helpers.phs": HELPERS}
        main = "$result int = seed();\n"
        source_bytes = len(HELPERS) + len(main) + len("return $result;\n")
        count = 0
        while source_bytes < target:
            count += 1
            path = f"src/group_{(count - 1) // FUNCTIONS_PER_FILE:04d}.phs"
            body = function(count)
            call = f"$result = sample_{count:06d}();\n"
            files[path] = files.get(path, "") + body
            main += call
            source_bytes += len(body) + len(call)
        files["src/main.phs"] = main + "return $result;\n"
        assert sum(len(s.encode()) for s in files.values()) == source_bytes
        assert abs(source_bytes / target - 1) <= 0.05
        project = DESTINATION / label
        # Regeneration may overwrite only known generated files, never sweep a directory.
        existing = {str(p.relative_to(project)) for p in project.rglob("*.phs")}
        if existing - files.keys():
            raise RuntimeError(f"Unexpected existing sources in {project}: {existing - files.keys()}")
        for name, content in files.items():
            output = project / name
            output.parent.mkdir(parents=True, exist_ok=True)
            output.write_text(content)
        (project / "project.json").write_text(json.dumps(
            {"source_folders": ["src"], "entry": "src/main.phs"}, indent=2) + "\n")
        digest = hashlib.sha256()
        for name, content in sorted(files.items()):
            digest.update(name.encode() + b"\0" + content.encode() + b"\0")
        inventory.append({"project": label, "target_bytes": target, "source_bytes": source_bytes,
                          "files": len(files), "functions": count + 2,
                          "generated_functions": count, "functions_per_file": FUNCTIONS_PER_FILE,
                          "edit_file": path, "edited_function": f"sample_{count:06d}",
                          "source_sha256": digest.hexdigest()})
    DESTINATION.mkdir(parents=True, exist_ok=True)
    (DESTINATION / "inventory.json").write_text(json.dumps(inventory, indent=2) + "\n")
    for row in inventory:
        print(f"{row['project']}: {row['source_bytes']:,} bytes, {row['files']} files, {row['functions']} functions")


if __name__ == "__main__":
    generate()

"""Compatibility entrypoint for the shared runtime concrete naming owner."""
from pathlib import Path
import sys
import json

import importlib.util
_project = Path(__file__).resolve().parents[2]
_candidates = [Path(__file__).resolve().parents[4] / "runtime/tools/type_names.py",
               _project / "dependencies/runtime/tools/type_names.py"]
_selection = _project / "dependencies/runtime.json"
if _selection.is_file():
    _package = Path(json.loads(_selection.read_text())["package"])
    if not _package.is_absolute():
        _package = _selection.parent / _package
    _candidates.append(_package / "tools/type_names.py")
_owner = next((path for path in _candidates if path.is_file()), None)
if _owner is None:
    raise RuntimeError("Missing shared naming tool; publish the runtime SDK first")
_shared_spec = importlib.util.spec_from_file_location("scpp_runtime_type_names", _owner)
_shared = importlib.util.module_from_spec(_shared_spec)
sys.modules[_shared_spec.name] = _shared
_shared_spec.loader.exec_module(_shared)
Type, Registry, parse, escape, structural_name = _shared.Type, _shared.Registry, _shared.parse, _shared.escape, _shared.structural_name
valid_name, CPP_KEYWORDS = _shared.valid_name, _shared.CPP_KEYWORDS
if __name__ == "__main__":
    _shared.main(Path(__file__).resolve().parents[1] / "types.json")

"""Composition policy facade over metadata-driven type publication."""
import json
from pathlib import Path
from type_publication import prepare as publish
TYPE = 'BackendFunctionCompositionInput'
OWNER = 'compile/backend/llvm_text_from_plan.hpp'
TYPE_HEADER = '__private_types/' + TYPE + '.hpp'
def metadata(path=None):
    return json.loads((Path(path) if path is not None else Path(__file__).with_name('type_publication_metadata.json')).read_text())['types']
def prepare(app, generated, graph):
    return publish(app, generated, graph, metadata()[TYPE])

"""Section packaging preserves the semantic metadata and rejects ambiguous inputs."""
import json
from pathlib import Path
import sys
import tempfile

root = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(root/'runtime/tools'))
import concrete_types
from public_package import sdk_identity

runtime = root/'runtime'
assembled = concrete_types.load_api_metadata(runtime)
resolved = concrete_types.metadata(runtime)
manifest = json.loads((runtime/'public/specializations.json').read_text())
assert concrete_types.digest(json.dumps(resolved, sort_keys=True).encode()) == manifest['api_identity']
assert 'sections' not in assembled
for path in (runtime/'metadata').rglob('*.json'):
    if path.name == 'public_api.json' or path.parent.name == 'public_api':
        assert path.stat().st_size < 200000

with tempfile.TemporaryDirectory(prefix='scpp-metadata-sections-') as directory:
    stage = Path(directory)
    metadata = stage/'metadata'
    metadata.mkdir()
    entry = metadata/'public_api.json'
    def write(data):
        entry.write_text(json.dumps(data))
    def reject(data, message):
        write(data)
        try:
            concrete_types.load_api_metadata(stage)
        except ValueError as error:
            assert message in str(error), error
        else:
            raise AssertionError('Accepted invalid section packaging')

    write(assembled)
    assert concrete_types.load_api_metadata(stage) == assembled
    (metadata/'families.json').write_text(json.dumps(assembled['families']))
    packaged = dict(assembled)
    del packaged['families']
    packaged['sections'] = {'families':'families.json'}
    write(packaged)
    assert concrete_types.load_api_metadata(stage) == assembled
    before = sdk_identity(stage)
    changed = dict(assembled['families'])
    changed['fixture'] = {}
    (metadata/'families.json').write_text(json.dumps(changed))
    assert sdk_identity(stage) != before
    assert 'fixture' in concrete_types.load_api_metadata(stage)['families']

    reject([], 'root must be an object')
    reject({'sections':[]}, 'sections must be an object')
    reject({'families':{},'sections':{'families':'families.json'}}, 'Duplicate')
    reject({'sections':{'sections':'families.json'}}, 'Duplicate')
    for invalid in ['', None, [], 3]:
        reject({'sections':{'families':invalid}}, 'nonempty string')
    for invalid in ['../outside.json', str(stage/'outside.json')]:
        reject({'sections':{'families':invalid}}, 'escapes')
    (stage/'outside.json').write_text('{}')
    (metadata/'escape.json').symlink_to(stage/'outside.json')
    reject({'sections':{'families':'escape.json'}}, 'escapes')
    (metadata/'array.json').write_text('[]')
    reject({'sections':{'families':'array.json'}}, 'section must be an object')
    write({'sections':{'families':'missing.json'}})
    try:
        concrete_types.load_api_metadata(stage)
    except FileNotFoundError:
        pass
    else:
        raise AssertionError('Accepted missing section')
print('PASS: section assembly, flat compatibility, stable API identity, SDK tracking and invalid packaging rejection')

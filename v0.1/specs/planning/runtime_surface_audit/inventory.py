"""Snapshot existing runtime catalogs for the public-surface audit; not a registry."""
import csv
import io
import json
from pathlib import Path

root = Path(__file__).resolve().parents[3]
out = Path(__file__).resolve().parent
# Replace only this tool's generated catalog snapshots, including obsolete chunks.
for stale in out.glob('catalog_*.tsv'):
    stale.unlink()

def compact(value):
    return json.dumps(value, ensure_ascii=False, separators=(',', ':'))

def row_bytes(row):
    stream = io.StringIO()
    csv.writer(stream, delimiter='\t', lineterminator='\n').writerow(row)
    return stream.getvalue().encode()

summary = [['module', 'dependencies', 'function_rows', 'distinct_names', 'declared_types', 'type_bindings', 'properties', 'constants', 'language_header_function_bindings']]
for path in sorted((root/'runtime/metadata/modules').glob('*.json')):
    data = json.loads(path.read_text())
    bindings = {row['id']: row for row in data.get('cpp', [])}
    language = sum('lang/' in row.get('header', '') for row in bindings.values())
    summary.append([data['module'], ','.join(data.get('dependencies', [])), len(data['functions']),
                    len({row['name'] for row in data['functions']}), len(data.get('types', [])),
                    len(data.get('cpp_types', [])), len(data.get('properties', [])), len(data.get('constants', [])), language])
    header = row_bytes(['kind', 'contract_record', 'native_binding', 'public_migration_status'])
    chunks = [bytearray(header)]
    groups = [('function', data['functions'], bindings),
              ('type', data.get('types', []), {r['id']:r for r in data.get('cpp_types', [])}),
              ('representation', data.get('cpp_types', []), {}),
              ('property', data.get('properties', []), {r['id']:r for r in data.get('cpp_properties', [])}),
              ('constant', data.get('constants', []), {}),
              ('initialization_binding', data.get('cpp_initialization', []), {})]
    for kind, rows, lookup in groups:
        for row in rows:
            # This is an evidence snapshot. It deliberately does not assign semantic support.
            item = row_bytes([kind, compact(row), compact(lookup.get(row.get('id'), {})), 'requires mapping/proof; see audit'])
            if len(chunks[-1]) + len(item) > 180000:
                chunks.append(bytearray(header))
            chunks[-1].extend(item)
    for index, chunk in enumerate(chunks, 1):
        (out/f'catalog_{data["module"]}_{index}.tsv').write_bytes(chunk)
(out/'modules.tsv').write_bytes(b''.join(row_bytes(row) for row in summary))
config = json.loads((root/'runtime/specs/config.json').read_text())
rows = [['family', 'existing_config_record', 'referenced_header_exists']]
for family, value in config['types'].items():
    rows.append([family, compact(value), (root/'runtime/include'/value['header']).is_file()])
(out/'configured_types.tsv').write_bytes(b''.join(row_bytes(row) for row in rows))
print('Snapshotted', len(summary)-1, 'modules;', sum(int(r[2]) for r in summary[1:]), 'function rows')

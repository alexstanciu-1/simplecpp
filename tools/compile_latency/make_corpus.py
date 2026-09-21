#!/usr/bin/env python3
"""Freeze bounded body-edit witnesses from a copied workload, with exact source hashes."""
import hashlib
import json
import re
import sys
from pathlib import Path
from experiment import require_scratch

app = Path(sys.argv[1]).resolve()
require_scratch(app)
rows = []
for path in sorted(app.rglob('*.phs')):
    if '.prism' in path.parts or path.name == 'main.phs':
        continue
    text = path.read_text()
    matches = list(re.finditer(r'public static function (\w+)\(\): (int|uint16|string|bool) \{\n\t\treturn ([^;\n]+);\n\t}', text))
    for match in matches:
        value = match[3]
        literal = re.fullmatch(r'(\d+)|"([^"\\]*)"|(true|false)|structure_row_ids::uint16_from_int\((\d+)\)', value)
        if literal is None:
            continue
        owners = re.findall(r'(?:final )?class (\w+)', text[:match.start()])
        if not owners:
            continue
        expected = literal[1] or literal[2] or literal[3] or literal[4]
        if expected is None:
            continue
        row = {'source': str(path.relative_to(app)), 'owner': owners[-1], 'method': match[1], 'type': match[2],
               'expression': value, 'expected': expected, 'source_lines': len(text.splitlines()),
               'original': match[0], 'source_sha256': hashlib.sha256(text.encode()).hexdigest()}
        rows.append(row)
        break
rows.sort(key=lambda r: (-r['source_lines'], r['source']))
selected = rows[:12]
for lower, upper, count in [(200, 700, 4), (1, 200, 4)]:
    selected.extend([r for r in rows if lower <= r['source_lines'] < upper and r not in selected][:count])
for i, row in enumerate(selected):
    row['id'] = f'body_{i:02}'
(app.parent / 'corpus.json').write_text(json.dumps(selected, indent=2) + '\n')
main = app / 'main.phs'
if not (app.parent / 'original-main.phs').exists():
    (app.parent / 'original-main.phs').write_text(main.read_text())
text = (app.parent / 'original-main.phs').read_text()
for row in selected:
    expression = f'{row["owner"]}::{row["method"]}()'
    if row['type'] != 'string':
        expression = '(int)' + expression
        if row['expected'] in ['true', 'false']:
            row['expected'] = '1' if row['expected'] == 'true' else '0'
    text += f'echo "latency_{row["id"]}=", {expression}, "\\n";\n'
main.write_text(text)
(app.parent / 'corpus.json').write_text(json.dumps(selected, indent=2) + '\n')
print(json.dumps({'cases': len(selected), 'large_units': [r['source'] for r in selected[:5]]}))

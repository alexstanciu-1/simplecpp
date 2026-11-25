"""Refresh lexical registry audit candidates; never infer semantic disposition."""
import argparse
from collections import Counter
import json
from pathlib import Path
import re

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
STAGES = ['01_prepare_inputs', '02_tokenize', '03_parse', '04_analyze',
          '05_backend', '06_native', 'compiler']


def identity(row):
    return tuple(row[key] for key in ['path', 'candidate_kind', 'subject', 'excerpt'])


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--inventory', type=Path, required=True)
    parser.add_argument('--output', type=Path, required=True)
    args = parser.parse_args()
    previous = json.loads(args.inventory.read_text())
    prior_rows = previous['candidates'] + previous.get('retired_candidates', [])
    prior = {identity(row): row for row in prior_rows}
    patterns = {name: re.compile(pattern) for name, pattern in previous['patterns'].items()}
    files = sorted(path for stage in STAGES for path in (APP / stage).rglob('*.php'))
    rows = []
    for path in files:
        relative = str(path.relative_to(ROOT))
        for number, line in enumerate(path.read_text().splitlines(), 1):
            for kind, pattern in patterns.items():
                match = pattern.search(line)
                if match is None:
                    continue
                row = dict(path=relative, line=number, candidate_kind=kind,
                           subject=match[1], excerpt=line.strip(),
                           disposition='pending source review', parked='/llvm/' in relative)
                old = prior.get(identity(row))
                if old is not None:
                    row['disposition'] = old['disposition']
                rows.append(row)
    current = {identity(row) for row in rows}
    retired = [row for key, row in prior.items() if key not in current]
    report = dict(status='planning', scope='lexical candidates, not completed semantic audit',
                  patterns=previous['patterns'],
                  scanned_files=[str(path.relative_to(APP)) for path in files],
                  candidates=rows, retired_candidates=retired)
    args.output.write_text(json.dumps(report, indent=2) + '\n')
    print(json.dumps(dict(files=len(files), current=len(rows), retired=len(retired),
                          kinds=dict(Counter(row['candidate_kind'] for row in rows)))))


if __name__ == '__main__':
    main()

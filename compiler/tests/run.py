"""Run registered rewrite-stage outcome proofs; readiness must match the registered sources."""
import argparse
import importlib.util
import json
from pathlib import Path
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[2]
STAGES = [('read_manifest', Path(__file__).parent / 'read_manifest/run.py'),
          ('discovery', Path(__file__).parent / 'discovery/run.py'),
          ('snapshot', Path(__file__).parent / 'snapshot/run.py'),
          ('tokenizer', Path(__file__).parent / 'tokenizer/run.py'),
          ('parser_foundation', Path(__file__).parent / 'parser_foundation/run.py'),
          ('expressions', Path(__file__).parent / 'expressions/run.py'),
          ('statements', Path(__file__).parent / 'statements/run.py'),
          ('syntax_access', Path(__file__).parent / 'syntax_access/run.py'),
          ('parser_project', Path(__file__).parent / 'parser_project/run.py'),
          ('collect_symbols', Path(__file__).parent / 'collect_symbols/run.py'),
          ('entry_preparation', Path(__file__).parent / 'entry_preparation/run.py'),
          ('type_representations', Path(__file__).parent / 'type_representations/run.py'),
          ('type_lifetimes', Path(__file__).parent / 'type_lifetimes/run.py'),
          ('scalar_catalog', Path(__file__).parent / 'scalar_catalog/run.py'),
          ('name_lookup', Path(__file__).parent / 'name_lookup/run.py'),
          ('lexical_resolution', Path(__file__).parent / 'lexical_resolution/run.py'),
          ('resolution_project', Path(__file__).parent / 'resolution_project/run.py'),
          ('type_store', Path(__file__).parent / 'type_store/run.py')]


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    parser.add_argument('--target-checkout', type=Path)
    parser.add_argument('--candidate-revision')
    args = parser.parse_args()
    files = []
    for name, path in STAGES:
        spec = importlib.util.spec_from_file_location(name, path)
        module = importlib.util.module_from_spec(spec); spec.loader.exec_module(module)
        files.extend(module.FILES)
    ready = json.loads((ROOT / 'compiler/portability.json').read_text())['files']
    if not ready or set(ready) != set(files) or len(files) != len(set(files)):
        raise SystemExit('Active ready set must match registered stage proof sources')
    results = args.results.resolve(); results.mkdir(parents=True, exist_ok=False)
    for name, path in STAGES:
        cmd = [sys.executable, str(path), '--results', str(results / name)]
        if args.target_checkout: cmd += ['--target-checkout', str(args.target_checkout)]
        if args.candidate_revision: cmd += ['--candidate-revision', args.candidate_revision]
        subprocess.run(cmd, cwd=ROOT, check=True)
    (results / 'summary.json').write_text(json.dumps({'passed': True, 'stages': [n for n, _ in STAGES],
        'production_files': len(files), 'native': bool(args.target_checkout)}, indent=2) + '\n')


if __name__ == '__main__': main()

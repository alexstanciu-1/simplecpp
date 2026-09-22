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
          ('snapshot', Path(__file__).parent / 'snapshot/run.py')]


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

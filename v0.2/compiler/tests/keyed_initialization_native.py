"""Run source keyed-initialization fixtures with either native compiler."""
import argparse
import json
from pathlib import Path
from list_initialization_native import ROOT, prove, run

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--out', type=Path, required=True)
    parser.add_argument('--cxx', default='clang++')
    parser.add_argument('--fixtures', type=Path)
    args = parser.parse_args()
    args.out.mkdir(parents=True, exist_ok=True)
    fixtures = args.fixtures or args.out / 'fixtures.json'
    if args.fixtures is None:
        run(['php', ROOT / 'compiler/tests/keyed_initialization.php', fixtures])
    prove(args.out, args.cxx, ROOT / 'runtime', json.loads(fixtures.read_text()),
          [('hash', ROOT / 'runtime/generated/src/hash.cpp')])

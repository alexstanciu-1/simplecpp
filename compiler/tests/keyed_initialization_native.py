"""Run source keyed-initialization fixtures with either native compiler."""
import argparse
import json
from pathlib import Path
from entry_probe import raw_entry
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
    cases = json.loads(fixtures.read_text())
    for case in cases.values():
        if error := case.get('error'):
            case['cpp'] = ('#include <stdexcept>\n#include <string_view>\n' + raw_entry(case['cpp']) +
                           '\nint main(){try {tested_entry();} catch(const std::exception& e) {'
                           'return std::string_view(e.what()) == ' + json.dumps(error) + ' ? 8 : 90;} return 91;}')
    prove(args.out, args.cxx, ROOT / 'runtime', cases,
          [('hash', ROOT / 'runtime/generated/src/hash.cpp'), ('owners', ROOT / 'runtime/src/ownership/owner_access.cpp'),
           ('entry', ROOT / 'runtime/src/entry/entry.cpp')])

"""Observe PHP-flavor presence idioms on present, false-like and missing values."""
from pathlib import Path
import sys
import tempfile

from snippets import Probe, exercise

# Each row fixes the static type. Columns within a cell use these exact inputs
# in order. A failed extraction is a recorded outcome, not a match for false.
families = {
    'nullable-int': ('$v ?int=INPUT;', ['0', '2', 'null'], '8', ()),
    'nullable-bool': ('$v ?bool=INPUT;', ['false', 'true', 'null'], 'true', ()),
    'nullable-text': ('$v ?string=INPUT;', ['""', '"ok"', 'null'], '"fallback"', ()),
    'result-or-bool': ('$v result_or_bool<bool> = INPUT;', ['false', 'true', 'false_sentinel', 'true_sentinel'], 'false', ()),
    'search': ('$v=strpos("abc",INPUT);', ['"a"', '"c"', '"z"'], '8', ()),
    'environment': ('$v=getenv(INPUT);', ['"QOS_EMPTY"', '"QOS_WORD"', '"QOS_MISSING"'], '"fallback"', ()),
    'hash-find': ('$h hash<int,string> = ["zero"=>0,"two"=>2]; $v=hash_find($h,INPUT);',
                  ['"zero"', '"two"', '"absent"'], '8', ()),
    'json-result': ('$v=json_decode_mixed(INPUT);', ['"false"', '"null"', '"{"'], 'false', ('json',)),
    'mixed': ('$v mixed=INPUT;', ['false', '0', '""', 'null', '[]'], 'false', ()),
    'dynamic': ('$v dynamic=INPUT;', ['false', '0', '""', 'null', '[]'], 'false', ()),
    'class-handle': ('class Setting { public int $limit=0; } $v ?Setting=INPUT;',
                     ['new Setting()', 'null'], 'new Setting()', ()),
}
# bool outputs always use the same literal characters regardless of echo's overloads.
forms = {
    'isset': 'echo isset($v) ? "Y" : "N";',
    'condition': 'echo $v ? "Y" : "N";',
    'eq-false': 'echo ($v === false) ? "Y" : "N";',
    'eq-null': 'echo ($v === null) ? "Y" : "N";',
    'empty': 'echo empty($v) ? "Y" : "N";',
    'fallback': '$chosen=$v ?? FALLBACK; echo $chosen ? "Y" : "N";',
}


def main():
    import os
    os.environ.update(QOS_EMPTY='', QOS_WORD='ok')
    os.environ.pop('QOS_MISSING', None)
    family_names = sys.argv[1:] or list(families)
    for name in family_names:
        setup, states, fallback, modules = families[name]
        print(name, flush=True)
        for operation, body in forms.items():
            outcomes = []
            reasons = set()
            for literal in states:
                code = setup.replace('INPUT',literal) + '\n' + body.replace('FALLBACK',fallback) + ' return 0;'
                with tempfile.TemporaryDirectory(prefix='presence-') as directory:
                    stage, observed = exercise(Probe(name, code, modules=modules, general=True), Path(directory))
                if stage == 'output':
                    outcomes.append(observed)
                else:
                    outcomes.append('B' if stage == 'build error' else 'R')
                    reasons.add(observed.split(': ', 1)[-1])
            print(f'  {operation:10} {" ".join(outcomes)}', ' | '.join(sorted(reasons)), flush=True)


if __name__ == '__main__':
    main()

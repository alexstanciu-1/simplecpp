"""Exercise prepared name targets across provider edits, shadowing and recovery."""
from pathlib import Path
import tempfile

from classes_native import fixture
from collection_goal_native import revisions


def prove(work, flavor):
    forms = fixture(flavor)
    var = forms['v']
    sep = '.' if flavor == 'js' else '::'
    enum = 'enum Kind {case Item=4;}' if flavor == 'php' else 'enum Kind {Item=4}'
    constant = 'const LIMIT:int=3;' if flavor == 'js' else 'const int LIMIT=3;'
    shared = {'php': 'global $sharedValue int=2;', 'cpp': 'global int sharedValue=2;',
              'js': 'global let sharedValue:int=2;'}[flavor]
    main = forms['infer']('local', '1') + f'echo {var}local,{var}sharedValue,LIMIT,enum_value(Kind{sep}Item);'
    pad = flavor == 'php'
    if pad:
        main += 'echo STR_PAD_RIGHT;'
    main += 'return 8;'

    def output(values):
        return values + (b'1' if pad else b'')

    edits = [(None, None, output(b'1234')), (None, None, output(b'1234')),
             ('global', shared.replace('=2', '=25'), output(b'12534')),
             ('constant', constant.replace('=3', '=31'), output(b'125314')),
             ('enumdef', enum.replace('Item=4', 'Item=42'), output(b'1253142')),
             ('constant', None, None), (None, None, None),
             ('constant', constant, output(b'125342'))]
    if pad:
        edits += [('shadow', 'const int STR_PAD_RIGHT=8;', b'1253428'),
                  ('shadow', None, b'1253421')]
    revisions(work, flavor, dict(main=main, enumdef=enum, constant=constant, **{'global': shared}), edits)


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-name-binding-') as tmp:
        for flavor in ('php', 'cpp', 'js'):
            prove(Path(tmp), flavor)

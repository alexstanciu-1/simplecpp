"""Compare native signature identity and lifetime with the prepared PHP frontend types."""
import json
from pathlib import Path
import shutil
import subprocess
import tempfile

from translated_preparation_recovery import ROOT, fresh_build, measure

ROWS = [
    ['int()', 'int(int)', 'int(int)', 'string(int)', 'int(string,int)', 'int(int,string)',
     'int(int&)', 'int&(int&)', 'int(int&,int)', 'int(int,int&)', 'int&(int&,int)',
     'int&(int,int&)', 'int(vector<string>)', 'int(string...)', 'int(Box)', 'int(Box)'],
    ['int(int)', 'int(Box)', 'int(Box)'],
]
GROUPS = [0, 1, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 13, 1, 14, 14]


def verify(work):
    observed = subprocess.run(['php', ROOT/'compiler/tests/callable_signature_oracle.php'],
                              input=json.dumps(ROWS), text=True, stdout=subprocess.PIPE, check=True)
    cases = json.loads(observed.stdout)
    assert [case[-1] for case in cases] == GROUPS, cases
    app = work/'app'
    app.mkdir()
    (app/'module.json').write_text('{}')
    for name in ('index', 'verify'):
        shutil.copyfile(ROOT/f'compiler/examples/signature_interning/{name}.phs', app/f'{name}.phs')
    main = ['$cache = new Signature_Index();', '$live vector<Owned_Callable> = [];']
    for key in sorted({key for ids, *_ in cases for key in ids}):
        main.append(f'$type_{key} = new Component("Component");')
    for ids, modes, reference_result, variadic, _ in cases:
        main.append(f'$sig = new Signature($type_{ids[0]}, {json.dumps(reference_result)}, {json.dumps(variadic)});')
        for key, mode in zip(ids[1:], modes, strict=True):
            main.append(f'$sig->input($type_{key}, {json.dumps(mode)});')
        main.append('record($cache, $sig, $live);')
    main.extend(['check_lifetime($cache);', 'return 8;'])
    (app/'main.phs').write_text('\n'.join(main))
    generated = work/'generated'
    result = fresh_build(app, generated)
    assert 'error' not in result, result
    measure(generated, work/'native', ''.join(f'{group}\n' for group in GROUPS).encode())


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-signature-index-') as temp:
        verify(Path(temp))

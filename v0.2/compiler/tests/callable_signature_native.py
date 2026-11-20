"""Bootstrap proof of one signature consumer shared by unrelated source and runtime declarations."""
import json
from pathlib import Path
import tempfile

from collection_goal_native import revisions
from interfaces_native import forms


def prove(work, flavor):
    s, _, klass, method, _, implement = forms(flavor)
    contract = {
        'php': 'public function name():string; public function return_type_def():TypeDef;',
        'cpp': 'string name(); TypeDef return_type_def();',
        'js': 'name():string; return_type_def():TypeDef;',
    }[flavor]

    def provider(name, label):
        methods = method('name', f'return "{label}";', result='string', intent='override')
        methods += method('return_type_def', 'return new TypeDef();', result='TypeDef', intent='override')
        return implement(name, methods, interfaces=('Signature',))

    receiver = s['v']+'target'+s['access']
    inspect = s['function']('inspect', 'void', [('target','Signature')],
                            'echo '+receiver+'name(),'+receiver+'return_type_def()'+s['access']+'code;')
    sources = dict(signature='interface Signature {'+contract+'}', types=klass('TypeDef',s['field']('code','int','8')),
                   source=provider('Source','S'), runtime=provider('Runtime','R'), inspect=inspect,
                   main='inspect(new Source());inspect(new Runtime());return 8;')
    revisions(work,flavor,sources,[(None,None,b'S8R8'),(None,None,b'S8R8'),('source',provider('Source','T'),b'T8R8'),
                                   ('source',None,None),('source',sources['source'],b'S8R8')])

    output = work/(flavor+'-out')
    units = json.loads((output/'manifest.json').read_text())['sources']
    unit = output/units[f'{flavor}/inspect.{flavor}']
    signature = unit.with_suffix('.hpp').read_text()
    assert 'owner_Signature var_target_0' in signature
    assert 'owner_Source' not in signature and 'owner_Runtime' not in signature


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-callable-signature-') as tmp:
        for flavor in ('php','cpp','js'):
            prove(Path(tmp),flavor)

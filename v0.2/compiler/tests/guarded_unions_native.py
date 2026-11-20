"""Positive union-arm guards preserve ownership, expire at rebinding and follow ancestry edits."""
import argparse
from pathlib import Path
import tempfile

from collection_goal_native import revisions
from interfaces_native import forms


def prove(work, flavor):
    s, _, klass, method, _, _ = forms(flavor)
    item = s['v'] + 'item'
    fn = s['function']
    pair = 'NameExpr|FieldExpr'
    arm = klass('NameExpr', s['field']('n', 'int', '4') + method('read', 'return '+s['v']+'this'+s['access']+'n;'), 'Expr')
    base = klass('Expr', method('kind', 'return 0;', intent='virtual'))
    read = item+s['access']+'n'
    cond = item+' instanceof NameExpr'
    consume = fn('consume','int',[('item','NameExpr')],'return '+item+s['access']+'read();')
    def inspect(body):
        return fn('inspect','int',[('item',pair)],body)
    good = inspect('if ('+cond+') {'+read+'=6;return consume('+item+')+'+read+';}return 0;')
    main = 'echo inspect(new Derived()),inspect(new FieldExpr());return 8;'
    leaf = klass('Derived','','NameExpr')
    param = {'php':'NameExpr $item','cpp':'NameExpr item','js':'item:NameExpr'}[flavor]
    ref = {'php':'NameExpr &$item','cpp':'NameExpr &item','js':'&item:NameExpr'}[flavor]
    replace = fn('replace','void',[('item','NameExpr')],item+'=new NameExpr();').replace(param,ref)
    sources = dict(base=base, arm=arm, other=klass('FieldExpr','','Expr'), leaf=leaf, inspect=good, api=consume+replace, main=main)
    revisions(work,flavor,sources,[
        (None,None,b'120'),(None,None,b'120'),
        ('inspect',inspect('if ('+cond+') {return 1;}return '+read+';'),'Unknown field'),
        ('inspect',inspect('if ('+cond+') {'+item+'=new FieldExpr();return '+read+';}return 0;'),'Unknown field'),
        ('inspect',good,b'120'),('leaf',klass('Derived','','FieldExpr'),b'00'),
        ('leaf',leaf,b'120'),('base',klass('Expr',''),'polymorphic'),
        (None,None,'polymorphic'),('base',base,b'120'),('arm',None,None),('arm',arm,b'120'),
        ('inspect',inspect('if ('+cond+') {replace('+item+');}return 0;'),'exact storage type'),
        ('inspect',inspect('if (!('+cond+')) {return 0;}return '+read+';'),'Unknown field'),
        ('inspect',inspect('if ('+cond+') {return '+item+s['access']+'read();}return 0;'),b'40'),
        ('inspect',inspect('if ('+cond+') {'+s['infer']('saved',item)+item+'=new FieldExpr();return '+
                           s['v']+'saved'+s['access']+'read();}return 0;'),b'40'),
        ('inspect',good,b'120'),
    ])
    units = work/(flavor+'-out')/'units'
    output = '\n'.join(p.read_text() for p in units.glob('*.cpp'))
    assert output.count('auto* scpp_guard_') == 1
    assert 'scpp_guard_' in next(line for line in output.splitlines() if '->n = ' in line)
    headers = '\n'.join(p.read_text() for p in units.glob('*.hpp'))
    assert headers.count('dynamic_cast<scpp_records::NameExpr*>(source.view)') == 1


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-union-guards-') as tmp:
        work = args.output or Path(tmp)
        work.mkdir(exist_ok=True)
        for flavor in ('php','cpp','js'):
            prove(work, flavor)

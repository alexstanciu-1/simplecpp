"""Positive and remaining-arm guards preserve ownership, expire at rebinding and follow ancestry edits."""
import argparse
from pathlib import Path
import tempfile

from collection_goal_native import revisions
from interfaces_native import forms
from exceptions_native import catch


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
    field = klass('FieldExpr', s['field']('offset','int','3'), 'Expr')
    offset = item+s['access']+'offset'
    offset_of = fn('offsetOf','int',[('item','FieldExpr')],'return '+offset+';')
    body = 'if ('+cond+') {'+read+'=4;return consume('+item+');}'+offset+'=7;return offsetOf('+item+')+'+offset+';'
    both = inspect(body)
    yes = read+'=4;return consume('+item+');'
    no = offset+'=7;return offsetOf('+item+')+'+offset+';'
    positive = inspect('if ('+cond+') {'+yes+'}else {'+no+'}')
    negative = inspect('if (!('+cond+')) {'+no+'}else {'+yes+'}')
    exceptional = inspect(body.replace('return consume('+item+');', 'throw new Failure();'))
    caught = ('try {echo inspect(new NameExpr());}'+catch(flavor,'Failure','caught')+
              '{echo "caught";}echo inspect(new FieldExpr());return 8;')
    sources = dict(base=base, arm=arm, other=field, leaf=leaf, inspect=good, api=consume+replace+offset_of, main=main, failure=klass('Failure','','Exception'))
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
        ('inspect',both,b'414'),
        ('inspect',inspect(body.replace('return consume('+item+');','if (true) {return 1;}echo "fall";')),'Unknown field'),
        ('inspect',inspect(body.replace(offset+'=7;', item+'=new NameExpr();'+offset+'=7;')),'Unknown field'),
        ('inspect',both,b'414'),('leaf',klass('Derived','','FieldExpr'),b'1414'),('leaf',leaf,b'414'),
        ('inspect',inspect('if ('+cond+') {return 4;}'+s['infer']('saved',item)+item+'=new NameExpr();return '+
                           s['v']+'saved'+s['access']+'offset;'),b'43'),
        ('inspect',both,b'414'),('main',caught,b'414'),('inspect',exceptional,b'caught14'),
        ('other',klass('FieldExpr','','Expr'),'Unknown field'),(None,None,'Unknown field'),
        ('other',None,None),('other',field,b'caught14'),('inspect',both,b'414'),('main',main,b'414'),
        ('inspect',positive,b'414'),('inspect',negative,b'414'),
        ('inspect',positive.replace(yes,item+'=new FieldExpr();'+yes),'Unknown field'),
        ('inspect',negative.replace(no,item+'=new NameExpr();'+no),'Unknown field'),
        ('inspect',positive.replace(yes,item+'=new FieldExpr();return 8;'),b'814'),
        ('inspect',negative.replace(no,item+'=new NameExpr();return 8;'),b'48'),
        ('inspect',inspect('if ('+cond+') {'+read+'=4;}else {'+offset+'=7;}return '+offset+';'),'Unknown field'),
        ('inspect',negative,b'414'),('leaf',klass('Derived','','FieldExpr'),b'1414'),
        ('other',None,None),('other',field,b'1414'),('leaf',leaf,b'414'),
    ])
    units = work/(flavor+'-out')/'units'
    output = '\n'.join(p.read_text() for p in units.glob('*.cpp'))
    assert output.count('auto* scpp_guard_') == 1
    assert 'scpp_guard_' in next(line for line in output.splitlines() if '->n = ' in line)
    assert 'static_cast<scpp_records::FieldExpr*>' in output
    assert '->offset = 7' in output
    headers = '\n'.join(p.read_text() for p in units.glob('*.hpp'))
    assert headers.count('dynamic_cast<scpp_records::NameExpr*>(source.view)') == 1
    assert 'dynamic_cast<scpp_records::FieldExpr*>' not in headers + output


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-union-guards-') as tmp:
        work = args.output or Path(tmp)
        work.mkdir(exist_ok=True)
        for flavor in ('php','cpp','js'):
            prove(work, flavor)

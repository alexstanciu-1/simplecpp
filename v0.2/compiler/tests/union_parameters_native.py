"""Closed two-class union admission, erased signatures, and incremental ancestry recovery."""
import argparse
from pathlib import Path
import tempfile

from collection_goal_native import revisions
from interfaces_native import forms


def prove(work, flavor):
    s, _, klass, method, _, implement = forms(flavor)
    fn = s['function']
    item, access = s['v']+'item', s['access']
    first, reverse, second = 'NameExpr|FieldExpr', 'FieldExpr|NameExpr', 'LiteralExpr|NameExpr'

    def param(contract):
        return item+':'+contract if flavor == 'js' else contract+' '+item

    def visit(contract):
        return fn('visit', 'int', [('item',contract)], item+access+'mark=6;return '+item+access+'read();')

    def reader(name, contract, value, parent=None):
        return klass(name, method('choose','return '+item+access+'mark+'+str(value)+';',
                     params=param(contract), intent='override' if parent else 'virtual'),parent)

    api = visit(first) + fn('forwarded','int',[('item',reverse)],'return visit('+item+');')
    main = 'echo forwarded(new Derived()),visit(new FieldExpr());return 8;'
    left = klass('NameExpr','','Expr')
    leaf = klass('Derived','','NameExpr')
    sources = dict(base=klass('Expr',s['field']('mark','int','4')+
                              method('read','return '+s['v']+'this'+access+'mark;')),
                   left=left, right=klass('FieldExpr','','Expr'), other=klass('LiteralExpr','','Expr'),
                   leaf=leaf, api=api, main=main, ar=reader('First',first,10),
                   br=reader('Second',second,20), concrete=reader('Concrete',reverse,30,'First'))
    sources['marker'] = 'interface Marker {}'
    sources['right'] = implement('FieldExpr',base='Expr',interfaces=('Marker',))
    sources['checked'] = fn('intersectionRead','int',[('item','FieldExpr&Marker')],'return '+item+access+'mark;')
    sources['middle'] = klass('Middle','','Expr')
    sources['box'] = klass('Box', {
        'php': 'public function __construct('+param(first)+'){echo visit('+item+');}',
        'js': 'constructor('+param(first)+'){echo visit('+item+');}',
        'cpp': 'public: Box('+param(first)+'){echo visit('+item+');}',
    }[flavor])
    base_call = fn('baseRead','int',[('item','Expr')],'return '+item+access+'mark;')
    forwarded_base = fn('baseForward','int',[('item',first)],'return baseRead('+item+');')
    virtual = fn('dispatch','int',[('target','First'),('item',first)],
                 'return '+s['v']+'target'+access+'choose('+item+');')
    second_call = s['infer']('r','new Second()')+'echo '+s['v']+'r'+access+'choose(new NameExpr());'
    dispatch = second_call+'echo dispatch(new Concrete(),new NameExpr());return 8;'
    guarded_ref = {'php':first+' &'+item, 'cpp':first+' &'+item, 'js':'&'+param(first)}[flavor]
    aliased = s['infer']('n','new Derived()') + 'echo visit('+s['v']+'n),'+s['v']+'n'+access+'mark;return 8;'
    revisions(work,flavor,sources,[
        (None,None,b'66'),(None,None,b'66'),('api',api+virtual,b'66'),('main',dispatch,b'2434'),
        ('concrete',reader('Concrete',second,30,'First'),'signature'),('concrete',sources['concrete'],b'2434'),
        ('main',main,b'66'),('leaf',klass('Derived','','LiteralExpr'),'Argument must belong'),
        ('leaf',None,None),('leaf',leaf,b'66'),
        ('main',main.replace('new Derived()', 'new Expr()'),'Argument must belong'),
        ('main',main.replace('new Derived()', 'new LiteralExpr()'),'Argument must belong'),
        ('main',main,b'66'),('left',klass('NameExpr',''),'common class base'),('left',left,b'66'),
        ('right',None,None),('right',sources['right'],b'66'),
        ('api',api+virtual+base_call+forwarded_base,b'66'),
        ('main',s['infer']('box','new Box(new NameExpr())')+'echo baseForward(new FieldExpr());return 8;',b'64'),
        ('left',klass('NameExpr','','Middle'),b'64'),('right',implement('FieldExpr',base='Middle',interfaces=('Marker',)),b'64'),
        ('left',left,b'64'),('right',sources['right'],b'64'),
        ('api',api.replace(first,'NameExpr|NameExpr')+virtual,'common class base'),
        ('api',api+virtual+base_call+forwarded_base,b'64'),('main',dispatch,b'2434'),
        ('ar',sources['ar'].replace(param(first),guarded_ref),'required by-value'),
        ('ar',sources['ar'].replace(param(first),param(first)+'=null'),'required by-value'),
        ('ar',sources['ar'],b'2434'),('main',aliased,b'66'),
        ('main',main.replace('new Derived()', 'null'),'Argument must belong'),('main',dispatch,b'2434'),
        ('main','echo intersectionRead(new FieldExpr());'+dispatch,b'42434'),
    ])
    files = list((work/(flavor+'-out')/'units').iterdir())
    emitted = '\n'.join(p.read_text() for p in files)
    for arms in ('FieldExpr|NameExpr','LiteralExpr|NameExpr'):
        slot = 'scpp_slot_choose_'+('int64\0value:'+arms).encode().hex()
        assert slot in emitted, slot
    assert 'dynamic_cast<' not in emitted and 'owner_Expr' in emitted


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-union-params-') as tmp:
        work = args.output or Path(tmp)
        work.mkdir(exist_ok=True)
        for flavor in ('php','cpp','js'):
            prove(work,flavor)

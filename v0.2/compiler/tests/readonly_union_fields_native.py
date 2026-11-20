"""Constructor-initialized union fields, owner lifetime and cross-unit layout recovery."""
import argparse
from pathlib import Path
import tempfile

from collection_goal_native import revisions
from interfaces_native import forms


def prove(work, flavor):
    s, _, klass, method, _, _ = forms(flavor)
    fn, v, access = s['function'], s['v'], s['access']
    first, reverse = 'NameExpr|FieldExpr', 'FieldExpr|NameExpr'
    target = v+'this'+access+'target'
    arg = v+'item'
    field = {'php': 'public readonly '+first+' $target;', 'cpp': 'public: readonly '+first+' target;',
             'js': 'readonly target:'+first+';'}[flavor]
    signature = {'php': 'public function __construct('+reverse+' $item)', 'cpp': 'WriteTarget('+reverse+' item)',
                 'js':'constructor(item:'+reverse+')'}[flavor]
    assign = target+'='+arg+';'

    def holder(body):
        return klass('WriteTarget',field+signature+'{'+body+'}')

    h = v+'h'
    read = fn('readBoth','int',[('h','WriteTarget')],h+access+'target'+access+'mark=9;return visit('+
              h+access+'target)+'+h+access+'target'+access+'read();')
    visit = fn('visit','int',[('item',first)],'return '+arg+access+'mark;')
    base = fn('asBase','Expr',[('item',first)],'return '+arg+';')
    make = fn('makeHolder','WriteTarget',[],s['infer']('item','new NameExpr()')+'return new WriteTarget('+arg+');')
    run = fn('exercise','weak<Expr>',[],s['infer']('h','makeHolder()')+'echo readBoth('+h+');return weak(asBase('+h+access+'target));')
    main = s['infer']('w','exercise()')+'if (lock('+v+'w)) {return 1;}return 8;'
    good = holder(assign)
    left = klass('NameExpr','','Expr')
    right = klass('FieldExpr','','Expr')
    sources = dict(aaa_holder=good, api=read+visit+base, factory=make+run, main=main,
                   common=klass('Expr',s['field']('mark','int','4')+method('read','return '+v+'this'+access+'mark;')),
                   left=left, right=right, middle=klass('Middle','','Expr'), other=klass('Other','','Expr'))
    revisions(work,flavor,sources,[
        (None,None,b'18'),(None,None,b'18'),
        ('aaa_holder',holder(''),'Readonly property is not initialized'),
        ('aaa_holder',holder(assign+assign),'exactly once'),
        ('aaa_holder',holder(target+'='+target+';'),'Readonly property is not initialized'),
        ('aaa_holder',holder(target+'=new Other();'),'Argument must belong'),
        ('aaa_holder',good,b'18'),
        ('api',read+visit+base+fn('replace','void',[('h','WriteTarget')],h+access+'target=new FieldExpr();'),'Cannot modify readonly'),
        ('api',read+visit+base,b'18'),
        ('left',klass('NameExpr','','Middle'),b'18'),('right',klass('FieldExpr','','Middle'),b'18'),
        ('right',klass('FieldExpr',''),'common class base'),(None,None,'common class base'),
        ('right',None,None),('right',right,b'18'),('left',left,b'18'),
        ('factory',make.replace('new NameExpr()', 'new FieldExpr()')+run,b'18'),
        ('child',klass('DeepName','','NameExpr'),b'18'),
        ('narrow',klass('NarrowTarget',field+signature.replace('WriteTarget','NarrowTarget').replace(reverse,'DeepName')+'{'+assign+'}'),b'18'),
        ('child',klass('DeepName','','Other'),'Argument must belong'),
        ('child',klass('DeepName','','FieldExpr'),b'18'),
    ])
    for revision, owner in [(0,'Expr'),(10,'Middle'),(15,'Expr')]:
        units = (work/f'{flavor}-fresh-{revision}'/'units').iterdir()
        assert any(('::App::owner_'+owner+' target{};') in unit.read_text() for unit in units), (flavor,revision,owner)


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-readonly-union-') as tmp:
        work = args.output or Path(tmp)
        work.mkdir(exist_ok=True)
        for flavor in ('php','cpp','js'):
            prove(work,flavor)

"""Bootstrap property-write dispatch and collection destination preparation through existing compound parameters."""
import argparse
import tempfile
from pathlib import Path

from collection_goal_native import revisions
from interfaces_native import forms


def prove(work, flavor):
    s, _, klass, method, _, implement = forms(flavor)
    fn, v, access = s['function'], s['v'], s['access']
    target = v+'target'
    node = v+'node'
    root = klass('Expr',method('tag','return 0;',intent='virtual'))
    name = klass('NameExpr',s['field']('writes','int','0'),'Expr')
    field = klass('FieldExpr',s['field']('writes','int','0'),'Expr')
    static_write = fn('staticDestination','void',[('target','NameExpr')],target+access+'writes=3;')
    field_write = fn('fieldDestination','void',[('target','FieldExpr')],target+access+'writes=4;')
    writable = fn('writableProperty','void',[('node','NameExpr|FieldExpr')],
                  'if ('+node+' instanceof NameExpr) {staticDestination('+node+');return;}fieldDestination('+node+');')

    indexed = 'interface IndexedCollection {}'
    write_type = {'php':'public function writeType():TypeDef;', 'cpp':'TypeDef writeType();', 'js':'writeType():TypeDef;'}[flavor]
    sequence = 'interface SequenceInitializer'+(' : public ' if flavor=='cpp' else ' extends ')+'IndexedCollection {'+write_type+'}'
    keyed = 'interface KeyedInitializer {'+write_type+'}'
    typedef = klass('TypeDef',s['field']('settled','int','0')+s['field']('code','int','2')+method('tag','return 0;',intent='virtual'))
    get_type = method('writeType','return new TypeDef();',result='TypeDef',intent='override')
    provider = implement('VectorType',get_type,base='TypeDef',interfaces=('SequenceInitializer',))
    other = implement('EmptyOnly',base='TypeDef',interfaces=('IndexedCollection',))
    t = v+'dest'
    # Project the intersection to its existing class view before testing an additional capability.
    projection = {'php':'$base TypeDef=$dest;', 'cpp':'TypeDef base=dest;', 'js':'let base:TypeDef=dest;'}[flavor]
    settle = fn('settleCollection','void',[('dest','TypeDef&IndexedCollection')],t+access+'settled=1;')
    populate = fn('populate','void',[('dest','TypeDef&SequenceInitializer')],t+access+'settled='+t+access+'writeType()'+access+'code;')
    collection = fn('collection','void',[('dest','TypeDef&IndexedCollection'),('empty','bool')],
                    'if ('+v+'empty) {settleCollection('+t+');return;}'+projection+
                    'if ('+v+'base instanceof SequenceInitializer) {populate('+v+'base);return;}echo "X";')
    keyed_provider = implement('HashType',get_type,base='TypeDef',interfaces=('KeyedInitializer',))
    keyed_settle = fn('settleKeyed','void',[('dest','TypeDef&KeyedInitializer')],t+access+'settled='+t+access+'writeType()'+access+'code;')
    main = (s['infer']('n','new NameExpr()')+s['infer']('f','new FieldExpr()')+
            'writableProperty('+v+'n);writableProperty('+v+'f);echo '+v+'n'+access+'writes,'+v+'f'+access+'writes;'+
            s['infer']('d','new VectorType()')+s['infer']('e','new EmptyOnly()')+
            'collection('+v+'d,true);echo '+v+'d'+access+'settled;collection('+v+'d,false);echo '+v+'d'+access+
            'settled;collection('+v+'e,false);echo '+v+'e'+access+'settled;'+s['infer']('h','new HashType()')+
            'settleKeyed('+v+'h);echo '+v+'h'+access+'settled;return 8;')
    src = dict(root=root,name=name,field=field,property=writable,hooks=static_write+field_write,
               types=typedef,contracts=indexed+sequence+keyed,provider=provider,other=other,
               hash=keyed_provider,settle=settle+populate+keyed_settle,collection=collection,main=main)
    revisions(work,flavor,src,[
        (None,None,b'3412X02'),(None,None,b'3412X02'),
        ('provider',implement('VectorType',base='TypeDef',interfaces=('IndexedCollection',)),b'341X1X02'),
        ('provider',implement('VectorType',base='TypeDef',interfaces=()),'statically satisfy'),
        (None,None,'statically satisfy'),('provider',provider,b'3412X02'),
        ('field',None,None),('field',field,b'3412X02'),
        ('main',main.replace('writableProperty('+v+'n)','writableProperty(new Expr())'),'Argument must belong'),
        ('main',main,b'3412X02'),
        ('hash',implement('HashType',base='TypeDef',interfaces=()),'statically satisfy'),
        ('hash',keyed_provider,b'3412X02'),
    ])

    units = (work/(flavor+'-out')/'units')
    headers = ''.join(p.read_text() for p in units.glob('*.hpp'))
    bodies = ''.join(p.read_text() for p in units.glob('*.cpp'))
    assert 'void writableProperty_([[maybe_unused]] owner_Expr ' in headers
    for capability in ('IndexedCollection','SequenceInitializer','KeyedInitializer'):
        assert 'owner_TypeDef base; scpp_records::'+capability+'* capability{};' in bodies


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output',type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-bootstrap-property-collection-') as tmp:
        work = args.output or Path(tmp)
        work.mkdir(exist_ok=True)
        for flavor in ('php','cpp','js'):
            prove(work,flavor)

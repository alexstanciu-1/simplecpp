"""Required class/interface intersection parameters, owner lifetime and incremental recovery."""
import argparse
from pathlib import Path
import tempfile

from collection_goal_native import revisions
from interfaces_native import forms


def prove(work, flavor):
    s, evaluate, klass, method, interface, implementation = forms(flavor)
    var = s['v']+'item'
    member = var+s['access']
    fn = s['function']
    base = klass('Base', s['field']('n','int','4')+method('marker','return 0;',intent='virtual'))
    leaf = implementation('Leaf', evaluate('override',s['v']+'this'+s['access']+'n+'+s['v']+'delta',default='1'),'Base')
    api = fn('measure','int',[('item','Base&Readable')],member+'n=6;return '+member+'evaluate()+baseRead('+var+')+interfaceRead('+var+');')
    api += fn('again','int',[('item','Readable&Base')],'return measure('+var+');')
    params = {'php':'Base&Readable $item','cpp':'Base&Readable item','js':'item:Base&Readable'}[flavor]
    host = klass('Host',method('apply','return measure('+var+');',params=params,intent='virtual'))
    host_call=s['infer']('h','new Host()')+'echo '+s['v']+'h'+s['access']+'apply(new Leaf());return 8;'
    helpers = fn('baseRead','int',[('item','Base')],'return '+member+'n;')
    helpers += fn('interfaceRead','int',[('item','Readable')],'return '+member+'evaluate();')
    slot = s['v']+'slot'
    replace = fn('replaceDuring','int',[('item','Base&Readable'),('slot','Leaf')],
        slot+'=new Leaf();'+member+'n=12;return '+member+'evaluate();')
    param = {'php':'Leaf $slot','cpp':'Leaf slot','js':'slot:Leaf'}[flavor]
    ref = {'php':'Leaf &$slot','cpp':'Leaf &slot','js':'&slot:Leaf'}[flavor]
    replace=replace.replace(param,ref)
    factory=fn('makeLeaf','Leaf',[],'echo "F";return new Leaf();')
    lifetime = s['infer']('slot','new Leaf()')+'echo replaceDuring('+slot+','+slot+'),'+slot+s['access']+'n,again(makeLeaf());return 8;'
    specific = 'interface Specific'+(' : public ' if flavor=='cpp' else ' extends ')+'Readable {}'
    specific += implementation('SpecificLeaf',base='Leaf',interfaces=('Specific',))
    specific += fn('forwardSpecific','int',[('item','Base&Specific')],'return again('+var+');')
    guarded = fn('guarded','int',[('item','Base')], 'if ('+var+' instanceof Readable){return measure('+var+');}return 0;')
    by_ref = {'php':'Base&Readable &$item','cpp':'Base&Readable &item','js':'&item:Base&Readable'}[flavor]
    main='echo again(new Leaf());return 8;'
    revisions(work, flavor,dict(base=base,contract=interface(),leaf=leaf,api=api,helpers=helpers,host=host,replace=replace+factory,main=main),[
        (None,None,b'20'),(None,None,b'20'),
        ('main','echo again(new Base());return 8;','statically satisfy'),
        (None,None,'statically satisfy'),('main',main,b'20'),
        ('contract',interface(default='2'),b'22'),
        ('leaf',implementation('Leaf',base='Base',interfaces=()),'statically satisfy'),
        ('leaf',leaf,b'22'),('contract',None,'interface'),
        ('contract',interface(),b'20'),
        ('main',host_call,b'20'),('api',api.replace('Base&Readable','Base&Base'),'requires one required class'),
        ('api',api,b'20'),('api',api+fn('bad','int',[('item','Readable')],'return measure('+var+');'),'statically satisfy'),
        ('api',api,b'20'),('main',lifetime,b'134F20'),
        ('main',main,b'20'),('specific',specific,b'20'),('main','echo forwardSpecific(new SpecificLeaf());return 8;',b'20'),
        ('api',api+guarded,'statically satisfy'),('api',api,b'20'),
        ('host',host.replace(params,by_ref),'required by-value'),('host',host,b'20'),
        ('host',host.replace(params,params+'=null'),'required by-value'),('host',host,b'20'),
    ])


if __name__=='__main__':
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output',type=Path)
    args=parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-intersection-') as tmp:
        work=args.output or Path(tmp)
        work.mkdir(exist_ok=True)
        for flavor in ('php','cpp','js'):
            prove(work,flavor)

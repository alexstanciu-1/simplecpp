"""Inherited interface dispatch, original diamond identity, and incremental contract recovery."""
import argparse
from pathlib import Path
import tempfile

from collection_goal_native import revisions
from interfaces_native import forms


def prove(work, flavor):
    s, evaluate, _, _, interface, implementation = forms(flavor)
    def derived(name, parents, body=''):
        edges = ' : ' + ', '.join('public '+p for p in parents) if flavor == 'cpp' else ' extends '+','.join(parents)
        return 'interface '+name+edges+' {'+body+'}'
    def signature(default):
        return interface(default=default).split('{', 1)[1].rsplit('}', 1)[0]
    def reader(name, kind):
        return s['function'](name,'int',[('x',kind)],'return '+s['v']+'x'+s['access']+'evaluate();')
    root = interface('Root')
    left, right = derived('Left',['Root']), derived('Right',['Root'])
    both = derived('Both',['Left','Right'])
    leaf = implementation('Leaf', evaluate('override','10+'+s['v']+'delta',default='4'),interfaces=('Both',))
    api = reader('rootRead','Root')+reader('childRead','Both')
    api += s['function']('asRoot','Root',[('x','Both')],'return '+s['v']+'x;')
    main = s['infer']('x','new Leaf()')+'echo rootRead(asRoot('+s['v']+'x)),childRead('+s['v']+'x);return 8;'
    idle = interface('IdleContract')+implementation('Idle',evaluate('override','3',default='2'),interfaces=('IdleContract',))
    revisions(work,flavor,dict(root=root,left=left,right=right,both=both,leaf=leaf,api=api,main=main,idle=idle),[
        (None,None,b'1111'),(None,None,b'1111'),('root',interface('Root',default='2'),b'1212'),
        ('root',None,'interface'),(None,None,'interface'),('root',root,b'1111'),
        ('left',derived('Left',['Both']),'Recursive interface inheritance'),('left',left,b'1111'),
        ('right',derived('Right',['Root'],signature('5')),b'1115'),
        ('left',derived('Left',['Root'],signature('6')),'Ambiguous inherited interface method'),
        ('api',api.replace('return '+s['v']+'x'+s['access']+'evaluate();','return 9;'),b'99'),
        ('api',api,'Ambiguous inherited interface method'),
        ('both',derived('Both',['Left','Right'],signature('3')),b'1113'),
        ('right',derived('Right',['Root'],signature('5').replace('int','float')),'match exactly'),
        ('right',right,b'1113'),('both',both,b'1116'),('left',left,b'1111'),
        ('right',interface('Right',default='7'),'Ambiguous inherited interface method'),
        ('both',derived('Both',['Left','Right'],signature('8')),b'1118'),
        ('right',right,b'1118'),('both',both,b'1111'),
    ],unaffected_source='idle.'+flavor)


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-interface-ancestry-') as tmp:
        work=args.output or Path(tmp)
        work.mkdir(exist_ok=True)
        for flavor in ('php','cpp','js'):
            prove(work,flavor)

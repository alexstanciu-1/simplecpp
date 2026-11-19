"""Final class identity, inherited protected access and interface tests across retained generations."""
import argparse
from pathlib import Path
from collection_goal_native import revisions
from interfaces_native import forms
from protected_methods_native import forms as protected_forms
from app_namespace_native import service,invoke
from extends_native import source_edit
from object_access_native import syntax


def final(flavor, source, name):
    return source.replace('class '+name, 'class '+name+' final', 1) if flavor=='cpp' else 'final '+source


def sources(flavor):
    s,evaluate,klass,method,interface,implementation=forms(flavor)
    _,_,protected=protected_forms(flavor)
    v,a=s['v'],s['access']
    base=klass('Base',protected('hidden','return 7;',visibility='protected')+evaluate('virtual','1',default='1'))
    leaf=implementation('Leaf',evaluate('override',f'{v}this{a}hidden()+{v}delta',default='1'),'Base')
    bad=klass('Bad',evaluate('virtual','3'))
    cast=syntax(flavor)[3]
    api=s['function']('view','Readable',[('x','Leaf')],f'return {v}x;')
    api+=s['function']('forward','bool',[('x','Bad')],f'return {v}x instanceof Readable;')
    api+=s['function']('backward','bool',[('x','Readable')],f'return {v}x instanceof Bad;')
    api+=s['function']('down','Leaf',[('x','Readable')],f'return {cast(v+"x","Leaf")};')
    main=s['infer']('p','new Leaf()')+s['infer']('i',f'view({v}p)')+s['infer']('q',f'down({v}i)')
    main+=f'if (forward(new Bad()) || backward({v}i)) {{return 90;}}if (!({v}p === {v}q)) {{return 91;}}'
    main+=f'echo {v}q{a}evaluate();return 8;'
    return dict(contract=interface(),base=base,leaf=final(flavor,leaf,'Leaf'),bad=bad,api=api,main=main),leaf


def rejection(work,flavor,src):
    s,evaluate,klass,method,interface,implementation=forms(flavor)
    p=service(work/flavor,work/(flavor+'-reject'))
    path=work/flavor/('extra.'+flavor)
    try:
        cases=[('final','Expected'),('class Incomplete','Expected'),(klass('Child','','Leaf'),'Cannot extend final'),
            ('abstract '+final(flavor,klass('Conflict'),'Conflict'),'non-abstract class'),
            ('final '+s['record']('Value'),'non-abstract class'),
            ('final '+interface('Closed'),'non-abstract class'),
            ('final '+final(flavor,klass('Twice'),'Twice'),'Duplicate class modifier')]
        for text,message in cases:
            source_edit(path,text)
            got=invoke(p);assert message in got.get('error',''),got
        path.unlink();assert 'error' not in invoke(p)
        # Test the reverse interface -> final class direction independently of forward().
        source_edit(work/flavor/('bad.'+flavor),final(flavor,src['bad'],'Bad'))
        source_edit(work/flavor/('api.'+flavor),src['api'].replace('return '+s['v']+'x instanceof Readable;', 'return false;'))
        got=invoke(p);assert 'Final class does not implement' in got.get('error',''),got
        source_edit(work/flavor/('bad.'+flavor),src['bad'])
        source_edit(work/flavor/('api.'+flavor),src['api'])
        assert 'error' not in invoke(p)
    finally:p.stdin.close();p.wait()


def main():
    p=argparse.ArgumentParser(description=__doc__);p.add_argument('--output',type=Path,required=True)
    args=p.parse_args();args.output.mkdir(parents=True,exist_ok=True)
    for f in ['php','cpp','js']:
        src,open_leaf=sources(f)
        revisions(args.output,f,src,[(None,None,b'8'),(None,None,b'8'),
            ('leaf',open_leaf,b'8'),('leaf',src['leaf'],b'8'),
            ('base',final(f,src['base'],'Base'),None),(None,None,None),('base',src['base'],b'8'),
            ('bad',final(f,src['bad'],'Bad'),None),('bad',src['bad'],b'8'),
            ('leaf',None,None),('leaf',src['leaf'],b'8')])
        assert any('struct Leaf final' in path.read_text() for path in (args.output/(f+'-out')).rglob('*.hpp'))
        rejection(args.output,f,src)


if __name__=='__main__':main()

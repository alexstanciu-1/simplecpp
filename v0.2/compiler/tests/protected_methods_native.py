"""Protected instance access, virtual visibility and all-flavor incremental/fresh recovery."""
import argparse
from pathlib import Path
from classes_native import fixture
from collection_goal_native import revisions
from app_namespace_native import service, invoke
from extends_native import source_edit


def forms(flavor):
    s = fixture(flavor); v = s['v']
    def klass(name, body='', base=None, abstract=False):
        return ('abstract ' if abstract else '') + s['record'](name, body, base).replace('struct ', 'class ', 1)
    def method(name, body, params=(), visibility='public', intent='', abstract=False):
        args = ','.join(f'{n}:{t}' if flavor == 'js' else f'{t} {v}{n}' for n,t in params)
        if flavor == 'cpp':
            head = ('virtual ' if intent == 'virtual' or abstract else '') + f'int {name}({args})' + (' override' if intent == 'override' else '')
            return visibility + ': ' + head + (' = 0;' if abstract else ' {'+body+'}') + ' public: '
        head = visibility+' '+('abstract ' if abstract else '')+(intent+' ' if intent else '')
        head += ('function ' if flavor == 'php' else '') + f'{name}({args}):int'
        return head + (';' if abstract else ' {'+body+'}')
    return s, klass, method


def sources(flavor):
    s, klass, method = forms(flavor); v,a = s['v'],s['access']
    base = klass('Base', method('hidden','return 3;',visibility='protected')
        +method('hook','return 4;',visibility='protected',intent='virtual')
        +method('read',f'return {v}this{a}hook();'))
    call = method('call',f'return {v}this{a}hidden()+{v}other{a}hook();',[('other','Child')])
    parent = {'php': 'parent::hidden()', 'cpp': 'Base::hidden()', 'js': 'super.hidden()'}[flavor]
    child = klass('Child',method('ancestor','return '+parent+';')+method('hook','return 7;',visibility='protected',intent='override')+call,'Base')
    abstract = klass('Abstract',method('evaluate','',visibility='protected',abstract=True),abstract=True)
    concrete = klass('Concrete',method('evaluate','return 8;',intent='override'),'Abstract')
    inline = s['record']('Inline',method('hidden','return 9;',visibility='protected')+method('read',f'return {v}this{a}hidden();'))
    main = s['infer']('p','new Child()')+s['infer']('q','new Child()')+f'echo {v}p{a}call({v}q),{v}p{a}read(),{v}p{a}ancestor();'
    main += s['infer']('c','new Concrete()')+f'echo {v}c{a}evaluate();'+s['local']('i','Inline')+f'echo {v}i{a}read();return 8;'
    return dict(base=base,child=child,abstract=abstract,concrete=concrete,inline=inline,main=main),call


def rejects(work, flavor, src, call):
    s,klass,method=forms(flavor);v,a=s['v'],s['access']
    p=service(work/flavor,work/(flavor+'-reject'))
    try:
        for text,message in [
            (s['infer']('p','new Child()')+f'echo {v}p{a}hook();return 8;','Cannot call protected method'),
        ]:
            source_edit(work/flavor/('main.'+flavor),text)
            assert message in invoke(p).get('error','')
        source_edit(work/flavor/('main.'+flavor),src['main'])
        # Context Child cannot use a base-typed receiver for protected instance access.
        for target in ['Base','Sibling']:
            bad=klass('Child',method('ancestor','return 3;')+method('hook','return 7;',visibility='protected',intent='override')
                +method('call',f'return {v}other{a}hidden();',[('other',target)]),'Base')
            source_edit(work/flavor/('sibling.'+flavor),klass('Sibling','','Base'))
            source_edit(work/flavor/('child.'+flavor),bad)
            assert 'Cannot call protected method' in invoke(p).get('error','')
        source_edit(work/flavor/('child.'+flavor),src['child'])
        # Public virtual slots cannot narrow their visibility.
        source_edit(work/flavor/('base.'+flavor),src['base'].replace('protected: virtual','public: virtual').replace('protected virtual','public virtual'))
        assert 'narrow visibility' in invoke(p).get('error','')
        source_edit(work/flavor/('base.'+flavor),src['base'])
        assert 'error' not in invoke(p)
    finally:p.stdin.close();p.wait()


def main():
    parser=argparse.ArgumentParser(description=__doc__);parser.add_argument('--output',type=Path,required=True)
    args=parser.parse_args();args.output.mkdir(parents=True,exist_ok=True)
    for flavor in ['php','cpp','js']:
        src,call=sources(flavor)
        revisions(args.output,flavor,src,[(None,None,b'107389'),(None,None,b'107389'),
            ('base',src['base'].replace('return 3;','return 5;'),b'127589'),
            ('base',src['base'].replace('protected','private',1),None),(None,None,None),
            ('base',src['base'],b'107389'),('base',None,None),('base',src['base'],b'107389')])
        rejects(args.output,flavor,src,call)


if __name__=='__main__':main()

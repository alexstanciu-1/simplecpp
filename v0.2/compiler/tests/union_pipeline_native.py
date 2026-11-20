"""Compiler-shaped closed union: static parser, readonly syntax node and visitor in separate units."""
import argparse
import tempfile
from pathlib import Path

from collection_goal_native import revisions
from interfaces_native import forms


def pipeline(work, flavor):
    s, _, klass, method, _, _ = forms(flavor)
    pair = 'RecordDecl|TraitDecl'
    v, access = s['v'], s['access']
    field, constructor, read = {
        'php': ('public readonly '+pair+' $decl;', 'public function __construct('+pair+' $item)',
                'public static function read(bool $record): '+pair),
        'cpp': ('public: readonly '+pair+' decl;', 'Parsed('+pair+' item)', 'public: static '+pair+' read(bool record)'),
        'js': ('readonly decl:'+pair+';', 'constructor(item:'+pair+')', 'static read(record:bool): '+pair),
    }[flavor]
    holder = klass('Parsed',field+constructor+'{'+v+'this'+access+'decl='+v+'item;}')
    parser = klass('Parser',read+'{if ('+v+'record) {return new RecordDecl();}return new TraitDecl();}')
    item = v+'node'
    visit = s['function']('visit','int',[('node',pair)],
        'if ('+item+' instanceof RecordDecl) {return '+item+access+'code;}else {return '+item+access+'flags;}')
    read_call = 'Parser'+('.' if flavor == 'js' else '::')+'read'
    main = (s['infer']('a','new Parsed('+read_call+'(true))')+
            s['infer']('b','new Parsed('+read_call+'(false))')+
            'echo visit('+v+'a'+access+'decl),visit('+v+'b'+access+'decl);return 8;')
    def arm(name, member, value, base='Node'):
        return klass(name,s['field'](member,'int',value),base)
    left = arm('RecordDecl','code','3')
    right = arm('TraitDecl','flags','7')
    src = dict(node=klass('Node',method('tag','return 0;',intent='virtual')), middle=klass('Middle','','Node'),
               left=left,right=right,parser=parser,holder=holder,visitor=visit,main=main)
    revisions(work,flavor,src,[
        (None,None,b'37'),(None,None,b'37'),
        ('left',arm('RecordDecl','code','5','Middle'),b'57'),
        ('right',arm('TraitDecl','flags','7','Middle'),b'57'),
        ('visitor',visit.replace('flags','code'),None),(None,None,None),('visitor',visit,b'57'),
        ('right',None,None),(None,None,None),('right',right,b'57'),('left',left,b'37'),
    ])
    for rev, owner in ((0,'Node'),(3,'Middle'),(9,'Node')):
        units = work/f'{flavor}-fresh-{rev}'/'units'
        assert any(f'owner_{owner} decl{{}};' in p.read_text() for p in units.glob('*.hpp'))


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output',type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-union-pipeline-') as tmp:
        work = args.output or Path(tmp)
        work.mkdir(exist_ok=True)
        for flavor in ('php','cpp','js'):
            pipeline(work,flavor)

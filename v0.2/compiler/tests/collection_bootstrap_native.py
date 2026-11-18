"""Token-like application records in vectors and symbol hashes, without readonly bootstrap claims."""
import argparse
import tempfile
from pathlib import Path
from classes_native import fixture
from collection_goal_native import revisions


def prove(work,flavor):
    s=fixture(flavor);v,a=s['v'],s['access']
    def decl(name,t,value):
        return {'php':f'${name} {t} = {value};','cpp':f'{t} {name} = {value};','js':f'let {name}:{t} = {value};'}[flavor]
    ctor={'php':'public function __construct(int $offset, string $text)',
          'cpp':'Token(int offset, string text)','js':'constructor(offset:int,text:string)'}[flavor]
    types=s['record']('Token',s['field']('offset','int')+s['field']('text','string')+ctor+'{'+f'{v}this{a}offset={v}offset;{v}this{a}text={v}text;'+'}')
    local={'php':'$token Token(4,"symbol");','cpp':'Token token(4,"symbol");','js':'let token:Token(4,"symbol");'}[flavor]
    api=s['function']('tokens','vector<Token>',[],decl('items','vector<Token>','[]')+local+f'{v}items[]={v}token;return {v}items;')
    main=decl('items','vector<Token>','tokens()')+decl('saved','vector<Token>',v+'items')+decl('symbols','hash<Token,string>','[]')
    main+=f'foreach ({v}items as {v}key => {v}token) {{{v}symbols[{v}token{a}text]={v}token;echo {v}key,{v}token{a}offset;}}'
    main+=decl('aliases','hash<Token,string>',v+'symbols')+f'{v}symbols{a}clear();{v}items{a}clear();'
    main+=f'foreach ({v}aliases as {v}key => {v}token) {{echo {v}key,{v}token{a}offset;}}'
    main+=f'echo count({v}saved),count({v}symbols);return 8;'
    expected=b'04symbol410'
    revisions(work,flavor,{'types':types,'api':api,'main':main},[(None,None,expected),(None,None,expected),
        ('api',api.replace('(4,','(9,'),b'09symbol910'),('types',None,None),(None,None,None),
        ('types',types,b'09symbol910'),('api',api,expected)])


if __name__=='__main__':
    p=argparse.ArgumentParser(description=__doc__);p.add_argument('--output',type=Path);args=p.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-collection-bootstrap-') as tmp:
        work=(args.output or Path(tmp)).resolve();work.mkdir(exist_ok=True)
        for f in ['php','cpp','js']:prove(work,f)

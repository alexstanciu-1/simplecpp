"""Container-preserving boxed clear, copy identity, mutation during foreach and recovery."""
import argparse
import re
import tempfile
from pathlib import Path
from classes_native import fixture
from collection_goal_native import revisions,guard


def prove(work,flavor):
    s=fixture(flavor);v,a=s['v'],s['access'];api='';main='';expected=b''
    def decl(name,t,value):
        return {'php':f'${name} {t} = {value};','cpp':f'{t} {name} = {value};','js':f'let {name}:{t} = {value};'}[flavor]
    for family in ['mixed','dynamic']:
        n=family[0];x=v+n
        api+=s['function']('reset'+n,'void',[('&items',family)],f'{v}items{a}clear();').replace('$&','&$')
        main+=decl(n,family,'[]')+f'{x}["a"]=7;'+decl('copy'+n,family,x)
        main+=f'reset{n}({x});echo count({x}),count({v}copy{n});{x}{a}clear();{x}[0]=9;echo (int)({x}[0]);'
        main+=f'foreach ({x} as {v}item{n}) {{{x}{a}clear();echo (int){v}item{n};}}echo count({x});'
        expected+=(b'01' if family=='mixed' else b'00')+b'990'
        if family=='dynamic':main+=f'{x}[0]={x};{x}{a}clear();echo empty({x});';expected+=b'1'
    main=re.sub(r'echo ([^;]+);',lambda m:'echo '+','.join('(int)('+x+')' for x in m[1].split(','))+';',main)+'return 8;'
    revisions(work,flavor,{'api':api,'main':main},[(None,None,expected),(None,None,expected),
        ('api',api.replace('clear();','clear();'+(' ')),expected),('api',None,None),(None,None,None),('api',api,expected),
        ('main',main.replace('resetm('+v+'m)', 'resetm('+v+'d)'),None),('main',main,expected)])
    for t in ['mixed','dynamic']:
        for value,label in [('null','null'),('7','scalar')]:
            guard(work,flavor,t+'-'+label,decl('x',t,value)+f'{v}x{a}clear();return 8;','boxed_expected_container')


if __name__=='__main__':
    p=argparse.ArgumentParser(description=__doc__);p.add_argument('--output',type=Path);args=p.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-boxed-clear-') as tmp:
        work=(args.output or Path(tmp)).resolve();work.mkdir(exist_ok=True)
        for f in ['php','cpp','js']:prove(work,f)

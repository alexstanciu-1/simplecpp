"""Generated scalar dispatch and existing-key boxed mutations, with fresh recovery equivalence."""
import argparse
import tempfile
from pathlib import Path
from classes_native import fixture
from collection_goal_native import revisions,guard


def prove(work,flavor):
    s=fixture(flavor);v=s['v']
    def decl(name,t,value):
        return {'php':f'${name} {t} = {value};','cpp':f'{t} {name} = {value};','js':f'let {name}:{t} = {value};'}[flavor]
    api=s['function']('amount','int',[],'return 2;')
    source=''
    for family in ['mixed','dynamic']:
        name='m' if family=='mixed' else 'd';target=f'{v}{name}["x"]'
        source+=decl(name,family,'[]')+f'{target}=8;'
        for op,rhs in [('+=','amount()'),('-=','1'),('*=','3'),('/=','3'),('%=','5'),('**=','2'),('&=','7'),('|=','8'),('^=','2'),('<<=','1'),('>>=','1')]:
            source+=f'{target}{op}{rhs};echo (int){target},",";'
        source+=f'{target}++;++{target};{target}--;--{target};echo (int){target},";";'
        source+=f'{target}=2.5;{target}+=1;{target}*=2;{target}/=2;{target}++;echo (string){target},";";'
        concat='.=' if flavor=='php' else '+='
        source+=f'{target}="A";{target}{concat}"B";echo (string){target},";";'
    source+='return 8;'
    expected=b'10,9,27,9,4,16,0,8,10,20,10,10;4.5;AB;'*2
    changed=b'11,10,30,10,0,0,0,8,10,20,10,10;4.5;AB;'*2
    revisions(work,flavor,{'api':api,'main':source},[(None,None,expected),(None,None,expected),
        ('api',api.replace('return 2','return 3'),changed),('api',None,None),(None,None,None),('api',api,expected)])
    for family in ['mixed','dynamic']:
        init=decl('items',family,'[]');target=f'{v}items["x"]'
        for label,code,error in [
            ('missing',f'{target}+=2;','boxed_missing_key'),
            ('missing-update',f'{target}++;','boxed_missing_key'),
            ('null',f'{target}=null;{target}+=2;','boxed_scalar_kind'),
            ('bool',f'{target}=true;{target}++;','boxed_scalar_kind'),
            ('zero',f'{target}=8;{target}/=0;','division_by_zero'),
            ('shift',f'{target}=8;{target}<<=64;','shift_count_out_of_range'),
            ('power',f'{target}=8;{target}**=-1;','power_negative_exponent'),
        ]:guard(work,flavor,family+'-'+label,init+code+'return 8;',error)
        remove=s['function']('remove_target','int',[('&items',family)],f'{v}items'+s['access']+'remove("x");return 2;').replace('$&','&$')
        guard(work,flavor,family+'-rhs-removal',remove+init+f'{target}=8;{target}+=remove_target({v}items);return 8;','boxed_missing_key')
        if flavor=='php':
            guard(work,flavor,family+'-text-add',init+f'{target}="A";{target}+="B";return 8;','boxed_scalar_kind')



if __name__=='__main__':
    p=argparse.ArgumentParser(description=__doc__);p.add_argument('--output',type=Path);args=p.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-boxed-index-mutation-') as tmp:
        work=(args.output or Path(tmp)).resolve();work.mkdir(exist_ok=True)
        for f in ['php','cpp','js']:prove(work,f)

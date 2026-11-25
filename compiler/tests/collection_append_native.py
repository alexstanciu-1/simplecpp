"""Generated append capabilities across typed hashes and boxes, including application values."""
import argparse
import tempfile
from pathlib import Path
from classes_native import fixture
from collection_goal_native import revisions,guard


def prove(work,flavor,quick=False):
    s=fixture(flavor);v,a=s['v'],s['access']
    def decl(name,t,value):
        return {'php':f'${name} {t} = {value};','cpp':f'{t} {name} = {value};','js':f'let {name}:{t} = {value};'}[flavor]
    types=s['record']('Point',s['field']('x','int','7'))
    main=decl('h','hash<int,int>','[]')+f'{v}h[-3]=8;{v}h[]=10;{v}h[4]=40;{v}h[]=50;echo {v}h[0],{v}h[5];'
    main+=f'{v}h{a}remove(5);{v}h[]=51;echo {v}h[5];{v}h{a}clear();{v}h[]=2;echo {v}h[0];'
    main+=decl('points','hash<Point,int>','[]')+s['local']('p','Point')+f'{v}points[]={v}p;'+s['infer']('copy',v+'points[0]')+f'echo {v}copy{a}x;'
    main+=decl('words','hash<string,int>','[]')+f'{v}words[]="heap-backed text";echo {v}words[0];'
    expected=b'10505127heap-backed text'
    for family in ['mixed','dynamic']:
        n=family[0];x=v+n
        main+=decl(n,family,'[]')+f'{x}[4]=4;{x}[]=5;echo (int)({x}[5]);{x}{a}remove(5);{x}[]=6;echo (int)({x}[5]);'
        main+=f'{x}{a}clear();{x}[]=7;echo (int)({x}[0]);'
        expected+=b'567'
    main+=decl('nil','mixed','null')+f'{v}nil[]=8;echo (int)({v}nil[0]);return 8;';expected+=b'8'
    edits = [(None,None,expected),(None,None,expected),
        ('types',types.replace('7','9'),expected.replace(b'127heap',b'129heap')),('types',types,expected),
        ('types',None,None),(None,None,None),('types',types,expected),
        ('main',main.replace('hash<int,int>','hash<int,string>'),None),('main',main,expected)]
    revisions(work,flavor,{'types':types,'main':main}, [edits[0], *edits[-2:]] if quick else edits)
    guard(work,flavor,'hash-overflow',decl('h','hash<int,int>','[]')+f'{v}h[9223372036854775807]=1;{v}h[]=2;return 8;','hash_append_key_overflow')
    if quick:
        return
    for family in ['mixed','dynamic']:
        guard(work,flavor,family+'-scalar',decl('h',family,'7')+f'{v}h[]=2;return 8;','boxed_expected_container')
        guard(work,flavor,family+'-overflow',decl('h',family,'[]')+f'{v}h[2147483647]=1;{v}h[]=2;return 8;','packed_append_key_limit')
    guard(work,flavor,'dynamic-null',decl('h','dynamic','null')+f'{v}h[]=2;return 8;','dynamic_requires_container')


if __name__=='__main__':
    p=argparse.ArgumentParser(description=__doc__);p.add_argument('--output',type=Path);p.add_argument("--quick",action="store_true");args=p.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-collection-append-') as tmp:
        work=(args.output or Path(tmp)).resolve();work.mkdir(exist_ok=True)
        for f in (['php'] if args.quick else ['php','cpp','js']):prove(work,f,args.quick)

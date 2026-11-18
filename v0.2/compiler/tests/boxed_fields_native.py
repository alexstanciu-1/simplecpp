"""Null-default boxed fields reuse record construction, copy and mutation ownership."""
import argparse
import tempfile
from pathlib import Path
from classes_native import fixture
from collection_goal_native import revisions


def prove(work,flavor):
    s=fixture(flavor);v,a=s['v'],s['access']
    def decl(name,t,value):
        return {'php':f'${name} {t} = {value};','cpp':f'{t} {name} = {value};','js':f'let {name}:{t} = {value};'}[flavor]
    fields=s['field']('m','mixed')+s['field']('d','dynamic')
    types=s['record']('Raw',fields)
    body=decl('m','mixed','[]')+decl('d','dynamic','[]')+f'{v}m["x"]=7;{v}d["x"]=7;{v}this{a}m={v}m;{v}this{a}d={v}d;'
    ctor={'php':'public function __construct()','cpp':'Box()','js':'constructor()'}[flavor]+'{'+body+'}'
    types+=s['record']('Box',fields+ctor)
    types+=s['record']('Outer',s['field']('inner','Box'))
    types+=s['function']('fetch','shared<Box>',[('owner','shared<Box>')],f'return {v}owner;')
    main=s['local']('raw','Raw')+decl('nil','null',v+'raw'+a+'m')+decl('nil2','null',v+'raw'+a+'d')
    main+=s['local']('original','Box')+s['infer']('copy',v+'original')
    main+=f'{v}copy{a}m["x"]=9;{v}copy{a}d["x"]=9;echo (int)({v}original{a}m["x"]),(int)({v}original{a}d["x"]);'
    main+=f'{v}copy{a}m{a}clear();{v}copy{a}d{a}clear();echo count({v}original{a}m),count({v}original{a}d);'
    main+=f'{v}original{a}m[]=8;{v}original{a}d[]=8;foreach ({v}original{a}m as {v}item) {{echo (int){v}item;}}'
    main+=s['infer']('owner','new Box()')+f'{v}owner{a}m["x"]=11;echo (int)({v}owner{a}m["x"]);'
    main+=s['local']('outer','Outer')+s['infer']('outerCopy',v+'outer')
    main+=f'{v}outerCopy{a}inner{a}m["x"]=3;{v}outerCopy{a}inner{a}d["x"]=3;echo (int)({v}outer{a}inner{a}m["x"]),(int)({v}outer{a}inner{a}d["x"]);'
    main+=f'fetch({v}owner){a}m["x"]+=1;echo (int)({v}owner{a}m["x"]);return 8;'
    expected=b'791078117312'
    revisions(work,flavor,{'types':types,'main':main},[(None,None,expected),(None,None,expected),
        ('types',types.replace(']=7;',']=6;'),b'691068116312'),('types',types,expected),
        ('types',None,None),(None,None,None),('types',types,expected),
        ('types',types.replace(s['field']('m','mixed'),s['field']('m','int')),None),('types',types,expected)])


if __name__=='__main__':
    p=argparse.ArgumentParser(description=__doc__);p.add_argument('--output',type=Path);args=p.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-boxed-fields-') as tmp:
        work=(args.output or Path(tmp)).resolve();work.mkdir(exist_ok=True)
        for f in ['php','cpp','js']:prove(work,f)

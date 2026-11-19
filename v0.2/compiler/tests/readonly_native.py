"""Stored-value readonly construction, reference boundaries and incremental recovery in every flavor."""
import argparse
import tempfile
from pathlib import Path
from classes_native import fixture
from collection_goal_native import revisions
from app_namespace_native import service,invoke
from extends_native import source_edit


def prove(work,flavor):
    s=fixture(flavor);v,a=s['v'],s['access']
    def field(name,t,default=None,ro=True):
        text=s['field'](name,t,default)
        return text.replace('public ','public readonly ',1) if ro and flavor=='php' else 'readonly '+text if ro else text
    def decl(name,t,value):
        return {'php':f'${name} {t} = {value};','cpp':f'{t} {name} = {value};','js':f'let {name}:{t} = {value};'}[flavor]
    ctor={'php':'public function __construct(int $x)','cpp':'Box(int x)','js':'constructor(x:int)'}[flavor]
    body=f'if ({v}x > 0) {{{v}this{a}x={v}x;}} else {{{v}this{a}x=7;}}'
    body+=decl('items','vector<int>','[]')+f'{v}items[]=8;{v}this{a}items={v}items;'
    body+=decl('m','mixed','[]')+decl('d','dynamic','[]')+f'{v}m["x"]=8;{v}d["x"]=8;{v}this{a}m={v}m;{v}this{a}d={v}d;'
    body+=f'{v}this{a}point=new Point();'
    enum='enum Kind {case Word=7;}' if flavor=='php' else 'enum Kind {Word=7}'
    case='Kind.Word' if flavor=='js' else 'Kind::Word'
    body+=f'{v}this{a}kind={case};'
    fields=field('kind','Kind')+field('x','int')+field('text','string','"ok"')+field('items','vector<int>')+field('m','mixed')+field('d','dynamic')+field('point','shared<Point>')+field('inlinePoint','Point')
    body+=s['local']('p','Point')+f'{v}this{a}inlinePoint={v}p;'
    method=s['function']('touch','void',[],f'{v}this{a}x=2;')
    method='public '+method if flavor=='php' else method.replace('function ','') if flavor=='js' else method
    types=enum+s['record']('Point',s['field']('x','int','1')+method)+s['record']('Box',fields+ctor+'{'+body+'}')
    local={'php':'$box Box(7);','cpp':'Box box(7);','js':'let box:Box(7);'}[flavor]
    main=local+f'if (!{v}box{a}items{a}has(0)) {{return 2;}}echo enum_value({v}box{a}kind);'+f'echo {v}box{a}x,{v}box{a}text,count({v}box{a}items);'
    main+=f'{v}box{a}point{a}touch();{v}box{a}point{a}x=9;{v}box{a}d["x"]=4;{v}box{a}d{a}clear();echo {v}box{a}point{a}x,count({v}box{a}d);'
    main+=s['infer']('copy',v+'box')+f'{v}copy={v}box;echo {v}copy{a}x;'
    main+=decl('items','vector<int>',v+'box'+a+'items')+f'{v}items[]=9;echo count({v}box{a}items);return 8;'
    expected=b'77ok19071'
    badtypes=types.replace(f'{v}this{a}point=new Point();',f'{v}this{a}point=new Point();{v}this{a}x=1;')
    revisions(work,flavor,{'types':types,'main':main},[(None,None,expected),(None,None,expected),
        ('main',main.replace('(7)','(6)'),b'76ok19061'),('types',badtypes,None),(None,None,None),
        ('types',types,b'76ok19061'),('main',main,expected),('types',types.replace(field('x','int'),field('x','int',ro=False)),expected),('types',types,expected)])
    # Each prohibited write is checked through the normal resident compiler, then recovered.
    app=work/flavor;proc=service(app,work/(flavor+'-reject-out'))
    bad=[f'{v}box{a}x=1;',f'{v}box{a}x++;',f'{v}box{a}x+=1;',f'{v}box{a}items[]=9;',
         f'{v}box{a}items[0]=9;',f'{v}box{a}items[0]+=9;',f'{v}box{a}items[0]++;',
         f'{v}box{a}items{a}clear();',f'{v}box{a}m["x"]=9;',f'{v}box{a}m{a}clear();',
         f'{v}box{a}text[0]=65;',f'{v}box{a}inlinePoint{a}x=3;',f'{v}box{a}point=null;',f'{v}box{a}inlinePoint{a}touch();']
    try:
        for text in bad:
            source_edit(app/('main.'+flavor),local+text+'return 8;')
            got=invoke(proc);assert 'readonly' in got.get('error','').lower(),(flavor,text,got)
        source_edit(app/('main.'+flavor),local+f'{v}box{a}x=1;return 8;')
        source_edit(app/('types.'+flavor),types.replace(field('x','int'),field('x','int',ro=False)));assert 'error' not in invoke(proc)
        source_edit(app/('types.'+flavor),types);assert 'readonly' in invoke(proc).get('error','').lower()
        source_edit(app/('main.'+flavor),main);assert 'error' not in invoke(proc)
        for edit in [types.replace(f'{v}this{a}point=new Point();',''),
                     types.replace(f'{v}this{a}point=new Point();',f'{v}this{a}text="bad";{v}this{a}point=new Point();'),
                     types.replace(f'{v}this{a}x={v}x;',f'echo {v}this{a}x;{v}this{a}x={v}x;'),
                     types.replace(f'{v}this{a}x={v}x;',''),
                     types.replace(f'{v}this{a}x={v}x;','return;'),
                     types.replace(f'{v}this{a}x={v}x;',f'while (true) {{{v}this{a}x={v}x;break;}}')]:
            source_edit(app/('types.'+flavor),edit);got=invoke(proc)
            assert 'readonly' in got.get('error','').lower(),(flavor,got)
            source_edit(app/('types.'+flavor),types);assert 'error' not in invoke(proc)
    finally:proc.stdin.close();proc.wait()


if __name__=='__main__':
    p=argparse.ArgumentParser(description=__doc__);p.add_argument('--output',type=Path);args=p.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-readonly-') as tmp:
        work=(args.output or Path(tmp)).resolve();work.mkdir(exist_ok=True)
        for f in ['php','cpp','js']:prove(work,f)

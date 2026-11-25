"""Shared identity hash keys: strong retention, empty keys, views and incremental/fresh recovery."""
import argparse
from pathlib import Path
from classes_native import fixture
from collection_goal_native import revisions
from interfaces_native import forms
from object_access_native import syntax
from app_namespace_native import service, invoke
from extends_native import source_edit
from nested_collections_native import sanitize


def sources(flavor):
    s = fixture(flavor)
    v, a = s['v'], s['access']
    def decl(n, t, value='[]'):
        return {'php': f'${n} {t} = {value};', 'cpp': f'{t} {n} = {value};', 'js': f'let {n}:{t} = {value};'}[flavor]
    point = s['record']('Point', s['field']('x', 'int', '7'))
    record = s['record']('Payload',s['field']('text','string','"heap backed value owned by a shared key table"'))
    api = s['function']('copyTable','hash<int,shared<Point>>',[('table','hash<int,shared<Point>>')],f'return {v}table;')
    live = s['infer']('p','new Point()') + s['infer']('w',f'weak({v}p)') + decl('table','hash<int,shared<Point>>')
    live += f'{v}table[{v}p]=1;{v}p=null;if (lock({v}w) === null) {{echo "bad";}}echo "L";return {v}w;'
    api += s['function']('lifetime','weak<Point>',[],live)
    main = decl('table','hash<int,shared<Point>>') + s['infer']('p','new Point()') + s['infer']('alias',v+'p') + s['infer']('q','new Point()')
    main += f'{v}table[{v}p]=1;{v}table[{v}alias]=2;{v}table[{v}q]=3;echo count({v}table),":",{v}table[{v}p],":",{v}p{a}x;'
    main += f'{v}p{a}x=99;if (!{v}table{a}has({v}p)) {{return 90;}}'
    main += s['local']('empty','shared<Point>') + f'{v}table[{v}empty]=4;echo ":",{v}table[{v}empty];'
    main += s['infer']('copy',f'copyTable({v}table)') + f'{v}table{a}clear();echo ":",count({v}copy);'
    main += s['infer']('watch',f'weak({v}p)') + f'{v}alias=null;{v}p=null;if (lock({v}watch) === null) {{return 91;}}'
    main += s['infer']('held',f'lock({v}watch)') + f'echo ":",{v}copy[{v}held];{v}copy{a}remove({v}held);{v}held=null;if (lock({v}watch) !== null) {{return 92;}}'
    main += f'{v}copy{a}clear();'
    # Growth, replacement, removal holes and keyed outputs all keep the ordinary owner lifecycle.
    main += s['infer']('i','0') + f'while ({v}i<80) {{'+s['infer']('key','new Point()')+f'{v}key{a}x={v}i;{v}table[{v}key]={v}i;{v}i++;}}'
    main += s['infer']('sum','0') + f'foreach ({v}table as {v}key => {v}value) {{{v}sum+={v}value;if ({v}key{a}x%2 == 0) {{{v}table{a}remove({v}key);}}}}echo ":",{v}sum,":",count({v}table);{v}table{a}clear();'
    main += s['infer']('dead','lifetime()') + f'if (lock({v}dead) !== null) {{return 93;}}'
    # Existing value contracts: string, inline record and nested collection payloads.
    main += decl('strings','hash<string,shared<Point>>')+f'{v}strings[{v}q]="text";echo {v}strings[{v}q];'
    main += s['local']('payload','Payload')+decl('records','hash<Payload,shared<Point>>')+f'{v}records[{v}q]={v}payload;'
    main += decl('items','vector<int>')+f'{v}items[]=8;'+decl('nested','hash<vector<int>,shared<Point>>')+f'{v}nested[{v}q]={v}items;echo {v}nested[{v}q][0];'
    # The two interface views can have different addresses but share one control identity.
    _, evaluate, klass, method, interface, implementation = forms(flavor)
    views = interface('Left')+interface('Right')+klass('Base')+implementation('Leaf',evaluate('override'), 'Base', ('Left','Right'))
    api += s['function']('asBase','Base',[('leaf','Leaf')],f'return {v}leaf;')
    api += s['function']('asLeft','Left',[('leaf','Leaf')],f'return {v}leaf;')
    api += s['function']('asRight','Right',[('leaf','Leaf')],f'return {v}leaf;')
    main += s['infer']('leaf','new Leaf()') + s['infer']('base',f'asBase({v}leaf)')+s['infer']('left',f'asLeft({v}leaf)')+s['infer']('right',f'asRight({v}leaf)')
    for name,t,expr in [('bases','Base','asBase'),('lefts','Left','asLeft'),('rights','Right','asRight')]:
        main += decl(name,f'hash<int,{t}>')+f'{v}{name}[{expr}({v}leaf)]=6;echo {v}{name}[{expr}({v}leaf)];'
    cast=syntax(flavor)[3]
    main += decl('roundtrip','Left',cast(v+'right','Left'))+f'if (!{v}lefts{a}has({v}roundtrip) || {v}lefts[{v}roundtrip] != 6) {{return 94;}}'
    main += 'return 8;'
    return dict(point=point,payload=record,views=views,api=api,main=main)


def reject(work,flavor,src):
    """Keep key admission exact, and leave weak observers and value-record keys unavailable."""
    s=fixture(flavor);v=s['v']
    app=work/flavor;proc=service(app,work/(flavor+'-reject-out'))
    try:
        bad_types=['hash<int,weak<Point>>','hash<int,Point>','hash<int,vector<int>>']
        for t in bad_types:
            source_edit(app/('main.'+flavor),s['local']('bad',t)+'return 8;')
            result=invoke(proc);assert 'Unavailable runtime specialization' in result.get('error',''),result
        bad=src['main'].replace(f'{v}table[{v}p]=1;',f'{v}table[null]=1;')
        source_edit(app/('main.'+flavor),bad);assert 'Hash key requires' in invoke(proc).get('error','')
        for replacement, message in [(f'{v}table[new Payload()]=1;', 'Hash key requires'),
                (f'{v}table[]=1;', 'Append requires an appendable collection')]:
            source_edit(app/('main.'+flavor),src['main'].replace(f'{v}table[{v}p]=1;',replacement))
            assert message in invoke(proc).get('error','')
        source_edit(app/('main.'+flavor),src['main']);assert 'error' not in invoke(proc)
    finally:proc.stdin.close();proc.wait()


def main():
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output',type=Path,required=True)
    parser.add_argument('--sanitize',action='store_true')
    args=parser.parse_args();args.output.mkdir(parents=True,exist_ok=True)
    expected=b'2:2:7:4:3:2:3160:40Ltext8666'
    for flavor in ['php','cpp','js']:
        src=sources(flavor)
        changed=src['point'].replace('=7','=8')
        # Keep a key AST while replacing its value declaration, and vice versa.
        changed_payload=src['payload'].replace('string','int').replace('"heap backed value owned by a shared key table"','17')
        revisions(args.output,flavor,src,[(None,None,expected),(None,None,expected),
            ('point',changed,expected.replace(b':7:',b':8:')),('point',None,None),(None,None,None),
            ('point',src['point'],expected),('payload',changed_payload,expected),('payload',src['payload'],expected),
            ('api',src['api'].replace('hash<int,shared<Point>>','hash<int,shared<Payload>>'),None),('api',src['api'],expected)])
        reject(args.output,flavor,src)
        if args.sanitize: sanitize(args.output/(flavor+'-out'),args.output/(flavor+'-sanitized'),expected)


if __name__=='__main__':main()

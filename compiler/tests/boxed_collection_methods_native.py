"""Boxed count/empty/has/remove: normalized keys, copy policies, guards and incremental recovery."""
from source_suffix import source_suffix
import argparse
import json
import re
import tempfile
from pathlib import Path
from app_namespace_native import artifacts, invoke, service
from application_vectors_native import measure
from classes_native import fixture
from extends_native import source_edit
from entry_probe import raw_entry


def prove(work,flavor):
    s=fixture(flavor);v,a=s['v'],s['access'];times=[]
    def decl(name,t,value):
        return {'php':f'${name} {t} = {value};','cpp':f'{t} {name} = {value};','js':f'let {name}:{t} = {value};'}[flavor]
    provider='';main='';expected=b''
    for family in ['mixed','dynamic']:
        name=family[0];x=v+name
        init=decl(name,family,'[]')+f'{x}[1]=null;{x}["01"]=0;{x}["a\\0b"]=false;{x}["4294967296"]=7;'
        provider+=s['function']('make'+name,family,[],init+f'return {x};')
        main+=decl(name,family,'make'+name+'()')
        main+=f'echo count({x}),empty({x}),{x}{a}has("1"),{x}{a}has(1),{x}{a}has("01"),{x}{a}has("a\\0b"),{x}{a}has("4294967296");'
        main+=decl('copy'+name,family,x)+f'echo {x}{a}remove("1"),{x}{a}remove(1),count({x}),count({v}copy{name});'
        main+=f'echo {x}{a}remove("01"),{x}{a}remove("a\\0b"),{x}{a}remove("4294967296"),empty({x}),count({x});'
        main+=decl('nil'+name,family,'null')+f'echo {v}nil{name}{a}has(0),{v}nil{name}{a}remove("0");'
        main+=f'echo count(make{name}()),make{name}(){a}has("1"),make{name}(){a}remove("01");'
        expected+=b'4011111103'+(b'4' if family=='mixed' else b'3')+b'1111000411'
    main=re.sub(r'echo ([^;]+);',lambda m:'echo '+','.join('(int)('+x+')' for x in m[1].split(','))+';',main)+'return 8;'
    app=work/flavor;app.mkdir();out=work/(flavor+'-out')
    source_edit(app/'module.json',json.dumps({'entry':'main.'+source_suffix(flavor)}));source_edit(app/('main.'+source_suffix(flavor)),main)
    edits=[(provider,main,expected),(provider,main,expected),
        (provider.replace(']=7;',']=8;'),main,expected),
        (provider,main,expected),(None,main,None),(None,main,None),(provider,main,expected),
        (provider,main.replace('has("1")','has(true)'),None),
        (provider,main.replace('remove("01")','remove(1.0)'),None),
        (provider,main.replace('has("1")','has()'),None),
        (provider,main.replace('remove("01")','remove("01",1)'),None),
        (provider.replace('mixed','dynamic'),main,None),(provider,main,expected)]
    proc=service(app,out)
    try:
        for rev,(api,body,wanted) in enumerate(edits):
            before=artifacts(out);path=app/('api.'+source_suffix(flavor))
            if api is None:
                if path.exists():path.unlink()
            else:source_edit(path,api)
            source_edit(app/('main.'+source_suffix(flavor)),body);got=invoke(proc)
            fresh_out=work/f'{flavor}-fresh-{rev}';fresh=service(app,fresh_out)
            try:clean=invoke(fresh)
            finally:fresh.stdin.close();fresh.wait()
            if wanted is None:
                assert 'error' in got and got['error']==clean.get('error'),(flavor,rev,got,clean)
                assert artifacts(out)==before
                continue
            assert 'error' not in got and 'error' not in clean,(flavor,rev,got,clean)
            assert artifacts(out)==artifacts(fresh_out)
            row={'revision':rev,'php_ms':got['php_ms'],'prepared':got['preparation']['prepared']}
            if rev==1:assert got['preparation']['prepared']==0
            else:row.update(measure(out,work/f'{flavor}-native-{rev}',wanted))
            times.append(row)
            print(flavor,rev,'boxed methods passed',flush=True)
    finally:proc.stdin.close();proc.wait()
    for family in ['mixed','dynamic']:
        for label,expr,error in [('count-null',f'count({v}x)','boxed_expected_container'),
                                 ('empty-null',f'empty({v}x)','invalid_empty_operand'),
                                 ('has-scalar',f'{v}x{a}has(0)','boxed_expected_container'),
                                 ('remove-scalar',f'{v}x{a}remove("0")','boxed_expected_container'),
                                 ('negative',f'{v}x{a}has(-1)','packed_integer_key_limit'),
                                 ('large',f'{v}x{a}remove(2147483648)','packed_integer_key_limit')]:
            value='7' if 'scalar' in label else '[]' if label in ['negative','large'] else 'null'
            source_edit(app/('main.'+source_suffix(flavor)),decl('x',family,value)+'if ('+expr+') {} return 8;')
            path=app/('api.'+source_suffix(flavor))
            if path.exists():path.unlink()
            probe=work/(flavor+'-'+family+'-'+label);p=service(app,probe)
            try:got=invoke(p)
            finally:p.stdin.close();p.wait()
            assert 'error' not in got,got
            entry=probe/'entry.cpp';text=raw_entry(entry.read_text())
            check=f'std::string_view(e.what()) == "{error}"'
            entry.write_text('#include <stdexcept>\n#include <string_view>\n'+text+'\nint main() {try {tested_entry();} catch(const std::exception& e) {return '+check+' ? 8 : 91;} return 92;}\n')
            measure(probe,work/(flavor+'-'+family+'-'+label+'-native'),b'')
    (work/(flavor+'-timings.json')).write_text(json.dumps(times,indent=2)+'\n')


def free_overload_probe(work):
    # Existing operand-zero dispatch must retain all six PHP number_format signatures.
    app=work/'free-overload';app.mkdir();out=work/'free-overload-out'
    source_edit(app/'module.json',json.dumps({'entry':'main.phs'}))
    source_edit(app/'main.phs','echo number_format(1234),"|",number_format(1234.5),"|",'
        'number_format(1234,2),"|",number_format(1234.5,2),"|",'
        'number_format(1234,2,",","."),"|",number_format(1234.5,2,",",".");return 8;')
    proc=service(app,out)
    try:got=invoke(proc)
    finally:proc.stdin.close();proc.wait()
    assert 'error' not in got,got
    measure(out,work/'free-overload-native',b'1,234|1,235|1,234.00|1,234.50|1.234,00|1.234,50')


if __name__=='__main__':
    p=argparse.ArgumentParser(description=__doc__);p.add_argument('--output',type=Path);args=p.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-boxed-methods-') as tmp:
        work=(args.output or Path(tmp)).resolve();work.mkdir(exist_ok=True)
        for flavor in ['php','cpp','js']:prove(work,flavor)
        free_overload_probe(work)

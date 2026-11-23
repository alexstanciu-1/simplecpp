"""Bounded match: lazy native switch, exact types and retained preparation recovery."""
import json
import subprocess
import tempfile
from pathlib import Path

from classes_native import fixture
from extends_native import runtime, source_edit
from interfaces_native import fresh
from app_namespace_native import artifacts, invoke, service
from inline_adapters_native import incremental_native_build
from parent_methods_native import write_app

ROOT = Path(__file__).resolve().parents[2]


def prove(work, flavor, libraries):
    s = fixture(flavor)
    const = lambda n,t,x: f'const {n}:{t}={x};' if flavor=='js' else f'const {t} {n}={x};'
    labels = const('LABEL','int','1')+const('ALIAS','int','1')+const('SMALL','byte','255')
    v, fn = s['v'], s['function']
    sep = '.' if flavor == 'js' else '::'
    enum = 'enum Kind {' + ('case One=1;case Two=2;' if flavor == 'php' else 'One=1,Two=2') + '}'
    textenum = 'enum Text:string {' + ('case One="a";case Two="b";' if flavor == 'php' else 'One="a",Two="b"') + '}'
    choose = fn('choose', 'int', [('x', 'int')], f'return match ({v}x) {{ 1 => 8, default => 9, }};')
    api = choose + fn('subject', 'int', [], 'echo "S";return 1;')
    api += fn('selected', 'int', [], 'echo "A";return 4;')
    api += fn('fail', 'int', [('x','int')], f'return match ({v}x) {{1=>8/0,default=>7}};')
    api += fn('limit', 'int', [('x','int')], f'return match ({v}x) {{-9223372036854775808=>1,9223372036854775807=>2,default=>3}};')
    api += fn('named', 'int', [('x','Kind')], f'return match ({v}x) {{Kind{sep}Two,Kind{sep}One=>1,default=>2}};')
    api += fn('textual', 'int', [('x','Text')], f'return match ({v}x) {{Text{sep}One=>3,default=>4}};')
    api += fn('labelled','int',[('x','int')],f'return match({v}x){{LABEL=>6,default=>0}};')
    api += fn('small','int',[('x','byte')],f'return match({v}x){{SMALL=>7,default=>0}};')
    api += fn('failure','int',[],s['infer']('n','match(1){1=>fail(1),default=>selected()}')+f'echo "L";return {v}n;')
    api += fn('subjectFailure','int',[],'return match(fail(1)){1=>selected(),default=>selected()};')
    api += fn('copy', 'string', [('x','string')], f'return match (1) {{1=>{v}x,default=>"other"}};')
    labels += const('EXT', 'string', '"a\\0b"')
    api += fn('extension', 'string', [], 'echo "T";return "a\\0b";')
    api += fn('selectText', 'int', [('x','string')],
              f'return match({v}x){{default=>9,EXT=>1,"a"=>2,""=>3,"01"=>4,"1"=>5}};')
    api += fn('lazyText', 'int', [], 'return match(extension()){default=>fail(1),"unused",EXT=>selected()};')
    api += fn('onlyDefault', 'int', [], 'return match(extension()){default=>7};')
    field = 'this field belongs to a temporary shared owner'
    holder = s['klass']('Holder', s['field']('text', 'string', '"' + field + '"'))
    api += fn('owner', 'Holder', [], 'echo "H";return new Holder();')
    api += fn('temporaryField', 'int', [], 'return match(owner()' + s['access'] + 'text){"' + field + '"=>2,default=>0};')
    main = s['infer']('n','match (subject()) { 400,1,404=>selected(),default=>fail(1)}')
    main += f'echo {v}n,choose(1),fail(0),limit((-9223372036854775807 - 1)),limit(9223372036854775807);'
    main += f'echo named(Kind{sep}One),textual(Text{sep}Two);'
    main += 'if (false && match(subject()){1=>true,default=>false}) {echo "BAD";}'
    main += 'if (true || match(subject()){1=>true,default=>false}) {}'
    main += 'echo labelled(1),small(255);'
    main += 'echo match (2) {default=>match(1){1=>5,default=>0},1=>0};'
    main += s['infer']('a','"a\\0b"')+s['infer']('b',f'copy({v}a)')+f'{v}a="changed";echo {v}b;echo selectText("a\\0b"),selectText("a"),selectText(""),selectText("01"),selectText("1"),selectText("a\\0c"),lazyText(),onlyDefault(),temporaryField();return 8;'
    expected=b'SA4871214675a\0b123459TA4T7H2'
    app,out=work/flavor,work/(flavor+'-out')
    write_app(app,flavor,{'labels':labels,'types':enum+textenum,'holder':holder,'api':api,'main':main,'idle':fn('idle','int',[],'return 3;')})
    result=fresh(app,out);assert 'error' not in result,result
    manifest=json.loads((out/'manifest.json').read_text())
    units={Path(p).stem:u for p,u in manifest['sources'].items()}
    generated=(out/units['api']).read_text()
    assert 'std::memcmp(' in generated and '.data()' in generated and 'const auto dispatch_subject = extension_()' in generated, generated
    assert 'switch (' in generated and generated.count('([&]()')==1,generated
    assert '([&]()' in (out/units['main']).read_text()
    for cc,lib in libraries.items():
        incremental_native_build(out,work/(flavor+'-native-'+cc),cc,lib,expected)
        # A selected checked failure unwinds, and skips the following observable action.
        probe=work/(flavor+'-'+cc+'-probe.cpp')
        probe.write_text('#include "'+str(out/units['api'])+'"\n#include <stdexcept>\nint main(){try{App::failure_();return 1;}catch(const std::domain_error&){}try{App::subjectFailure_();return 2;}catch(const std::domain_error&){return 0;}}\n')
        exe=probe.with_suffix('')
        p=subprocess.run([cc,'-std=c++23','-O1','-Wall','-Wextra','-Werror','-I',ROOT/'runtime/generated/public',probe,lib,'-o',exe],capture_output=True,text=True)
        assert p.returncode==0,p.stderr
        p=subprocess.run([exe],capture_output=True);assert (p.returncode,p.stdout,p.stderr)==(0,b'',b''),p
    # A label dependency and selected function body edit must invalidate the owner,
    # while no-op and unrelated callers retain byte-identical/native artifacts.
    resident=service(app,out)
    edits=[('api',api,None,expected),('api',api.replace('1 => 8','1 => 6'),None,expected.replace(b'487',b'467')),
           ('api',api.replace('default => 9','1 => 9'),'Duplicate match label',None),
           ('api',api.replace('default => 9','1 => 9'),'Duplicate match label',None),
           ('api',api,None,expected),('types',enum.replace('One=1','Renamed=1')+textenum,'Unknown enum case',None),
           ('types',enum+textenum,None,expected),('api',api,None,expected),
           ('labels',labels.replace('=1;', '=2;', 1),None,expected.replace(b'4675',b'4075')),
           ('labels',labels.replace('a\\0b','a'),'Duplicate match label',None),('labels',None,'Unknown',None),('labels',labels,None,expected),('api',api,None,expected)]
    edits.extend([
        ('api', api.replace('LABEL=>6', '2,LABEL=>6'), None, expected),
        ('labels', labels.replace('=1;', '=2;', 1), 'Duplicate match label', None),
        ('labels', labels, None, expected),
    ])
    try:
        for i,(name,source,error,stdout) in enumerate(edits):
            before=artifacts(out)
            path=app/(name+'.'+flavor)
            if source is None:path.unlink()
            else:source_edit(path,source)
            current=invoke(resident);comparison_out=work/f'{flavor}-fresh-{i}';comparison=fresh(app,comparison_out)
            if error:
                assert error in current.get('error',''),current
                assert current['error']==comparison.get('error')
                assert artifacts(out)==before
                continue
            assert 'error' not in current and 'error' not in comparison,(current,comparison)
            assert artifacts(out)==artifacts(comparison_out),i
            if i in [7,12]:assert current['preparation']['prepared']==0 and current['emitted_sources']==[],current
            for cc,lib in libraries.items():
                changed=incremental_native_build(out,work/(flavor+'-native-'+cc),cc,lib,stdout)
                if i in [7,12]:assert not changed,changed
                if i==1:assert changed=={units['api']},changed
    finally:resident.stdin.close();resident.wait()
    invalid=[('match(1){1,2=>2,2,3=>3,default=>4}', 'Duplicate match label'),
             ('match(true){true,false=>1,default=>2}', '=>'),
             ('match("x"){EXT=>1,"a\\0b"=>2,default=>3}','Duplicate match label'),
             ('match("x"){1=>1,default=>2}','label type'),
             ('match("x"){extension()=>1,default=>2}','literal, named constant'),
             ('match(1){LABEL=>2,ALIAS=>3,default=>4}','Duplicate match label'),('match(1){1=>2}','default arm'),('match(1){default=>2,default=>3}','Duplicate match default'),
             ('match(1){1=>2,1=>3,default=>4}','Duplicate match label'),
             ('match(1){true=>2,default=>3}','label type'),('match(false){default=>1}','constant-label dispatch'),
             ('match(1.0){default=>1}','constant-label dispatch'),('match(1){subject()=>2,default=>3}','literal, named constant'),
             ('match(1){1=>2,default=>"x"}','result types'),('match(1){}','default arm')]
    for i,(expr,error) in enumerate(invalid):
        bad=work/f'{flavor}-bad-{i}';write_app(bad,flavor,{'labels':labels,'main':fn('subject','int',[],'return 1;')+fn('extension','string',[],'return "x";')+s['infer']('x',expr)+'return 8;'})
        result=fresh(bad,work/f'{flavor}-bad-out-{i}');assert error in result.get('error',''),result
    print(flavor,'match behavior, rejection and incremental/native recovery passed',flush=True)


if __name__=='__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-match-') as temporary:
        work=Path(temporary)
        libraries={cc:runtime(work,cc) for cc in ['clang++','g++']}
        for flavor in ['cpp','php','js']:prove(work,flavor,libraries)

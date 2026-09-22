from pathlib import Path
import copy
import json
import re

ROOT=Path(__file__).resolve().parents[3]
def quote(text):
    return '"'+''.join(chr(b) if 32<=b<127 and b not in (34,92) else '\\'+f'{b:02X}' for b in text.encode())+'"'
def facts(text):return {k:int(v) for k,v in re.findall(r'^@(\w+) = [^\n]*constant i64 (\d+)\b',text,re.M)}
def build():
    cases=[];evidence=ROOT/'specs/planning/compiler_migration/results/layout-witness-01'
    rows=json.loads((evidence/'cases.json').read_text())
    for index,row in enumerate(rows):
        path=evidence/f'witness-{index}.ll'
        if not path.exists():continue
        output=path.read_text();primitive=evidence/f'primitive-{index}-folded.ll'
        cases.append(dict(nodes=row['nodes'],root=row['root'],triple=re.search(r'target triple = "([^"]+)"',output).group(1),layout=re.search(r'target datalayout = "([^"]+)"',output).group(1),output=output,primitive=primitive.read_text() if primitive.exists() else '',facts=facts(output),spelling='['+str(facts(output)['size'])+' x i8]',mode='measurement',value=0,accept=True))
    # Independent ordinary-LLVM result fixture.
    base=dict(nodes=[dict(kind='integer',width=32)],root=1,triple='target',layout='e',primitive='',facts=dict(size=4,alignment=4),spelling='i32',mode='measurement',value=0,accept=True)
    base['output']='target triple = "target"\ntarget datalayout = "e"\n@size = constant i64 4\n@alignment = constant i64 4\n'
    cases.append(base)
    for prefix in ['internal addrspace(3) ', 'thread_local(localexec) ', 'dso_local local_unnamed_addr ']:
        c=copy.deepcopy(base);c['output']=c['output'].replace('@size = constant','@size = '+prefix+'constant');cases.append(c)
    real=next(c for c in cases if c['primitive'] and 'field_2' in c['facts'])
    def mutate(source,label,output=None,primitive=None,accept=False):
        c=copy.deepcopy(source);c['label']=label;c['accept']=accept
        if output is not None:c['output']=output
        if primitive is not None:c['primitive']=primitive
        cases.append(c)
    for name in ['size','alignment','field_0','field_1','field_2']:
        mutate(real,'missing-'+name,re.sub(r'^@'+name+r' = [^\n]*\n','',real['output'],flags=re.M))
    for prefix in ['target triple = ','target datalayout = ']:
        line=next(l for l in real['output'].splitlines() if l.startswith(prefix))
        mutate(real,'missing-'+prefix,real['output'].replace(line,''))
        mutate(real,'comment-'+prefix,real['output'].replace(line,'; '+line))
        mutate(real,'duplicate-'+prefix,real['output']+'\n'+line+'\n')
        mutate(real,'changed-'+prefix,real['output'].replace(line,prefix+'"wrong"'))
    size_line=next(l for l in real['output'].splitlines() if l.startswith('@size = '))
    mutate(real,'duplicate-fact',real['output']+'\n'+size_line+'\n')
    for number in ['-1','+1','01','1.0','1e2','9223372036854775808','18446744073709551615','999999999999999999999999','ptrtoint (ptr null to i64)','4 junk']:
        mutate(real,'bad-number-'+number,real['output'].replace(size_line,'@size = constant i64 '+number))
    for prefix in ['notconstant i64 ','; constant i64 ','"constant i64 ','constant i32 ']:
        mutate(real,'bad-prefix-'+prefix,real['output'].replace(size_line,'@size = '+prefix+'8'))
    for name,value in [('size',0),('alignment',3),('alignment',0),('field_1',0),('field_2',99999)]:
        text=re.sub(r'(^@'+name+r' = [^\n]*constant i64 )\d+',lambda m:m[1]+str(value),real['output'],flags=re.M)
        mutate(real,'invalid-measurement-'+name+str(value),text)
    primitive_name=next(k for k in facts(real['primitive']))
    mutate(real,'primitive-mismatch',primitive=re.sub(r'(^@'+primitive_name+r' = [^\n]*constant i64 )\d+',lambda m:m[1]+'99',real['primitive'],flags=re.M))
    mutate(real,'primitive-absent',primitive='')
    mutate(real,'primitive-target',primitive=real['primitive'].replace('target triple = "','target triple = "wrong'))
    mutate(real,'crlf',real['output'].replace('\n','\r\n'),primitive=real['primitive'].replace('\n','\r\n'),accept=True)
    mutate(real,'no-final-newline',real['output'].rstrip('\n'),accept=True)
    for number in [0,1,9223372036854775806,9223372036854775807]:
        c=copy.deepcopy(base);c.update(mode='facts',value=number,output='target triple = "target"\ntarget datalayout = "e"\n@size = constant i64 '+str(number)+'\n');cases.append(c)
    for target in ['quote"slash\\','nonascii-α','control\n\t\x00']:
        c=copy.deepcopy(base);c.update(mode='facts',value=4,triple=target,layout=target+'-layout',output='target triple = '+quote(target)+'\ntarget datalayout = '+quote(target+'-layout')+'\n@size = constant i64 4');cases.append(c)
    return cases

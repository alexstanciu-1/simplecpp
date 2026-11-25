"""Isolated diagnostic traces after the unprofiled matrix; baseline artifacts stay untouched."""
import json,subprocess
from pathlib import Path
import benchmark_support as b
B=b.BASE;O=B/'profiles';O.mkdir()

def main():
    rows=[]
    for opt,policy in [(0,2),(0,0),(1,2),(1,1)]:
        tag=f'O{opt}-policy{policy}-trial3'
        calls=json.loads((B/'results'/tag/'full/clang-calls.json').read_text())
        for name in ('workflow_checks','closing_checks','warehouse_desk'):
            encoded='n'+('src/'+name+'.phs').encode().hex()+'.cpp'
            c=next(c for c in calls if '-c' in c['args'] and c['args'][c['args'].index('-c')+1].endswith(encoded))
            args=[];i=0
            while i<len(c['args']):
                a=c['args'][i]
                if a=='-MD':i+=1;continue
                if a in ('-MF','-MQ','-o'):i+=2;continue
                args.append(a);i+=1
            stem=f'O{opt}-policy{policy}-{name}'
            command=['/usr/bin/clang++-18',*args,'-ftime-trace','-o',str(O/(stem+'.o'))]
            p=subprocess.run(['/usr/bin/time','-f','%e %U %S %M','-o',str(O/(stem+'.time')),*command],env=dict(PATH='/usr/bin:/bin',LC_ALL='C',TZ='UTC',SOURCE_DATE_EPOCH='0'),capture_output=True,text=True)
            (O/(stem+'.stderr')).write_text(p.stderr)
            assert p.returncode==0,p.stderr
            events=json.loads((O/(stem+'.json')).read_text())['traceEvents']
            fields=(O/(stem+'.time')).read_text().split()
            rows.append(dict(name=name,optimization=opt,policy_level=policy,command=command,wall_s=float(fields[0]),peak_rss_kib=int(fields[3]),stages={e['name']:e['dur']/1e6 for e in events if e['name'] in ('Total ExecuteCompiler','Total Frontend','Total Backend','Total InstantiateFunction','Total InstantiateClass')}))
    (O/'summary.json').write_text(json.dumps(rows,indent=2)+'\n')

if __name__=='__main__':b.run_tasks([b.Task('isolated-policy-traces',main)])

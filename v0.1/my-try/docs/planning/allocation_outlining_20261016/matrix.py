"""Compare execution spelling and latency outlining with identical native optimization."""
import json, subprocess, time
from pathlib import Path
import benchmark_support as b
B=b.BASE

def restore():
    subprocess.run(['git','-C',str(b.APP),'restore','--source=HEAD','--worktree','--','src'],check=True)

def check(name):
    p=subprocess.run([str(b.BUILD/'app')],capture_output=True,text=True,check=True)
    assert 'ACCOUNTING_512K_PASS' in p.stdout
    (b.RESULTS/name/'application.txt').write_text(p.stdout)
    return p.stdout

def unit_name(c):
    if '-c' not in c['args']:return c['stage']
    path=Path(c['args'][c['args'].index('-c')+1])
    if path.stem.startswith('n'):
        return bytes.fromhex(path.stem[1:]).decode()
    return path.name

def measure(name,reuse):
    row=b.measure(name,reuse)
    row['runtime_output']=check(name)
    calls=json.loads((b.RESULTS/name/'clang-calls.json').read_text())
    row['rebuilt_units']=[unit_name(c) for c in calls if c['stage'] in ('object','runtime')]
    row['unit_wall_s']={unit_name(c):c['wall_s'] for c in calls if c['stage']=='object'}
    row['optimization']=b.OPTIMIZATION;row['policy_level']=b.POLICY_LEVEL
    if name.endswith('unchanged'):assert row['actions']==dict(pch=0,runtime=0,object=0,link=0)
    if name in ('body-edit','revert'):
        assert row['actions']==dict(pch=0,runtime=0,object=1,link=1),row['actions']
        assert row['rebuilt_units']==['src/workflow_checks.phs']
    return row

def main():
    root=B/'results';root.mkdir()
    rows=[];baseline_output=None
    try:
        configurations=[(opt,variant,trial) for trial in range(1,4) for opt,variant in ([(0,'previous'),(0,'outlined'),(1,'previous'),(1,'outlined')] if trial%2 else [(1,'outlined'),(1,'previous'),(0,'outlined'),(0,'previous')])]+[(2,'outlined',1)]
        for opt,variant,trial in configurations:
            restore()
            b.OPTIMIZATION=opt;b.POLICY_LEVEL=opt;b.VARIANT=variant
            b.EXE=(B if variant=='outlined' else B.parent/'bench')/'phpp/.prism/build/main'
            tag=f'O{opt}-{variant}-trial{trial}'
            b.PUBLISHED=B/('published-'+tag);b.BUILD=B/('build-'+tag)
            b.RESULTS=root/tag;b.RESULTS.mkdir()
            for name,reuse in [('full',False),('baseline-unchanged',True),('body-edit',True),('revert',True),('revert-unchanged',True)]:
                if name=='body-edit':
                    p=b.APP/'src/workflow_checks.phs';s=p.read_text();assert s.count('$customer->credit_limit = 20000;')==1
                    p.write_text(s.replace('$customer->credit_limit = 20000;','$customer->credit_limit = 20000 + 0;'));time.sleep(1.05)
                if name=='revert':restore();time.sleep(1.05)
                r=measure(name,reuse);r['trial']=trial;r['configuration']=tag;r['variant']=variant
                if baseline_output is None:baseline_output=r['runtime_output']
                assert r['runtime_output']==baseline_output
                rows.append(r)
                (root/'summary.json').write_text(json.dumps(rows,indent=2)+'\n')
            # Store policy shape for analysis; no modified baseline output.
            manifest=json.loads((b.PUBLISHED/'.scpp-output.manifest').read_text())
            (b.RESULTS/'manifest.json').write_text(json.dumps(manifest,indent=2)+'\n')
    finally:restore()

if __name__=='__main__': b.run_tasks([b.Task('native-policy-matrix',main)])

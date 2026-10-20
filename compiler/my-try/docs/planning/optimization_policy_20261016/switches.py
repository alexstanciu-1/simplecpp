"""Native policy and optimization switches in one existing Ninja directory."""
import json, subprocess, sys
import benchmark_support as b
B=b.BASE

def deps():
    text=subprocess.check_output(['ninja','-C',str(b.BUILD),'-t','deps'],text=True)
    result={};current=None
    for line in text.splitlines():
        if ': #deps ' in line:current=line.split(': #deps ',1)[0];result[current]=set()
        elif line.startswith('    ') and current:result[current].add(line[4:])
    return result

def snapshot():
    return {str(b.PUBLISHED/r['path']):r['sha256'] for r in json.loads((b.PUBLISHED/'.scpp-output.manifest').read_text())['files']}

def main():
    b.PUBLISHED=B/'published-O0-policy2-trial1';b.BUILD=B/'build-O0-policy2-trial1'
    b.RESULTS=B/'switch-results';b.RESULTS.mkdir(exist_ok='--resume-after-rejection' in sys.argv);rows=[]
    for opt,policy,name in [(0,0,'profile-only'),(0,0,'profile-noop'),(1,1,'O1'),(2,2,'O2'),(2,2,'O2-noop')]:
        if '--resume-after-rejection' in sys.argv:
            row=json.loads((b.RESULTS/name/'result.json').read_text())
            calls=json.loads((b.RESULTS/name/'clang-calls.json').read_text())
            row['actual_outputs']=sorted(c['args'][c['args'].index('-o')+1] for c in calls if c['stage']!='link')
            rows.append(row);continue
        before=snapshot();inputs=deps();b.OPTIMIZATION=opt;b.POLICY_LEVEL=policy
        row=b.measure(name,True);after=snapshot()
        calls=json.loads((b.RESULTS/name/'clang-calls.json').read_text())
        actual={c['args'][c['args'].index('-o')+1] for c in calls if c['stage']!='link'}
        if name=='profile-only':
            changed={p for p in before.keys()|after.keys() if before.get(p)!=after.get(p)}
            expected={target for target,paths in inputs.items() if paths&changed}
            assert actual==expected,(actual-expected,expected-actual)
            assert row['actions']['pch']==0 and row['actions']['runtime']==0
        elif name.endswith('noop'):assert row['actions']==dict(pch=0,runtime=0,object=0,link=0)
        else:assert row['actions']==dict(pch=1,runtime=3,object=93,link=1),row['actions']
        p=subprocess.run([str(b.BUILD/'app')],capture_output=True,text=True,check=True)
        assert 'ACCOUNTING_512K_PASS' in p.stdout
        row['actual_outputs']=sorted(actual);rows.append(row)
    # Attempt a release build from latency output, with a real native consumer.
    b.OPTIMIZATION=2;b.POLICY_LEVEL=0
    failure_name='reject-release-latency-verified'
    try:b.measure(failure_name,True)
    except RuntimeError as error:
        assert 'native driver exit 1' in str(error),str(error)
    else:raise AssertionError('Expected native rejection')
    failure=json.loads((b.RESULTS/failure_name/'result.json').read_text())
    assert '05-cpp-ready' in failure['marks'] and '06-build-done' not in failure['marks']
    assert json.loads((b.PUBLISHED/'.scpp-output.manifest').read_text())['cpp_policy']=='cpp-lowering-v1:latency'
    assert not [p for p in (b.BUILD/'.measure').glob('*.json') if json.loads(p.read_text())['start_ns']>=failure['start_ns']]
    # Restore valid publication and executable readiness after the deliberate failure.
    b.POLICY_LEVEL=2
    rows.append(b.measure('recover-O2',True))
    (b.RESULTS/'summary.json').write_text(json.dumps(rows,indent=2)+'\n')

if __name__=='__main__':b.run_tasks([b.Task('native-policy-switches',main)])

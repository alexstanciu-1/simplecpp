"""Capture actual strict S2S output; never compile or run the emitted programs."""
from pathlib import Path
import sys,json,hashlib
BUNDLE=Path(__file__).resolve().parent
ROOT=BUNDLE.parents[3]
sys.path.insert(0,str(ROOT/'compiler/my-try/tools'))
from test_runner import CommandRunner, Task, run_tasks
cases=json.loads((BUNDLE/'samples.json').read_text())['cases']
# Record tool/source state; another agent may edit the shared checkout.
def fingerprint():
    paths=list((ROOT/'compiler/my-try').rglob('*.php'))+list((ROOT/'generators/php/src').rglob('*.php'))
    paths=[p for p in paths if '/build/' not in str(p) and '/docs/status_samples_' not in str(p)]
    return {str(p.relative_to(ROOT)):hashlib.sha256(p.read_bytes()).hexdigest() for p in paths}
before=fingerprint()
with CommandRunner(BUNDLE/'logs', ROOT, jobs=12) as commands:
    def emit(case):
        folder=BUNDLE/case['id']
        results={}
        for version in ('v1','v2'):
            name=case['id']+'-'+version
            if version=='v1': cmd=['php',BUNDLE/'legacy_emit.php',ROOT,folder/'main.phs']
            else: cmd=['php',ROOT/'compiler/my-try/main.php','--s2s',folder,'--entry','main.phs']
            try:
                commands.run(name,cmd,timeout=45,cli_reason='Capture S2S CLI exit status and diagnostics; no native compilation.')
            except RuntimeError:
                pass # Rejection is evidence, preserved below, not a runner failure.
            record=next(r for r in commands.commands if r['name']==name)
            out=(BUNDLE/'logs'/f'{name}.stdout').read_bytes()
            err=(BUNDLE/'logs'/f'{name}.stderr').read_bytes()
            (folder/f'{version}.cpp.txt').write_bytes(out)
            (folder/f'{version}.diagnostic.txt').write_bytes(err)
            results[version]={'exit_code':record['code'],'error':record.get('error'), 'stdout_bytes':len(out),'stderr_bytes':len(err),'source_sha256':hashlib.sha256((folder/'main.phs').read_bytes()).hexdigest()}
        (folder/'result.json').write_text(json.dumps(results,indent=2)+'\n')
        return results
    outcomes=run_tasks([Task(c['id'],lambda c=c:emit(c)) for c in cases if c['kind']=='probe'],jobs=12)
after=fingerprint()
(BUNDLE/'provenance.json').write_text(json.dumps({'doc_status':'supporting','generation_only':True,'before_sha256':before,'changed_during_collection':[p for p,h in before.items() if after.get(p)!=h],'new_during_collection':sorted(set(after)-set(before))},indent=2)+'\n')
print('Captured',len(outcomes)*2,'generator results')

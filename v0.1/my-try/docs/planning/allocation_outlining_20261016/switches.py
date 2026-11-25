import json,subprocess
import benchmark_support as b
B=b.BASE

def main():
 tag='O0-previous-trial3';b.OPTIMIZATION=0;b.POLICY_LEVEL=0;b.VARIANT='outlined'
 b.PUBLISHED=B/('published-'+tag);b.BUILD=B/('build-'+tag);b.RESULTS=B/'switch-results';b.RESULTS.mkdir()
 before=json.loads((b.PUBLISHED/'.scpp-output.manifest').read_text());assert before['cpp_policy']=='cpp-lowering-v1:latency'
 rows=[]
 for name in ['upgrade','unchanged']:
  r=b.measure(name,True);p=subprocess.run([str(b.BUILD/'app')],capture_output=True,text=True,check=True);assert 'ACCOUNTING_512K_PASS' in p.stdout
  (b.RESULTS/name/'application.txt').write_text(p.stdout)
  assert r['actions']['pch']==0 and r['actions']['runtime']==0
  if name=='unchanged':assert r['actions']==dict(pch=0,runtime=0,object=0,link=0)
  else:assert r['actions']['object']>0 and r['actions']['link']==1
  rows.append(r)
 after=json.loads((b.PUBLISHED/'.scpp-output.manifest').read_text());assert after['cpp_policy']=='cpp-lowering-v2:latency'
 a={r['path']:r['sha256'] for r in before['files']};c={r['path']:r['sha256'] for r in after['files']}
 assert a.keys()==c.keys()
 (b.RESULTS/'summary.json').write_text(json.dumps(dict(builds=rows,changed_artifacts=[k for k in a if a[k]!=c[k]]),indent=2))
if __name__=='__main__':b.run_tasks([b.Task('native-policy-upgrade',main)])

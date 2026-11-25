import json
import benchmark_support as b
B=b.BASE

def main():
 b.RESULTS=B/'release-guard';b.RESULTS.mkdir();b.PUBLISHED=B/'release-guard-published';b.BUILD=B/'release-guard-build';b.OPTIMIZATION=2;b.POLICY_LEVEL=0
 try:b.measure('reject-v2-at-O2',False)
 except RuntimeError:pass
 else:raise AssertionError('Latency publication accepted at O2')
 row=json.loads((b.RESULTS/'reject-v2-at-O2/result.json').read_text());assert row['exit']==1
 assert '05-cpp-ready' in row['marks'] and '06-build-done' not in row['marks']
 assert not list((b.BUILD/'.measure').glob('*.json'))
 manifest=json.loads((b.PUBLISHED/'.scpp-output.manifest').read_text());assert manifest['cpp_policy']=='cpp-lowering-v2:latency'
 (b.RESULTS/'summary.json').write_text(json.dumps(dict(passed=True,native_actions=0,policy=manifest['cpp_policy'],optimization=2),indent=2))
if __name__=='__main__':b.run_tasks([b.Task('release-policy-guard',main)])

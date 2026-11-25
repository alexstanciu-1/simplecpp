from pathlib import Path
import sys,json
base=Path('/home/alex-ai/compiler-self-o2-20261025');root=base/'repo';out=base/'concurrency-07';folder=out/'activity-32';folder.mkdir()
sys.path.insert(0,str(root/'compiler/my-try/tools'));from test_runner import CommandRunner,Task,run_tasks
for key,value in {'request':base/'input','output':folder/'published','runtime':root/'runtime/metadata/modules','jobs':32,'stages':folder/'stages'}.items():(folder/(key+'.txt')).write_text(str(value))
with CommandRunner(folder/'logs',root) as runner:
 run_tasks([Task('activity',lambda:runner.run('activity',['python3',base/'concurrency_monitor_fast07.py',out/'compiler-release',folder/'threads.json'],cwd=folder,timeout=600))],jobs=1)
samples=json.loads((folder/'threads.json').read_text())['samples'];report={'purpose':'Thread activity only: 5ms sampling; overlapping diagnostic work; not a timing benchmark','stages':{}}
for stage in ['sync-start','prepare-start','emit-start']:
 rows=[x for x in samples if x['stage']==stage]
 report['stages'][stage]={'max_threads':max(x['threads'] for x in rows),'max_active_threads':max(x['active'] for x in rows)}
(folder/'summary.json').write_text(json.dumps(report,indent=2)+'\n');print(json.dumps(report))

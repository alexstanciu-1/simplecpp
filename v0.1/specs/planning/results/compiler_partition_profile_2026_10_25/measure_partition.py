from pathlib import Path
import sys,json
base=Path('/home/alex-ai/compiler-self-o2-20261025');root=base/'repo';out=base/sys.argv[1];jobs=int(sys.argv[2]);label=sys.argv[3] if len(sys.argv)>3 else str(jobs);folder=out/('measure-'+label);folder.mkdir()
sys.path.insert(0,str(root/'compiler/my-try/tools'));from test_runner import CommandRunner,Task,run_tasks
for key,value in {'request':base/'input','output':folder/'published','runtime':root/'runtime/metadata/modules','jobs':jobs,'stages':folder/'stages'}.items():(folder/(key+'.txt')).write_text(str(value))
with CommandRunner(folder/'logs',root) as runner:
 run_tasks([Task('compile',lambda:runner.run('compile',['/usr/bin/time','-v','-o',folder/'compile.time',out/'compiler-release'],cwd=folder,timeout=600))],jobs=1)
marks=sorted([(p.name,p.stat().st_mtime) for p in (folder/'stages').iterdir()],key=lambda row:row[1]);result={'jobs':jobs,'intervals':[{ 'from':a[0],'to':b[0],'seconds':b[1]-a[1]} for a,b in zip(marks,marks[1:])],'time':(folder/'compile.time').read_text()};(folder/'measurement.json').write_text(json.dumps(result,indent=2)+'\n');print(json.dumps(result,indent=2))

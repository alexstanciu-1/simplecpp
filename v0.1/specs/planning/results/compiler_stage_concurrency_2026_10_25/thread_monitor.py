from pathlib import Path
import json,sys,subprocess,time,os
start=time.monotonic();p=subprocess.Popen([sys.argv[1]]);previous={};records=[];ticks=os.sysconf('SC_CLK_TCK');last=start
while p.poll() is None:
 now=time.monotonic();current={}
 try:
  for task in (Path('/proc')/str(p.pid)/'task').iterdir():
   try:
    a=(task/'stat').read_text().split(') ',1)[1].split();current[int(task.name)]=int(a[11])+int(a[12])
   except (FileNotFoundError,ProcessLookupError):pass
 except FileNotFoundError:pass
 changes=[max(0,cpu-previous.get(tid,cpu)) for tid,cpu in current.items()]
 stage='startup'
 for name in ['init-start','sync-start','prepare-start','emit-start','done']:
  if (Path('stages')/name).exists():stage=name
 records.append({'stage':stage,'elapsed':now-start,'threads':len(current),'active':sum(x>0 for x in changes),'cpu_seconds':sum(changes)/ticks,'interval':now-last})
 previous=current;last=now;time.sleep(.1)
Path(sys.argv[2]).write_text(json.dumps({'seconds':time.monotonic()-start,'exit':p.returncode,'samples':records},indent=2)+'\n');sys.exit(p.returncode)

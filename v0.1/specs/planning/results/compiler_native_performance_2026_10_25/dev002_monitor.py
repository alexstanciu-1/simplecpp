from pathlib import Path
import json,sys,subprocess,time,os
started=time.monotonic();p=subprocess.Popen([sys.argv[1]])
previous={};seen={};peak_threads=0;peak_active=0;peak_cores=0;rows=[];prior_time=started;ticks=os.sysconf('SC_CLK_TCK')
while p.poll() is None:
 now=time.monotonic();current={};root=Path('/proc')/str(p.pid)/'task'
 try:
  for task in root.iterdir():
   try:
    data=(task/'stat').read_text().split(') ',1)[1].split();current[int(task.name)]=int(data[11])+int(data[12])
   except (FileNotFoundError,ProcessLookupError):pass
 except FileNotFoundError:pass
 peak_threads=max(peak_threads,len(current));active=0;delta=0
 for tid,cpu in current.items():
  change=max(0,cpu-previous.get(tid,0));delta+=change;active+=int(change>0);seen[tid]=max(seen.get(tid,0),cpu)
 cores=delta/ticks/max(now-prior_time,0.001);peak_cores=max(peak_cores,cores);peak_active=max(peak_active,active)
 if now-started<5 or active>1:rows.append({'seconds':round(now-started,4),'threads':len(current),'active_threads':active,'cpu_seconds':delta/ticks,'interval':now-prior_time})
 previous=current;prior_time=now;time.sleep(0.01 if now-started<5 else 0.2)
Path(sys.argv[2]).write_text(json.dumps({'elapsed':time.monotonic()-started,'exit':p.returncode,'peak_threads':peak_threads,'peak_active_threads':peak_active,'peak_interval_cpu_cores':peak_cores,'observed_thread_cpu_seconds':{str(k):v/ticks for k,v in seen.items()},'samples':rows},indent=2)+'\n')
sys.exit(p.returncode)

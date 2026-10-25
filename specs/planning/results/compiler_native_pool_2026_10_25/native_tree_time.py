from pathlib import Path
import subprocess,time,sys,os,json
out=Path(sys.argv[1]);start=time.monotonic();p=subprocess.Popen(sys.argv[2:]);peak=0;max_children=0;samples=0
while p.poll() is None:
 stats={}
 for entry in Path('/proc').iterdir():
  if not entry.name.isdigit():continue
  try:
   fields=(entry/'stat').read_text().split(') ',1)[1].split();stats[int(entry.name)]=(int(fields[1]),int(fields[21]))
  except (OSError,IndexError,ValueError):continue
 owned={p.pid};before=0
 while len(owned)!=before:
  before=len(owned);owned.update(pid for pid,(parent,rss) in stats.items() if parent in owned)
 peak=max(peak,sum(stats.get(pid,(0,0))[1] for pid in owned)*os.sysconf('SC_PAGE_SIZE'));max_children=max(max_children,len(owned)-1);samples+=1;time.sleep(.1)
out.write_text(json.dumps({'seconds':time.monotonic()-start,'exit':p.returncode,'peak_tree_rss_bytes':peak,'maximum_descendants':max_children,'samples':samples},indent=2)+'\n');sys.exit(p.returncode)

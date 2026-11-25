from pathlib import Path
from datetime import datetime,timezone
import json,sys
p=Path(__file__).with_name('timing.json')
now=datetime.now(timezone.utc).isoformat()
if p.exists():d=json.loads(p.read_text())
else:d={'measurement':'Elapsed wall time by primary activity, including tool waits; not CPU time or a general productivity rate.','started':'2026-09-22T07:35:47+00:00','phases':[{'activity':'inspection/design','started':'2026-09-22T07:35:47+00:00'}]}
last=d['phases'][-1]
if 'finished' not in last:
 last['finished']=now;last['seconds']=round((datetime.fromisoformat(now)-datetime.fromisoformat(last['started'])).total_seconds(),2)
if sys.argv[1]!='finish':d['phases'].append({'activity':sys.argv[1],'started':now})
else:d['finished']=now;d['elapsed_seconds']=round((datetime.fromisoformat(now)-datetime.fromisoformat(d['started'])).total_seconds(),2)
p.write_text(json.dumps(d,indent=2)+'\n')

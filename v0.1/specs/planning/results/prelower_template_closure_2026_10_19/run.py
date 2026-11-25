from pathlib import Path
import sys,json,re,collections
ROOT=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');OUT=Path('/tmp/accounting-template-audit');PUB=Path('/tmp/accounting-header-fix/prepared')
sys.path.insert(0,str(ROOT/'compiler/my-try/tools'))
from test_runner import CommandRunner,Task,run_tasks
rows=json.loads(Path('/tmp/accounting-parallel/logs/commands.json').read_text());commands=[r for r in rows if r['name'].startswith('j1-r0-unit')]
assert len(commands)==16
with CommandRunner(OUT/'logs',ROOT,jobs=12) as runner:
 def inspect(row):
  cmd=row['command'];args=cmd[cmd.index('/usr/bin/clang++')+1:].copy();src=args[args.index('-c')+1];name=Path(src).stem
  args.remove('-c');i=args.index('-include-pch');del args[i:i+2];args[args.index('-o')+1]=str(OUT/(name+'.ii'));args.append('-E')
  runner.run(name,['/usr/bin/clang++',*args],timeout=120)
  origins=collections.Counter();current='';line_number=0;generated=[]
  for line in (OUT/(name+'.ii')).read_text().splitlines():
   m=re.match(r'# (\d+) "([^"]+)"',line)
   if m:line_number=int(m[1])-1;current=m[2]
   elif re.search(r'\btemplate\s*<',line):
    origins[current]+=1
    if current.startswith(str(PUB)):generated.append({'file':current,'line':line_number,'text':line[:180]})
   line_number+=1
  return {'source':src,'template_lines':sum(origins.values()),'origins':dict(origins),'generated_templates':generated}
 result=run_tasks([Task(r['name'],lambda r=r:inspect(r)) for r in commands])
 summary=[r.value for r in result];(OUT/'summary.json').write_text(json.dumps(summary,indent=2)+'\n')
 for row in summary:
  p=Path(row['source']);name=bytes.fromhex(p.stem[1:]).decode() if p.stem.startswith('n') else p.name
  print(name,row['template_lines'],'generated',len(row['generated_templates']))
 print('Runtime origins',sorted({p for r in summary for p in r['origins'] if '/runtime/include/' in p}))

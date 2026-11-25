#!/usr/bin/env python3
"""Exact-context source-hunk replay eligibility for the fixed 15-commit sample."""
import json,re
from pathlib import Path
SAMPLE=Path(__file__).parent/'results/2026-09-19/historical-patterns/recent-source-sample'
def reverse_sources(app,patch):
 changes={}
 for section in patch.split('diff --git ')[1:]:
  path=section.splitlines()[0].split(' b/',1)[1]
  if not path.startswith('compiler/src/') or not path.endswith('.phs'):raise ValueError('Unsupported file surface: '+path)
  key=path[len('compiler/src/'):]
  if 'new file mode ' in section or 'deleted file mode ' in section or 'rename from ' in section:raise ValueError('File ownership change requires separate replay planning: '+key)
  source=app/key
  if not source.is_file():raise ValueError('Retired current source: '+key)
  text=source.read_text()
  for hunk in re.split(r'^@@[^\n]*\n',section,flags=re.M)[1:]:
   before=[];after=[]
   for line in hunk.splitlines(True):
    if line.startswith(' '):before.append(line[1:]);after.append(line[1:])
    elif line.startswith('+'):after.append(line[1:])
    elif line.startswith('-'):before.append(line[1:])
    elif line.startswith('\\'):raise ValueError('Unsupported no-newline hunk')
    else:break
   a=''.join(after);b=''.join(before)
   if text.count(a)!=1:raise ValueError('Current hunk context differs or is ambiguous: '+key)
   text=text.replace(a,b,1)
  changes[key]=text
 return changes
if __name__=='__main__':
 import argparse
 p=argparse.ArgumentParser();p.add_argument('--app',type=Path,required=True);p.add_argument('--out',type=Path,required=True);a=p.parse_args();m=json.loads((SAMPLE/'manifest.json').read_text());rows=[]
 for c in m['commits']:
  rev=c['commit'][:8];r={'commit':rev,'subject':c['subject'],'status':'not_measured'}
  try:r['sources']=list(reverse_sources(a.app,(SAMPLE/(rev+'.patch')).read_text()));r['textual_replay_eligible']=True
  except ValueError as e:r['textual_replay_eligible']=False;r['limitation']=str(e)
  rows.append(r)
 a.out.parent.mkdir(parents=True,exist_ok=True);a.out.write_text(json.dumps({'selection':m['selection'],'scope':'textual applicability only; does not prove semantic compatibility or build correctness','commits':rows},indent=2)+'\n')
 print(json.dumps(rows,indent=2))

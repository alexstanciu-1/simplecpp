#!/usr/bin/env python3
"""Classify captured generated-input deltas against a previously timed replay.

Text categories are evidence, not semantic equivalence or proof that a rebuild
can be omitted. Require identical graphs and type/dependency manifests first.
"""
import argparse,difflib,json,re
from pathlib import Path
from stable_locals import TOKENS
p=argparse.ArgumentParser(description=__doc__);p.add_argument('--captured',type=Path,required=True);p.add_argument('--measured',type=Path,required=True);p.add_argument('--out',type=Path,required=True);a=p.parse_args();a.out.mkdir(parents=True,exist_ok=False)
for state in ['before','after']:
 for filename in ['build.ninja','dependency-manifest.json']:
  assert (a.captured/state/filename).read_bytes()==(a.measured/state/filename).read_bytes(), (state,filename)
row=json.loads((a.measured/'measurements.jsonl').read_text().splitlines()[0]);graph=(a.measured/'after/build.ninja').read_text();objects={m[1]:m[2].split('/generated/',1)[1] for m in re.finditer(r'^build (\S+): compile\w* (\S+)',graph,re.M) if '/generated/' in m[2] and m[2].endswith('.cpp')}
include=re.compile(r'^#include [^\n]+\n',re.M)
signature=re.compile(r'^(?!namespace\b)[A-Za-z_][^\n;{}]*\([^\n;{}]*\) \{$',re.M)
def tokens(text):
 # Keep punctuation and literals; remove only comments and insignificant spacing.
 parts=[];offset=0
 for match in TOKENS.finditer(text):
  parts.extend(text[offset:match.start()].split())
  if not match[0].startswith(('//','/*')):parts.append(match[0])
  offset=match.end()
 parts.extend(text[offset:].split())
 return tuple(parts)
def classify(before,after,rel):
 if before is None:return 'new_cpp'
 if before==after:return 'unchanged_cpp_dependency'
 b=include.sub('',before);c=include.sub('',after)
 if b==c:return 'includes_only'
 location = r'namespace scpp \{ extern const int __latency_lines_\w+\[\] = \{[0-9, -]*\}; \}\s*'
 if re.fullmatch(location,b) and re.fullmatch(location,c):return 'location_metadata'
 if tokens(b)==tokens(c):return 'whitespace_or_comments'
 if signature.sub('FUNCTION_SIGNATURE {',b)==signature.sub('FUNCTION_SIGNATURE {',c):return 'function_signatures_only'
 return 'body_or_other_cpp'
results=[]
for step in row['native_steps']:
 obj=step['output']
 if obj not in objects:continue
 rel=objects[obj];bp=a.captured/'before/generated'/rel;ap=a.captured/'after/generated'/rel;before=bp.read_text() if bp.exists() else None;after=ap.read_text();kind=classify(before,after,rel)
 diff=''.join(difflib.unified_diff((before or '').splitlines(True),after.splitlines(True),fromfile='before/'+rel,tofile='after/'+rel));target=a.out/'diffs'/Path(rel+'.patch');target.parent.mkdir(parents=True,exist_ok=True);target.write_text(diff)
 results.append({'object':obj,'source':rel,'category':kind,'job_wall_seconds':step['elapsed_ms']/1000,'changed_signatures_before':signature.findall(include.sub('',before or '')) if kind=='function_signatures_only' else [],'changed_signatures_after':signature.findall(include.sub('',after)) if kind=='function_signatures_only' else []})
# Explain unchanged units using their actual include graph and changed headers.
roots={state:a.captured/state/'generated' for state in ['before','after']}
changed_headers={str(p.relative_to(roots['after'])) for p in roots['after'].rglob('*.hpp') if not (roots['before']/p.relative_to(roots['after'])).exists() or p.read_bytes()!=(roots['before']/p.relative_to(roots['after'])).read_bytes()}
inc=re.compile(r'^#include "([^"]+)"',re.M)
def reachable(rel,state):
 seen=set()
 def walk(path):
  if path in seen:return
  seen.add(path);f=roots[state]/path
  assert f.exists(),str(f)
  for dep in inc.findall(f.read_text()):walk(dep)
 walk(rel);return seen
for r in results:
 if r['category']=='unchanged_cpp_dependency':
  r['changed_reachable_headers']=sorted(reachable(r['source'],'after')&changed_headers)
for rel in changed_headers:
 bp=roots['before']/rel;ap=roots['after']/rel;target=a.out/'header-diffs'/Path(rel+'.patch');target.parent.mkdir(parents=True,exist_ok=True);target.write_text(''.join(difflib.unified_diff(bp.read_text().splitlines(True) if bp.exists() else [],ap.read_text().splitlines(True),fromfile='before/'+rel,tofile='after/'+rel)))
summary={k:{'objects':sum(r['category']==k for r in results),'compiler_job_wall_seconds':round(sum(r['job_wall_seconds'] for r in results if r['category']==k),3)} for k in sorted({r['category'] for r in results})}
report={'scope':'exact captured graph/manifest match to timed replay; textual categories, not proof of semantic equivalence or avoidable work','categories':summary,'changed_headers':sorted(changed_headers),'objects':results};(a.out/'analysis.json').write_text(json.dumps(report,indent=2)+'\n');print(json.dumps(summary,indent=2))

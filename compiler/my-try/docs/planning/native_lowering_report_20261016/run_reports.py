from pathlib import Path
import json,subprocess,hashlib,tarfile
B=Path(__file__).resolve().parent;previous=B.parent/'initialization'
rows=[]
for level in [0,1,2]:
 for enabled in [False,True]:
  name=f'O{level}-'+('report' if enabled else 'plain');run=B/name;run.mkdir();marks=run/'marks';marks.mkdir()
  req=dict(optimization=level,policy_level=level,marks=str(marks),published=str(run/'published'),source=str(previous/'accounting'),catalogs=str(previous/'runtime/metadata/modules'),report=enabled)
  (run/'request.json').write_text(json.dumps(req,indent=2))
  with (run/'stdout').open('w') as out,(run/'stderr').open('w') as err:
   subprocess.run(['/usr/bin/time','-v','-o',str(run/'resource.txt'),str(B/'phpp/.prism/build/main')],cwd=run,stdout=out,stderr=err,check=True)
  assert (run/'stdout').read_text()=='PASS\n'
  artifact_bytes=(marks/'artifacts.json').read_bytes()
  if enabled:
   assert artifact_bytes==(B/f'O{level}-plain/marks/artifacts.json').read_bytes()
   for filename in ['lowering-report.json','lowering-explain.json']:
    native=json.loads((marks/filename).read_text());php=json.loads((B/'php-references'/f'O{level}'/filename).read_text())
    assert native==php,(level,filename)
  artifacts=json.loads(artifact_bytes)['artifacts']
  assert len(artifacts)==292
  prior=previous/f'published-O{level}-outlined-trial1'
  for a in artifacts:assert (prior/a['path']).read_text()==a['text'],a['path']
  rows.append(dict(level=level,report=enabled,artifacts=len(artifacts),sha256=hashlib.sha256(artifact_bytes).hexdigest(),prior_artifacts_identical=True,php_report_parity=enabled))
  print(name,'PASS',flush=True)
(B/'validation.json').write_text(json.dumps(rows,indent=2)+'\n')
with tarfile.open(B/'evidence.tar.gz','w:gz') as t:
 for name in ['native-build.log','native-build.log.attempt1','native-build.log.attempt2','native-build-resource.txt','native-build-resource.txt.attempt1','native-build-resource.txt.attempt2','native-identity.json','validation.json','source','generated']:
  t.add(B/name,arcname=name)
 for row in rows:
  name=f'O{row["level"]}-'+('report' if row['report'] else 'plain')
  for p in (B/name).rglob('*'):
   if p.is_file() and 'published' not in p.parts:t.add(p,arcname=str(p.relative_to(B)))
print('Native reports validated and archived',flush=True)

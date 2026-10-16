from pathlib import Path
import tarfile,shutil,subprocess,hashlib,json
B=Path(__file__).resolve().parent
with tarfile.open(B/'native-overlay.tar.gz') as t:t.extractall(B,filter='data')
for p in (B/'generated').rglob('*'):
 if p.is_file():
  dest=B/'phpp/.prism/generated'/p.relative_to(B/'generated');dest.parent.mkdir(parents=True,exist_ok=True)
  if not dest.exists() or p.read_bytes()!=dest.read_bytes():shutil.copy2(p,dest)
f=B/'phpp/.prism/build/build.ninja';s=f.read_text()
header=' -include ../generated/05_backend/cpp/lowering_report.hpp'
# New source participates in the same legacy build recipe and full link.
edge='build 05_backend/cpp/lowering_report.o: compile ../generated/05_backend/cpp/lowering_report.cpp | runtime_signature.txt app_pch.hpp.gch\n  more_cxxflags = -include ../generated/__project_units/547083fc992d4986.hpp'+header+'\n'
if 'build 05_backend/cpp/lowering_report.o:' not in s:
 s=s.replace('build main: link ',edge+'\nbuild main: link 05_backend/cpp/lowering_report.o ',1)
lines=s.splitlines()
for i,line in enumerate(lines):
 if line.startswith('build main.o:') and header not in lines[i+1]:lines[i+1]+=header
f.write_text('\n'.join(lines)+'\n')
for name in ['native-build.log','native-build-resource.txt']:
 p=B/name
 if p.exists():
  attempt=1
  while (B/(name+'.attempt'+str(attempt))).exists():attempt+=1
  p.rename(B/(name+'.attempt'+str(attempt)))
with (B/'native-build.log').open('w') as out:
 subprocess.run(['/usr/bin/time','-v','-o',str(B/'native-build-resource.txt'),'ninja','-j8'],cwd=f.parent,stdout=out,stderr=subprocess.STDOUT,check=True)
(B/'native-identity.json').write_text(json.dumps(dict(executable=hashlib.sha256((f.parent/'main').read_bytes()).hexdigest(),sources={str(p.relative_to(B/'source')):hashlib.sha256(p.read_bytes()).hexdigest() for p in (B/'source').rglob('*.php')}),indent=2))
print('O2 native report compiler rebuilt',flush=True)

import pathlib,tarfile,shutil,subprocess,hashlib,json
B=pathlib.Path(__file__).resolve().parent
with tarfile.open(B/'native-overlay.tar.gz') as t:t.extractall(B,filter='data')
for p in (B/'generated').rglob('*'):
 if p.is_file():
  dest=B/'phpp/.prism/generated'/p.relative_to(B/'generated')
  if not dest.exists() or p.read_bytes()!=dest.read_bytes():shutil.copy2(p,dest)
f=B/'phpp/.prism/build/build.ninja'
f.write_text(f.read_text().replace('/bench/','/allocation/'))
with (B/'native-build.log').open('w') as out:
 subprocess.run(['/usr/bin/time','-v','-o',str(B/'native-build-resource.txt'),'ninja','-j8'],cwd=f.parent,stdout=out,stderr=subprocess.STDOUT,check=True)
(B/'native-identity.json').write_text(json.dumps(dict(executable=hashlib.sha256((f.parent/'main').read_bytes()).hexdigest(),sources={str(p.relative_to(B/'source')):hashlib.sha256(p.read_bytes()).hexdigest() for p in (B/'source').rglob('*.php')}),indent=2))
print('O2 native compiler rebuilt',flush=True)

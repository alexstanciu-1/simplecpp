import sys,json,shutil,hashlib,os
from pathlib import Path
repo=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');sys.path.insert(0,str(repo/'compiler/my-try/tools'))
from test_runner import CommandRunner,Task,run_tasks
root=Path(__file__).parent;out=root/'results';out.mkdir()
with CommandRunner(out/'logs',repo,jobs=12) as runner:
 def scenario(name):
  d=out/name;d.mkdir();published=d/'published';cache=d/'cache';runtime=d/'runtime';mode='pch' if name.endswith('_pch') else 'no-pch'
  if mode=='pch':shutil.copytree(repo/'runtime/include',runtime)
  else:runtime.mkdir()
  records={}
  def publish(label,files):
   p=d/(label+'.json');p.write_text(json.dumps(files));runner.run(name+'-'+label,['php',root/'driver.php','publish',published,p])
  def build(label,expected=0,target=None):
   target=target or cache
   payload=runner.run(name+'-'+label,['php',root/'driver.php','build',published,target,runtime,mode],expected=expected,timeout=300)
   r=json.loads(payload) if expected==0 else {'error':(out/'logs'/(name+'-'+label+'.stderr')).read_text()}
   r['status']=(target/'.scpp-native.status').read_text();records[label]=r
   (d/'results.json').write_text(json.dumps(records,indent=2));return r
  if name.startswith('has_include'):
   body='#if __has_include("optional.hpp")\nstatic_assert(false, "newly selected invalid code");\n#endif\nint main() { return 7; }\n'
   publish('publish',{'entry.cpp':body});base=build('cold');build('warm');(runtime/'optional.hpp').write_text('// now exists\n')
   edited=build('added-file');runner.run(name+'-execute-stale',[edited['executable']],expected=7)
   build('fresh-invalid',1,d/'fresh-cache');(runtime/'optional.hpp').unlink();build('repair')
  else:
   files={'entry.cpp':'int answer(); int main(){return answer();}', 'answer.cpp':'int answer(){return 7;}'}
   publish('publish',files);build('cold');warm=build('warm');runner.run(name+'-execute',[warm['executable']],expected=7)
   publish('publish-invalid',{**files,'answer.cpp':'int answer(){return missing;}'});build('invalid-compile',1);build('retry-compile',1)
   publish('publish-link-invalid',{'entry.cpp':files['entry.cpp']});build('missing-definition',1)
   publish('publish-repair',files);build('repair')
   # Unpublished source corruption must reject before reuse.
   p=published/'answer.cpp';old=p.read_bytes();p.write_bytes(b'int answer(){return 9;}');build('digest-mismatch',1);p.write_bytes(old);build('digest-repair')
   # Runtime transitive header content: preserve metadata while changing semantics.
   h=runtime/'ordinary.hpp';h.write_text('inline int value(){return 7;}');publish('publish-header',{'entry.cpp':'#include "ordinary.hpp"\nint main(){return value();}'});build('header-cold');st=h.stat();h.write_text('inline int value(){return x;}');os.utime(h,ns=(st.st_atime_ns,st.st_mtime_ns));build('same-stat-header',1);h.write_text('inline int value(){return 7;}');build('header-repair')
   # Corrupt object/executable bytes and verify replacement.
   for p in (cache/'artifacts').glob('*.o'):p.write_bytes(b'corrupt')
   build('corrupt-objects');last=build('warm-again');Path(last['executable']).write_bytes(b'corrupt');fixed=build('corrupt-executable');runner.run(name+'-repaired-execute',[fixed['executable']],expected=7)
   (published/'.scpp-output.status').write_text('pending\n');build('pending-publication',1)
  return records
 results=run_tasks([Task(n,lambda n=n:scenario(n)) for n in ['has_include','has_include_pch','failures']],jobs=12,report=False)
 (out/'summary.json').write_text(json.dumps({r.name:r.value for r in results},indent=2))

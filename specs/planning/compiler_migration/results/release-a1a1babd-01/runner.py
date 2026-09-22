from pathlib import Path
import subprocess,json,time,hashlib,platform,shutil
ROOT=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01')
OUT=Path('/tmp/scpp-a1a1babd-portability');OUT.mkdir(exist_ok=False)
TARGET=Path('/tmp/scpp-a1a1babd-probe');REV='a1a1babd07082d9abf7ac885b2328c99368ad4cf'
PIN=ROOT/'compiler/tools/portability_target.json';original=PIN.read_bytes();candidate=json.loads(original);candidate['verified_commit']=REV;temporary=(json.dumps(candidate,indent=2)+'\n').encode()
report={'status':'running','candidate':REV,'host':platform.platform(),'migration_commit':subprocess.check_output(['git','rev-parse','HEAD'],cwd=ROOT,text=True).strip(),'pin_restored':False,'commands':[]}
(OUT/'original-pin.json').write_bytes(original);(OUT/'candidate-pin.json').write_bytes(temporary)
shutil.copyfile(__file__,OUT/'runner.py');shutil.copyfile('/tmp/scpp-a1a1babd-query-proof.py',OUT/'query-runner.py')
def save():(OUT/'summary.json').write_text(json.dumps(report,indent=2)+'\n')
def run(name,command):
 print('START '+name,flush=True);start=time.monotonic()
 with (OUT/(name+'.stdout.log')).open('w') as out,(OUT/(name+'.stderr.log')).open('w') as err:
  r=subprocess.run([str(x) for x in command],cwd=ROOT,stdout=out,stderr=err)
 report['commands'].append({'name':name,'command':[str(x) for x in command],'exit':r.returncode,'seconds':round(time.monotonic()-start,3)})
 save();print(('PASS ' if r.returncode==0 else 'FAIL ')+name,flush=True)
try:
 assert subprocess.check_output(['git','-C',TARGET,'rev-parse','HEAD'],text=True).strip()==REV
 assert not subprocess.check_output(['git','-C',TARGET,'status','--porcelain'],text=True).strip()
 PIN.write_bytes(temporary);save()
 run('fast',['python3','tools/php_portability/validate.py','--results',OUT/'fast'])
 run('structural-queries',['python3','/tmp/scpp-a1a1babd-query-proof.py'])
 for name,script in [('compiler','compiler_context/run.py'),('collections','collections.py'),('os','os_native.py'),('methods','method_signatures.py'),('returns','container_returns.py'),('iteration','map_iteration.py'),('containers','container_annotations.py'),('snapshots','collection_snapshots.py'),('utf8','utf8.py'),('traits','traits.py')]:
  command=['python3',ROOT/'tests/portability'/script,'--results',OUT/name,'--target-checkout',TARGET]
  if name=='collections':command+=['--candidate-revision',REV]
  run(name,command)
 for name,command in [('os-php',['php','tests/portability/os_php.php']),('nullable-rejections',['python3','tests/portability/nullable_fields.py']),('decimal-oracle',['python3','tests/portability/decimal_range.py']),('return-rejections',['python3','tests/portability/container_returns_rejections.py']),('iteration-rejections',['python3','tests/portability/map_iteration_rejections.py'])]:run(name,command)
 report['target_clean']=not subprocess.check_output(['git','-C',TARGET,'status','--porcelain'],text=True).strip()
 report['status']='passed' if all(c['exit']==0 for c in report['commands']) and report['target_clean'] else 'failed'
finally:
 if PIN.read_bytes()==temporary:
  PIN.write_bytes(original);report['pin_restored']=True
 else:report['pin_restore_conflict']=True
 save()
print('FINAL '+report['status']+'; pin restored='+str(report['pin_restored']),flush=True)

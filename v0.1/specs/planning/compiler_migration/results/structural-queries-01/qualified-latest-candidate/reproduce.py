from pathlib import Path
import subprocess,json
root=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01')
source=root/'specs/planning/compiler_migration/results/structural-queries-01/minimal-source'
work=Path('/tmp/scpp-query-minimal-361');work.mkdir()
output=work/'phpp';target=Path('/tmp/scpp-361b1e97-probe');report=[]
def run(label,cmd,cwd=root):
 r=subprocess.run([str(x) for x in cmd],cwd=cwd,capture_output=True,text=True)
 (work/(label+'.stdout.log')).write_text(r.stdout);(work/(label+'.stderr.log')).write_text(r.stderr)
 report.append({'label':label,'command':[str(x) for x in cmd],'cwd':str(cwd),'exit':r.returncode})
 return r
assert run('convert',['php',root/'tools/php_portability/convert.php',source,output]).returncode==0
assert run('init',['php',target/'bin/scpp.php','init','--php-profile=strict'],output).returncode==0
config=json.loads((output/'prism.json').read_text());config['build']['cxx']='clang++-18';config['runtime']['modules']=[];(output/'prism.json').write_text(json.dumps(config,indent=2)+'\n')
assert run('framework',['php',root/'tools/php_portability/install_native_runtime.php',output]).returncode==0
r=run('native',['php',target/'bin/scpp.php','run','--build-runtime'],output)
(work/'summary.json').write_text(json.dumps({'candidate':'361b1e9752817cd5924a9117fba806c5928bb406','commands':report},indent=2)+'\n')
print('Native exit:',r.returncode);print(r.stderr[:1200]);assert r.returncode!=0 and 'Qualified self-reference construction is rejected' in r.stderr

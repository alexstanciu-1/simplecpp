from pathlib import Path
import subprocess,json,shutil,hashlib,time
root=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01')
original=root/'specs/planning/compiler_migration/results/structural-queries-01/minimal-source'
work=Path('/tmp/scpp-a1a1babd-portability/structural-queries');work.mkdir(parents=True)
source=work/'source';shutil.copytree(original,source)
for p in (source/'src').rglob('*.php'):
 assert p.read_bytes()==(root/'compiler'/p.relative_to(source)).read_bytes(),p
output=work/'phpp';target=Path('/tmp/scpp-a1a1babd-probe');report=[]
def run(label,cmd,cwd=root):
 start=time.monotonic();r=subprocess.run([str(x) for x in cmd],cwd=cwd,capture_output=True,text=True)
 (work/(label+'.stdout.log')).write_text(r.stdout);(work/(label+'.stderr.log')).write_text(r.stderr)
 report.append({'label':label,'command':[str(x) for x in cmd],'cwd':str(cwd),'exit':r.returncode,'seconds':round(time.monotonic()-start,3)})
 (work/'commands.json').write_text(json.dumps(report,indent=2)+'\n')
 assert r.returncode==0,(label,r.stdout,r.stderr)
 return r
expected='Struct member cursor is not positioned\nmember:3:3\nmember:12:12\nmethod:6\nfunction:8:9:10:11\nfield:13:14:0\nroot:14:0\nparameter:0\ndeferred\nExpected a parsed struct declaration\nStruct member cursor is not positioned\nExpected a parsed function declaration\nquery-tree-retained:18:6\n'
files=['src/03_parse/data/nodes.php','src/03_parse/data/tree.php','src/03_parse/data/role_views.php','src/03_parse/utilities/metaprogramming_syntax.php','src/03_parse/utilities/syntax_access.php','src/03_parse/utilities/struct_member_cursor.php','main.php']
assert run('php',['php','-r','foreach(array_slice($argv,1) as $f) { require $f; }',root/'tools/php_portability/runtime/bootstrap.php',*[source/f for f in files]]).stdout==expected
run('convert',['php',root/'tools/php_portability/convert.php',source,output])
run('init',['php',target/'bin/scpp.php','init','--php-profile=strict'],output)
config=json.loads((output/'prism.json').read_text());config['build']['cxx']='clang++-18';config['runtime']['modules']=[];(output/'prism.json').write_text(json.dumps(config,indent=2)+'\n')
run('framework',['php',root/'tools/php_portability/install_native_runtime.php',output])
r=run('native',['php',target/'bin/scpp.php','run','--build-runtime'],output)
assert r.stdout.endswith(expected),r.stdout
(work/'summary.json').write_text(json.dumps({'passed':True,'candidate':'a1a1babd07082d9abf7ac885b2328c99368ad4cf','expected':expected,'source_sha256':{f:hashlib.sha256((source/f).read_bytes()).hexdigest() for f in files},'commands':report},indent=2)+'\n')
print('Structural query and cursor PHP/native proof passed.',flush=True)

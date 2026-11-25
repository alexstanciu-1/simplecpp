from pathlib import Path
import subprocess,json,time
root=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');work=Path('/tmp/scpp-a1a1babd-portability/shortcircuit-diagnostic');work.mkdir();src=work/'source';src.mkdir();out=work/'phpp';target=Path('/tmp/scpp-a1a1babd-probe');events=[]
(src/'main.php').write_text('''<?php
declare(strict_types=1);
class Guard_Probe {
    public static function rhs(): bool {
        echo "rhs-called\\n";
        return true;
    }
}
$guard /** int */ = 0;
if (($guard !== 0) && Guard_Probe::rhs()) { echo "entered\\n"; }
echo "done\\n";
''')
def run(label,cmd,cwd=root):
 r=subprocess.run(list(map(str,cmd)),cwd=cwd,capture_output=True,text=True);events.append({'label':label,'command':list(map(str,cmd)),'exit':r.returncode,'stdout':r.stdout,'stderr':r.stderr});(work/'commands.json').write_text(json.dumps(events,indent=2)+'\n');assert r.returncode==0,(label,r.stdout,r.stderr);return r.stdout
run('imports',['php',root/'tools/php_portability/sync_imports.php',src])
php=run('php',['php','-r','require $argv[1];require $argv[2];',root/'tools/php_portability/runtime/bootstrap.php',src/'main.php']);assert php=='done\n'
run('convert',['php',root/'tools/php_portability/convert.php',src,out]);run('init',['php',target/'bin/scpp.php','init','--php-profile=strict'],out)
p=out/'prism.json';s=json.loads(p.read_text());s['build']['cxx']='clang++-18';s['runtime']['modules']=[];p.write_text(json.dumps(s,indent=2)+'\n')
run('framework',['php',root/'tools/php_portability/install_native_runtime.php',out]);native=run('native',['php',target/'bin/scpp.php','run','--build-runtime'],out)
print(native[-500:]);(work/'summary.json').write_text(json.dumps({'candidate':'a1a1babd07082d9abf7ac885b2328c99368ad4cf','php':php,'native_tail':native[-500:],'unexpected_rhs':'rhs-called\n' in native},indent=2)+'\n')

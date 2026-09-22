"""Convert the OS facade and prove PHP/native lifecycle behavior on the selected target."""
import argparse
import json
from pathlib import Path
import subprocess

ROOT=Path(__file__).resolve().parents[2]
TOOLS=ROOT/'tools/php_portability'
BODY=r'''$handle = lock_empty();
echo lock_try($handle, LOCK_PATH, false) ? "locked\n" : "bad\n";
$alias = $handle;
$other = lock_empty();
echo lock_try($other, LOCK_PATH, false) ? "bad\n" : "contended\n";
$moved = lock_transfer($handle);
lock_release($alias);
echo lock_try($other, LOCK_PATH, true) ? "bad\n" : "transfer-held\n";
lock_release($moved); lock_release($moved);
echo lock_try($other, LOCK_PATH, true) ? "shared\n" : "bad\n";
$reader = lock_empty(); echo lock_try($reader, LOCK_PATH, true) ? "shared-pair\n" : "bad\n";
lock_release($other); lock_release($reader);
try { lock_transfer($moved); } catch (\RuntimeException $error) { echo "released-transfer-error\n"; }
try { lock_try($other, "", false); } catch (\RuntimeException $error) { echo "path-error\n"; }
$args /** vector<string> */ = ["-c", "cat; printf err >&2; exit 7"];
$p = process_spawn("/bin/sh", $args, "input", 1000, "");
$alias_process = $p;
for ($i = 0; $i < 1000000; $i++) { if (process_poll($p)) { break; } }
$out = process_output($p);
echo $out->stdout_text, ":", $out->stderr_text, ":", $out->exit_code, "\n";
$out->stdout_text = "edited";
$again = process_output($p); echo $again->stdout_text, "\n";
process_close($alias_process); process_close($p);
try { process_poll($p); } catch (\RuntimeException $error) { echo "closed-error\n"; }
$missing_args /** vector<string> */ = [];
try { process_spawn("/not/a/program", $missing_args, "", 0, ""); } catch (\RuntimeException $error) { echo "exec-error\n"; }
$args_127 /** vector<string> */ = ["-c", "exit 127"];
$p127 = process_spawn("/bin/sh", $args_127, "", 1000, "");
for ($i = 0; $i < 1000000; $i++) { if (process_poll($p127)) { break; } }
$out127 = process_output($p127); echo $out127->exit_code, "\n";process_close($p127);
$sleep_args /** vector<string> */ = ["10"];
$slow = process_spawn("/bin/sleep", $sleep_args, "", 10, "");
try { process_output($slow); } catch (\RuntimeException $error) { echo "pending-error\n"; }
for ($i = 0; $i < 1000000; $i++) { if (process_poll($slow)) { break; } }
$timed = process_output($slow);
echo $timed->timed_out ? "timeout" : "bad", ":", $timed->signal, "\n";process_close($slow);
$stop = process_spawn("/bin/sleep", $sleep_args, "", 0, "");process_stop($stop);process_stop($stop);
for ($i = 0; $i < 1000000; $i++) { if (process_poll($stop)) { break; } }
$stopped = process_output($stop);echo $stopped->stopped ? "stopped" : "bad", ":", $stopped->signal, "\n";process_close($stop);
'''
EXPECTED='locked\ncontended\ntransfer-held\nshared\nshared-pair\nreleased-transfer-error\npath-error\ninput:err:7\ninput\nclosed-error\nexec-error\n127\npending-error\ntimeout:9\nstopped:9\n'

def main():
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results',type=Path,required=True)
    parser.add_argument('--target-checkout',type=Path,required=True)
    parser.add_argument('--candidate-revision', help='Full immutable revision for pre-adoption validation')
    args=parser.parse_args()
    if args.candidate_revision and (len(args.candidate_revision)!=40 or any(c not in '0123456789abcdef' for c in args.candidate_revision)):
        parser.error('Candidate revision must be a full lowercase commit hash')
    out=args.results.resolve();out.mkdir(parents=True,exist_ok=False)
    source=out/'php';source.mkdir();project=out/'phpp';events=[]
    (out/'summary.json').write_text(json.dumps(dict(status='running',scope='OS facade candidate'))+'\n')
    def run(cmd,cwd=ROOT,ok=True):
        p=subprocess.run(list(map(str,cmd)),cwd=cwd,text=True,capture_output=True,timeout=240)
        events.append(dict(command=list(map(str,cmd)),exit=p.returncode,stdout=p.stdout,stderr=p.stderr))
        (out/'commands.json').write_text(json.dumps(events,indent=2)+'\n')
        if (p.returncode==0)!=ok:
            (out/'summary.json').write_text(json.dumps(dict(status='failed',failed_command=list(map(str,cmd))),indent=2)+'\n')
        assert (p.returncode==0)==ok,(cmd,p.stdout,p.stderr)
        return p.stdout
    checkout=args.target_checkout.resolve();target=json.loads((ROOT/'compiler/tools/portability_target.json').read_text())
    if args.candidate_revision: target['verified_commit']=args.candidate_revision
    assert run(['git','-C',checkout,'rev-parse','HEAD']).strip()==target['verified_commit']
    assert not run(['git','-C',checkout,'status','--porcelain']).strip()
    path=source/'main.php';path.write_text('<?php\ndeclare(strict_types=1);\n'+BODY.replace('LOCK_PATH',json.dumps(str(out/'shared.lock'))))
    run(['php',TOOLS/'sync_imports.php',source]);run(['php',TOOLS/'check.php',source]);run(['php',TOOLS/'convert.php',source,project])
    assert run(['php','-r','require $argv[1];require $argv[2];',TOOLS/'runtime/bootstrap.php',path])==EXPECTED
    run(['php',TOOLS/'install_native_runtime.php',project,'--os'])
    cli=checkout/'bin/scpp.php';run(['php',cli,'init','--php-profile=strict'],cwd=project)
    config_path=project/'prism.json';config=json.loads(config_path.read_text());config['build']['cxx']='clang++-18';config['runtime']['modules']=['filesystem','process'];config_path.write_text(json.dumps(config,indent=2)+'\n')
    assert run(['php',cli,'run','--build-runtime'],cwd=project).endswith(EXPECTED)
    assert not run(['git','-C',checkout,'status','--porcelain']).strip()
    (out/'summary.json').write_text(json.dumps(dict(passed=True,target_revision=target['verified_commit'],expected=EXPECTED),indent=2)+'\n')
    print('OS facade PHP/native parity passed.')
if __name__=='__main__':main()

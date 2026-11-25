"""Force the READY/ack-open interleaving on a private copy of the PHP launch protocol."""
import argparse
import json
import os
from pathlib import Path
import signal
import subprocess
import time

ROOT=Path(__file__).resolve().parents[2]
def main():
    parser=argparse.ArgumentParser(description=__doc__);parser.add_argument('--results',type=Path,required=True);args=parser.parse_args()
    out=args.results.resolve();out.mkdir(parents=True,exist_ok=False);results=[]
    for name,executable,argv,expected in [('success','/bin/true',[],0),('exit127','/bin/sh',['-c','exit 127'],127),('exec_error','/not/an/executable',[],'error')]:
        case=out/name;case.mkdir();sent=case/'ack-sent';release=case/'release';pidfile=case/'shim.pid'
        processes=(ROOT/'tools/php_portability/runtime/processes.php').read_text()
        anchor='            $tail = stream_get_contents($control, 4096);';assert processes.count(anchor)==1
        processes=processes.replace(anchor,'            file_put_contents('+repr(str(sent))+', "sent");\n'+anchor)
        (case/'processes.php').write_text(processes)
        launcher=(ROOT/'tools/php_portability/runtime/process_launcher.php').read_text();anchor="    $ack = fopen($config['ack'], 're');";assert launcher.count(anchor)==1
        launcher=launcher.replace(anchor,'    file_put_contents('+repr(str(pidfile))+', (string)getmypid());\n    while (!file_exists('+repr(str(release))+')) { usleep(1000); }\n'+anchor)
        (case/'process_launcher.php').write_text(launcher)
        program='require $argv[1]; try {$h=\\scpp\\process_spawn($argv[2],json_decode($argv[3],true),"",1000,""); while(!\\scpp\\process_poll($h)){usleep(1000);} $o=\\scpp\\process_output($h); \\scpp\\process_close($h); echo $o->exit_code;} catch(RuntimeException $e){echo "error";}'
        command=['php','-r',program,str(case/'processes.php'),executable,json.dumps(argv)]
        start=time.monotonic();process=subprocess.Popen(command,stdout=subprocess.PIPE,stderr=subprocess.PIPE,text=True)
        try:
            while not sent.exists():
                assert process.poll() is None, 'Launch exited before acknowledgement'
                assert time.monotonic()-start<3,'Parent did not acknowledge'
                time.sleep(.001)
            release.write_text('go')
            stdout,stderr=process.communicate(timeout=3)
            assert process.returncode==0 and stdout==str(expected),(name,stdout,stderr)
            results.append(dict(case=name,passed=True,seconds=round(time.monotonic()-start,3),stdout=stdout))
        finally:
            if process.poll() is None:
                if pidfile.exists():
                    try:os.killpg(int(pidfile.read_text()),signal.SIGKILL)
                    except ProcessLookupError:pass
                process.kill();process.communicate()
    (out/'summary.json').write_text(json.dumps(dict(passed=True,cases=results),indent=2)+'\n');print('PHP launch acknowledgement: 3 forced interleavings passed')
if __name__=='__main__':main()

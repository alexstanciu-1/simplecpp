from pathlib import Path
import shutil
import time

def build(out):
    python=str(Path(shutil.which('python3')).resolve());clang=str(Path(shutil.which('clang-18')).resolve())
    rows=[]
    def add(name,command,input='',want='',timeout=3000,error='',contains=False,marker=''):
        rows.append(dict(name=name,command=command,input=input,want=want,timeout=timeout,error=error,contains=contains,marker=marker))
    add('echo',[python,'-c','import sys;sys.stdout.buffer.write(sys.stdin.buffer.read())'],'hello\x00bytes\n','hello\x00bytes\n')
    add('literal-argv',[python,'-c','import sys;print(sys.argv[1],end="")','space;$(false)'],'','space;$(false)')
    add('output-before-input',[python,'-c','import sys;sys.stderr.write("e"*70000);sys.stdout.write("x"*70000);sys.stdout.flush();sys.stdout.buffer.write(sys.stdin.buffer.read())'],'tail','x'*70000+'tail')
    add('exit-failure',[python,'-c','import sys;sys.stderr.write("failure detail\\n");sys.exit(7)'],error='Clang backend operation failed: failure detail\n')
    add('legitimate-127',[python,'-c','import sys;sys.exit(127)'],error='Clang backend operation failed: ')
    add('signal',[python,'-c','import os,signal;os.kill(os.getpid(),signal.SIGTERM)'],error='Clang backend operation failed: ')
    add('missing-exec',['/not/a/compiler-tool'],error='*')
    add('relative-exec',['python3'],error='*')
    add('empty-command',[],error='invalid')
    add('no-deadline',[python,'-c','pass'],timeout=0,error='invalid')
    add('negative-deadline',[python,'-c','pass'],timeout=-1,error='invalid')
    add('timeout',[python,'-c','import time;time.sleep(30)'],timeout=50,error='Clang backend operation timed out')
    source='target triple = "x86_64-unknown-linux-gnu"\n@answer = constant i64 add (i64 20, i64 22)\n'
    add('clang',[clang,'-target','x86_64-unknown-linux-gnu','-x','ir','-O2','-S','-emit-llvm','-','-o','-'],source,'@answer = local_unnamed_addr constant i64 42',10000,contains=True)
    for mode in ['success','failure','timeout']:
        marker=str(out/('descendant-'+mode+'.pid'))
        program='import os,sys,time\npid=os.fork()\nif pid==0: time.sleep(30);os._exit(0)\nopen(sys.argv[1],"w").write(str(pid))\n'
        if mode=='success':program+='print("done",end="")\n'
        elif mode=='failure':program+='sys.stderr.write("child failure");sys.exit(4)\n'
        else:program+='time.sleep(30)\n'
        add('descendants-'+mode,[python,'-c',program,marker],want='done' if mode=='success' else '',timeout=1000,error={'success':'','failure':'Clang backend operation failed: child failure','timeout':'Clang backend operation timed out'}[mode],marker=marker)
    return rows

def reset(rows):
    for row in rows:
        if row['marker']:Path(row['marker']).unlink(missing_ok=True)
def cleanup(rows):
    checked=[]
    for row in rows:
        if not row['marker']:continue
        marker=Path(row['marker']);assert marker.exists(),row['name']
        pid=int(marker.read_text());status=Path(f'/proc/{pid}/status');deadline=time.monotonic()+2
        while status.exists():
            text=status.read_text()
            if '\nState:\tZ' in text:break
            assert time.monotonic()<deadline,('descendant survived',row['name'],pid,text)
            time.sleep(.01)
        checked.append(dict(case=row['name'],pid=pid,stopped=True))
    return checked

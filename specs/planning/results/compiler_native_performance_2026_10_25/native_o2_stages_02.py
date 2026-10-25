from pathlib import Path
import json,sys,subprocess
root=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');app=root/'compiler/my-try';out=Path('/tmp/compiler-native-o2-stages-02');out.mkdir(exist_ok=False)
binary=json.loads(Path('/tmp/compiler-native-o2-01/logs/build-o2.stdout').read_text())['executable']
(out/'request.txt').write_text('/tmp/compiler-bootstrap-native-transport-01/phpp');(out/'output.txt').write_text(str(out/'published'))
symbols={}
for line in subprocess.check_output(['nm',binary],text=True).splitlines():
 if ' T ' not in line or 'implementation_scppUqcompilerUqCompiler__' not in line:continue
 for name in ['init','syncU_live','prepare','cpp']:
  if '__method_'+name+'E' in line:symbols[name]=line.split()[-1]
script='''set pagination off
set confirm off
set debuginfod enabled off
python
import gdb, time, json
start=time.monotonic()
events=[]
def record(stage, event):
    row={'stage':stage,'event':event,'seconds':time.monotonic()-start}
    events.append(row)
    with open(OUTPUT,'w') as f:json.dump(events,f,indent=2)
class End(gdb.FinishBreakpoint):
    def __init__(self,stage):
        super().__init__(gdb.newest_frame(), internal=True)
        self.stage=stage
    def stop(self):
        record(self.stage,'leave')
        return False
class Begin(gdb.Breakpoint):
    def __init__(self,stage,symbol):
        super().__init__(symbol, internal=True)
        self.stage=stage
    def stop(self):
        record(self.stage,'enter')
        End(self.stage)
        return False
for stage,symbol in SYMBOLS.items():Begin(stage,symbol)
gdb.execute('run')
record('process','exit')
end
'''.replace('OUTPUT',repr(str(out/'stages.json'))).replace('SYMBOLS',repr(symbols))
(out/'stages.gdb').write_text(script)
sys.path.insert(0,str(app/'tools'));from test_runner import CommandRunner,Task,run_tasks
with CommandRunner(out/'logs',root) as runner:
 run_tasks([Task('stages',lambda:runner.run('stages',['gdb','--batch','--nx','-x',out/'stages.gdb',binary],cwd=out,timeout=1200))],jobs=1)

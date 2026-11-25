set pagination off
set confirm off
set debuginfod enabled off
python
import gdb, time, json
start=time.monotonic()
events=[]
def record(stage, event):
    row={'stage':stage,'event':event,'seconds':time.monotonic()-start}
    events.append(row)
    with open('/tmp/compiler-native-o2-stages-02/stages.json','w') as f:json.dump(events,f,indent=2)
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
for stage,symbol in {'cpp': '_ZN31record_scppUqcompilerUqCompiler51implementation_scppUqcompilerUqCompiler__method_cppEN4scpp8internal118concrete_n_nullable_scpp_compiler_Output_Publication__ffe87a9f8d9bcf08a7a23fdd5850abf71d19561f4fb2e0e6e12d333321dc1771ENS1_119concrete_n_nullable_scpp_compiler_cpp_lowering_policy__36d5e82911bb812a0c045d4849591edaab876a9bf2727de1eea8a69b099efb0dE', 'init': '_ZN31record_scppUqcompilerUqCompiler52implementation_scppUqcompilerUqCompiler__method_initEN4scpp8internal90concrete_n_vector_string__27f3c3e8a5ca274709c96b128cfd9697c92c144385ac86f43efdc76009048142E', 'prepare': '_ZN31record_scppUqcompilerUqCompiler55implementation_scppUqcompilerUqCompiler__method_prepareEN4scpp8internal80concrete_n_bool_186b6fb4c7e65e0a2d027fe4a8eb785f77810b58584e99fc5cb73047a04b3bacE', 'syncU_live': '_ZN31record_scppUqcompilerUqCompiler58implementation_scppUqcompilerUqCompiler__method_syncU_liveEv'}.items():Begin(stage,symbol)
gdb.execute('run')
record('process','exit')
end

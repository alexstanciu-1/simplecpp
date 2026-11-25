"""Execute existing exported initializer fixtures against the separate dev002 runtime."""
import json
import benchmark_support as b
from cpp_fixture import CppFixtureCompiler
from test_runner import CommandRunner,Task,run_tasks
B=b.BASE

def main():
 out=B/'runtime-checks';out.mkdir()
 with CommandRunner(out/'logs',B) as runner:
  cpp=CppFixtureCompiler(runner,B,out,runtime=True,pch=False,cxx='/usr/bin/clang++-18')
  files=list((B/'focused-programs').glob('*.cpp'))
  def check(source):
   exe=out/source.stem;cpp.compile(source.stem,source,exe);runner.run(source.stem+'-run',[exe])
  run_tasks([Task(p.stem,lambda p=p:check(p)) for p in files])
 (out/'summary.json').write_text(json.dumps(dict(passed=True,programs=len(files),pch=False),indent=2))
if __name__=='__main__':main()

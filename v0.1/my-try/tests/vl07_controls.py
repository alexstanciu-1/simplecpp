"""Explicit conversions and bounded indexed-update audit."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler
parser=argparse.ArgumentParser(description=__doc__);parser.add_argument('--results',type=Path,required=True);args=parser.parse_args()
out=args.results.resolve();out.mkdir(parents=True,exist_ok=False)
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 cpp=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=True)
 def proof():
  runner.run('controls-prepare',['php',APP/'tests/vl07_controls.php',out]);cpp.compile('controls-build',out/'controls.cpp',out/'native');runner.run('controls-execute',[out/'native'])
 run_tasks([Task('controls',proof)])
(out/'summary.json').write_text(json.dumps({'passed':True,'explicit_conversion_controls':4,'indexed_positive_controls':2,'power_precedence_snapshot_controls':2,'rejections':json.loads((out/'rejections.json').read_text()),'full_matrix_run':False},indent=2)+'\n')

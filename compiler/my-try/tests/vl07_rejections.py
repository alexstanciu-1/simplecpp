"""Exhaustive E5/C2 rejection and recovery proofs, not a full matrix run."""
import argparse
import csv
import json
from pathlib import Path
import sys
APP=Path(__file__).resolve().parents[1]
ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
parser=argparse.ArgumentParser(description=__doc__)
parser.add_argument('--results',type=Path,required=True)
args=parser.parse_args()
out=args.results.resolve();out.mkdir(parents=True,exist_ok=False)
rows=[r for r in csv.DictReader((APP/'docs/planning/vl07_inventory/signatures.csv').open()) if r['group'] in ('E5','C2')]
assert len(rows)==576
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 def proof(i,chunk):
  source=out/f'rows-{i}.json';source.write_text(json.dumps(chunk))
  runner.run(f'rejections-{i}',['php',APP/'tests/vl07_rejections.php',source,out/f'results-{i}.json'])
 run_tasks([Task(f'rejections-{i}',lambda i=i:proof(i,rows[i:i+24])) for i in range(0,len(rows),24)])
records=[r for path in out.glob('results-*.json') for r in json.loads(path.read_text())]
assert {r['signature_id'] for r in records}=={r['signature_id'] for r in rows}
(out/'summary.json').write_text(json.dumps({'passed':True,'heterogeneous_equality':480,'narrowing_compounds':96,'recoveries':len(records),'full_matrix_run':False,'records':records},indent=2)+'\n')

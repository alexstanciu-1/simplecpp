"""Strengthen the frozen scalar fixture's generic-exception oracle.

Reuses emitted source functions unchanged; changes only the native test observer.
The default scalar fixture must have been exported without --ieee-zero.
"""
import argparse
import hashlib
import json
from pathlib import Path
import sys

APP = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--scalar-results', required=True, type=Path)
parser.add_argument('--results', required=True, type=Path)
args = parser.parse_args()
output = args.results.resolve()
output.mkdir(parents=True, exist_ok=False)
original = args.scalar_results / 'scalar_admission.cpp'
text = original.read_text()
coarse = 'catch (const std::exception&) {}'
precise = ('catch (const scpp::runtime_error& error) { '
           'if (error.code() != "division_by_zero") return 90; }')
if text.count(coarse) != 6:
    raise RuntimeError('Expected six default zero-division observers; source changed')
text = text.replace(coarse, precise)
extra = []
for numerator in ['0.0', '-0.0', '1.0', '-1.0']:
    for denominator in ['0.0', '-0.0']:
        extra.append(f'try {{ (void)function_divff(F{{{numerator}}}, F{{{denominator}}}); return 91; }} {precise}')
for numerator in ['0', '1', '-1']:
    for denominator in ['0.0', '-0.0']:
        extra.append(f'try {{ (void)function_divif(I{{{numerator}}}, F{{{denominator}}}); return 92; }} {precise}')
for numerator in ['0.0', '-0.0', '1.0', '-1.0']:
    extra.append(f'try {{ (void)function_divfi(F{{{numerator}}}, I{{0}}); return 93; }} {precise}')
before, after = text.rsplit('    return 0;', 1)
source = output / 'scalar_error_codes.cpp'
source.write_text(before + '\n'.join(extra) + '\n    return 0;' + after)
(output / 'provenance.json').write_text(json.dumps(dict(
    input=str(original.resolve()), input_sha256=hashlib.sha256(original.read_bytes()).hexdigest(),
    observer_sha256=hashlib.sha256(source.read_bytes()).hexdigest(),
    strengthened_existing_observers=6, additional_zero_cases=len(extra),
    lane='test observer extension; emitted source-language functions unchanged'), indent=2) + '\n')
with CommandRunner(output / 'logs', APP.parents[1], jobs=1) as runner:
    def proof():
        compiler = CppFixtureCompiler(runner, APP.parents[1], output, pch=False, runtime=True)
        executable = output / 'scalar_error_codes'
        compiler.compile('scalar-error-codes-build', source, executable)
        runner.run('scalar-error-codes-execute', [executable])
    run_tasks([Task('structured-zero-division', proof)], jobs=1)

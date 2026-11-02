"""Exact CLI contract, also imported by the retained/fresh incremental harness."""
import json
from pathlib import Path
import sys

sys.dont_write_bytecode = True
APP = Path(__file__).resolve().parent
sys.path.insert(0, str(APP))
from baseline_cli_cases import cases as baseline_cases, BASELINE, USAGE

SCENARIO_COUNTS = {'credit': 20, 'procurement': 46, 'expenses': 41, 'payroll': 37, 'treasury': 45, 'projects': 41, 'warehouse': 41, 'allocations': 34}


def cases():
    rows = [r for r in baseline_cases() if r[0] not in
            {'default-self-test', 'explicit-self-test', 'help'}]
    rows.append(('help', ['--help'], USAGE +
                 'accounting scenario NAME\naccounting document TYPE\naccounting documents\n', 0))
    docs = json.loads((APP / 'tests/document_output.json').read_text())
    rows.append(('document-list', ['documents'], '\n'.join(docs) + '\n', 0))
    for name, expected in docs.items():
        rows.append(('document-' + name, ['document', name], expected, 0))
    rows.extend([
        ('bad-document', ['document', 'Missing'], 'error=document.name\n', 64),
        ('bad-scenario', ['scenario', 'missing'], 'error=scenario.name\n', 64)])
    for name, count in SCENARIO_COUNTS.items():
        rows.append(('scenario-' + name, ['scenario', name],
                     f'CHECKS passed={count} failed=0\nACCOUNTING_WORKLOAD_PASS\n', 0))
    expected = BASELINE + ('CHECKS passed=857 failed=0\n'
                           'ACCOUNTING_WORKLOAD_PASS\nACCOUNTING_512K_PASS\n')
    rows.extend([('default-self-test', [], expected, 0),
                 ('explicit-self-test', ['self-test'], expected, 0)])
    return rows

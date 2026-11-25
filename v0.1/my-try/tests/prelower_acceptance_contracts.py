#!/usr/bin/env python3
"""Verify aggregate failure reporting without running native proof suites."""
import contextlib
import io
import json
from pathlib import Path
import tempfile
import unittest
from unittest.mock import patch

import prelower_acceptance as acceptance


class AcceptanceReporting(unittest.TestCase):
    def exercise(self, failure=None):
        with tempfile.TemporaryDirectory() as temporary:
            out = Path(temporary) / 'results'
            calls = []

            class Runner:
                def __init__(self, *args, **kwargs):
                    pass

                def __enter__(self):
                    return self

                def __exit__(self, *args):
                    pass

                def run(self, name, command, **kwargs):
                    calls.append(name)
                    if name == failure:
                        raise RuntimeError('injected failure')
                    if name == 'accounting':
                        folder = out / name
                        folder.mkdir()
                        data = {} if failure == 'summary' else dict(
                            behavior_identical=True, checks_passed=998, checks_failed=0,
                            application_units=16, template_declarations=0, instantiations=0)
                        (folder / 'result.json').write_text(json.dumps(data))
                    return b'proof identity\n'

            with patch.object(acceptance, 'CommandRunner', Runner), \
                    patch('sys.argv', ['prelower_acceptance.py', '--results', str(out)]), \
                    contextlib.redirect_stdout(io.StringIO()):
                if failure == 'summary':
                    with self.assertRaises(KeyError):
                        acceptance.main()
                    code = None
                else:
                    code = acceptance.main()
            return code, json.loads((out / 'result.json').read_text()), calls

    def test_complete_success(self):
        code, report, _ = self.exercise()
        self.assertEqual(code, 0)
        self.assertEqual(report['status'], 'passed')
        self.assertEqual(len(report['phases']), 8)
        self.assertEqual(report['accounting']['application_units'], 16)

    def test_suite_failure_cannot_be_hidden_by_accounting_success(self):
        code, report, calls = self.exercise('contracts')
        self.assertEqual(code, 1)
        self.assertEqual(report['status'], 'failed')
        self.assertEqual(report['phases'][1]['status'], 'failed')
        self.assertIn('accounting', calls)

    def test_metadata_failure_is_retained(self):
        code, report, _ = self.exercise('runtime_inventory.php')
        self.assertEqual(code, 1)
        self.assertEqual(report['phases'][0]['status'], 'failed')

    def test_invalid_final_evidence_cannot_publish_success(self):
        _, report, _ = self.exercise('summary')
        self.assertEqual(report['status'], 'incomplete')
        self.assertIn('error', report)


if __name__ == '__main__':
    unittest.main()

"""Check compatibility-oracle handling without compiling fixtures."""
from pathlib import Path
import tempfile
import unittest

from legacy_compatibility import classify, fixture_bytes, normalize, matches, observed_rejection, operator_matrix


class CompatibilityTests(unittest.TestCase):
    def test_baseline_failure_is_not_a_regression_and_binary_outputs_stay_distinct(self):
        result = {'v01': {'passed': False}, 'v02': {'passed': False}}
        self.assertEqual(classify(result, {}), 'baseline-unproven')
        result['v01']['passed'] = True
        self.assertEqual(classify(result, {}), 'compatibility-gap')
        for version in ('v01', 'v02'):
            result[version] = dict(passed=True, stdout_hex='ff', stderr_hex='')
        self.assertEqual(classify(result, {}), 'compatible-proved')
        result['v02']['stdout_hex'] = b'\\xff'.hex()
        self.assertEqual(classify(result, {}), 'behavior-difference')

    def test_rejection_excludes_timeout_signal_and_launch_failure(self):
        self.assertTrue(observed_rejection({'code': 1}))
        for record in ({'code': 0}, {'code': -9}, {'code': None},
                       {'code': 1, 'error': 'timeout after 15s'}):
            self.assertFalse(observed_rejection(record))

    def test_matrix_exclusion_does_not_hide_ordinary_tests(self):
        self.assertTrue(operator_matrix('tests/php-matrix/add/test.phs'))
        self.assertTrue(operator_matrix('tests/runtime-matrix/add/test.cpp'))
        self.assertFalse(operator_matrix('tests/php/operators/level_01/add.phs'))
        self.assertFalse(operator_matrix('tests/php/arrays/matrix_like_nested_writes.phs'))

    def test_declared_substring_oracle_requires_every_fragment(self):
        comparison = {'stdout': 'substring_all'}
        self.assertTrue(matches(b'hello world', ['hello', 'world'], comparison, 'stdout'))
        self.assertFalse(matches(b'hello', ['hello', 'world'], comparison, 'stdout'))

    def test_extraction_keeps_source_and_rejects_ambiguous_nowdocs(self):
        with tempfile.TemporaryDirectory() as temporary:
            path = Path(temporary) / 'harness.php'
            payload = b"$source = <<<'PHS'\n$x /** int */ = 1;\necho $x;\nPHS);\n"
            path.write_bytes(payload)
            self.assertEqual(fixture_bytes(path, {}), payload)
            self.assertEqual(fixture_bytes(path, {'nowdoc': 'PHS'}),
                             b'$x /** int */ = 1;\necho $x;\n')
            path.write_bytes(payload + payload)
            with self.assertRaises(ValueError):
                fixture_bytes(path, {'nowdoc': 'PHS'})

    def test_normalization_only_applies_declared_oracle_policy(self):
        data = b'a\r\nb\r\n\n'
        self.assertEqual(normalize(data, {}, 'stdout'), data)
        policy = {'normalize_stdout_newlines': True, 'trim_final_stdout_newline': True}
        self.assertEqual(normalize(data, policy, 'stdout'), b'a\nb\n')
        self.assertEqual(normalize(data, policy, 'stderr'), data)


if __name__ == '__main__':
    unittest.main()

"""Prove the parity comparator rejects drift instead of blessing its own snapshots."""
import unittest

from parity import compare, fingerprint, json_digest, target_facts


class ComparisonTests(unittest.TestCase):
    def setUp(self):
        self.artifacts = {
            "public/scpp/sample.hpp": b"class sample {};\n",
            "prepared.json": b'{"operations":["add","subtract"],"explicit":true}',
            "inventory.json": b'["prepared.json","public/scpp/sample.hpp"]\n',
        }

    def test_equal_artifacts(self):
        compare(self.artifacts, dict(self.artifacts), "identity")
        self.assertEqual(fingerprint(self.artifacts), fingerprint(dict(self.artifacts)))

    def test_missing_extra_and_changed_cpp(self):
        for change in ["missing", "extra", "cpp", "newline"]:
            with self.subTest(change=change):
                actual = dict(self.artifacts)
                if change == "missing":
                    del actual["public/scpp/sample.hpp"]
                elif change == "extra":
                    actual["extra.hpp"] = b""
                elif change == "cpp":
                    actual["public/scpp/sample.hpp"] = b"struct sample {};\n"
                else:
                    actual["public/scpp/sample.hpp"] += b"\n"
                with self.assertRaises(AssertionError):
                    compare(self.artifacts, actual, change)
                self.assertNotEqual(fingerprint(self.artifacts), fingerprint(actual))

    def test_prepared_semantic_drift_is_not_normalized(self):
        original = self.artifacts["prepared.json"]
        for changed in [b'{"operations":["subtract","add"],"explicit":true}',
                        b'{"operations":["add","subtract"],"explicit":1}',
                        b'{"operations":["add","subtract"],"explicit":false}',
                        b'{"operations":["add","subtract"]}']:
            self.assertNotEqual(json_digest(original), json_digest(changed))
        reordered = b'{ "explicit": true, "operations": ["add", "subtract"] }'
        self.assertEqual(json_digest(original), json_digest(reordered))
        changed = dict(self.artifacts, **{"prepared.json": reordered})
        # Even benign serialization differences require review under strict artifact parity.
        with self.assertRaises(AssertionError):
            compare(self.artifacts, changed, "JSON formatting")

    def test_target_layout_and_native_range_changes_are_visible(self):
        owner = {"ownership": {"records": [{"families": [
            {"family": "shared", "pointee": "const", "size": 16, "alignment": 8},
            {"family": "value"},
        ]}]}}
        original = target_facts({"native_inputs": [{"cpp": "int", "digits": 31}]}, owner)
        changed = target_facts({"native_inputs": [{"cpp": "int", "digits": 15}]}, owner)
        self.assertNotEqual(original, changed)
        owner["ownership"]["records"][0]["families"][0]["size"] = 8
        self.assertNotEqual(original, target_facts({"native_inputs": [{"cpp": "int", "digits": 31}]}, owner))


if __name__ == "__main__":
    unittest.main()

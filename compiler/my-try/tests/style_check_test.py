"""Protect authored string contents while enforcing code layout."""
import importlib.util
from pathlib import Path
import unittest

spec = importlib.util.spec_from_file_location(
    'my_try_style', Path(__file__).resolve().parents[1] / 'tools/style_check.py')
style = importlib.util.module_from_spec(spec)
spec.loader.exec_module(style)


class LayoutTests(unittest.TestCase):
    def check_layout(self, source):
        formatted = style.layout(source)
        before = [(t['kind'], t['text']) for t in style.significant(source)]
        after = [(t['kind'], t['text']) for t in style.significant(formatted)]
        self.assertEqual(before, after)
        self.assertEqual(formatted, style.layout(formatted))
        return formatted

    def test_interpolated_output_and_method_gap(self):
        source = '''<?php
final class Output {
public function first(): void {
echo "value {$this->value}\\n";
}
/** Separate operation. */
public function second(): void {
return;
}
}
'''
        formatted = self.check_layout(source)
        self.assertIn('\t\techo "value {$this->value}\\n";', formatted)
        self.assertIn('\t}\n\n\t/** Separate operation. */', formatted)

    def test_multiline_literals_and_comments(self):
        source = '''<?php
function sample(): void {
/* first
 keep this indentation
*/
$text = 'first
    second
third';
$other = "first {$text}
  second
third";
$raw = <<<'TEXT'
    raw source
TEXT;
echo $raw;
}
'''
        formatted = self.check_layout(source)
        self.assertIn("'first\n    second\nthird'", formatted)
        self.assertIn('    raw source\nTEXT;', formatted)
        self.assertIn('\techo $raw;', formatted)


if __name__ == '__main__':
    unittest.main()

#!/usr/bin/env python3
"""Regression cases for rejecting incomplete or ambiguous closure-audit recordings."""
from pathlib import Path
import unittest

from prelower_template_closure import select_commands


class Coverage(unittest.TestCase):
    root = Path('/tmp/closure-coverage-publication')

    def manifest(self):
        return dict(format='scpp-output-manifest-v1', files=[
            dict(path='entry.cpp', kind='translation_unit'),
            dict(path='units/app.cpp', kind='translation_unit'),
            dict(path='specializations/support.cpp', kind='translation_unit'),
            dict(path='specializations/layout.cpp', kind='layout_probe')])

    def command(self, source, extra=()):
        return dict(phase='object_compile', exit_code=0,
                    arguments=['-c', str(self.root / source), '-o', '/tmp/a.o', *extra])

    def commands(self):
        return [self.command('entry.cpp'), self.command('units/app.cpp')]

    def test_complete_and_exclusions(self):
        records = self.commands() + [self.command('specializations/support.cpp'),
                                    self.command('specializations/layout.cpp')]
        commands, excluded = select_commands(self.manifest(), self.root, records)
        self.assertEqual(set(commands), {self.root / 'entry.cpp', self.root / 'units/app.cpp'})
        self.assertEqual(len(excluded), 2)

    def test_missing_entry_or_unit_or_all(self):
        for records in [self.commands()[:1], self.commands()[1:], []]:
            with self.subTest(records=records), self.assertRaisesRegex(ValueError, 'Missing application'):
                select_commands(self.manifest(), self.root, records)

    def test_unknown_application(self):
        with self.assertRaisesRegex(ValueError, 'Unexpected application'):
            select_commands(self.manifest(), self.root, self.commands() + [self.command('units/stale.cpp')])

    def test_conflicting_recording(self):
        with self.assertRaisesRegex(ValueError, 'Conflicting application'):
            select_commands(self.manifest(), self.root, self.commands() + [self.command('entry.cpp', ['-O2'])])

    def test_identical_repeat(self):
        commands, _ = select_commands(self.manifest(), self.root, self.commands() * 2)
        self.assertEqual(len(commands), 2)

    def test_pch(self):
        for args in [['-include-pch', '/tmp/runtime.pch'], ['-include-pch=/tmp/runtime.pch']]:
            with self.subTest(args=args), self.assertRaisesRegex(ValueError, 'uses PCH'):
                select_commands(self.manifest(), self.root,
                                [self.command('entry.cpp', args), self.command('units/app.cpp')])

    def test_failed_compilation(self):
        records = self.commands()
        records[0]['exit_code'] = 1
        with self.assertRaisesRegex(ValueError, 'Failed application'):
            select_commands(self.manifest(), self.root, records)

    def test_missing_manifest_entry(self):
        manifest = self.manifest()
        manifest['files'] = manifest['files'][1:]
        with self.assertRaisesRegex(ValueError, 'no application entry'):
            select_commands(manifest, self.root, self.commands())

    def test_unclassified_compilation_artifact(self):
        manifest = self.manifest()
        manifest['files'].append(dict(path='unexpected.cpp', kind='translation_unit'))
        with self.assertRaisesRegex(ValueError, 'Unclassified compilation'):
            select_commands(manifest, self.root, self.commands())

    def test_duplicate_manifest_unit(self):
        manifest = self.manifest()
        manifest['files'].append(manifest['files'][0])
        with self.assertRaisesRegex(ValueError, 'Duplicate application'):
            select_commands(manifest, self.root, self.commands())


if __name__ == '__main__':
    unittest.main()

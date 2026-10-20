#!/usr/bin/env python3
"""Audit recorded app compile commands, including transitive input and instantiations."""
import argparse
import json
from pathlib import Path
import re
import sys

ROOT = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(ROOT / 'compiler/my-try/tools'))
from test_runner import CommandRunner, Task, run_tasks


def application_source(source, published):
    return source.is_relative_to(published / 'units') or source == published / 'entry.cpp'


def select_commands(manifest, published, records):
    """Require exact coverage of the existing publication's application inventory."""
    if manifest.get('format') != 'scpp-output-manifest-v1':
        raise ValueError('Unsupported publication manifest')
    expected = set()
    excluded = []
    for item in manifest['files']:
        source = (published / item['path']).resolve()
        if not source.is_relative_to(published):
            raise ValueError('Published artifact escapes publication: ' + item['path'])
        if item['kind'] == 'translation_unit' and application_source(source, published):
            if source in expected:
                raise ValueError('Duplicate application unit: ' + item['path'])
            expected.add(source)
        elif item['kind'] in ('translation_unit', 'layout_probe'):
            if not source.is_relative_to(published / 'specializations'):
                raise ValueError('Unclassified compilation artifact: ' + item['path'])
            excluded.append(dict(path=item['path'], kind=item['kind']))
    if published / 'entry.cpp' not in expected:
        raise ValueError('Publication has no application entry.cpp')
    commands = {}
    for row in records:
        if row['phase'] != 'object_compile':
            continue
        args = row['arguments']
        source = Path(args[args.index('-c') + 1])
        if not source.is_absolute():
            raise ValueError('Recorded compilation source must be absolute: ' + str(source))
        source = source.resolve()
        if not application_source(source, published):
            continue
        if source not in expected:
            raise ValueError('Unexpected application compilation: ' + str(source))
        if row.get('exit_code', 0) != 0:
            raise ValueError('Failed application compilation: ' + str(source))
        if any(arg == '-include-pch' or arg.startswith('-include-pch=') for arg in args):
            raise ValueError('Application command uses PCH: ' + str(source))
        if source in commands and commands[source] != args:
            raise ValueError('Conflicting application commands: ' + str(source))
        commands[source] = args
    missing = sorted(str(source.relative_to(published)) for source in expected - commands.keys())
    if missing:
        raise ValueError('Missing application compilation commands: ' + ', '.join(missing))
    return commands, excluded


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--commands', type=Path, required=True,
                        help='JSONL records with phase and arguments from the compiler recorder')
    parser.add_argument('--published', type=Path, required=True)
    parser.add_argument('--results', type=Path, required=True)
    options = parser.parse_args()
    out = options.results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    published = options.published.resolve()
    with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
        manifest = json.loads(runner.run('publication-inventory', ['php',
            ROOT / 'compiler/my-try/tests/fixtures/prelower_closure_inventory.php', published]))
        records = [json.loads(line) for line in options.commands.read_text().splitlines() if line.strip()]
        commands, excluded = select_commands(manifest, published, records)
        coverage = dict(application_units=sorted(str(source.relative_to(published)) for source in commands),
                        excluded_artifacts=excluded)
        (out / 'coverage.json').write_text(json.dumps(coverage, indent=2) + '\n')
        def inspect(source, original):
            relative = str(source.relative_to(published))
            name = relative.encode().hex()
            assert '-include-pch' not in original, source
            args = original.copy()
            args[args.index('-o') + 1] = str(out / (name + '.ii'))
            args.remove('-c')
            args.append('-E')
            runner.run(name + '-preprocess', ['/usr/bin/clang++', *args])
            text = (out / (name + '.ii')).read_text()
            origins = set(re.findall(r'^# \d+ "([^"]+)"', text, re.M))
            templates = re.findall(r'\btemplate\s*<', text)
            args = original.copy()
            args[args.index('-o') + 1] = str(out / (name + '-trace.o'))
            args.extend(['-ftime-trace', '-ftime-trace-granularity=0'])
            runner.run(name + '-trace', ['/usr/bin/clang++', *args])
            trace = json.loads((out / (name + '-trace.json')).read_text())
            instances = [event for event in trace['traceEvents']
                         if event['name'] in ('InstantiateClass', 'InstantiateFunction')]
            result = dict(source=relative, template_lines=len(templates),
                          instantiations=len(instances), origins=sorted(origins),
                          preprocessed_bytes=len(text))
            assert not templates and not instances, result
            return result

        results = run_tasks([Task(str(source.relative_to(published)),
                                  lambda source=source, args=args: inspect(source, args))
                             for source, args in commands.items()])
        (out / 'audit.json').write_text(json.dumps([row.value for row in results], indent=2) + '\n')


if __name__ == '__main__':
    main()

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
    commands = {}
    for row in map(json.loads, options.commands.read_text().splitlines()):
        if row['phase'] != 'object_compile':
            continue
        args = row['arguments']
        source = Path(args[args.index('-c') + 1])
        if source.is_relative_to(published / 'units') or source == published / 'entry.cpp':
            commands[source] = args
    assert commands, 'No application compilation commands found'
    with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
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

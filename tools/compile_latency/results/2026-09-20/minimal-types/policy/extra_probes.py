#!/usr/bin/env python3
"""Additional native stress and private-helper dependency probes, after corpus runs."""
import argparse
import json
import subprocess
from pathlib import Path
from experiment import prepare, measure, write_changed
from run_corpus import regen, warm, verify


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--app', type=Path, required=True)
    parser.add_argument('--repo', type=Path, required=True)
    parser.add_argument('--out', type=Path, required=True)
    parser.add_argument('probe', choices=['stress', 'insertion', 'helper'])
    args = parser.parse_args()
    app, repo, out = args.app.resolve(), args.repo.resolve(), args.out.resolve()
    out.mkdir(parents=True, exist_ok=True)
    corpus = json.loads((app.parent / 'corpus.json').read_text())
    sources = [r['source'] for r in corpus[:12]]
    build = app / '.prism/build'
    manifest = json.loads((build / 'latency-manifest.json').read_text())
    if args.probe == 'stress':
        warm(app, 'latency-experiment.ninja')
        largest = sorted(manifest, key=lambda r: r['lines'], reverse=True)[:5]
        for repeat in range(5):
            for i, row in enumerate(largest):
                Path(row['cpp']).touch()
                label = f'largest-group-{i}-r{repeat}'
                measure(app, 'latency-experiment.ninja', label, out, [])
                verify(app, corpus, out, label)
            for row in largest[:2]:
                Path(row['cpp']).touch()
            measure(app, 'latency-experiment.ninja', f'two-groups-r{repeat}', out, [])
        (out / 'largest-groups.json').write_text(json.dumps(largest, indent=2))
        return
    row = corpus[0]
    source = row['source']
    path = app / source
    original = path.read_text()
    try:
        if args.probe == 'insertion':
            # An ordinary added local shifts diagnostics in every later method.
            changed_method = row['original'].replace('{\n', '{\n\t\t$latency_line_insert int = 0;\n', 1)
            path.write_text(original.replace(row['original'], changed_method))
            regen(repo, app, source)
            prepare(app, sources, 180)
            measure(app, 'latency-experiment.ninja', 'line-insertion', out, [])
            verify(app, corpus, out, 'line-insertion')
        else:
            helper_name = 'latency_assessment_private_helper'
            helper_source = 'latency_assessment_private_helper.phs'
            helper = app / helper_source
            if helper.exists():
                raise RuntimeError('Helper already exists')
            helper.write_text(f'function {helper_name}(): {row["type"]} {{\n\treturn {row["expression"]};\n}}\n')
            changed_method = row['original'].replace('return ' + row['expression'] + ';', f'return {helper_name}();')
            path.write_text(original.replace(row['original'], changed_method))
            regen(repo, app, source)
            regen(repo, app, helper_source)
            prepare(app, sources, 180)
            manifest = json.loads((build / 'latency-manifest.json').read_text())
            identity = row['owner'] + '::' + row['method']
            caller = next(r for r in manifest if identity in r['methods'])
            target = Path(caller['cpp'])
            target.write_text('#include "' + str(app / '.prism/generated' / Path(helper_source).with_suffix('.hpp')) + '"\n' + target.read_text())
            ninja = (build / 'latency-experiment.ninja').read_text()
            ninja = ninja.replace('build main: link ', 'build main: link __latency_private_helper.o ')
            ninja += f'\nbuild __latency_private_helper.o: compile_latency ../generated/{helper_name}.cpp | latency-project.pch runtime_signature.txt\n'
            write_changed(build / 'latency-helper.ninja', ninja)
            (out / 'helper-dependency-proof.json').write_text(json.dumps({'caller_method': identity, 'caller_object': caller['object'],
                'new_helper': helper_source, 'declaration_consumers': [caller['object'], '__latency_private_helper.o'],
                'policy': 'Keep stable declaration PCH unchanged; expose new helper only to its actual consumer.'}, indent=2))
            measure(app, 'latency-helper.ninja', 'add-private-helper', out, [])
            verify(app, corpus, out, 'add-private-helper')
    finally:
        path.write_text(original)
        regen(repo, app, source)
        prepare(app, sources, 180)
        if args.probe == 'helper':
            helper.unlink(missing_ok=True)
        warm(app, 'latency-experiment.ninja')
        verify(app, corpus, out, args.probe + '-restored')


if __name__ == '__main__':
    main()

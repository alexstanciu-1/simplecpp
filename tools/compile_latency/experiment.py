#!/usr/bin/env python3
"""Bounded, experimental generated-C++ partitioning and native-build measurement.

Works only in a copied application. It never edits a runtime or input repository.
Unsupported C++ shapes fail rather than guessing. No source semantics are removed.
"""
import argparse
import hashlib
import json
import re
import subprocess
import time
from pathlib import Path


def require_scratch(app):
    manifest_path = app.parent / 'manifest.json'
    if not manifest_path.is_file():
        raise ValueError('Expected an isolated assessment copy with manifest.json')
    manifest = json.loads(manifest_path.read_text())
    if app.resolve() == Path(manifest['source']).resolve():
        raise ValueError('Refusing to modify the original workload')


def write_changed(path, text):
    path.parent.mkdir(parents=True, exist_ok=True)
    if not path.exists() or path.read_text() != text:
        path.write_text(text)


def functions(text):
    prefix, sep, body = text.partition('namespace scpp {\n\tusing namespace ::scpp;\n')
    if not sep or not re.fullmatch(r'#include "[^"\n]+"\s*', prefix):
        raise ValueError('Expected one generated header and one scpp namespace')
    body = body.rstrip()
    if not body.endswith('}'):
        raise ValueError('Missing namespace closure')
    body = body[:-1].strip() + '\n'
    chunks = []
    offset = 0
    for end in re.finditer(r'^}\n', body, re.M):
        chunk = body[offset:end.end()].strip() + '\n'
        first = chunk.splitlines()[0]
        match = re.search(r'\b([A-Za-z_]\w*::(?:~?[A-Za-z_]\w*))\(', first)
        if not match or not first.endswith(' {'):
            raise ValueError('Unsupported top-level definition: ' + first)
        # Bounds intentionally exclude overloaded identities, free statics, templates,
        # namespace aliases, namespace-scope data and arbitrary native C++.
        chunks.append((match[1], chunk))
        offset = end.end()
    if body[offset:].strip() or not chunks or len({key for key, _ in chunks}) != len(chunks):
        raise ValueError('Unparsed data, no functions, or overloaded method identity')
    return chunks


def prepare(app, sources, lines, transform=None, publish_ninja=True, body_transform=None):
    require_scratch(app)
    build = app / '.prism/build'
    generated = app / '.prism/generated'
    baseline = build / 'latency-baseline.ninja'
    if not baseline.exists():
        baseline.write_text((build / 'build.ninja').read_text())
    ninja = baseline.read_text()
    if 'cxx_launcher =' in ninja:
        raise ValueError('Use a launcher-free baseline for native cache-miss assessment')
    mapping_file = build / 'latency-partitions.json'
    mapping = json.loads(mapping_file.read_text()) if mapping_file.exists() else {}
    replacements = {}
    added_edges = []
    manifest = []
    for source in sources:
        relative = Path(source).with_suffix('.cpp')
        original = generated / relative
        chunks = functions(original.read_text())
        if body_transform is not None:
            chunks = [(identity, body_transform(source, identity, body)) for identity, body in chunks]
        source_mapping = mapping.setdefault(source, {})
        # Retained allocated group IDs keep existing membership unchanged across edits.
        # On first allocation, group consecutive complete functions to a soft line cap.
        next_group = max(source_mapping.values(), default=-1) + 1
        group_lines = 0
        for identity, body in chunks:
            if identity in source_mapping:
                continue
            if group_lines >= lines:
                next_group += 1
                group_lines = 0
            source_mapping[identity] = next_group
            group_lines += len(body.splitlines())
        grouped = {}
        for identity, body in chunks:
            grouped.setdefault(source_mapping[identity], []).append((identity, body))
        objects = []
        for group, members in sorted(grouped.items()):
            target = generated / '__latency' / relative.with_suffix('') / f'part_{group}.cpp'
            contents = '#include "' + str(original.with_suffix('.hpp')) + '"\n\nnamespace scpp {\nusing namespace ::scpp;\n\n'
            contents += '\n'.join(body for _, body in members) + '\n}\n'
            if transform is not None:
                contents = transform(source, contents)
            write_changed(target, contents)
            obj = '__latency/' + str(relative.with_suffix('')) + f'/part_{group}.o'
            objects.append(obj)
            added_edges.append(f'build {obj}: compile_latency {target} | latency-project.pch runtime_signature.txt\n')
            manifest.append({'source': source, 'object': obj, 'cpp': str(target), 'methods': [k for k, _ in members], 'lines': len(contents.splitlines()), 'sha256': hashlib.sha256(contents.encode()).hexdigest()})
        old_obj = str(Path(source).with_suffix('.o'))
        pattern = r'^build ' + re.escape(old_obj) + r': compile [^\n]*\n(?:  [^\n]*\n)*'
        ninja, count = re.subn(pattern, '', ninja, flags=re.M)
        if count != 1:
            raise ValueError('Expected one baseline compile edge: ' + old_obj)
        replacements[old_obj] = objects
    # Replace only link input tokens; do not change unaffected object compile commands.
    def link_line(match):
        return ' '.join(item for token in match[0].split(' ') for item in replacements.get(token, [token]))
    ninja = re.sub(r'^build [^\n]+: link [^\n]+', link_line, ninja, flags=re.M)
    ninja += '''\nrule latency_pch
  command = $cxx $cxxflags -x c++-header -MD -MF $out.d $in -o $out
  depfile = $out.d
  deps = gcc
  description = PROJECT_PCH $out
rule compile_latency
  command = $cxx $cxxflags -include-pch latency-project.pch -MMD -MF $out.d -MT $out -c $in -o $out
  depfile = $out.d
  deps = gcc
  description = PART $out
build latency-project.pch: latency_pch ../generated/__project_units.hpp
'''
    ninja += ''.join(added_edges)
    if publish_ninja:
        write_changed(build / 'latency-experiment.ninja', ninja)
    write_changed(mapping_file, json.dumps(mapping, indent=2) + '\n')
    write_changed(build / 'latency-manifest.json', json.dumps(manifest, indent=2) + '\n')
    print(json.dumps({'sources': len(sources), 'partitions': len(manifest), 'native_budget_seconds': 8.5}))
    return ninja


def measure(app, config, label, out, targets, jobs=16):
    require_scratch(app)
    build = app / '.prism/build'
    command = ['ninja', '-f', config, f'-j{jobs}', *targets]
    ninja_log = build / '.ninja_log'
    before_log = ninja_log.read_text().splitlines() if ninja_log.exists() else []
    started = time.perf_counter()
    result = subprocess.run(command, cwd=build, text=True, stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
    elapsed = time.perf_counter() - started
    out.mkdir(parents=True, exist_ok=True)
    log = out / (label + '.log')
    log.write_text(result.stdout)
    row = {'label': label, 'config': config, 'command': command, 'native_wall_seconds': elapsed,
           'budget_seconds': 8.5, 'within_budget': result.returncode == 0 and elapsed <= 8.5, 'exit_code': result.returncode,
           'log': str(log), 'steps': result.stdout.count('\n')}
    after_log = ninja_log.read_text().splitlines() if ninja_log.exists() else []
    # Ninja can compact its persistent log at startup; retain new records then too.
    old_records = set(before_log)
    appended = after_log[len(before_log):] if after_log[:len(before_log)] == before_log else [line for line in after_log if line not in old_records]
    row['native_steps'] = [{'output': fields[3], 'start_ms': int(fields[0]), 'end_ms': int(fields[1]), 'elapsed_ms': int(fields[1]) - int(fields[0])}
                           for line in appended if len(fields := line.split('\t')) == 5]
    compiler_steps = [step for step in row['native_steps'] if step['output'].endswith(('.o', '.pch', '.gch'))]
    events = sorted(event for step in compiler_steps for event in [(step['start_ms'], 1), (step['end_ms'], -1)])
    active = peak = 0
    for _, delta in events:
        active += delta
        peak = max(peak, active)
    row['jobs_requested'] = jobs
    row['peak_compiler_jobs'] = peak
    row['compiler_work_seconds'] = sum(step['elapsed_ms'] for step in compiler_steps) / 1000
    with (out / 'measurements.jsonl').open('a') as handle:
        handle.write(json.dumps(row) + '\n')
    print(json.dumps(row), flush=True)
    if result.returncode:
        print(result.stdout[-5000:])
        raise SystemExit(result.returncode)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--app', type=Path, required=True)
    sub = parser.add_subparsers(dest='mode', required=True)
    p = sub.add_parser('prepare')
    p.add_argument('--source', action='append', required=True)
    p.add_argument('--lines', type=int, default=180)
    p = sub.add_parser('measure')
    p.add_argument('--config', default='build.ninja')
    p.add_argument('--label', required=True)
    p.add_argument('--out', type=Path, required=True)
    p.add_argument('targets', nargs='*')
    args = parser.parse_args()
    if args.mode == 'prepare':
        prepare(args.app.resolve(), args.source, args.lines)
    else:
        measure(args.app.resolve(), args.config, args.label, args.out.resolve(), args.targets)


if __name__ == '__main__':
    main()

#!/usr/bin/env python3
"""Real-probe/cache tests and scoped Rust/C++ preparation parity. No production writes."""
import argparse
import json
from pathlib import Path
import subprocess
import sys
import tempfile


def run(args, ok=True):
    result = subprocess.run(list(map(str, args)), capture_output=True, text=True)
    if ok:
        assert result.returncode == 0, result.stderr
    else:
        assert result.returncode == 1, result.stderr
        assert result.stderr and not result.stdout
    return result.stdout


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--rust', type=Path, required=True)
    parser.add_argument('--cpp', type=Path, required=True)
    parser.add_argument('--cxx', required=True)
    parser.add_argument('--flag', action='append', default=[])
    parser.add_argument('--work', type=Path, required=True)
    args = parser.parse_args()
    args.rust, args.cpp = args.rust.resolve(), args.cpp.resolve()
    source = Path(__file__).resolve().parents[2]
    driver = Path(__file__).with_name('probe.py')
    args.work.mkdir(parents=True, exist_ok=True)
    with tempfile.TemporaryDirectory(dir=args.work, prefix='g2-') as temporary:
        work = Path(temporary)
        # Copy the driver/source so source invalidation can be tested without editing working code.
        import shutil
        probe_source = work / 'source'
        probe_source.mkdir()
        for name in ['probe.py', 'probe.cpp']:
            shutil.copy2(driver.parent / name, probe_source / name)
        command = [sys.executable, '-B', probe_source / 'probe.py', '--cxx', args.cxx,
                   *['--flag=' + flag for flag in args.flag], '--out', work / 'native']
        facts_file, context_file = work / 'native/facts.json', work / 'native/context.json'
        assert 'Generated' in run(command)
        facts, context = json.loads(facts_file.read_text()), json.loads(context_file.read_text())
        assert json.loads(run([args.rust, 'validate-target', facts_file, context_file]))['status'] == 'valid'
        timestamps = [p.stat().st_mtime_ns for p in [facts_file, context_file, work / 'native/probe']]
        assert 'current' in run(command)
        assert timestamps == [p.stat().st_mtime_ns for p in [facts_file, context_file, work / 'native/probe']]
        assert 'Generated' in run(command + ['--flag=-DSCPP_CONTEXT_TEST=1'])
        changed_context = json.loads(context_file.read_text())
        assert changed_context['context_id'] != context['context_id']
        stale = work / 'stale.json'
        stale.write_text(json.dumps(facts))
        run([args.rust, 'validate-target', stale, context_file], ok=False)
        # Even an unused source comment changes the source fingerprint.
        with (probe_source / 'probe.cpp').open('a') as stream:
            stream.write('\n// context invalidation proof\n')
        assert 'Generated' in run(command)
        assert json.loads(context_file.read_text())['context_id'] != context['context_id']
        header = work / 'environment.hpp'
        header.write_text('inline constexpr unsigned scpp_probe_header_revision = 1;\n')
        header_command = command + ['--flag=-include', '--flag=' + str(header)]
        assert 'Generated' in run(header_command)
        header_context = json.loads(context_file.read_text())
        header.write_text('inline constexpr unsigned scpp_probe_header_revision = 2;\n')
        assert 'Generated' in run(header_command)
        assert json.loads(context_file.read_text())['context_id'] != header_context['context_id']
        facts_file.write_text('{}')
        assert 'Generated' in run(command)
        (work / 'native/cache.json').write_text('[]')
        assert 'Generated' in run(command)
        run(command + ['--cross-compiling', 'TRUE'], ok=False)
        run(command + ['--flag=--target=aarch64-linux-gnu'], ok=False)
        facts = json.loads(facts_file.read_text())
        assert facts['schema'] == 1
        assert facts['double']['digits'] == 53 and facts['double']['iec559']
        # Same-input C++ preparation oracle. Rust currently covers integer construction only.
        metadata = source / 'runtime/metadata/runtime.json'
        pub = json.loads((source / 'tests/generator_parity/compiler_publication.json').read_text())
        pub.update(types=['int8','int16','int32','int64','uint8','uint16','uint32','uint64'], values=[], operators=[], short_aliases=[])
        publication = work / 'publication.json'
        publication.write_text(json.dumps(pub))
        run([args.cpp, metadata, publication, work / 'cpp'])
        cpp = json.loads((work / 'cpp/prepared.json').read_text())
        rust = json.loads(run([args.rust, 'prepare-integer-construction', metadata, publication, facts_file, context_file]))
        for field in ['types', 'native_inputs', 'conversions']:
            assert cpp[field] == rust[field], field
        assert len(rust['conversions']) == 104
        # Reordering publication requests is not a physical-plan change.
        pub['types'].reverse()
        publication.write_text(json.dumps(pub))
        assert rust == json.loads(run([args.rust, 'prepare-integer-construction', metadata, publication, facts_file, context_file]))
        pub['values'] = ['bool_t']
        publication.write_text(json.dumps(pub))
        run([args.rust, 'prepare-integer-construction', metadata, publication, facts_file, context_file], ok=False)
        # A missing expected context must never fall back to host facts.
        run([args.rust, 'validate-target', facts_file, work / 'missing.json'], ok=False)
        # Compare measured native ranges and ownership storage with the existing ownership plan.
        run([args.cpp, metadata, source / 'tests/ownership/publication.json', work / 'owners'])
        owners = json.loads((work / 'owners/prepared.json').read_text())
        for native in cpp['native_inputs']:
            measured = facts['native_integers'][native['cpp']]
            assert (native['signed'], native['digits']) == (measured['signed'], measured['digits'])
        for record in owners['ownership']['records']:
            for family in record['families']:
                if family['family'] != 'value':
                    assert facts['ownership'][family['family']] == {'size': family['size'], 'alignment': family['alignment']}
        report = {'native_probe':'passed','cache_noop_and_invalidation':'passed','cross_target_rejection':'passed',
                  'integer_types':len(rust['types']), 'checked_conversions':len(rust['conversions']),
                  'cpp_projection_parity':'passed','ownership_native_fact_parity':'passed'}
        (args.work / 'summary.json').write_text(json.dumps(report, indent=2) + '\n')
        print(json.dumps(report))


if __name__ == '__main__':
    main()

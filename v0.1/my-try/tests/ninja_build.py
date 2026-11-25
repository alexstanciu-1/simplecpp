#!/usr/bin/env python3
"""Focused published-program Ninja integration; not a native compiler rebuild."""
import argparse
import json
from pathlib import Path
import shutil
import sys

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    out = parser.parse_args().results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    runtime = out / 'runtime'
    shutil.copytree(ROOT / 'runtime/include', runtime)
    provider = runtime / 'build_proof'
    provider.mkdir()
    (provider / 'provider.hpp').write_text('#pragma once\n#include "scpp/int_t.hpp"\nscpp::int_t<> optional_answer();\n')
    implementation = provider / 'provider.cpp'
    implementation.write_text('#include "provider.hpp"\nscpp::int_t<> optional_answer() { return scpp::int_t<>{7 + PROOF_BONUS}; }\n')
    catalogs = out / 'catalogs'
    catalogs.mkdir()
    shutil.copyfile(ROOT / 'runtime/metadata/modules/core.json', catalogs / 'core.json')
    schema = dict(version=1, module='build_proof', dependencies=['core'],
                  functions=[dict(id='proof.answer', name='optional_answer', parameters=[], returns='int')],
                  cpp=[dict(id='proof.answer', symbol='optional_answer', header='build_proof/provider.hpp')],
                  cpp_build=dict(sources=['core/runtime.cpp', 'build_proof/provider.cpp'],
                                 libraries=['m'], definitions=['PROOF_BONUS=1']))
    catalog_file = catalogs / 'build_proof.json'
    catalog_file.write_text(json.dumps(schema))
    source = out / 'source'
    source.mkdir()
    (source / 'main.phs').write_text('return answer();')
    helper = source / 'helper.phs'
    helper.write_text('function answer(): int { return optional_answer(); }')
    # Exercise Ninja escaping separately from POSIX shell quoting in real invocations.
    published = out / "published space $dollar:colon"
    build_dir = out / "build space $dollar:colon"
    observations = []
    with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
        def proof():
            def publish(name):
                runner.run(name + '-publish', ['php', APP / 'tests/fixtures/runtime_build_publish.php',
                           catalogs, source, published], cli_reason='Independent publication process')

            def build(name, expected_exit=8, options=(), directory=build_dir, expected_actions=None):
                command = ['php', APP / 'tools/build_ninja.php', published, directory,
                           '--runtime=' + str(runtime), '--jobs=2', '--pch', *options]
                result = json.loads(runner.run(name, command, timeout=300,
                                              cli_reason='Managed Ninja integration'))
                runner.run(name + '-execute', [result['executable']], expected=expected_exit)
                actions = sum('] CXX ' in line or '] LINK ' in line for line in result['output'].splitlines())
                if expected_actions is not None and actions != expected_actions:
                    raise RuntimeError(f'{name}: {actions} actions != {expected_actions}: {result["output"]}')
                observations.append(dict(name=name, actions=actions, **result))
                (out / 'observations.json').write_text(json.dumps(observations, indent=2))

            publish('cold')
            build('cold', expected_actions=6)  # PCH + two providers + helper + entry + link.
            before = {p: p.stat().st_mtime_ns for p in build_dir.rglob('*') if p.is_file()}
            build('warm', options=['--reuse'], expected_actions=0)
            for path, stamp in before.items():
                if path.name != '.scpp-ninja.status' and path.stat().st_mtime_ns != stamp:
                    raise RuntimeError('No-op rewrote ' + str(path))
            runner.run('reuse-required', ['php', APP / 'tools/build_ninja.php', published, build_dir],
                       expected=1, cli_reason='Explicit reuse admission')
            helper.write_text('function answer(): int { return optional_answer() + 1; }')
            publish('body')
            build('body', 9, ['--reuse'], expected_actions=2)
            extra = source / 'extra.phs'
            extra.write_text('function extra(): int { return 1; }')
            helper.write_text('function answer(): int { return optional_answer() + extra(); }')
            publish('add-unit')
            build('add-unit', 9, ['--reuse'], expected_actions=3)
            extra.unlink()
            helper.write_text('function answer(): int { return optional_answer() + 1; }')
            publish('remove-unit')
            build('remove-unit', 9, ['--reuse'], expected_actions=2)
            implementation.write_text(implementation.read_text().replace('7 +', '9 +'))
            build('provider', 11, ['--reuse'], expected_actions=2)
            # A linker-only recipe change must not rebuild objects or PCH.
            schema['cpp_build']['libraries'] = []
            catalog_file.write_text(json.dumps(schema))
            publish('libraries')
            build('libraries', 11, ['--reuse'], expected_actions=1)
            # Ordinary headers are tracked by compilation depfiles, without scan actions.
            (provider / 'provider.hpp').write_text((provider / 'provider.hpp').read_text() + '// header edit\n')
            build('header', 11, ['--reuse'], expected_actions=3)
            build('no-pch', 11, ['--no-pch'], out / 'no-pch', expected_actions=5)
            build('optimized', 11, ['-O2'], out / 'optimized', expected_actions=6)
            # PCH headers are real dependency inputs, not order-only prerequisites.
            pch_header = runtime / 'scpp/int_t.hpp'
            pch_header.write_text(pch_header.read_text() + '// PCH dependency edit\n')
            build('pch-header', 11, ['--reuse'], expected_actions=6)
            build('warm-after-pch', 11, ['--reuse'], expected_actions=0)
            # Reject unsupported depfile path syntax before executing Ninja.
            runner.run('unsupported-path', ['php', APP / 'tools/build_ninja.php', published,
                       out / "bad'quote", '--runtime=' + str(runtime)], expected=1,
                       cli_reason='Bounded depfile path support')
            # Compiler failure must leave readiness pending, and retry must recover.
            saved = implementation.read_text()
            implementation.write_text('this is invalid C++;\n')
            runner.run('failed-build', ['php', APP / 'tools/build_ninja.php', published, build_dir,
                       '--reuse', '--runtime=' + str(runtime), '--jobs=2', '--pch'], expected=1,
                       cli_reason='Failed native action and readiness')
            if (build_dir / '.scpp-ninja.status').read_text() != 'pending\n':
                raise RuntimeError('Failed build claimed readiness')
            implementation.write_text(saved)
            build('retry', 11, ['--reuse'], expected_actions=2)
            # Source publication validation remains before Ninja execution.
            entry = published / 'entry.cpp'
            entry.write_text(entry.read_text() + '// unauthorized edit\n')
            runner.run('tampered-publication', ['php', APP / 'tools/build_ninja.php', published, build_dir,
                       '--reuse', '--runtime=' + str(runtime)], expected=1,
                       cli_reason='Published digest validation')
            if (build_dir / '.scpp-ninja.status').read_text() != 'pending\n':
                raise RuntimeError('Tampered publication claimed readiness')
        run_tasks([Task('ninja-build', proof)])


if __name__ == '__main__':
    main()

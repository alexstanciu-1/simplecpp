#!/usr/bin/env python3
"""One optional provider through publication, native cache and PCH invalidation."""
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
    main_source = source / 'main.phs'
    main_source.write_text('return optional_answer();')
    published, cache = out / 'published', out / 'cache'
    observations = []
    with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
        def proof():
            def publish(name):
                runner.run(name + '-publish', ['php', APP / 'tests/fixtures/runtime_build_publish.php',
                           catalogs, source, published], cli_reason='Independent publication process')
            def build(name, expected_counts, exit_code):
                payload = runner.run(name, ['php', APP / 'tests/fixtures/native_cache_driver.php',
                    published, cache, runtime, '/usr/bin/clang++'], timeout=300,
                    cli_reason='Managed native provider build')
                result = json.loads(payload)
                actual = tuple(result[key] for key in ('compiled', 'pch_built', 'linked'))
                if actual != expected_counts:
                    raise RuntimeError(f'{name}: {actual} != {expected_counts}')
                runner.run(name + '-execute', [result['executable']], expected=exit_code)
                observations.append(dict(name=name, **result))
                (out / 'observations.json').write_text(json.dumps(observations, indent=2))
            publish('cold')
            requirements = json.loads((published / '.scpp-output.manifest').read_text())['runtime_build']
            if requirements['sources'] != ['core/runtime.cpp', 'build_proof/provider.cpp']:
                raise RuntimeError('Dependency provider sources were not deduplicated')
            # Native consumption must not reopen the compiler's module catalogs.
            catalog_file.rename(catalogs / 'saved.json')
            build('cold', (3, 1, 1), 8)
            build('warm', (0, 0, 0), 8)
            (catalogs / 'saved.json').rename(catalog_file)
            main_source.write_text('$value = optional_answer(); return $value + 1;')
            publish('body')
            build('body', (1, 0, 1), 9)
            implementation.write_text(implementation.read_text().replace('7 +', '9 +'))
            build('provider', (1, 0, 1), 11)
            schema['cpp_build']['definitions'] = ['PROOF_BONUS=2']
            catalog_file.write_text(json.dumps(schema))
            publish('configuration')
            build('configuration', (3, 1, 1), 12)
            schema['cpp_build']['libraries'] = []
            catalog_file.write_text(json.dumps(schema))
            publish('link-configuration')
            build('link-configuration', (0, 0, 1), 12)
        run_tasks([Task('runtime-module-build', proof)])


if __name__ == '__main__':
    main()

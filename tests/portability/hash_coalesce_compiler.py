"""Rebuild unchanged my-try and prove native publication after keyed coalesce lowering."""
import argparse
import hashlib
import json
from pathlib import Path
import shutil
import sys

ROOT = Path(__file__).resolve().parents[2]
APP = ROOT / 'compiler/my-try'
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner
from native_validate import php_literal


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    parser.add_argument('--resume', action='store_true')
    args = parser.parse_args()
    out = args.results.resolve()
    out.mkdir(parents=True, exist_ok=args.resume)
    (out / 'summary.json').unlink(missing_ok=True)
    logs = out / 'logs'
    index = 1
    while logs.exists():
        index += 1
        logs = out / f'logs-{index}'
    with CommandRunner(logs, ROOT) as runner:
        runner.run('host-publication', ['php', APP / 'tests/fixtures/output_publication.php', out / ('host-regression-' + logs.name)])
        runtime = out / 'runtime'
        shutil.copytree(ROOT / 'runtime/include', runtime, dirs_exist_ok=True)
        provider = runtime / 'build_proof'
        provider.mkdir(exist_ok=True)
        (provider / 'provider.hpp').write_text('#pragma once\n#include "scpp/int_t.hpp"\nscpp::int_t<> optional_answer();\n')
        (provider / 'provider.cpp').write_text('#include "provider.hpp"\nscpp::int_t<> optional_answer() { return scpp::int_t<>{7 + PROOF_BONUS}; }\n')
        catalogs = out / 'catalogs'
        catalogs.mkdir(exist_ok=True)
        shutil.copyfile(ROOT / 'runtime/metadata/modules/core.json', catalogs / 'core.json')
        requirements = dict(sources=['core/runtime.cpp', 'build_proof/provider.cpp'], libraries=['m'], definitions=['PROOF_BONUS=1'])
        schema = dict(version=1, module='build_proof', dependencies=['core'],
                      functions=[dict(id='proof.answer', name='optional_answer', parameters=[], returns='int')],
                      cpp=[dict(id='proof.answer', symbol='optional_answer', header='build_proof/provider.hpp')],
                      cpp_build=requirements)
        (catalogs / 'build_proof.json').write_text(json.dumps(schema))
        driver = (ROOT / 'tests/portability/fixtures/publication_driver.php.in').read_text().replace('__CATALOG__', php_literal(catalogs))
        source = out / 'source'
        source.mkdir(exist_ok=True)
        for stage in ['01_prepare_inputs', '02_tokenize', '03_parse', '04_analyze', '05_backend', '06_native', 'compiler']:
            shutil.copytree(APP / stage, source / stage, dirs_exist_ok=True)
        (source / 'main.php').write_text(driver)
        hashes = {str(p.relative_to(source)): hashlib.sha256(p.read_bytes()).hexdigest() for p in source.rglob('*.php')}
        (out / 'source_hashes.json').write_text(json.dumps(hashes, indent=2))
        project = out / 'phpp'
        tools = ROOT / 'tools/php_portability'
        cli = ROOT / 'bin/scpp.php'
        print('Converting and rebuilding native compiler...', flush=True)
        runner.run('convert', ['php', tools / 'convert.php', source, project])
        runner.run('framework', ['php', tools / 'install_native_runtime.php', project, '--filesystem', '--os', '--json'])
        if not (project / 'prism.json').exists():
            runner.run('init', ['php', cli, 'init', '--php-profile=strict'], project, cli_reason='CLI project setup')
        config = json.loads((project / 'prism.json').read_text())
        config['build']['cxx'] = 'clang++-18'
        config['runtime']['modules'] = ['compiler', 'filesystem', 'tasks', 'process', 'datetime', 'json']
        (project / 'prism.json').write_text(json.dumps(config, indent=2))
        runner.run('native-build', ['php', cli, 'build', '--build-runtime', '--no-stan'], project,
                   timeout=1200, cli_reason='CLI native compiler build')
        executable = project / '.prism/build/main'
        cpp = (project / '.prism/generated/compiler/output_publication.cpp').read_text()
        assert 'current.find(' in cpp and 'hashes.find(' in cpp
        assert (source / 'compiler/output_publication.php').read_bytes() == (APP / 'compiler/output_publication.php').read_bytes()
        print('Native compiler rebuilt; checking publication lifecycle...', flush=True)
        consumer = out / 'consumer'
        consumer.mkdir(exist_ok=True)
        (consumer / 'main.phs').write_text('return optional_answer();')
        (consumer / 'request.txt').write_text(str(consumer))
        php_driver = out / 'emit.php'
        php_driver.write_text(driver.replace('namespace scpp\\compiler;', 'namespace scpp\\compiler;\nrequire ' + php_literal(APP / 'boot.php') + ';', 1))
        def invoke(mode, stage, published, action, error=None):
            (consumer / 'mode.txt').write_text(action)
            (consumer / 'output.txt').write_text(str(published))
            command = ['php', php_driver] if mode == 'php' else [executable]
            result = runner.run(mode + '-' + stage, command, consumer)
            if error is None:
                assert result == b'OK\n', (mode, stage, result)
            else:
                assert result.startswith(b'ERROR:') and error.encode() in result, (mode, stage, result)
            return result

        def bytes_snapshot(directory):
            return {str(p.relative_to(directory)): p.read_bytes() for p in directory.rglob('*')
                    if p.is_file() and p.name != '.scpp-output.lock'}

        def stable_snapshot(directory):
            names = [r['path'] for r in json.loads((directory / '.scpp-output.manifest').read_text())['files']]
            return {name: ((directory / name).read_bytes(), (directory / name).stat().st_ino,
                           (directory / name).stat().st_mtime_ns) for name in names + ['.scpp-output.manifest']}

        observations = {}
        for mode in ['php', 'native']:
            published = out / (mode + '-lifecycle-' + logs.name)
            seen = {}
            invoke(mode, 'fresh', published, 'first')
            seen['fresh'] = bytes_snapshot(published)
            before = stable_snapshot(published)
            invoke(mode, 'warm', published, 'first')
            assert stable_snapshot(published) == before
            (published / 'notes.txt').write_text('user-owned')
            invoke(mode, 'changed-added-retired', published, 'second')
            assert not (published / 'old.cpp').exists()
            assert (published / 'entry.cpp').read_text() == 'second'
            assert (published / 'new.cpp').read_text() == 'added'
            assert stable_snapshot(published)['decl.hpp'] == before['decl.hpp']
            seen['second'] = bytes_snapshot(published)
            (published / 'collision.cpp').write_text('owned by user')
            invoke(mode, 'collision', published, 'collision', 'Refusing to adopt unowned output')
            assert (published / 'collision.cpp').read_text() == 'owned by user'
            invoke(mode, 'collision-retry', published, 'second')
            (published / 'entry.cpp').write_text('user edit')
            for attempt in range(2):
                invoke(mode, 'edited-' + str(attempt), published, 'second', 'Refusing to replace edited output')
                assert (published / 'entry.cpp').read_text() == 'user edit'
            (published / 'entry.cpp').write_text('second')
            (published / 'decl.hpp').unlink()
            invoke(mode, 'restore-missing', published, 'second')
            assert (published / 'decl.hpp').read_text() == 'stable'
            second = {'entry.cpp': 'second', 'decl.hpp': 'stable', 'new.cpp': 'added'}
            digest = lambda text: hashlib.sha256(text.encode()).hexdigest()
            for replaced in [False, True]:
                rows = [dict(path=name, before=digest(text), after='' if name == 'new.cpp' else digest('partial'))
                        for name, text in second.items()]
                rows.append(dict(path='partial.cpp', before='', after=digest('partial')))
                journal = json.dumps(dict(format='scpp-output-journal-v1', files=rows), separators=(',', ':'))
                (published / '.scpp-output.status').write_text('scpp-output-v1:pending:' + digest(journal) + '\n' + journal)
                (published / 'entry.cpp').write_text('partial')
                (published / 'partial.cpp').write_text('partial')
                (published / 'new.cpp').unlink()
                if replaced:
                    (published / '.scpp-output.manifest').write_text('{interrupted manifest revision}')
                invoke(mode, 'recover-' + str(replaced), published, 'second')
                assert not (published / 'partial.cpp').exists()
                assert all((published / name).read_text() == text for name, text in second.items())
            seen['recovered'] = bytes_snapshot(published)
            assert (published / 'notes.txt').read_text() == 'user-owned'
            observations[mode] = seen
        assert observations['php'] == observations['native'], 'Publication lifecycle bytes differ'
        provider_snapshots = []
        for mode in ['php', 'native']:
            published = out / (mode + '-provider-' + logs.name)
            invoke(mode, 'provider', published, 'provider')
            manifest = json.loads((published / '.scpp-output.manifest').read_text())
            assert manifest['runtime_build'] == requirements
            provider_snapshots.append(bytes_snapshot(published))
            payload = runner.run(mode + '-provider-build', ['php', APP / 'tests/fixtures/native_cache_driver.php',
                published, out / (mode + '-cache-' + logs.name), runtime, '/usr/bin/clang++-18'],
                timeout=300, cli_reason='Managed native provider build')
            built = json.loads(payload)
            runner.run(mode + '-provider-execute', [built['executable']], expected=8)
        assert provider_snapshots[0] == provider_snapshots[1], 'Provider publication bytes differ'
        for name, digest in hashes.items():
            if name != 'main.php':
                assert hashlib.sha256((APP / name).read_bytes()).hexdigest() == digest, name
        (out / 'summary.json').write_text(json.dumps(dict(passed=True,
            compiler_sources_unchanged=True, native_compiler_sha256=hashlib.sha256(executable.read_bytes()).hexdigest(),
            lifecycle_bytes_identical=True, warm_identity_preserved=True, recovery_states=2,
            provider_publications_identical=True, provider_execution_exit=8,
            stan='skipped', llvm='skipped'), indent=2))
    print('Native publication coalescing: PASS')


if __name__ == '__main__':
    main()

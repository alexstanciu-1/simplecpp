"""Rebuild my-try; prove split-based build validation and a provider consumer."""
import argparse
import hashlib
import json
from pathlib import Path
import shutil
import sys

ROOT = Path(__file__).resolve().parents[2]
APP = ROOT / 'compiler/my-try'
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
from native_validate import php_literal
from cpp_artifacts import materialize_artifacts


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
        direct = (ROOT / 'tests/portability/fixtures/string_split_contract.php').read_text().split('namespace scpp\\compiler;', 1)[1]
        host = out / 'direct.php'
        host.write_text('<?php\nnamespace scpp\\compiler;\nrequire ' + php_literal(APP / 'boot.php') + ';\n' + direct)
        run_tasks([
            Task('direct-contract', lambda: runner.run('direct-contract', ['php', host])),
            Task('retained-contract', lambda: runner.run('retained-contract', ['php', APP / 'tests/runtime_build_contract.php']))
        ], jobs=12)
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
        driver = '<?php\nnamespace scpp\\compiler;\n' + direct + '\n' + '''
$runtime_modules /** vector<string> */ = ['build_proof'];
Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load(__CATALOG__, $runtime_modules));
$paths /** vector<string> */ = [fs_read_text('request.txt')];
$compiler = new Compiler();
$compiler->init($paths);
$compiler->select_entry('main', 'main.phs');
$compiler->exec_cpp();
$first = CPP_Output::encode(Model::$cpp_files);
$compiler->prepare();
$compiler->cpp();
if (CPP_Output::encode(Model::$cpp_files) !== $first) { throw new \\LogicException('Repeated output changed'); }
echo Runtime_Build_Contract::encode(CPP_Runtime_Build::requirements(Model::$runtime_metadata)), "\\n";
echo $first;
'''.replace('__CATALOG__', php_literal(catalogs))
        source = out / 'source'
        source.mkdir(exist_ok=True)
        for stage in ['01_prepare_inputs', '02_tokenize', '03_parse', '04_analyze', '05_backend', '06_native', 'compiler']:
            shutil.copytree(APP / stage, source / stage, dirs_exist_ok=True)
        (source / 'main.php').write_text(driver)
        migrated = Path('compiler/runtime_catalog/build.php')
        text = (source / migrated).read_text()
        assert text.count('q_explode(') == 2 and 'split_byte' not in text
        hashes = {str(p.relative_to(source)): hashlib.sha256(p.read_bytes()).hexdigest() for p in source.rglob('*.php')}
        (out / 'source_hashes.json').write_text(json.dumps(hashes, indent=2))
        project = out / 'phpp'
        tools = ROOT / 'tools/php_portability'
        cli = ROOT / 'bin/scpp.php'
        print('Converting and rebuilding native compiler...', flush=True)
        runner.run('convert', ['php', tools / 'convert.php', source, project])
        phs = (project / migrated.with_suffix('.phs')).read_text()
        assert phs.count('explode(') == 2 and 'q_explode' not in phs and 'split_byte' not in phs
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
        cpp = (project / '.prism/generated' / migrated.with_suffix('.cpp')).read_text()
        assert cpp.count('php::explode(') == 2 and 'split_byte' not in cpp
        print('Native compiler rebuilt; checking contract and provider emission parity...', flush=True)
        consumer = out / 'consumer'
        consumer.mkdir(exist_ok=True)
        (consumer / 'main.phs').write_text('return optional_answer();')
        (consumer / 'request.txt').write_text(str(consumer))
        php_driver = out / 'emit.php'
        php_driver.write_text(driver.replace('namespace scpp\\compiler;', 'namespace scpp\\compiler;\nrequire ' + php_literal(APP / 'boot.php') + ';', 1))
        outputs = []
        encoded_requirements = []
        for mode in ['php', 'native']:
            command = ['php', php_driver] if mode == 'php' else [executable]
            payload = runner.run(mode + '-emit', command, consumer)
            encoded, artifacts = payload.split(b'\n', 1)
            build = json.loads(encoded)
            assert build == requirements, build
            outputs.append(artifacts)
            encoded_requirements.append(encoded)
            # Consume the actual native/host artifact bundle and declared provider inputs.
            # Native publication lifecycle is covered separately by hash_coalesce_compiler.py.
            # This proof stays focused on splitting and emitted provider requirements.
            directory = out / (mode + '-artifacts-' + logs.name)
            units = materialize_artifacts(artifacts, directory)
            program = out / (mode + '-program')
            runner.run(mode + '-provider-build', ['clang++-18', '-std=c++23',
                '-DSCPP_LANGUAGE_TARGET_PHP=1', '-pthread', '-I', runtime, '-I', directory,
                *['-D' + definition for definition in build['definitions']],
                *units, *[runtime / name for name in build['sources']],
                *['-l' + name for name in build['libraries']], '-o', program], timeout=300)
            runner.run(mode + '-execute', [program], expected=8)
        assert outputs[0] == outputs[1], 'PHP/native compiler artifacts differ'
        assert encoded_requirements[0] == encoded_requirements[1], 'PHP/native build encoding differs'
        assert (source / migrated).read_bytes() == (APP / migrated).read_bytes()
        (out / 'summary.json').write_text(json.dumps(dict(passed=True, direct_invalid_cases=15,
            exact_encoding=True, ordered_merge=True, native_compiler_sha256=hashlib.sha256(executable.read_bytes()).hexdigest(),
            provider_execution_exit=8, identical_artifacts=True, identical_build_encoding=True,
            native_publication='covered separately by hash_coalesce_compiler.py',
            stan='skipped', llvm='skipped'), indent=2))
    print('Typed string split compiler integration: PASS')


if __name__ == '__main__':
    main()

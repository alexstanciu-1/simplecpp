"""Rebuild my-try and prove the body-preparation integer method default."""
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
from cpp_fixture import CppFixtureCompiler
from cpp_artifacts import probe_text
from native_validate import php_literal



def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    parser.add_argument('--baseline', type=Path, required=True)
    parser.add_argument('--resume', action='store_true')
    args = parser.parse_args()
    out = args.results.resolve(); out.mkdir(parents=True, exist_ok=args.resume)
    (out / 'summary.json').unlink(missing_ok=True)
    logs = out / 'logs'; index = 1
    while logs.exists():
        index += 1; logs = out / f'logs-{index}'
    with CommandRunner(logs, ROOT) as r:
        def host(suite):
            folder = out / suite; folder.mkdir(exist_ok=True)
            r.run(suite, ['php', APP / 'tests' / f'{suite}.php', folder])
            baseline = args.baseline.resolve() / suite
            actual = {p.name: p.read_bytes() for p in folder.glob('*.cpp')}
            expected = {p.name: p.read_bytes() for p in baseline.glob('*.cpp')}
            assert actual == expected, suite
            assert actual, 'Consumer suite must publish comparison artifacts'
            return len(actual)
        counts = {}
        def check_suite(suite):
            counts[suite] = host(suite)
        run_tasks([Task(s, lambda s=s: check_suite(s)) for s in ['control_flow', 'constructors', 'inheritance']], jobs=12)
        source = out / 'source'; source.mkdir(exist_ok=True)
        for stage in ['01_prepare_inputs', '02_tokenize', '03_parse', '04_analyze', '05_backend', '06_native', 'compiler']:
            shutil.copytree(APP / stage, source / stage, dirs_exist_ok=True)
        migrated = ['04_analyze/prepare/semantics/bodies.php']
        for name in migrated:
            assert 'int $start = 0' in (source / name).read_text()
        direct = (ROOT / 'tests/portability/fixtures/method_default_body.php').read_text().split('namespace scpp\\compiler;', 1)[1]
        direct_host = out / 'direct.php'
        direct_host.write_text('<?php\nnamespace scpp\\compiler;\nrequire ' + php_literal(APP / 'boot.php') + ';\n' + direct)
        r.run('direct-php', ['php', direct_host])
        driver = (APP / 'tests/native_driver.php.in').read_text()
        driver = driver.replace('namespace scpp\\compiler;', 'namespace scpp\\compiler;\n' + direct, 1)
        for key, value in {'__REQUEST_FILE__': "'request.txt'", '__RUNTIME_CATALOG_DIR__': php_literal(ROOT / 'runtime/metadata/modules'),
                           '__RECOVERY_DIR__': php_literal(out / 'recovery'), '__PIPELINE_DIR__': php_literal(out / 'pipeline'),
                           '__RUN_LLVM_PROOFS__': 'false'}.items():
            driver = driver.replace(key, value)
        (source / 'main.php').write_text(driver)
        for name in ['s2s_proof.php', 'cpp_probe.php']:
            shutil.copyfile(APP / 'tests' / name, source / name)
        hashes = {str(p.relative_to(source)): hashlib.sha256(p.read_bytes()).hexdigest() for p in source.rglob('*.php')}
        (out / 'source_hashes.json').write_text(json.dumps(hashes, indent=2))
        project = out / 'phpp'; tools = ROOT / 'tools/php_portability'; cli = ROOT / 'bin/scpp.php'
        print('Converting and rebuilding native compiler...', flush=True)
        r.run('convert', ['php', tools / 'convert.php', source, project])
        for name in migrated:
            assert 'int $start = 0' in (project / Path(name).with_suffix('.phs')).read_text()
        r.run('framework', ['php', tools / 'install_native_runtime.php', project, '--filesystem', '--os', '--json'])
        if not (project / 'prism.json').exists():
            r.run('init', ['php', cli, 'init', '--php-profile=strict'], project, cli_reason='CLI project setup')
        config = json.loads((project / 'prism.json').read_text())
        config['build']['cxx'] = 'clang++-18'
        config['runtime']['modules'] = ['compiler', 'filesystem', 'tasks', 'process', 'datetime', 'json']
        (project / 'prism.json').write_text(json.dumps(config, indent=2))
        r.run('native-build', ['php', cli, 'build', '--build-runtime', '--no-stan'], project,
              timeout=1200, cli_reason='CLI native compiler build')
        executable = project / '.prism/build/main'
        print('Native compiler rebuilt; checking body preparation and constructor-skip parity...', flush=True)
        cases = {
            'ordinary': ('function answer(): int { $x = 3; $x += 4; return $x; } return answer();', 7),
            'block': ('function answer(): int { $x = 2; if (true) { $x += 7; } return $x; } return answer();', 9),
            'parent_once': ('class Log { static int $n = 0; static function next(): int { self::$n += 1; return self::$n; } } class B { int $n; function __construct() { $this->n = Log::next(); } } class C extends B { function __construct() { parent::__construct(); $this->n = $this->n * 10 + Log::next(); } } $c = new C(); return $c->n;', 12),
        }
        probe = out / 'emit.php'
        probe.write_text('<?php\nnamespace scpp\\compiler;\nrequire ' + php_literal(APP / 'boot.php') + ';\n' +
                         '$c = new Compiler(); $c->init([$argv[1]]); $c->exec_cpp(); echo CPP_Output::encode(Model::$cpp_files);\n')
        compiler = CppFixtureCompiler(r, ROOT, out, pch=False, runtime=True, cxx='clang++-18')
        def check(name, data):
            text, expected = data
            folder = out / name; folder.mkdir(exist_ok=True)
            (folder / 'main.phs').write_text(text)
            (folder / 'request.txt').write_text('s2s:' + str(folder))
            host_output = r.run(name + '-php', ['php', probe, folder])
            native_output = r.run(name + '-native', [executable], folder)
            assert native_output == host_output, name
            cpp = folder / 'program.cpp'; cpp.write_text(probe_text(native_output))
            program = folder / 'program'; compiler.compile(name + '-compile', cpp, program)
            r.run(name + '-execute', [program], expected=expected)
        run_tasks([Task(n, lambda n=n, d=d: check(n, d)) for n, d in cases.items()], jobs=12)
        for name in migrated:
            assert (source / name).read_bytes() == (APP / name).read_bytes()
            cpp = (project / '.prism/generated' / Path(name).with_suffix('.cpp')).read_text()
            signature = next(line for line in cpp.splitlines() if 'Body_Preparation::prepare_statements(' in line and line.startswith('shared_p'))
            assert '=' not in signature, signature
            header = (project / '.prism/generated' / Path(name).with_suffix('.hpp')).read_text()
            assert 'start = static_cast<int_t<> >(0)' in header
        (out / 'summary.json').write_text(json.dumps(dict(passed=True, baseline_artifacts=counts,
            native_compiler_sha256=hashlib.sha256(executable.read_bytes()).hexdigest(), native_parity_cases=len(cases),
            generated_executions=len(cases), stan='skipped', llvm='skipped'), indent=2))
    print('Integer method default compiler integration: PASS')


if __name__ == '__main__':
    main()

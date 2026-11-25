"""Prove the adopted update context through PHP, local conversion and native PHP++."""
import argparse
import importlib.util
import json
from pathlib import Path
import shutil
import subprocess
import tempfile
import time

ROOT = Path(__file__).resolve().parents[3]
EXPECTED = 'initial=0\nshared=1\nnext=0\nprevious=1\nrebound=0\nretained=1\ndefault=1\n0:0\ntoken-shared=1\n7:3\nindependent=1\ncreated=1\nfinished=1\n'

EXPECTED += 'a/b\na/b\n/x\n/x\na/\n0\n0\n1\n1\n0\n0\n0\n0\n0\n1\n0\n1\n0\n1\n1\n1\n0\n0\n0\n0\n1\n1\n0\n0\n0\n0\n'

EXPECTED += '0\n1\ncdef::\nbyte=1\n'

EXPECTED += '4:2:2\n'

EXPECTED += 'node-default=1\n0:0:0:0\n12:9:3\n10:2\nnode-independent=1\n'

EXPECTED += '1:2:3:4\n5:6:7\n8:9\n1:2:0\n2:3\n4:5:0\n6:7\n8:0\n9:10\n11:12:13\nreference-absent=1\nreference-present=1\n6\n'

EXPECTED += 'reference-taken=1\nreference-kind=1\n'

EXPECTED += 'cursor-default=1\n0:0:0:0\n21:20:1\noperator=1\n10:90\n30:31:0\n0:2\n'

EXPECTED += '1:/source.phs:100:4\nsnapshot-shared=1\nsnapshot-version=1\nsnapshot-bytes=1\n1\n0:0:0\n4:4\n4:9\n1:2\n'

EXPECTED += 'snapshot-distinct=1\n'

EXPECTED += '0\n1\n0\n0\n1\n1\n0\n0\n1\n0\n0\n1\n0\n0\n1\n0\n0\n1\n0\n0\n1\n0\n0\n1\n0\n0\n1\n0\n0\n1\n0\n0\n1\n0\n0\n0\n195:169:-1:-1\n'


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--target-checkout', type=Path, required=True)
    parser.add_argument('--results', type=Path, required=True)
    args = parser.parse_args()
    results = args.results.resolve()
    if results.exists():
        raise SystemExit('Use a fresh evidence directory.')
    target = json.loads((ROOT / 'compiler/tools/portability_target.json').read_text())
    checkout = args.target_checkout.resolve()
    def git(*parts):
        return subprocess.check_output(['git', *parts], cwd=checkout, text=True).strip()
    assert git('rev-parse', 'HEAD') == target['verified_commit']
    assert git('status', '--porcelain') == ''
    results.mkdir(parents=True)
    work = Path(tempfile.mkdtemp(prefix='scpp-context-port-'))
    source, output = work / 'php', work / 'phpp'
    source.mkdir()
    report = {'workspace': str(work), 'target_revision': target['verified_commit'], 'commands': []}

    def run(label, cmd, cwd=ROOT, ok=True):
        started = time.monotonic()
        proc = subprocess.run(cmd, cwd=cwd, text=True, capture_output=True)
        (results / (label + '.stdout.log')).write_text(proc.stdout)
        (results / (label + '.stderr.log')).write_text(proc.stderr)
        report['commands'].append({'label': label, 'command': [str(x) for x in cmd], 'cwd': str(cwd), 'exit_code': proc.returncode, 'seconds': round(time.monotonic() - started, 3)})
        (results / 'summary.json').write_text(json.dumps(report, indent=2) + '\n')
        assert (proc.returncode == 0) == ok, (label, proc.stdout, proc.stderr)
        return proc

    # The component stays owned in compiler/; staging is a disposable source set,
    # not a parallel maintained implementation or an expansion of directory CLI scope.
    selection = json.loads((ROOT / 'compiler/portability.json').read_text())
    assert selection['schema_version'] == 1 and selection['source_root'] == '.'
    for relative in selection['files']:
        p = Path(relative)
        assert not p.is_absolute() and '..' not in p.parts and p.suffix == '.php'
        dst = source / p
        dst.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(ROOT / 'compiler' / p, dst)
    shutil.copy2(Path(__file__).parent / 'main.php', source / 'main.php')
    tools = ROOT / 'tools/php_portability'
    run('imports', ['php', str(tools / 'sync_imports.php'), str(source), '--check'])
    php = run('php', ['php', '-r', 'foreach (array_slice($argv, 1) as $file) { require $file; }',
        str(tools / 'runtime/bootstrap.php'),
        *[str(ROOT / 'compiler' / relative) for relative in selection['files']], str(source / 'main.php')])
    assert php.stdout == EXPECTED
    readonly = run('php-readonly', ['php', '-r',
        'require $argv[1]; $view = new parse\\function_parts(1,2,3,4); '
        'try { $view->name_id=9; } catch (Error $error) { echo "PHP readonly enforced"; exit(0); } exit(1);',
        str(ROOT / 'compiler/bootstrap.php')])
    assert readonly.stdout == 'PHP readonly enforced'
    contract = run('step-contract', ['php', '-r',
        'require $argv[1]; $out = []; foreach (["Step_Result", "Step_Store", "Step", "Runnable_Step", "Store_Providing_Step"] as $name) { '
        '$r = new ReflectionClass("compile" . chr(92) . $name); $methods = []; foreach ($r->getMethods() as $m) { '
        '$methods[$m->getName()] = [(string)$m->getReturnType(), $m->getNumberOfParameters(), $m->isPublic()]; } '
        '$out[$name] = [$r->isInterface(), $methods]; } echo json_encode($out);',
        str(ROOT / 'compiler/src/compile/step.php')])
    actual = json.loads(contract.stdout)
    expected_methods = {
        'Step_Result': [], 'Step_Store': [],
        'Step': {'init': ['void', 0, True], 'finalize': ['void', 0, True],
                 'result': ['compile\\Step_Result', 0, True], 'status': ['compile\\step_status', 0, True],
                 'supports_run': ['bool', 0, True]},
        'Runnable_Step': {'run': ['void', 0, True]},
        'Store_Providing_Step': {'store': ['compile\\Step_Store', 0, True]},
    }
    assert actual == {name: [True, methods] for name, methods in expected_methods.items()}, actual
    convert = ['php', str(tools / 'convert.php'), str(source), str(output)]
    assert json.loads(run('convert', convert).stdout)['converted'] == len(selection['files']) + 1
    generated = output / 'src/compile/state.phs'
    stamp = generated.stat().st_mtime_ns
    assert json.loads(run('reuse', convert).stdout) == {'converted': 0, 'reused': len(selection['files']) + 1, 'removed': 0}
    assert generated.stat().st_mtime_ns == stamp
    assert 'namespace compile;' in generated.read_text() and 'declare(' not in generated.read_text()
    before = (output / '.scpp-portability.json').read_bytes()
    rejects = [
        'class Bad { public function __construct(public readonly array $items) {} }',
        'class Bad { public function __construct(public readonly int $n) { echo $n; } }',
        'class Bad { public function __construct(int $n) {} }',
        'class Bad { public array $values = []; }',
        'class Bad { public array $values /** vector<mixed> */ = []; }',
        'class Bad { public array $values /** vector<int> */ = [1]; }',
        r'echo "\xA9";',
        r'echo "\251";',
        r'echo "a\0b";',
        r'echo "\u{e9}";',
        r'class Bad { public string $bytes = "\xA9"; }',
        'class Bad { public static function f($x): bool { return true; } }',
        'class Bad { public static function f(string $x = "x"): bool { return true; } }',
        'class Bad { public static function f(string &$x): bool { return true; } }',
        'class Bad { public static function f(): mixed { return true; } }',
        '$x = str_starts_with("x");',
        'interface Bad { public function f($arg): bool; }',
        'interface Bad { public function f(): bool {} }',
        'interface Bad extends Other {}',
        'interface Bad { public function f(): mixed; }',
        'enum Bad { case a = 1; }',
        'enum Bad: int { case a; }',
        'enum Bad: string { case a = "a"; }',
        'enum Bad: int { case a = 1 + 2; }',
        'enum Bad: int { case a = -1; }',
        'enum Bad: int { public function f(): bool { return true; } }',
        '$a = Kind::$dynamic;',
        'class Bad { public Kind $kind = Kind::method(); }',
        'class Bad { public function method(): bool { return false; } }',
        'class Bad { public static bool $value = false; }',
        'class Bad { private bool $value = false; }',
        'class Bad { public ?bool $value = null; }',
        'class Bad { public bool $value; }',
        'class Bad extends Other {}',
        '$name = "x"; $object->$name = true;',
        '$object->method();',
        '$object = new $name();',
    ]
    block = (source / 'main.php').read_text().split('// </scpp-imports>')[0] + '// </scpp-imports>\n'
    for i, body in enumerate(rejects):
        (source / 'bad.php').write_text(block + body + '\n')
        failure = run('reject-' + str(i), convert, ok=False)
        assert 'bad.php:' in failure.stderr
        assert (output / '.scpp-portability.json').read_bytes() == before
    (source / 'bad.php').unlink()
    # Existing compiler proof exercises real request decisions after the import edit.
    spec = importlib.util.spec_from_file_location('compiler_tests', ROOT / 'compiler/tests/run.py')
    compiler_tests = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(compiler_tests)
    for name in ['compile/compile_driver.php', 'compile/incremental_policy.php', '02_tokenize/tokenization.php', '02_tokenize/variable_tokens.php', '02_tokenize/lexical_updates.php', '01_prepare_inputs/read_sources/source_paths.php', '01_prepare_inputs/read_sources/source_discovery.php', '01_prepare_inputs/read_sources/source_scan_tasks.php', '01_prepare_inputs/read_sources/source_snapshots.php', '03_parse/parsing.php', '03_parse/parameter_parsing.php', '03_parse/struct_parsing.php', '03_parse/frontend_storage.php', '03_parse/parse_updates.php', '03_parse/metaprogramming_parsing.php', 'features/integer_conversions.php']:
        result = compiler_tests.run_fixture(name, 180)
        label = Path(name).stem
        (results / (label + '.stdout.log')).write_text(result.stdout)
        (results / (label + '.stderr.log')).write_text(result.stderr)
        assert result.returncode == 0, (name, result.stderr)
    report['compiler_fixtures'] = ['compile/compile_driver.php', 'compile/incremental_policy.php', '02_tokenize/tokenization.php', '02_tokenize/variable_tokens.php', '02_tokenize/lexical_updates.php', '01_prepare_inputs/read_sources/source_paths.php', '01_prepare_inputs/read_sources/source_discovery.php', '01_prepare_inputs/read_sources/source_scan_tasks.php', '01_prepare_inputs/read_sources/source_snapshots.php', '03_parse/parsing.php', '03_parse/parameter_parsing.php', '03_parse/struct_parsing.php', '03_parse/frontend_storage.php', '03_parse/parse_updates.php', '03_parse/metaprogramming_parsing.php', 'features/integer_conversions.php']
    cli = str(checkout / target['cli'])
    run('init', ['php', cli, 'init', '--php-profile=strict'], cwd=output)
    config = json.loads((output / 'prism.json').read_text())
    config['build']['cxx'] = 'clang++-18'
    config['runtime']['modules'] = []
    (output / 'prism.json').write_text(json.dumps(config, indent=2) + '\n')
    # init creates main.phs only if absent; verify converter-owned entry survives.
    assert (output / 'main.phs').read_text().find('new \\compile\\Update_Context()') >= 0
    native = run('native', ['php', cli, 'run', '--build-runtime'], cwd=output)
    assert native.stdout.endswith(EXPECTED), native.stdout
    report.update(passed=True, expected_stdout=EXPECTED, target_clean=git('status', '--porcelain') == '')
    assert report['target_clean']
    (results / 'summary.json').write_text(json.dumps(report, indent=2) + '\n')
    shutil.copy2(generated, results / 'state.phs')
    for relative in selection['files']:
        generated_path = Path(relative).with_suffix('.phs')
        saved = results / 'generated' / generated_path
        saved.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(output / generated_path, saved)
    shutil.copy2(__file__, results / 'runner.py')
    shutil.copy2(source / 'main.php', results / 'main.php')
    print('Adopted compiler components: PHP/native identity and independent-update behavior passed; compiler fixtures and rejection checks passed.')


if __name__ == '__main__':
    main()

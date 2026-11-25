"""Locally typed string-hash coalescing: values, effects, purity and access context."""
import argparse
import hashlib
import json
from pathlib import Path
import shutil
import sys

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'compiler/my-try/tools'))
from test_runner import CommandRunner
from cpp_fixture import CppFixtureCompiler


def snapshot(path):
    return {str(p.relative_to(path)): (p.read_bytes(), p.stat().st_mtime_ns)
            for p in path.rglob('*') if p.is_file()}


def check_mixed_regressions(runner, project, cli):
    for name in ['nullable_004_mixed_null_coalesce_basic', 'nullable_005_mixed_null_coalesce_chain']:
        fixture = ROOT / 'tests/php/types/nullable/level_02' / name
        shutil.copyfile(fixture.with_suffix('.phs'), project / 'main.phs')
        expected = json.loads(fixture.with_suffix('.test-info.json').read_text())['expect']['run']
        runner.run(name + '-build', ['php', cli, 'build', '--no-stan'], project,
                   timeout=120, cli_reason='CLI build of retained mixed coalescing regression')
        actual = runner.run(name + '-execute', [project / '.prism/build/main'], project, expected=expected['exit_code'])
        assert actual == expected['stdout'].encode(), name


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    parser.add_argument('--resume', action='store_true')
    args = parser.parse_args()
    out = args.results.resolve()
    out.mkdir(parents=True, exist_ok=args.resume)
    (out / 'summary.json').unlink(missing_ok=True)
    logs = out / 'logs'; index = 1
    while logs.exists():
        index += 1; logs = out / f'logs-{index}'
    source = out / 'source'; source.mkdir(exist_ok=True)
    shutil.copyfile(ROOT / 'tests/portability/fixtures/hash_coalesce.php', source / 'main.php')
    expected = b'fallback\nvalue\n[]\n0\n4:1:3\nvalue:changed\nlast-fallback\nkey error\nfallback error\n1:3\nmethod:vector\nnullable\n[]\n'
    (out / 'expected.bin').write_bytes(expected)
    tools = ROOT / 'tools/php_portability'; cli = ROOT / 'bin/scpp.php'; project = out / 'phpp'
    with CommandRunner(logs, ROOT) as r:
        assert r.run('php', ['php', '-d', 'auto_prepend_file=' + str(tools / 'runtime/bootstrap.php'), source / 'main.php']) == expected
        original = snapshot(source)
        command = ['php', tools / 'convert.php', source, project]
        r.run('convert', command)
        assert snapshot(source) == original
        phs = (project / 'main.phs').read_text()
        assert phs.count('??') == (source / 'main.php').read_text().count('??')
        before = snapshot(project)
        assert json.loads(r.run('reuse', command))['reused'] == 1
        assert snapshot(project) == before
        (source / 'bad.php').write_text('<?php\nq_explode();\n')
        r.run('rejection', command, expected=1, cli_reason='Converter rejection exits process')
        assert snapshot(project) == before
        assert 'bad.php:2:' in (logs / 'rejection.stderr').read_text()
        (source / 'bad.php').unlink()
        r.run('framework', ['php', tools / 'install_native_runtime.php', project])
        if not (project / 'prism.json').exists():
            r.run('init', ['php', cli, 'init', '--php-profile=strict'], project, cli_reason='CLI setup')
        config = json.loads((project / 'prism.json').read_text())
        config['build']['cxx'] = 'clang++-18'; config['runtime']['modules'] = []
        (project / 'prism.json').write_text(json.dumps(config))
        build = ['php', cli, 'build', '--build-runtime', '--no-stan']
        r.run('build', build, project, timeout=300, cli_reason='CLI native build')
        assert r.run('native', [project / '.prism/build/main'], project) == expected
        cpp = (project / '.prism/generated/main.cpp').read_text()
        assert 'values.find(' in cpp and 'values.at(' in cpp
        assert 'return values.find(' in cpp and 'auto owned = php::coalesce_eval' in cpp
        (out / 'valid.cpp').write_text(cpp); (out / 'valid.phs').write_text(phs)
        owned_line = next(line for line in cpp.splitlines() if 'auto owned = php::coalesce_eval' in line)
        shape = out / 'result-type.cpp'
        shape.write_text(cpp.replace(owned_line, owned_line + '\n\tstatic_assert(std::is_same_v<decltype(owned), string_t>);'))
        r.run('result-type', ['clang++-18', '-std=c++23', '-DSCPP_LANGUAGE_TARGET_PHP=1',
              '-I', ROOT / 'runtime/include', '-I', project / '.prism/generated',
              '-include', project / '.prism/generated/scpp_framework/exceptions.hpp', '-fsyntax-only', shape], timeout=120)
        fixture = CppFixtureCompiler(r, ROOT, out, pch=False, runtime=True, cxx='clang++-18')
        fixture.flags += ['-I', ROOT]
        fixture.compile('wrapper-regression', ROOT / 'tests/runtime/wrappers/level_01/runtime_wrappers_002_php_operator_normalization.cpp', out / 'wrappers')
        r.run('wrapper-execute', [out / 'wrappers'])
        # Ordinary indexed reads must still throw; do not make all hash reads nullable.
        (source / 'main.php').write_text("<?php\n$values /** hash<string> */ = [];\necho $values['missing'];\n")
        r.run('ordinary-convert', command)
        r.run('ordinary-build', build, project, timeout=120, cli_reason='CLI native build')
        r.run('ordinary-missing', [project / '.prism/build/main'], project, expected=1)
        assert 'hash_t::at: not found' in (logs / 'ordinary-missing.stderr').read_text()
        (source / 'main.php').write_text("<?php\n$values /** hash<string> */ = [];\n$bad /** vector<string> */ = [];\necho $values['missing'] ?? $bad;\n")
        r.run('incompatible-convert', command)
        r.run('incompatible-build', build, project, timeout=120, expected=1, cli_reason='Native type rejection exits process')
        diagnostic = (logs / 'incompatible-build.stdout').read_text() + (logs / 'incompatible-build.stderr').read_text()
        assert 'coalesce' in diagnostic
        check_mixed_regressions(r, project, cli)
    (out / 'summary.json').write_text(json.dumps(dict(passed=True, source_sha256=hashlib.sha256(original['main.php'][0]).hexdigest(),
        expected_bytes=len(expected), ordinary_access_rejection=True, incompatible_fallback_rejection=True,
        wrapper_regression=True, direct_mixed_cases=2, stan='skipped'), indent=2))
    print('Hash coalescing: PASS')


if __name__ == '__main__':
    main()

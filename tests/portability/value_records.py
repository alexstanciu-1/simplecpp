"""Scalar value records: explicit copy, stable aliases and vector replacement parity."""
import argparse
import json
from pathlib import Path
import shutil
import subprocess

ROOT = Path(__file__).resolve().parents[2]
TOOLS = ROOT / 'tools/php_portability'
EXPECTED = '5:10:2:9\n20:10:12:30\n4294967295:7\n'


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    parser.add_argument('--target-checkout', type=Path)
    args = parser.parse_args()
    results = args.results.resolve()
    results.mkdir(parents=True, exist_ok=False)
    source, output = results / 'php', results / 'phpp'
    shutil.copytree(Path(__file__).with_suffix(''), source)
    report = {'passed': False, 'commands': []}

    def run(command, cwd=ROOT, ok=True):
        p = subprocess.run(list(map(str, command)), cwd=cwd, capture_output=True, text=True)
        report['commands'].append({'command': list(map(str, command)), 'cwd': str(cwd),
                                   'exit': p.returncode, 'stdout': p.stdout, 'stderr': p.stderr})
        (results / 'summary.json').write_text(json.dumps(report, indent=2) + '\n')
        assert (p.returncode == 0) == ok, (command, p.stdout, p.stderr)
        return p

    run(['php', TOOLS / 'check.php', source])
    php = run(['php', '-r', 'foreach(array_slice($argv,1) as $p) { require $p; }',
               TOOLS / 'runtime/bootstrap.php', source / 'span.php', source / 'operations.php', source / 'main.php'])
    assert php.stdout == EXPECTED, php.stdout
    convert = ['php', TOOLS / 'convert.php', source, output]
    assert json.loads(run(convert).stdout)['converted'] == 3
    assert json.loads(run(convert).stdout) == {'converted': 0, 'reused': 3, 'removed': 0}
    # A layout-owning file changes, while consumers contain no inlined declaration.
    span = source / 'span.php'
    original = span.read_text()
    span.write_text(original.replace('$length /** uint32 */ = 0', '$length /** uint32 */ = 1'))
    assert json.loads(run(convert).stdout) == {'converted': 1, 'reused': 2, 'removed': 0}
    span.write_text(original)
    assert json.loads(run(convert).stdout)['converted'] == 1
    manifest = (output / '.scpp-portability.json').read_bytes()
    prefix = "<?php\n"
    rejects = [
        '/** @scpp-struct */ class Bad { public int $n /** uint32 */ = 0; }',
        '/** @scpp-struct */ final class Bad { public int $n = 0; }',
        '/** @scpp-struct */ final class Bad { public string $n = ""; }',
        '/** @scpp-struct */ final class Bad { public int $n /** uint32 */ = 4294967296; }',
        '/** @scpp-struct */ final class Bad { public int $n /** uint32 */ = -1; }',
        '/** @scpp-struct */ final class Bad { public function f(): int { return 1; } }',
        '/** @scpp-struct */ final class Bad { private bool $n = false; }',
        '$b = /** &ref */ $rows[0];',
        '$b = /** &ref */ $a->field;',
        '$b = /** &ref */ new \\records\\Source_Span();',
        '$b = /** &ref */ $b;',
        '$b /** &ref int */ = $a;',
        '$b /** &ref records\\Source_Span */ = /** &ref */ $a;',
        '$b = new \\records\\Source_Span(); $b = /** &ref */ $a;',
        '$b = /** &ref */ $a; $a = new \\records\\Source_Span();',
        '$b = /** &ref */ $a; $b = new \\records\\Source_Span();',
        'echo /** &ref */ $a;',
        '$outer = $b = /** &ref */ $a;',
        'class Bad { /** @scpp-struct */ public int $n = 0; }',
    ]
    for body in rejects:
        (source / 'bad.php').write_text(prefix + body + '\n')
        rejected = run(convert, ok=False)
        assert 'bad.php:' in rejected.stderr
        assert (output / '.scpp-portability.json').read_bytes() == manifest
    (source / 'bad.php').unlink()
    if args.target_checkout:
        target = json.loads((ROOT / 'compiler/tools/portability_target.json').read_text())
        checkout = args.target_checkout.resolve()
        assert run(['git', '-C', checkout, 'rev-parse', 'HEAD']).stdout.strip() == target['verified_commit']
        assert run(['git', '-C', checkout, 'status', '--porcelain']).stdout.strip() == ''
        cli = checkout / target['cli']
        run(['php', cli, 'init', '--php-profile=strict'], cwd=output)
        config = json.loads((output / 'prism.json').read_text())
        config['build']['cxx'] = 'clang++-18'
        config['runtime']['modules'] = []
        (output / 'prism.json').write_text(json.dumps(config, indent=2) + '\n')
        native = run(['php', cli, 'run', '--build-runtime'], cwd=output)
        assert native.stdout.endswith(EXPECTED), native.stdout
        # Independent target-only layout witness; do not edit converter-owned PHS.
        layout = results / 'layout'; layout.mkdir()
        shutil.copy2(output / 'span.phs', layout / 'span.phs')
        (layout / 'main.phs').write_text('echo layout_sizeof(\\records\\Source_Span), ":", layout_alignof(\\records\\Source_Span), ":", layout_field_sizeof(\\records\\Source_Span, length), "\\n";\n')
        (layout / 'prism.json').write_text(json.dumps(config, indent=2) + '\n')
        observed = run(['php', cli, 'run', '--build-runtime'], cwd=layout).stdout.splitlines()[-1]
        size, alignment, field_size = map(int, observed.split(':'))
        assert size >= 8 and alignment > 0 and field_size == 4, observed
        report.update(target_revision=target['verified_commit'], native_layout={'size': size, 'alignment': alignment, 'field_size': field_size})
        assert run(['git', '-C', checkout, 'status', '--porcelain']).stdout.strip() == ''
    report.update(passed=True, expected=EXPECTED, native=bool(args.target_checkout))
    (results / 'summary.json').write_text(json.dumps(report, indent=2) + '\n')
    print('Value records: copy/alias/vector behavior, incremental conversion and rejections passed' + ('; native/layout passed.' if args.target_checkout else ' (PHP only).'))


if __name__ == '__main__':
    main()

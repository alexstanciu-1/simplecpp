"""Restricted trait expansion, token cache, and incremental project membership proofs."""
import argparse
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import time

ROOT = Path(__file__).resolve().parents[2]
TOOLS = ROOT / 'tools/php_portability'


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--target-checkout', type=Path)
    parser.add_argument('--results', type=Path)
    args = parser.parse_args()
    work = Path(tempfile.mkdtemp(prefix='scpp-traits-'))
    source, output = work / 'php', work / 'phpp'
    source.mkdir()
    events = []

    def run(command, ok=True, cwd=ROOT):
        started = time.perf_counter()
        p = subprocess.run(list(map(str, command)), cwd=cwd, capture_output=True, text=True)
        events.append({'command': list(map(str, command)), 'exit': p.returncode, 'stdout': p.stdout, 'stderr': p.stderr,
                       'seconds': round(time.perf_counter() - started, 6)})
        if args.results:
            args.results.mkdir(parents=True, exist_ok=True)
            (args.results / 'commands.json').write_text(json.dumps(events, indent=2) + '\n')
        assert (p.returncode == 0) == ok, (command, p.stdout, p.stderr)
        return p

    def write(name, body, namespace='demo'):
        p = source / name
        p.parent.mkdir(parents=True, exist_ok=True)
        p.write_text('<?php\ndeclare(strict_types=1);\n' + (f'namespace {namespace};\n' if namespace else '') + body + '\n')
        run(['php', TOOLS / 'sync_imports.php', source])

    trait = '''trait Operations {
    private function adjust(int $amount): int {
        $this->value = $this->value + $amount;
        return $this->value;
    }
    public function advance(int $amount): int { return $this->adjust($amount); }
}'''
    write('parts/operations.php', trait)
    write('first.php', 'class First { use Operations; public int $value = 1; }')
    write('second.php', 'class Second { use \\demo\\Operations; public int $value = 10; }')
    write('contract.php', 'interface Contract { public function ready(): bool; }')
    write('main.php', '$a = new \\demo\\First(); $b = new \\demo\\Second();\necho $a->advance(2), ":", $b->advance(3), ":", $a->value, "\\n";', '')
    command = ['php', TOOLS / 'convert.php', source, output, '--stats']

    def convert():
        return json.loads(run(command).stdout)

    first = convert()
    assert first['converted'] == first['cache']['tokenized'] == 5, first
    assert 'trait Operations' not in (output / 'parts/operations.phs').read_text()
    assert 'private function adjust' in (output / 'first.phs').read_text()
    stamps = {p: p.stat().st_mtime_ns for p in output.rglob('*.phs')}
    assert convert()['cache']['tokenized'] == 0
    # Cross a timestamp tick, then establish a stable observation. The next run
    # must perform no directory listings, source reads, token loads or tokenization.
    time.sleep(1.1)
    convert()
    warm = convert()
    assert warm['cache'] == dict(listed_directories=0, read_sources=0, tokenized=0, loaded_tokens=0), warm
    assert all(p.stat().st_mtime_ns == stamp for p, stamp in stamps.items())

    state = json.loads((output / '.scpp-portability-index.json').read_text())
    artifact = output / '.scpp-token-cache' / (state['files']['first.php']['cache'] + '.php-cache')
    cached = run(['php', '-d', 'opcache.enable_cli=1', '-d', 'opcache.file_update_protection=0', '-r',
                  '$data = require $argv[1]; echo (int)is_array($data), ":", (int)opcache_is_script_cached($argv[1]);', artifact])
    assert cached.stdout == '1:1'
    if args.results:
        shutil.copytree(source, args.results / 'source')
        for generated in output.rglob('*.phs'):
            dest = args.results / 'generated' / generated.relative_to(output)
            dest.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy2(generated, dest)

    php = run(['php', '-r', 'foreach (array_slice($argv, 1) as $file) { require $file; }',
               TOOLS / 'runtime/bootstrap.php', source / 'parts/operations.php', source / 'first.php', source / 'second.php', source / 'main.php'])
    assert php.stdout == '3:13:3\n'
    if args.target_checkout:
        target = json.loads((ROOT / 'compiler/tools/portability_target.json').read_text())
        checkout = args.target_checkout.resolve()
        assert run(['git', '-C', checkout, 'rev-parse', 'HEAD']).stdout.strip() == target['verified_commit']
        assert run(['git', '-C', checkout, 'status', '--porcelain']).stdout == ''
        cli = checkout / target['cli']
        run(['php', cli, 'init', '--php-profile=strict'], cwd=output)
        config_path = output / 'prism.json'
        config = json.loads(config_path.read_text())
        config['build']['cxx'] = 'clang++-18'
        config['runtime']['modules'] = []
        config_path.write_text(json.dumps(config, indent=2) + '\n')
        native = run(['php', cli, 'run', '--build-runtime'], cwd=output)
        assert native.stdout.endswith(php.stdout), native.stdout
        assert run(['git', '-C', checkout, 'status', '--porcelain']).stdout == ''

    # Same-sized source edit with restored mtime must still invalidate via ctime
    # or the conservative same-second content check. Both consumers regenerate.
    p = source / 'parts/operations.php'
    before = p.stat()
    p.write_text(p.read_text().replace(' + $amount', ' - $amount'))
    os.utime(p, ns=(before.st_atime_ns, before.st_mtime_ns))
    changed = convert()
    assert changed['converted'] == 3 and changed['reused'] == 2 and changed['cache']['tokenized'] == 1, changed
    assert ' - $amount' in (output / 'second.phs').read_text()
    assert (output / 'main.phs').stat().st_mtime_ns == stamps[output / 'main.phs']

    # Adding/removing a nested directory updates membership without retokenizing peers.
    write('new/deep/extra.php', 'class Extra {}')
    added = convert()
    assert added['converted'] == 1 and added['cache']['tokenized'] == 1, added
    shutil.rmtree(source / 'new')
    removed = convert()
    assert removed['removed'] == 1 and removed['converted'] == 0, removed
    index = json.loads((output / '.scpp-portability-index.json').read_text())
    assert 'new/deep/extra.php' not in index['files'] and 'new' not in index['directories']
    assert not (output / 'new/deep/extra.phs').exists()

    # Removing a still-used trait must fail before publication; removing its users
    # too then retires all three outputs, declarations, and owned cache artifacts.
    saved = p.read_text()
    p.unlink()
    manifest = (output / '.scpp-portability.json').read_bytes()
    assert 'missing trait' in run(command, ok=False).stderr
    assert (output / '.scpp-portability.json').read_bytes() == manifest
    p.write_text(saved)
    convert()

    prologue = saved.split('// </scpp-imports>')[0] + '// </scpp-imports>\n'
    rejects = [
        ('trait Nested { use Operations; }', 'traits cannot use traits'),
        ('class Bad { use \\other\\Operations; }', 'namespace'),
        ('class Bad { use Operations { advance as other; } }', 'adaptations'),
        ('class Bad { use Operations { Operations::advance insteadof Other; } }', 'adaptations'),
        ('class Bad { use Operations; public function ADVANCE(int $n): int { return $n; } }', 'collision'),
        ('class Bad { use Operations, Operations; }', 'duplicate trait'),
        ('trait First {}', 'duplicate declaration'),
        ('class Bad { use Contract; }', 'missing trait'),
        ('trait Bad { public int $x = 0; }', 'methods only'),
        ('trait Bad { public function __construct() {} }', 'magic methods'),
        ('if (true) { class Bad {} }', 'top-level'),
        ('namespace again; class Bad {}', 'prologue'),
    ]
    for body, diagnostic in rejects:
        (source / 'bad.php').write_text(prologue + body)
        error = run(command, ok=False).stderr
        assert diagnostic in error and 'bad.php:' in error, error
        assert (output / '.scpp-portability.json').read_bytes() == manifest
    (source / 'bad.php').unlink()
    # Prologue restrictions apply before indexing, including bracketed namespaces
    # and declaration/import aliases that would change the trait's lexical context.
    for bad in ['<?php namespace demo { class Bad {} }',
                '<?php class Bad {} namespace demo;',
                '<?php namespace demo; namespace again; class Bad {}']:
        (source / 'bad.php').write_text(bad)
        assert 'bad.php:' in run(command, ok=False).stderr
    (source / 'bad.php').write_text(prologue + 'use other\\Thing; trait Bad { public function f(): int { return 1; } }')
    assert 'manual imports' in run(['php', TOOLS / 'sync_imports.php', source], ok=False).stderr
    (source / 'bad.php').unlink()
    # Original trait path/line survives expansion diagnostics.
    p.write_text(saved.replace('return $this->value;', 'return unknown();'))
    error = run(command, ok=False).stderr
    assert 'parts/operations.php:' in error, error
    p.write_text(saved)
    convert()

    # Disk token caches are data-only PHP return files; modified contents are never executed.
    state = json.loads((output / '.scpp-portability-index.json').read_text())
    cache = output / '.scpp-token-cache' / (state['files']['first.php']['cache'] + '.php-cache')
    cache_bytes = cache.read_bytes()
    cache.write_text('<?php throw new Exception("EXECUTED_BAD_CACHE");')
    (output / 'first.phs').unlink()  # force loading that otherwise reusable file
    error = run(command, ok=False).stderr
    assert 'modified token cache' in error and 'EXECUTED_BAD_CACHE' not in error, error
    cache.write_bytes(cache_bytes)
    convert()

    # Missing trait and both consumers removed together: no dangling declarations.
    for name in ['parts/operations.php', 'first.php', 'second.php']:
        (source / name).unlink()
    final = convert()
    assert final['removed'] == 3, final
    state = json.loads((output / '.scpp-portability-index.json').read_text())
    assert set(state['files']) == {'main.php', 'contract.php'}
    assert len(list((output / '.scpp-token-cache').glob('*.php-cache'))) == 2
    # Global-namespace traits and multiple declarations in one source are supported.
    write('global.php', 'trait GlobalMethods { public function value(): int { return 7; } } class GlobalOwner { use GlobalMethods; }', '')
    assert convert()['converted'] == 1
    assert 'public function value' in (output / 'global.phs').read_text()
    if args.results:
        shutil.copy2(__file__, args.results / 'runner.py')
        (args.results / 'summary.json').write_text(json.dumps({'passed': True, 'workspace': str(work), 'warm': warm,
            'trait_edit': changed, 'added': added, 'removed': removed, 'php_stdout': php.stdout,
            'native_verified': bool(args.target_checkout)}, indent=2) + '\n')
    print('Trait composition restrictions, shared consumers, incremental membership and token cache proofs passed.')


if __name__ == '__main__':
    main()

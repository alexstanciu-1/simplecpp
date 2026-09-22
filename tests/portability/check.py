"""Read-only checking shares conversion rules, including cross-file trait expansion."""
import json
from pathlib import Path
import subprocess
import tempfile

ROOT = Path(__file__).resolve().parents[2]
TOOLS = ROOT / 'tools/php_portability'


def run(script, *args, ok=True):
    result = subprocess.run(['php', str(TOOLS / script), *map(str, args)],
                            capture_output=True, text=True)
    assert (result.returncode == 0) == ok, (script, result.stdout, result.stderr)
    return result


def snapshot(root):
    return {str(p.relative_to(root)): (p.read_bytes(), p.stat().st_mtime_ns)
            for p in root.rglob('*') if p.is_file()}


def main():
    with tempfile.TemporaryDirectory(prefix='scpp-check-') as temp:
        base = Path(temp)
        source, output = base / 'php', base / 'phpp'
        source.mkdir()

        def write(name, body):
            p = source / name
            p.parent.mkdir(parents=True, exist_ok=True)
            p.write_text('<?php\nnamespace demo;\n' + body + '\n')

        write('parts/operations.php', 'trait Operations { public function next(int $n): int { return $n + 1; } }')
        write('owner.php', 'class Owner { use Operations; }')
        run('sync_imports.php', source)
        before = snapshot(base)
        assert json.loads(run('check.php', source).stdout)['checked'] == 2
        assert snapshot(base) == before
        run('convert.php', source, output)
        before = snapshot(base)
        assert json.loads(run('check.php', source, '--cache', output).stdout)['checked'] == 2
        assert snapshot(base) == before  # Includes cache/output contents and timestamps.

        # Literal interface declarations pass through without symbol resolution.
        write('owner.php', 'interface First {} interface Second {} class Owner implements First, \\demo\\Second { use Operations; }')
        run('sync_imports.php', source)
        run('check.php', source)
        run('convert.php', source, output)
        assert 'class Owner implements First, \\demo\\Second' in (output / 'owner.phs').read_text()

        # Checks discover additions/removals even when reading an older output cache.
        write('added.php', 'class Added { public int $n = 0; }')
        run('sync_imports.php', source)
        before = snapshot(base)
        assert json.loads(run('check.php', source, '--cache', output).stdout)['checked'] == 3
        assert snapshot(base) == before
        (source / 'added.php').unlink()
        (source / 'parts/operations.php').unlink()
        before = snapshot(base)
        assert 'trait' in run('check.php', source, '--cache', output, ok=False).stderr.lower()
        assert snapshot(base) == before

        # Check and conversion reject the same source/trait/operation restrictions.
        cases = [
            ('class Bad { public function __construct($item) {} }', 'type'),
            ('class Bad { public function __construct(array $items /** vector<int> */ = [1]) {} }', 'containers require explicit arguments'),
            ('class Bad { public function __construct(int &$item) {} }', 'parameter'),
            ('class Bad { public function f(): ?array /** vector<int> */ { return null; } }', 'nullable container'),
            ('interface Bad { public function f(): ?Thing; }', 'interface returns'),
            ('enum_name();', 'wrong argument count'),
            ('class Bad { public function f(array $items): void {} }', 'container'),
            ('class Bad { public function f(array $items /** vector<int> */ = []): void {} }', 'parameter separator'),
            ('class Bad { public function f(array &$items /** vector<int> */): void {} }', 'named parameter'),
            ('interface Bad { public function f(array $items /** vector<int> */): void; }', 'container interface parameters'),
            ('class Bad { public function __construct(public array $items /** vector<int> */ = []) {} }', 'constructor containers require explicit arguments'),
            ('lock_empty(1);', 'wrong argument count'),
            ('lock_try();', 'wrong argument count'),
            ('class Owner extends Base {}', 'expected {'),
            ('class Owner implements {}', 'owner.php:'),
            ('class Owner { use Missing; }', 'trait'),
            ('class Owner { public function f(int $n): int { return $n(; } }', 'owner.php:'),
            ('$fn = "strlen"; $fn("x");', 'dynamic calls'),
            ('echo \\strlen("x");', 'bypass'),
            ('trait A { public function f(int $n): int { return $n; } }\n'
             'class Owner { use A { f as g; } }', 'owner.php:'),
            ('class Owner { public function f(int $n): mixed { } }', 'method type'),
        ]
        for body, diagnostic in cases:
            write('owner.php', body)
            run('sync_imports.php', source)
            before = snapshot(base)
            checked = run('check.php', source, ok=False)
            converted = run('convert.php', source, output, ok=False)
            assert diagnostic in checked.stderr, checked.stderr
            assert diagnostic in converted.stderr, converted.stderr
            assert snapshot(base) == before

        # Raw PHP compile errors are checked even if token parsing can accept them.
        write('owner.php', 'class Owner { public function f(int $n, int $n): int { return $n; } }')
        run('sync_imports.php', source)
        assert 'PHP syntax check failed' in run('check.php', source, ok=False).stderr
        write('owner.php', 'echo "must not execute";')
        before = snapshot(base)
        assert 'must not execute' not in run('check.php', source).stdout
        assert snapshot(base) == before
        run('sync_imports.php', source)
        assert 'must not execute' not in run('check.php', source).stdout
        run('check.php', source, '--cache', source, ok=False)
        run('check.php', source, '--unknown', output, ok=False)
        print('Check command: read-only behavior, PHP lint, rejection parity and cache discovery passed.')


if __name__ == '__main__':
    main()

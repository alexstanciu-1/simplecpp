"""Code-point text vs raw bytes, malformed UTF-8, and selected-target parity."""
import argparse
import json
from pathlib import Path
import shutil
import subprocess
import tempfile

ROOT = Path(__file__).resolve().parents[2]
TOOLS = ROOT / 'tools/php_portability'


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--target-checkout', type=Path)
    parser.add_argument('--results', type=Path, required=True)
    args = parser.parse_args()
    args.results.mkdir(parents=True, exist_ok=False)
    work = Path(tempfile.mkdtemp(prefix='scpp-utf8-'))
    source, output = work / 'php', work / 'phpp'
    source.mkdir()
    events = []

    def run(label, cmd, cwd=ROOT, ok=True):
        p = subprocess.run(list(map(str, cmd)), cwd=cwd, text=True, capture_output=True)
        (args.results / (label + '.stdout.log')).write_text(p.stdout)
        (args.results / (label + '.stderr.log')).write_text(p.stderr)
        events.append({'label': label, 'command': list(map(str, cmd)), 'exit': p.returncode})
        assert (p.returncode == 0) == ok, (label, p.stdout, p.stderr)
        return p

    def literal(s):
        return json.dumps(s, ensure_ascii=False)

    lines = ['<?php', 'declare(strict_types=1);', '$position /** int */ = -9;']
    expected = []

    def echo(expr, result):
        lines.append(f'echo {expr}, "\\n";')
        expected.append(str(result))

    values = ['', 'ascii', 'é中😀', 'e\u0301', '👩\u200d💻', '\U0010ffff']
    for value in values:
        s = literal(value)
        echo(f'q_strlen({s})', len(value))
        echo(f'string_byte_len({s})', len(value.encode()))
        echo(f'string_utf8_is_valid({s}) ? 1 : 0', 1)
        for index in [-1, 0, 1, len(value), len(value) + 1]:
            echo(f'string_codepoint_at({s}, {index})', ord(value[index]) if 0 <= index < len(value) else -1)
        for offset in [-20, -1, 0, 1, 20]:
            start = min(len(value), max(0, offset if offset >= 0 else len(value) + offset))
            echo(f'q_substr({s}, {offset})', value[start:])
            for length in [-2, 0, 2, 20]:
                end = min(len(value), start + length) if length >= 0 else max(start, len(value) + length)
                echo(f'q_substr({s}, {offset}, {length})', value[start:end])

    for text, needle in [('é中😀é', 'é'), ('é中😀é', '😀'), ('é中😀é', '😀é'), ('é中😀é', 'x'), ('é中😀é', ''), ('', '')]:
        for offset in sorted({-len(text), -min(1, len(text)), 0, min(1, len(text)), len(text)}):
            start = offset if offset >= 0 else len(text) + offset
            for name in ['strpos', 'strrpos']:
                found = text.find(needle, start) if name == 'strpos' else text.rfind(needle, start)
                # Negative reverse-search offsets limit the match's starting position.
                if name == 'strrpos' and offset < 0:
                    found = text.rfind(needle, 0, min(len(text), start + len(needle)))
                lines.append('$position = -9;')
                lines.append(f'if (take_false($position, q_{name}({literal(text)}, {literal(needle)}, {offset}))) {{ echo $position, "\\n"; }} else {{ echo "F\\n"; }}')
                expected.append(str(found) if found >= 0 else 'F')
        for name in ['strpos', 'strrpos']:
            found = text.find(needle) if name == 'strpos' else text.rfind(needle)
            lines.append(f'if (take_false($position, q_{name}({literal(text)}, {literal(needle)}))) {{ echo $position, "\\n"; }} else {{ echo "F\\n"; }}')
            expected.append(str(found) if found >= 0 else 'F')
    echo('q_str_starts_with("é中", "é") ? 1 : 0', 1)
    echo('q_str_ends_with("é中", "中") ? 1 : 0', 1)
    # Runtime malformed data built from valid source literals: continuation,
    # truncation, overlong sequence, surrogate, and a value beyond U+10FFFF.
    bad_values = [
        'string_byte_slice("é", 1, 1)',
        'string_byte_slice("😀", 0, 3)',
        'string_byte_slice("ࠀ", 0, 1) . string_byte_slice("ࠀ", 2, 1) . string_byte_slice("ࠀ", 2, 1)',
        'string_byte_slice("퀀", 0, 1) . string_byte_slice("ࠀ", 1, 1) . string_byte_slice("ࠀ", 2, 1)',
        'string_byte_slice("􀀀", 0, 1) . string_byte_slice("𐀀", 1, 3)',
    ]
    for bad in bad_values:
        lines.append('$bad = ' + bad + ';')
        echo('string_utf8_is_valid($bad) ? 1 : 0', 0)
        for expression in ['q_strlen($bad)', 'q_substr($bad, 0)', 'q_strpos("ok", $bad)', 'q_strrpos($bad, "")',
                           'q_str_starts_with($bad, "")', 'q_str_ends_with("ok", $bad)', 'string_codepoint_at($bad, -1)']:
            lines.append('try { $ignored = ' + expression + '; echo "BAD\\n"; } catch (\\InvalidArgumentException $error) { echo $error->getMessage(), "\\n"; }')
            expected.append('Text operation requires valid UTF-8')
    echo('string_byte_len(string_byte_slice("é", 1, 1))', 1)
    echo('string_byte_starts_with(string_byte_slice("é", 1, 1), string_byte_slice("é", 1, 1)) ? 1 : 0', 1)
    for expression in ['q_strpos("é", "", 2)', 'q_strrpos("é", "", -2)']:
        lines.append('try { $ignored = ' + expression + '; echo "BAD\\n"; } catch (\\OutOfBoundsException $error) { echo $error->getMessage(), "\\n"; }')
        expected.append('Text search offset is out of range')
    expected_text = '\n'.join(expected) + '\n'
    (source / 'main.php').write_text('\n'.join(lines) + '\n')
    run('imports', ['php', TOOLS / 'sync_imports.php', source])
    php = run('php', ['php', '-d', 'auto_prepend_file=' + str(TOOLS / 'runtime/bootstrap.php'), source / 'main.php'])
    assert php.stdout == expected_text, (php.stdout, expected_text)
    command = ['php', TOOLS / 'convert.php', source, output]
    run('convert', command)
    run('runtime', ['php', TOOLS / 'install_native_runtime.php', output])
    assert json.loads(run('reuse', command).stdout) == {'converted': 0, 'reused': 1, 'removed': 0}
    for bad in ['echo \\strlen("é");', 'echo \\substr("é", 0);', 'echo q_strlen();', 'echo q_substr("a");', 'echo q_strpos("a", "a", 0, 1);']:
        prologue = "<?php\n"
        (source / 'bad.php').write_text(prologue + bad)
        assert 'bad.php:' in run('reject', command, ok=False).stderr
    (source / 'bad.php').unlink()
    if args.target_checkout:
        target = json.loads((ROOT / 'compiler/tools/portability_target.json').read_text())
        checkout = args.target_checkout.resolve()
        assert run('revision', ['git', '-C', checkout, 'rev-parse', 'HEAD']).stdout.strip() == target['verified_commit']
        assert run('clean-before', ['git', '-C', checkout, 'status', '--porcelain']).stdout == ''
        cli = checkout / target['cli']
        run('init', ['php', cli, 'init', '--php-profile=strict'], cwd=output)
        config = json.loads((output / 'prism.json').read_text())
        config['build']['cxx'] = 'clang++-18'
        config['runtime']['modules'] = []
        (output / 'prism.json').write_text(json.dumps(config, indent=2) + '\n')
        native = run('native', ['php', cli, 'run', '--build-runtime'], cwd=output)
        assert native.stdout.endswith(expected_text), native.stdout
        assert run('clean-after', ['git', '-C', checkout, 'status', '--porcelain']).stdout == ''
    shutil.copy2(source / 'main.php', args.results / 'main.php')
    shutil.copy2(output / 'main.phs', args.results / 'main.phs')
    shutil.copytree(output / 'scpp_framework', args.results / 'scpp_framework')
    shutil.copy2(__file__, args.results / 'runner.py')
    (args.results / 'summary.json').write_text(json.dumps({'passed': True, 'native': bool(args.target_checkout),
        'workspace': str(work), 'assertion_lines': len(expected), 'commands': events}, indent=2) + '\n')
    print(f'UTF-8/code-point and byte contracts passed ({len(expected)} output assertions).')


if __name__ == '__main__':
    main()

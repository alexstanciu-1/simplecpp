"""Run the same PHP-flavor expression and stock PHP expression on real byte files."""
from collections import Counter
from pathlib import Path
import subprocess
import tempfile

from check_apps import build
from snippets import probes

# Each input pair can be written to a file, including NUL and invalid UTF-8.
inputs = {
    'empty': b'', 'digit-zero': b'0', 'padded-id': b'01', 'one': b'1', 'ten': b'10',
    'two': b'2', 'ascii': b'Abc', 'spaces': b' \t0\r\n', 'utf8': 'café'.encode(),
    'decomposed': 'cafe\u0301'.encode(), 'emoji': 'a😀b'.encode(), 'nul': b'A\x00B',
    'invalid-utf8': b'\xffa', 'delimiters': b'a||b|', 'path': b'/docs/one?q=two',
}
pairs = [('padded-id', 'one'), ('ten', 'two'), ('two', 'ten'), ('digit-zero', 'empty'),
         ('empty', 'empty'), ('nul', 'nul'), ('ascii', 'utf8'), ('decomposed', 'utf8')]

# PHP and Simple C++ express a falseable result differently. strpos itself gets
# identical inputs, and either outcome is printed in the same external format.
expressions = {
    'truth': 'echo ($s ? "Y" : "N");',
    'length': 'echo strlen($s);',
    'trim': 'echo trim($s);',
    'ascii-lower': 'echo strtolower($s);',
    'slice': 'echo substr($s, -3, 2);',
    'starts-with': 'echo str_starts_with($s, $t) ? "Y" : "N";',
    'split': 'foreach (explode("|", $s) as $part) { echo strlen($part), ":", $part; }',
    'replace': '$n=99; echo str_replace("a","@",$s,$n), "|", $n;',
    'equals': 'echo ($s == $t) ? "Y" : "N";',
    'less': 'echo ($s < $t) ? "Y" : "N";',
    'position': '$p=strpos($s,$t); if (HIT) { echo $p+0; } else { echo "miss"; }',
}

def entry(standard):
    text = ('<?php\n$op=$argv[1]; $s=file_get_contents($argv[2]); $t=file_get_contents($argv[3]);\n'
            if standard else '$args=cli_args(); $op string=$args[1]; $a string=$args[2]; $b string=$args[3];\n'
            '$s string=file_get_contents($a); $t string=file_get_contents($b);\n')
    for name, body in expressions.items():
        body = body.replace('HIT', '$p !== false' if standard else 'isset($p)')
        text += f'if ($op === "{name}") {{ {body} return 0; }}\n'
    return text + 'return 2;\n'


# Identical source text for the two engines. Each selected snippet is also legal
# stock PHP syntax. These controls isolate language differences from rewrites.
stock_examples = {
    'strict-sentinel-miss': '',
    'nullable-field': '10',
    'promoted-property': 'A',
    'case-method': 'Ada',
    'uppercase-bool': 'yes',
    'uppercase-builtin': '3',
    'enum-php-value': 'open',
    'bool-output': '1/',
    'byte-index': 'A',
    'string-byte-value': '0',
    'template-local': 'Hello Ada 1',
    'strict-types': 'ok',
    'branch-local': 'ok',
    'hello-minimal': 'hello',
    'truthy-default': 'anon',
    'php-trim-set': 'key',
    'money-format': '1,234.50',
    'file-basename': 'docs|.',
    'loose-form-id': '10',
    'form-int': '42',
}


def stock_controls():
    for probe in probes:
        expected = stock_examples.get(probe.name)
        if expected is None:
            continue
        command = ['php', '-n', '-r', probe.code]
        run = subprocess.run(command, capture_output=True, check=True)
        if (run.stdout, run.stderr) != (expected.encode(), b''):
            raise AssertionError((probe.name, run.stdout, run.stderr, expected))
        print(f'PHP control {probe.name}: {expected!r}')


def main():
    stock_controls()
    with tempfile.TemporaryDirectory(prefix='php-comparison-') as directory:
        work = Path(directory)
        app = work/'app'
        app.mkdir()
        (app/'module.json').write_text('{"dependencies":{"runtime":{"modules":["filesystem"]}}}')
        same_source = '$p=strpos("abc","x"); echo isset($p)?"present":"absent"; return 0;'
        (app/'main.php').write_text(same_source)
        check = subprocess.check_output([build(app, work)])
        stock = subprocess.check_output(['php', '-n', '-r', same_source])
        print(f'Identical source, isset on a missed search: Simple C++ {check!r}, PHP {stock!r}')
        (app/'main.php').write_text(entry(False))
        exe = build(app, work)
        control = work/'control.php'
        control.write_text(entry(True))
        for name, data in inputs.items():
            (work/name).write_bytes(data)
        for name in expressions:
            category = Counter()
            differences = []
            test_pairs = pairs if name in ('equals','less') else [(a, 'ascii') for a in inputs]
            if name in ('position','starts-with'):
                test_pairs = [(a,b) for a in inputs for b in ('empty','digit-zero','ascii','nul')]
            for left, right in test_pairs:
                args = [name, work/left, work/right]
                a = subprocess.run([exe, *args], capture_output=True, check=True, timeout=5).stdout
                b = subprocess.run(['php', '-n', control, *args], capture_output=True, check=True, timeout=5).stdout
                category['same' if a == b else 'different'] += 1
                if a != b:
                    differences.append(f'{left}/{right}: {a!r} vs PHP {b!r}')
            print(f'{name}: {category["same"]} same, {category["different"]} different', *differences, sep='\n  ')


if __name__ == '__main__':
    main()

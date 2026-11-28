"""Verify the PHP UTF-8/HTML 4.01 slice against the stock interpreter."""
import json
import os
from pathlib import Path
import subprocess
import tempfile

root = Path(__file__).resolve().parents[2]
env = dict(os.environ, XDEBUG_MODE='off')
flags = [f'{quote}|{invalid}|ENT_HTML401'
         for quote in ('ENT_NOQUOTES', 'ENT_COMPAT', 'ENT_QUOTES')
         for invalid in ('0', 'ENT_IGNORE', 'ENT_SUBSTITUTE', '(ENT_IGNORE|ENT_SUBSTITUTE)')]
body = 'function exercise(string $s):void {\n'
for mode in flags:
    body += f'echo bin2hex(htmlspecialchars($s,{mode},"uTf-8",true)),";";\n'
body += '''echo bin2hex(htmlspecialchars($s)),";",bin2hex(htmlspecialchars($s,ENT_COMPAT)),";",
    bin2hex(htmlspecialchars($s,ENT_QUOTES,null)),";";
}
'''
data = [b'', b'\x00&<>"\' &amp; &#39; &bogus;', '¢é猫😀'.encode(), bytes(range(256))]
# Include valid leads followed by invalid bytes, a new lead, or an ASCII delimiter.
edges = bytes.fromhex('00 26 7f 80 9f a0 bf c0 c1 c2 df e0 ed ef f0 f4 f5 ff')
data.append(b'|'.join(bytes((a, b)) for a in edges for b in edges))
for invalid in ('80', 'ff', 'c0af', 'e282', 'eda080', 'e08080', 'f4908080', 'f09f92',
                'c241', 'c2ff', 'e0ffc2a2', 'f0ffff41', 'e2c3a9', 'f58080'):
    data.append(b'&before' + bytes.fromhex(invalid) + b'<after')
for value in data:
    body += f'exercise((string)hex2bin("{value.hex()}"));\n'

rejections = [
    ('ENT_HTML5', 'null', 'true', 'unsupported flags'),
    ('ENT_XHTML', 'null', 'true', 'unsupported flags'),
    ('ENT_XML1', 'null', 'true', 'unsupported flags'),
    ('ENT_DISALLOWED', 'null', 'true', 'unsupported flags'),
    ('1', 'null', 'true', 'unsupported flags'),
    ('-1', 'null', 'true', 'unsupported flags'),
    ('ENT_QUOTES', '""', 'true', 'encoding must be UTF-8'),
    ('ENT_QUOTES', '"ISO-8859-1"', 'true', 'encoding must be UTF-8'),
    ('ENT_QUOTES', '"UTF-8"', 'false', 'double_encode must be true'),
]
checks = 'function checked(int $f, ?string $enc, bool $twice):string { return htmlspecialchars("",$f,$enc,$twice); }\n'
for i, (mode, encoding, double, _) in enumerate(rejections):
    checks += f'if(cli_argc()==={i+2}) {{ echo checked({mode},{encoding},{double}); return 0; }}\n'

with tempfile.TemporaryDirectory() as directory:
    work = Path(directory)
    app = work / 'app'
    app.mkdir()
    (app / 'module.json').write_text('{}')
    (app / 'main.php').write_text(checks + body)
    config = work / 'toolchain.json'
    config.write_text(json.dumps({'cxx': 'clang++', 'flags': ['-std=c++23', '-stdlib=libc++', '-I',
        str(root / 'runtime/generated/public')], 'cache': str(root / 'build/clang/native-qualification')}))
    subprocess.run(['php', root / 'compiler/main.php', 'build', app, root / 'build/clang/native-layout/facts.json',
                    work / 'generated', work / 'native', config], check=True, stdout=subprocess.DEVNULL, env=env)
    exe = work / 'native/program'
    expected = subprocess.check_output(['php', '-n', '-d', 'default_charset=UTF-8', '-r', body])
    assert subprocess.check_output([exe]) == expected
    for i, (*_, message) in enumerate(rejections):
        run = subprocess.run([exe, *(['x'] * (i + 1))], capture_output=True)
        assert run.returncode == 1 and not run.stdout and message.encode() in run.stderr, (message, run)

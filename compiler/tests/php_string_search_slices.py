"""Compare observable PHP search results with stock PHP, including false versus empty bytes."""
import json
import os
from pathlib import Path
import subprocess
import tempfile

ROOT = Path(__file__).resolve().parents[2]
env = dict(os.environ, XDEBUG_MODE='off')
calls = []
for name in ('strstr', 'strchr', 'stristr', 'strrchr'):
    for haystack, needle in [(r'a\0aBc\0x', r'\0a'), ('aBcAbC', 'Bc'), ('aBcAbC', 'bc'),
                             ('abc', 'z'), ('abc', 'a'), ('abc', ''), ('', ''), (r'a\0b\0', ''),
                             (r'a\0xx\0b', r'\0xxx'), ('éÉ', 'É')]:
        for flag in ('', ',true', ',false'):
            calls.append(f'{name}("{haystack}","{needle}"{flag})')
    calls += [f'{name}(needle:"b",haystack:"abc")',
              f'{name}(before_needle:true,needle:"b",haystack:"abc")']
calls += [r'strpbrk("a\0c","\0b")', 'strpbrk("abc","zb")', 'strpbrk("abc","z")', 'strpbrk("","x")']
body = '\n'.join(f'$r={call}; if ($r === false) {{ echo "F;"; }} else {{ echo "H",bin2hex((string)$r),";"; }}'
                 for call in calls)
with tempfile.TemporaryDirectory() as directory:
    work = Path(directory)
    app = work/'app'
    app.mkdir()
    (app/'module.json').write_text('{}')
    main = app/'main.php'
    main.write_text(body + '\nstrpbrk("abc",""); echo "unreachable";')
    expected = subprocess.check_output(['php', '-n', '-r', body], env=env)
    assert b'H;' in expected and b'F;' in expected
    config = work/'toolchain.json'
    config.write_text(json.dumps({'cxx':'clang++','flags':['-std=c++23','-stdlib=libc++','-I',
        str(ROOT/'runtime/generated/public')], 'cache':str(ROOT/'build/clang/native-qualification')}))
    generated = work/'generated'
    subprocess.run(['php', ROOT/'compiler/main.php', 'build', app, ROOT/'build/clang/native-layout/facts.json',
                    generated, work/'native', config], check=True, capture_output=True, env=env)
    exe = subprocess.run([work/'native/program'], capture_output=True)
    assert exe.stdout == expected and exe.returncode == 1 and b'strpbrk' in exe.stderr, exe
    cpp = ''.join(unit.read_text() for unit in generated.rglob('*.cpp'))
    assert 'strchr(' not in cpp and 'strstr(' in cpp

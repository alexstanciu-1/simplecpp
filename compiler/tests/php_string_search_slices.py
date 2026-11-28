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
# Run identical calls through PHP and the native executable. The typed helper covers a
# runtime nullable as well as the literal null, zero and omitted argument paths.
for name in ('strspn', 'strcspn'):
    body += f'\nfunction check_{name}(string $s, string $m, int $o, ?int $n):int {{return {name}($s,$m,$o,$n);}}\n'
    body += f'function window_{name}(string $s,string $m,int $o):void {{echo {name}($s,$m,$o),";";'
    for length in ('null', '0', '1', '-1', '-20', '20'):
        body += f'echo check_{name}($s,$m,$o,{length}),";";'
    body += '}\n'
    for subject, mask in [(r'aa\0ab', r'a\0'), (r'\0ab', r'\0'), ('aabc', 'ab'),
                          ('é!', 'é'), ('abc', ''), ('', 'x')]:
        inputs = f'"{subject}","{mask}"'
        body += f'echo {name}({inputs}),";";\n'
        for offset in (0, 1, -2, -20, 20):
            body += f'window_{name}({inputs},{offset});\n'
    body += f'echo {name}("aaaa","a",0,null),";",{name}("aaaa","a",0,0),";";\n'
    body += f'echo {name}("aaaa","a",-9223372036854775807-1,9223372036854775807),";";\n'
    body += f'echo {name}("aaaa","a",0,-9223372036854775807-1),";";\n'

# Encode inputs as hex so both frontends receive identical bytes, including
# control bytes that have different escape spellings across source flavors.
def literal(data):
    return f'(string)hex2bin("{data.hex()}")'


transforms = []
all_bytes = bytes(range(256))
for name in ('urlencode', 'rawurlencode'):
    for data in (b'', b'a b+~/', all_bytes):
        transforms.append(f'{name}(string:{literal(data)})')
escaped = b''.join(b'%%%02X' % byte for byte in all_bytes)
for name in ('urldecode', 'rawurldecode'):
    for data in (b'', all_bytes, escaped, escaped.lower(), b'A+B%2BC', b'%00%ff%2F',
                 b'%', b'%2', b'%GG', b'%2G', b'%+%A', b'%%41', b'%2%41', b'%252F', b'%25zz'):
        transforms.append(f'{name}(string:{literal(data)})')
for data in (b'', b'a\r\nb', b'a\rb\nc', b'\n\r', b'  \r\n', b' \n', b' \r', b'end ',
             b'\t \t\r\n', b'A\0B=Z', bytes(range(256)), b'x' * 75 + b'\r\ny'):
    transforms.append(f'quoted_printable_encode(string:{literal(data)})')
for width in (63, 66, 69, 72, 74, 75, 76):
    for tail in (b'a', b'=', b'\xc2\xa2', b'\xe2\x82\xac', b'\xf0\x9f\x98\x80', b'\x80', b'\xf5\xffZ'):
        transforms.append(f'quoted_printable_encode({literal(b"x" * width + tail)})')
for data in (b'', b'foo_bar', b'a=\r\nb', b'a=\nb', b'a=\rb', b'a=\n\rb', b'= \t\r\nZ',
             b'a \t\r\nb', b'a \t', b'a=', b'a= \t', b'= \tx', b'=zQ=2', b'=aF=Ff=00B',
             b'A\0B', b'A=\0B', b'A=00B\0C', b'==41', b'x' * 75 + b'=\r\ny'):
    transforms.append(f'quoted_printable_decode(string:{literal(data)})')
for data in (b'', b'aaaa', b'one-two words', b'a\tb\rc\nd\fe\vf', b'\0a\xffb'):
    for sep in (None, b'', b'-', b'a', b'A', b'\0\xff'):
        args = literal(data) if sep is None else f'separators:{literal(sep)},string:{literal(data)}'
        transforms.append(f'ucwords({args})')
for data in (b'', b'a\rb\nc', b'\r\n\n\r', b'\r\r\n\n', b'\0\n\xff'):
    for flag in ('', ',true', ',false'):
        transforms.append(f'nl2br({literal(data)}{flag})')
for data in (b'', bytes(range(256)), b"'\x22\\\0", b'q\\', b'\\\\', b'\\\\\\', b'\\q\\0\\\0'):
    transforms.extend(f'{name}({literal(data)})' for name in ('addslashes', 'stripslashes'))
for data in (b'', b'ab', b'abcd', b'abcde', b'a\0b', b'x' * 77):
    arg = literal(data)
    transforms.extend((f'chunk_split({arg})', f'chunk_split({arg},2)'))
    for sep in (b'', b'|', b'\0!', b'\r\n'):
        transforms.append(f'chunk_split({arg},2,{literal(sep)})')
transforms.extend(('wordwrap("a b")', 'wordwrap("a b",2)', 'wordwrap("",0,"",true)',
                   'chunk_split("abc",9223372036854775807,"|")'))
for data in (b'', b' a  bc ', b'abcdef gh ij', b'ab||cd||z', b'ab\r\ncd\n', b'a\0xyzz', b'a\tb\xffc'):
    arg = literal(data)
    for sep in (b'|', b'||', b'\r\n', b'\0q'):
        br = literal(sep)
        transforms.extend((f'wordwrap({arg},2,{br})', f'wordwrap({arg},2,{br},true)'))
for width,cut in ((0,'false'), (-1,'false'), (-1,'true'), (9223372036854775807,'true')):
    transforms.append(f'wordwrap(" abc  def ",{width},"|",{cut})')
for subject, src, dst in [
        (b'abcabc', b'abc', b'XY'), (b'abc', b'a', b'XYZ'), (b'abba', b'ab', b'ba'),
        (b'abba', b'aba', b'XYZ'), (b'\xff\0a', b'\0\xff', b'\xff\0'),
        (bytes(range(256)), bytes(range(256)), bytes(reversed(range(256)))),
        (b'', b'ab', b'xy'), (b'abc', b'', b'xy'), (b'abc', b'ab', b''),
        (b'no match', b'XYZ', b'abc')]:
    transforms.append(f'strtr({literal(subject)},{literal(src)},{literal(dst)})')
transforms.append('strtr(to:"ba",string:"ab",from:"ab")')
for name in ('str_increment', 'str_decrement'):
    values = ['1', 'b', 'B', '9', 'z', 'Z', '10', '100', 'aa', 'AA', 'aA', 'Aa',
              'a0', 'A0', '1a', '2a', '9z', 'Zz', 'z9', '9Z9', '10a', 'aA0',
              '1b', 'a1', 'AA99zz', 'z' * 40, 'a' * 40, '1' + '0' * 40]
    if name == 'str_increment':
        values += ['0', '00', '009', '0Az', 'A', 'a']
    transforms.extend(f'{name}(string:"{value}")' for value in values)
body += '\n'.join(f'echo bin2hex({call}),";";' for call in transforms)
body += """
function check_ireplace(string $search, string $replace, string $subject):void {
    $n=77;
    $result=str_ireplace($search,$replace,$subject,$n);
    echo bin2hex($result),":",$n,";",bin2hex(str_ireplace($search,$replace,$subject)),";";
}
"""
for search, replacement, subject in [
        (b'Ab', b'!', b'aBAbabAB'), (b'aa', b'x', b'AaaAa'), (b'a', b'aA', b'Aa'),
        (b'X', b'X', b'XXX'), (b'', b'!', b'abc'), (b'a', b'!', b''),
        (b'Z', b'x', b'abc'), (b'A\0b', b'\0', b'a\0B-A\0b'),
        (b'\0', b'', b'\0a\0'), (b'\xc4', b'x', b'\xe4\xc4'),
        (b'aB', b'!', b'Keep Ab CaSe')]:
    body += f'check_ireplace({literal(search)},{literal(replacement)},{literal(subject)});\n'


# Keep the PHP oracle and the compiled caller on the same binary inputs.
for left, right in [
        (b'', b''), (b'', b'0'), (b'file2', b'file10'), (b'v2.11', b'v2.9'),
        (b'x' + b'9' * 80, b'x1' + b'0' * 80), (b'0010', b'10'),
        (b'000', b'0'), (b'00a', b'a'), (b'v02', b'v2'), (b'v02', b'v010'),
        (b'v001', b'v0010'), (b' 01', b'1'), (b'A2', b'a2'), (b'a', b'['),
        (b'A\xff', b'a\xfe'), (b'ab\0x2', b'Ab\0x10'), (b'a\0', b'a'),
        (b'1\0a', b'1\0b'), (b'x \t2', b'x2'), (b'x', b'x '),
        (b'1 a', b'1a'), (b' ', b'\0'), (b'\0\0', b'\0')]:
    for a, b in ((left, right), (right, left)):
        args = f'string2:{literal(b)},string1:{literal(a)}'
        body += f'echo strnatcmp({args}),";",strnatcasecmp({args}),";";\n'

errors = ['chunk_split("",0)', 'chunk_split("x",-2)', 'wordwrap("x",2,"")',
          'wordwrap("x",0,"",true)', 'wordwrap("x",0,"|",true)']
for name in ('str_increment', 'str_decrement'):
    for invalid in (b'', b'0!', b'!z', b' a', b'a-b', b'a\0z', b'a\xff', 'é'.encode()):
        errors.append(f'{name}({literal(invalid)})')
errors.extend(f'str_decrement("{value}")' for value in ('0', '00', '09', '0a', '0Z', 'A', 'a'))
error_cases = ''.join(f'if(cli_argc()==={i+2}) {{ {call}; return 99; }}\n'
                      for i,call in enumerate(errors))

with tempfile.TemporaryDirectory() as directory:
    work = Path(directory)
    app = work/'app'
    app.mkdir()
    (app/'module.json').write_text('{}')
    main = app/'main.phs'
    expected = subprocess.check_output(['php', '-n', '-r', body], env=env)
    # PHP 8.5.7 duplicated a final break at the width boundary when cutting.
    # Pin the dev002 (8.5.12) result and compare newer PHP directly as well.
    trailing = 'echo bin2hex(wordwrap("ab||cd||",2,"||",true)),";";'
    main.write_text(error_cases + body + trailing + 'strpbrk("abc",""); echo "unreachable";')
    pinned = b'61627c7c63647c7c;'
    php_version = int(subprocess.check_output(['php', '-n', '-r', 'echo PHP_VERSION_ID;']))
    if php_version >= 80512:
        assert subprocess.check_output(['php', '-n', '-r', trailing]) == pinned
    expected += pinned
    assert b'H;' in expected and b'F;' in expected
    config = work/'toolchain.json'
    config.write_text(json.dumps({'cxx':'clang++','flags':['-std=c++23','-stdlib=libc++','-I',
        str(ROOT/'runtime/generated/public')], 'cache':str(ROOT/'build/clang/native-qualification')}))
    generated = work/'generated'
    subprocess.run(['php', ROOT/'compiler/main.php', 'build', app, ROOT/'build/clang/native-layout/facts.json',
                    generated, work/'native', config], check=True, stdout=subprocess.DEVNULL, env=env)
    exe = subprocess.run([work/'native/program'], capture_output=True)
    assert exe.stdout == expected and exe.returncode == 1 and b'strpbrk' in exe.stderr, exe
    for i, call in enumerate(errors):
        php = subprocess.check_output(['php', '-n', '-r',
            f'try {{ {call}; }} catch (ValueError $e) {{ echo $e->getMessage(); }}'], env=env)
        failure = subprocess.run([work/'native/program', *(['x'] * (i + 1))], capture_output=True)
        assert php and failure.returncode == 1 and php in failure.stderr, (call, php, failure)
    cpp = ''.join(unit.read_text() for unit in generated.rglob('*.cpp'))
    assert 'strchr(' not in cpp and 'strstr(' in cpp

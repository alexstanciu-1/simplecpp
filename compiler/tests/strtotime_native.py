"""Compare PHP's relative parser with a compiled caller and check module visibility."""
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile

from app_namespace_native import service, invoke

cxx, linker, build_dir = sys.argv[1:]
root = Path(__file__).resolve().parents[2]
base = 1706704496  # 2024-01-31 12:34:56 UTC
cases = [
    ('1970-01-01', 0), ('1969-12-31T23:59:59Z', base), ('2024-02-29T15:04:05+05:30', 0),
    ('2024-02-29', base), ('15:00', base), ('15:00 America/New_York', 0), ('January', base),
    ('next Thursday', base), ('+1 day', base), ('tomorrow', base), ('+1 month', base),
    ('last day of next month', base), ('@0', base), ('@-1', base), ('now', 0), ('now', -1),
    ('2024-03-10 02:30 America/New_York', 0), ('2024-11-03 01:30 America/New_York', 0),
    ('2024-11-03 01:30 EDT', 0), ('2024-11-03 01:30 EST', 0),
    ('2024-11-03 01:30 america/new_york', 0), ('2024-02-30', 0), ('2024-13-01', 0),
    ('2024-01-01 24:00', 0), ('', 0), ('nonsense', 0), ('2024-01-01 Mars/Olympus', 0),
    ('2024-01-01\0junk', 0), ('2024-01-01\0', 0),
]
fixed = '''function check(string $s, ?int $base):string {
    $r = strtotime($s, $base);
    if ($r === false) { return "false"; }
    return (string)(int)$r;
}
'''
for text, timestamp in cases:
    literal = json.dumps(text).replace(r'\u0000', r'\0')
    fixed += f'echo check({literal}, {timestamp}), "\\n";\n'
now = r'echo check(" ", 1706704496), "\n", (int)strtotime("now"), "\n", (int)strtotime("+1 hour", null), "\n", check("now", null);'
php = ['php', '-n', '-d', 'date.timezone=UTC', '-r']
expected = subprocess.check_output([*php, fixed]).splitlines()
with tempfile.TemporaryDirectory(prefix='scpp-strtotime-') as tmp:
    work = Path(tmp)
    app, out = work/'app', work/'out'
    app.mkdir()
    (app/'main.php').write_text(fixed + now)
    manifest = app/'module.json'
    manifest.write_text(json.dumps({'dependencies': {'runtime': {'modules': ['datetime']}}}))
    proc = service(app, out)
    try:
        result = invoke(proc)
        assert 'error' not in result, result
        bundle = json.loads((out/'manifest.json').read_text())
        units = [out/path for path in bundle['translation_units']]
        lowered = ''.join(unit.read_text() for unit in units)
        assert '::scpp::dt::php_strtotime(' in lowered and 'timelib' not in lowered
        exe = work/'run'
        subprocess.run([cxx, '-std=c++23', '-stdlib=libc++', f'--ld-path={linker}', '-I',
                        root/'runtime/generated/public', *units,
                        *(Path(build_dir)/Path(lib).name for lib in bundle['link_inputs']), '-o', exe], check=True)
        before = int(subprocess.check_output([*php, 'echo time();']))
        actual = subprocess.check_output([exe], env={**os.environ, 'TZ': 'Asia/Tokyo'}).splitlines()
        after = int(subprocess.check_output([*php, 'echo time();']))
        assert actual[:-4] == expected, list(zip(cases, actual, expected))
        # Pinned timelib/PHP 8.5.12 rejects whitespace alone; earlier 8.5 releases accepted it.
        assert actual[-4] == b'false'
        for value, offset in zip(actual[-3:], (0, 3600, 0)):
            assert before <= int(value) - offset <= after
        manifest.write_text('{}')
        assert 'requires module runtime-datetime' in invoke(proc).get('error', '')
    finally:
        proc.stdin.close()
        proc.wait()

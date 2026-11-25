"""Inspect equality from a PHP caller on wrappers, literals and typed peers."""
from pathlib import Path
import subprocess
import tempfile

from check_apps import build

# Each input mode fixes an expression and a labelled list of concrete states.
helpers = '''
function flag(string $s): result_or_false<bool> {
    if ($s === "miss") { return false_sentinel; }
    return $s === "true";
}
function offset_for(string $s): result_or_false<int> {
    return strpos("abc", $s);
}
function maybe(string $s): ?int {
    if ($s === "missing") { return null; }
    if ($s === "zero") { return 0; }
    return 2;
}
'''
flags = ('false', 'true', 'miss')
search = ('a', 'c', 'x')
nullable = ('zero', 'two', 'missing')
modes = {
    'falseable bool / falseable bool': ('flag($args[1])', 'flag($args[2])', flags, flags),
    'search / search': ('offset_for($args[1])', 'offset_for($args[2])', search, search),
    'nullable int / nullable int': ('maybe($args[1])', 'maybe($args[2])', nullable, nullable),
    'falseable bool / literal false': ('flag($args[1])', 'false', flags, ('-',)),
    'falseable bool / named bool': ('flag($args[1])', '$b', flags, ('false', 'true')),
    'falseable bool / sentinel': ('flag($args[1])', 'false_sentinel', flags, ('-',)),
    'search / literal false': ('offset_for($args[1])', 'false', search, ('-',)),
    'search / plain int': ('offset_for($args[1])', '$i', search, ('zero', 'two')),
}


def inspect(work, mode, op):
    left, right, left_inputs, right_inputs = modes[mode]
    app = work/'app'
    app.mkdir()
    (app/'module.json').write_text('{"general_api":true}')
    entry = f'''
$args=cli_arguments();
$b=$args[2] === "true";
$i=$args[2] === "two" ? 2 : 0;
echo ({left} {op} {right}) ? "Y" : "N";
return 0;
'''
    (app/'main.php').write_text(helpers + entry)
    try:
        exe = build(app, work)
    except RuntimeError as error:
        return str(error).strip().replace(str(app)+'/', '')
    rows = []
    for a in left_inputs:
        row = []
        for b in right_inputs:
            result = subprocess.run([exe, a, b], capture_output=True, timeout=5)
            if result.returncode == 0 and not result.stderr:
                row.append(result.stdout.decode())
            elif result.stderr.strip() == b'carrier_no_value' and not result.stdout:
                row.append('R')
            else:
                raise AssertionError((mode, op, a, b, result))
        rows.append(''.join(row))
    return '/'.join(rows)


if __name__ == '__main__':
    for mode in modes:
        for op in ('==', '===', '!=', '!=='):
            with tempfile.TemporaryDirectory(prefix='php-states-') as directory:
                print(mode, op, inspect(Path(directory), mode, op), sep='\t', flush=True)

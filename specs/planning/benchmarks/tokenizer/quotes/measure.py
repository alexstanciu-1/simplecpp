"""Compare two translated scanners on dev002. Pass the baseline frontend.php.scpp as argv[1]."""
import csv
import json
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile

ROOT = Path(__file__).resolve().parents[5]
sys.path.insert(0, str(ROOT/'compiler/tests'))
from translated_punctuation import scanner_project
from translated_preparation_recovery import fresh_build

PLAIN = 'factor = (header_count + 17) * 2 '
HEAVY = """x = "say \\"café\\" // ok" + 'a # b' """
COPIES = 2048
PASSES = 256
MODES = ('php', 'cpp', 'js', 'python')


def compile_probe(work, baseline):
    work.mkdir()
    app = scanner_project(work)
    if baseline:
        shutil.copyfile(baseline, app/'frontend.php')
    (app/'module.json').write_text(json.dumps({'general_api': True, 'dependencies': {
        'runtime': {'modules': ['datetime']}}}))
    source = '''
$cli = cli_arguments();
$unit string = hex2bin("PLAIN_HEX");
if ($cli[1] === "heavy") $unit = hex2bin("HEAVY_HEX");
$input = str_repeat($unit, COPIES);
$mode = Scan_Flavor::php;
if ($cli[2] === "cpp") $mode = Scan_Flavor::cpp;
if ($cli[2] === "js") $mode = Scan_Flavor::js;
if ($cli[2] === "python") $mode = Scan_Flavor::python;
$total = 0;
$start = dt_monotonic_ms();
for ($i = 0; $i < PASSES; $i++) {
    $tokens = tokenize_source("bench", $input, $mode);
    $total += count($tokens);
}
$elapsed = dt_monotonic_ms() - $start;
echo $elapsed, " ", $total;
return 0;
'''
    (app/'main.php').write_text(source.replace('PLAIN_HEX', PLAIN.encode().hex()).replace(
        'HEAVY_HEX', HEAVY.encode().hex()).replace('COPIES', str(COPIES)).replace('PASSES', str(PASSES)))
    out = work/'bundle'
    result = fresh_build(app, out)
    assert 'error' not in result, result
    manifest = json.loads((out/'manifest.json').read_text())
    exe = work/'probe'
    subprocess.run(['clang++', '-O2', '-g0', '-std=c++23', '-stdlib=libc++', '--ld-path=ld.mold',
                    '-I', ROOT/'runtime/generated/public', *(out/u for u in manifest['translation_units']),
                    *manifest['link_inputs'], '-o', exe], check=True)
    return exe


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='quote-bench-') as tmp:
        root = Path(tmp)
        exes = {'before': compile_probe(root/'before', Path(sys.argv[1])),
                'after': compile_probe(root/'after', None)}
        writer = csv.writer(sys.stdout, lineterminator='\n')
        writer.writerow(['sample', 'flavor', 'version', 'input', 'bytes', 'tokens', 'ms'])
        for rep in range(12):
            for mode in MODES:
                pair = [('before', 'plain'), ('after', 'plain')]
                for version, data in (pair if rep % 2 == 0 else pair[::-1]) + [('after', 'heavy')]:
                    ms, tokens = map(int, subprocess.check_output([exes[version], data, mode]).split())
                    size = len((PLAIN if data == 'plain' else HEAVY).encode()) * COPIES * PASSES
                    assert tokens == ((9 if data == 'plain' else 5) * COPIES + 1) * PASSES, tokens
                    if rep:
                        writer.writerow([rep, mode, version, data, size, tokens, ms])

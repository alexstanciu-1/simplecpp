#!/usr/bin/env python3
"""Concrete family behavior, error contracts, incremental reuse and native timings."""
import argparse
import json
from pathlib import Path
import random
import re
import statistics
import sys
import time

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks

SOURCE = '''function bad_index(): int { $v vector<int> = [1]; return $v[-1]; }
function bad_divide(): int { $a int = 7; $b int = 0; return $a / $b; }
function bad_modulo(): int { $a int = 7; $b int = 0; return $a % $b; }
function bad_cast(): int8 { $a int = 128; return (int8)$a; }
function bad_unsigned_cast(): uint8 { $a int = -1; return (uint8)$a; }
function work(): int {
 $values vector<int> = [];
 for ($i int = 0; $i < 300; $i++) { $values[] = $i; }
 $copy vector<int> = $values;
 $copy = $copy;
 $copy[0] = 77;
 if ($values[0] !== 0) { return 1; }
 if ($copy[0] !== 77) { return 2; }
 $sum int = 0;
 foreach ($values as $key => $value) { $sum += $value; }
 if ($sum !== 44850) { return 3; }
 if (count($values) !== 300) { return 4; }
 $bits vector<bool> = [true, false];
 if (!$bits[0]) { return 5; }
 $nested vector<vector<int>> = [[3], [5]];
 if ($nested[1][0] !== 5) { return 6; }
 WIDTH_CASES
 $a int = 17; $b int = 3;
 if (($a / $b) !== 5) { return 7; }
 if (($a % $b) !== 2) { return 8; }
 $small int8 = (int8)127;
 $negative int8 = (int8)(-128);
 $unsigned uint8 = (uint8)255;
 if ((int)$small !== 127) { return 10; }
 if ((int)$negative !== -128) { return 11; }
 if ((int)$unsigned !== 255) { return 12; }
 return 0;
}
return work();
'''


def native_representations(runner, folder, units):
    """Check native limits and aliasing independently of source arithmetic admission."""
    includes = ['#include <limits>']
    checks = []
    for prefix in ['int', 'uint']:
        for bits in [8, 16, 32, 64]:
            name = prefix + str(bits)
            includes.append('#include "specializations/vector_' + name + '.hpp"')
            checks.append(f"""{{
 using integer = scpp::internal::{name};
 using vector = scpp::internal::vector_{name};
 using native = {name}_t;
 static_assert(sizeof(integer) == sizeof(native));
 auto low = std::numeric_limits<native>::min();
 auto high = std::numeric_limits<native>::max();
 if (integer(low).native_value() != low || integer(high).native_value() != high) return 1;
 vector values; values.reserve(1); values.append(integer(low));
 values.append(values.at(scpp::internal::int64(0)));
 vector copy(values); values.at(scpp::internal::int64(0)) = integer(high);
 if (copy.at(scpp::internal::int64(0)).native_value() != low) return 2;
 vector moved(static_cast<vector&&>(values));
 if (moved.count().native_value() != 2 || values.size() != 0) return 3;
 moved.clear(); if (moved.size() != 0) return 4;
}}""")
    source = folder / 'representation_probe.cpp'
    source.write_text('\n'.join(includes) + '\nint main() {\n' + '\n'.join(checks) + '\n}\n')
    objects = [folder / f'probe-{i}.o' for i, unit in enumerate(units) if unit.name != 'entry.cpp']
    binary = folder / 'representations'
    runner.run('representations-build', ['clang++', '-std=c++23', '-O2', '-g0',
               '-I' + str(ROOT / 'runtime/include'), '-I' + str(folder), source, *objects, '-o', binary])
    runner.run('representations-run', [binary])


def record_elements(runner, out):
    """Source-derived capabilities and equal short names use the same family recipe."""
    source = out / 'record-source'
    source.mkdir()
    (source / 'left.phs').write_text('namespace Left; struct Item { public int $value = 3; }')
    (source / 'right.phs').write_text('namespace Right; struct Item { public int $value = 7; }')
    (source / 'main.phs').write_text(r"""$left = new \Left\Item(); $right = new \Right\Item();
$a vector<\Left\Item> = [$left]; $b vector<\Right\Item> = [$right];
$nested vector<vector<\Left\Item>> = [$a];
$copy = $nested; $first = $copy[0]; $row = $first[0]; $row->value = 9;
if ($nested[0][0]->value !== 3) { return 1; }
return $a[0]->value + $b[0]->value - 10;""")
    for variant in ['original', 'concrete']:
        folder = out / ('records-' + variant)
        cmd = ['php', APP / 'main.php', '--s2s', source, '--entry', 'main.phs', '--output', folder]
        if variant == 'concrete':
            cmd += ['--prelower-templates']
        data = json.loads(runner.run('records-' + variant + '-emit', cmd, cli_reason='Compiler CLI publication contract'))
        units = [folder / f['path'] for f in data['artifacts'] if f['kind'] == 'translation_unit']
        binary = folder / 'app'
        runner.run('records-' + variant + '-build', ['clang++', '-std=c++23', '-O0', '-g0',
                   '-I' + str(ROOT / 'runtime/include'), '-I' + str(folder), *units, '-o', binary], timeout=120)
        runner.run('records-' + variant + '-run', [binary])


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    args = parser.parse_args()
    out = args.results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    source = out / 'source'
    source.mkdir()
    widths = []
    for prefix in ['int', 'uint']:
        for bits in [8, 16, 32, 64]:
            name = prefix + str(bits)
            widths.append(f'${name}_values vector<{name}> = [1, 2]; ${name}_values[] = 3; '
                          f'if (count(${name}_values) !== 3) {{ return 9; }}')
    (source / 'main.phs').write_text(SOURCE.replace('WIDTH_CASES', '\n'.join(widths)))
    report = {}
    with CommandRunner(out / 'logs', ROOT, jobs=12, journals=[out / 'commands.json']) as runner:
        def lifecycle():
            runner.run('lifecycle', ['php', APP / 'tests/fixtures/prelower_templates.php', out / 'lifecycle-source'])

        def regression():
            runner.run('default-incremental', ['php', APP / 'tests/incremental_cpp.php'])

        run_tasks([Task('lifecycle', lifecycle), Task('default-incremental', regression)], jobs=12)
        variants = {}
        for variant in ['original', 'concrete']:
            folder = out / variant
            cmd = ['php', APP / 'main.php', '--s2s', source, '--output', folder]
            if variant == 'concrete':
                cmd += ['--prelower-templates']
            data = json.loads(runner.run(variant + '-emit', cmd, cli_reason='Compiler CLI publication contract'))
            units = [folder / f['path'] for f in data['artifacts'] if f['kind'] == 'translation_unit']
            variants[variant] = (folder, units)
            if variant == 'concrete':
                text = '\n'.join(f['text'] for f in data['artifacts'])
                assert 'scpp::int_t<' not in text and 'scpp::vector_t<' not in text
                assert 'scpp/generated/operators.hpp' not in text
                names = [f['path'] for f in data['artifacts']]
                assert len(names) == len(set(names))
                for prefix in ['int', 'uint']:
                    for bits in [8, 16, 32, 64]:
                        assert f'specializations/vector_{prefix}{bits}.hpp' in names
        timings = []
        error_outputs = {}
        # Native work only: matched no-PCH/debug-disabled builds, lowering excluded.
        for opt in ['O0', 'O2']:
            for trial in range(3):
                order = list(variants)
                random.Random(trial).shuffle(order)
                for variant in order:
                    folder, units = variants[variant]
                    objects = []
                    groups = {'application_s': 0.0, 'support_s': 0.0}
                    for i, unit in enumerate(units):
                        obj = folder / f'probe-{i}.o'
                        objects.append(obj)
                        start = time.perf_counter()
                        runner.run(f'{variant}-{opt}-{trial}-{i}',
                                   ['clang++', '-std=c++23', '-' + opt, '-g0',
                                    '-I' + str(ROOT / 'runtime/include'), '-I' + str(folder),
                                    '-c', unit, '-o', obj], timeout=120)
                        group = 'support_s' if 'specializations' in unit.parts else 'application_s'
                        groups[group] += time.perf_counter() - start
                    binary = folder / 'app'
                    start = time.perf_counter()
                    runner.run(f'{variant}-{opt}-{trial}-link', ['clang++', *objects, '-o', binary])
                    link_s = time.perf_counter() - start
                    runner.run(f'{variant}-{opt}-{trial}-run', [binary])
                    timings.append(dict(variant=variant, opt=opt, trial=trial,
                                        compile_s=sum(groups.values()), link_s=link_s, **groups))
                    if trial == 0:
                        # Catch production exceptions at the native test boundary, comparing
                        # stable messages/codes/details; trace addresses are intentionally omitted.
                        harness = folder / 'error_probe.cpp'
                        calls = []
                        headers = []
                        for name in ['bad_index', 'bad_divide', 'bad_modulo', 'bad_cast', 'bad_unsigned_cast']:
                            header = folder / 'callables' / ('n' + name.encode().hex() + '.hpp')
                            symbol = re.search(r'(function_[A-Za-z0-9_]+)\(', header.read_text()).group(1)
                            headers.append('#include "' + str(header) + '"')
                            calls.append('try { (void)' + symbol + '(); return 1; } '
                                         'catch (const scpp::runtime_error& e) { '
                                         'std::cout << e.what() << "|" << e.code() << "|" '
                                         '<< e.component() << "|" << e.operator_symbol(); '
                                         'for (const auto& d : e.details()) std::cout << "|" << d.key << "=" << d.value; '
                                         'std::cout << "\\n"; }')
                        harness.write_text('#include <cstdint>\n#include <iostream>\n#include "scpp/runtime_error.hpp"\n'
                                           + '\n'.join(headers) + '\nint main() {\n' + '\n'.join(calls) + '\n}\n')
                        inputs = [obj for obj, unit in zip(objects, units) if unit.name != 'entry.cpp']
                        error_binary = folder / 'errors'
                        runner.run(f'{variant}-{opt}-errors-link', ['clang++', '-std=c++23', '-' + opt,
                                   '-I' + str(ROOT / 'runtime/include'), '-I' + str(folder),
                                   harness, *inputs, '-o', error_binary])
                        error_outputs[variant, opt] = runner.run(f'{variant}-{opt}-errors-run', [error_binary]).decode()
        assert len(set(error_outputs.values())) == 1, error_outputs
        report['errors'] = next(iter(error_outputs.values())).splitlines()
        report['timings'] = timings
        report['medians'] = {
            opt: {v: {key: statistics.median(t[key] for t in timings if t['variant'] == v and t['opt'] == opt)
                      for key in ['application_s', 'support_s', 'compile_s', 'link_s']} for v in variants}
            for opt in ['O0', 'O2']}

        folder, units = variants['concrete']
        native_representations(runner, folder, units)
        sanitized = folder / 'sanitized'
        runner.run('concrete-sanitizers-build', ['clang++', '-std=c++23', '-O1', '-g0',
                   '-fsanitize=address,undefined', '-fno-omit-frame-pointer',
                   '-I' + str(ROOT / 'runtime/include'), '-I' + str(folder), *units, '-o', sanitized], timeout=120)
        runner.run('concrete-sanitizers-run', ['env', 'ASAN_OPTIONS=detect_leaks=0',
                                             'UBSAN_OPTIONS=halt_on_error=1', sanitized])
        cache = out / 'native-cache'
        native_command = ['php', APP / 'tools/build_cpp.php', folder, cache, '--no-pch']
        first = json.loads(runner.run('native-cold', native_command, timeout=120))
        second = json.loads(runner.run('native-warm', native_command, timeout=120))
        assert first['compiled'] > 0 and second['compiled'] == 0
        assert second['linked'] == 0 and second['pch_built'] == 0
        runner.run('native-cached-run', [second['executable']])
        support = {p.name: p.read_bytes() for p in (folder / 'specializations').iterdir() if p.suffix in ['.hpp', '.cpp']}
        main_source = source / 'main.phs'
        main_source.write_text(main_source.read_text().replace('$sum int = 0;', '$sum int = 0; $unused int = 123;'))
        runner.run('body-edit-emit', ['php', APP / 'main.php', '--s2s', source, '--output', folder,
                                     '--prelower-templates'], cli_reason='Compiler CLI publication contract')
        assert support == {p.name: p.read_bytes() for p in (folder / 'specializations').iterdir() if p.suffix in ['.hpp', '.cpp']}
        third = json.loads(runner.run('native-body-edit', native_command, timeout=120))
        assert third['compiled'] == 1 and third['reused'] == second['reused'] - 1
        runner.run('native-body-edit-run', [third['executable']])
        report['native_cache'] = [first, second, third]
        report['preprocessed'] = {}
        for variant, (folder, units) in variants.items():
            unit = next(u for u in units if 'units' in u.parts)
            text = runner.run(variant + '-preprocess', ['clang++', '-std=c++23', '-E',
                              '-I' + str(ROOT / 'runtime/include'), '-I' + str(folder), unit]).decode()
            report['preprocessed'][variant] = dict(bytes=len(text.encode()),
                                                   template_tokens=len(re.findall(r'\btemplate\s*<', text)))
        record_elements(runner, out)
        report['record_elements'] = True
    report['passed'] = True
    (out / 'summary.json').write_text(json.dumps(report, indent=2) + '\n')
    print(json.dumps(report['medians']))


if __name__ == '__main__':
    main()

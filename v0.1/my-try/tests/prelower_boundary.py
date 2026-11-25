#!/usr/bin/env python3
"""Native ABI experiment, not compiler lowering or a second runtime registry.

A test-only binding table supplies concrete runtime types/operations. One emitter
handles every owner; production selection remains with existing registry/prepared
facts. No source semantics or runtime algorithms are implemented here.
"""
import argparse
import json
from pathlib import Path
import shutil
import sys

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
FIXTURE = APP / 'tests/fixtures/prelower_boundary'
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks

TYPES = {
    'Integer': 'scpp::int_t<std::int64_t>',
    'Value': 'Tracked',
    'Values': 'scpp::vector_t<Tracked>',
    'Nested': 'scpp::vector_t<scpp::vector_t<Tracked>>',
    'Handle': 'scpp::shared_p<Tracked>',
}
OWNED_RESULTS = [('Values', 'make_values', 'provider_values')]
BORROWS = {'Leaf': ('Tracked', False), 'ConstLeaf': ('Tracked', True),
           'Row': ('scpp::vector_t<Tracked>', False)}
# Parameter mappings describe transport, not family-specific emitter dispatch.
# Owners bind by reference; borrowed addresses never acquire ownership.
OPS = [
    ('void', 'set_integer', [('Integer&', 'x'), ('int', 'v')], 'x = v;'),
    ('long', 'read_integer', [('const Integer&', 'x')], 'return x.native_value();'),
    ('void', 'set_value', [('Value&', 'x'), ('int', 'v')], 'x.value = v;'),
    ('int', 'read_value', [('const Value&', 'x')], 'return x.value;'),
    ('void', 'reserve_values', [('Values&', 'x'), ('int', 'n')], 'x.reserve(n);'),
    ('void', 'append_value', [('Values&', 'x'), ('int', 'v')], 'x.append(Tracked(v));'),
    ('void', 'append_alias', [('Values&', 'x')], 'x.append(x.at(0));'),
    ('long', 'size_values', [('const Values&', 'x')], 'return x.size();'),
    ('bool', 'bounds_error', [('const Values&', 'x')],
     'try { (void)x.at(scpp::int_t<>(-1)); } catch(const scpp::runtime_error& e) { return e.code()=="bounds_error" && e.operator_symbol()=="operator[]" && std::string(e.what()).find("vector index -1")!=std::string::npos; } return false;'),
    ('void', 'clear_values', [('Values&', 'x')], 'x.clear();'),
    ('Leaf', 'leaf', [('Values&', 'x'), ('int', 'i')], 'return {&x.at(i)};'),
    ('ConstLeaf', 'const_leaf', [('const Values&', 'x'), ('int', 'i')], 'return {&x.at(i)};'),
    ('int', 'read_leaf', [('Leaf', 'x')], 'return x.value;'),
    ('int', 'read_const_leaf', [('ConstLeaf', 'x')], 'return x.value;'),
    ('void', 'write_leaf', [('Leaf', 'x'), ('int', 'v')], 'x.value = v;'),
    ('void', 'append_row', [('Nested&', 'x'), ('const Values&', 'v')], 'x.append(v);'),
    ('Row', 'row', [('Nested&', 'x'), ('int', 'i')], 'return {&x.at(i)};'),
    ('Leaf', 'row_leaf', [('Row', 'x'), ('int', 'i')], 'return {&x.at(i)};'),
    ('ConstLeaf', 'const_nested_leaf', [('const Nested&', 'x'), ('int', 'i'), ('int', 'j')], 'return {&x.at(i).at(j)};'),
    ('const void*', 'nested_address', [('const Nested&', 'x'), ('int', 'i'), ('int', 'j')], 'return &x.at(i).at(j);'),
    ('void', 'make_handle', [('Handle&', 'x'), ('int', 'v')], 'x = RuntimeHandle(std::make_shared<Tracked>(v));'),
    ('long', 'handle_count', [('const Handle&', 'x')], 'return x.use_count();'),
    ('const void*', 'handle_address', [('const Handle&', 'x')], 'return x.arrow();'),
    ('Leaf', 'handle_leaf', [('Handle&', 'x')], 'return {&x.deref()};'),
]
RUNTIME = '''#include "instrument.hpp"
#include "scpp/vector_t.hpp"
#include "scpp/shared_p.hpp"
#include <memory>
#include <utility>
'''
ALIASES = '\n'.join(f'using Runtime{k} = {v};' for k, v in TYPES.items())
PROVIDER = '''
// Existing runtime constructors/append behavior, behind a by-value native API.
RuntimeValues provider_values() {
    RuntimeValues value;
    value.append(Tracked(13)); value.append(Tracked(17));
    return value;
}
'''


def emit(folder, layouts, opaque):
    header = '#pragma once\n#include <cstddef>\n'
    if not opaque:
        header += RUNTIME + ALIASES + '\n'
    support = RUNTIME + ALIASES + '\n#include "interface.hpp"\n' + PROVIDER
    for name, native in TYPES.items():
        if opaque:
            size, alignment = layouts[name]
            header += f'''struct alignas({alignment}) {name} {{
    unsigned char storage[{size}];
    {name}(); ~{name}();
    {name}(const {name}&); {name}({name}&&);
    {name}& operator=(const {name}&); {name}& operator=({name}&&);
}};
'''
            support += f'''
static_assert(sizeof({name}) == sizeof(Runtime{name}));
static_assert(alignof({name}) == alignof(Runtime{name}));
Runtime{name}& unwrap({name}& x) {{
    return *std::launder(reinterpret_cast<Runtime{name}*>(x.storage));
}}
const Runtime{name}& unwrap(const {name}& x) {{
    return *std::launder(reinterpret_cast<const Runtime{name}*>(x.storage));
}}
{name}::{name}() {{ ::new(static_cast<void*>(storage)) Runtime{name}(); }}
{name}::~{name}() {{ std::destroy_at(&unwrap(*this)); }}
{name}::{name}(const {name}& x) {{ ::new(static_cast<void*>(storage)) Runtime{name}(unwrap(x)); }}
{name}::{name}({name}&& x) {{ ::new(static_cast<void*>(storage)) Runtime{name}(std::move(unwrap(x))); }}
{name}& {name}::operator=(const {name}& x) {{ unwrap(*this)=unwrap(x); return *this; }}
{name}& {name}::operator=({name}&& x) {{ unwrap(*this)=std::move(unwrap(x)); return *this; }}
'''
        else:
            header += f'using {name} = Runtime{name};\n'
            support += f'{name}& unwrap({name}& x) {{ return x; }}\nconst {name}& unwrap(const {name}& x) {{ return x; }}\n'
    for name, (native, readonly) in BORROWS.items():
        qualifier = 'const ' if readonly else ''
        header += f'struct {name} {{ {qualifier}void* address; }};\n'
        support += f'{qualifier}{native}& unwrap({name} x) {{ return *static_cast<{qualifier}{native}*>(x.address); }}\n'
    for result, name, parameters, body in OPS:
        declarations, bindings = [], []
        for kind, arg in parameters:
            is_object = kind.replace('const ', '').rstrip('&') in TYPES or kind in BORROWS
            declarations.append(f'{kind} input_{arg}' if is_object else f'{kind} {arg}')
            if is_object:
                bindings.append(f'auto& {arg} = unwrap(input_{arg});')
        signature = f'{result} {name}(' + ', '.join(declarations) + ')'
        header += signature + ';\n'
        support += signature + ' { ' + ' '.join(bindings) + ' ' + body + ' }\n'
    # The same destination-construction mechanism serves any owned result binding.
    for result, operation, provider in OWNED_RESULTS:
        if opaque:
            tag = operation + '_tag'
            header = header.replace(f'    {result}();',
                f'    struct {tag} {{}}; explicit {result}({tag});\n    {result}();')
            support += f'{result}::{result}({tag}) {{ ::new(static_cast<void*>(storage)) Runtime{result}({provider}()); }}\n'
            support += f'{result} {operation}() {{ return {result}({result}::{tag}{{}}); }}\n'
        else:
            support += f'{result} {operation}() {{ return {provider}(); }}\n'
        header += f'{result} {operation}();\n'
    (folder / 'interface.hpp').write_text(header)
    (folder / 'support.cpp').write_text(support)
    for name in ['instrument.hpp', 'instrument.cpp', 'scenario.cpp']:
        shutil.copyfile(FIXTURE / name, folder / name)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', required=True, type=Path)
    args = parser.parse_args()
    out = args.results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    base = ['clang++', '-std=c++23', '-g0', '-I' + str(ROOT / 'runtime/include')]
    with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
        layout = out / 'layout'
        layout.mkdir()
        shutil.copyfile(FIXTURE / 'instrument.hpp', layout / 'instrument.hpp')
        source = RUNTIME + ALIASES + '\nint main() {\n'
        for name in TYPES:
            source += f'std::printf("{name} %zu %zu\\n", sizeof(Runtime{name}), alignof(Runtime{name}));\n'
        source += '}\n'
        (layout / 'layout.cpp').write_text(source)
        runner.run('layout-build', [*base, layout / 'layout.cpp', '-o', layout / 'probe'])
        data = runner.run('layout-read', [layout / 'probe']).decode()
        layouts = {name: [int(size), int(alignment)] for name, size, alignment in (line.split() for line in data.splitlines())}
        version = runner.run('clang-version', ['clang++', '--version']).decode()
        def check(mode, flags):
            results = {}
            for variant in ['direct', 'opaque']:
                folder = out / f'{mode}-{variant}'
                folder.mkdir()
                emit(folder, layouts, variant == 'opaque')
                objects = []
                # Separate compilation, no LTO: the boundary cannot disappear by inlining.
                for unit in ['instrument', 'support', 'scenario']:
                    obj = folder / (unit + '.o')
                    runner.run(f'{mode}-{variant}-{unit}', [*base, *flags, '-c', folder / (unit + '.cpp'), '-o', obj])
                    objects.append(obj)
                runner.run(f'{mode}-{variant}-link', [*base, *flags, *objects, '-o', folder / 'proof'])
                output = runner.run(f'{mode}-{variant}-run', ['env', 'ASAN_OPTIONS=detect_leaks=0', folder / 'proof']).decode()
                results[variant] = {parts[0]: list(map(int, parts[1:])) for parts in (line.split() for line in output.splitlines())}
                if variant == 'opaque':
                    expanded = runner.run(f'{mode}-application-preprocess', [*base, '-E', folder / 'scenario.cpp']).decode()
                    assert 'class vector_t' not in expanded and 'class shared_p' not in expanded
            assert results['direct'] == results['opaque'], results
            for phase in ['integer', 'borrow', 'nested_borrow']:
                assert results['opaque'][phase] == [0] * 7, (phase, results)
            assert results['direct']['seed'][0] > 0
            assert results['direct']['copy_failure_cleanup'][4] == 1
            assert results['direct']['copy_failure_cleanup'][6] == 1
            if mode == 'O0':
                # Sensitivity control: intentionally add allocation and a payload copy
                # to a borrowed read. Reuse all other objects and application code.
                support = (folder / 'support.cpp').read_text()
                old = 'int read_leaf(Leaf input_x) { auto& x = unwrap(input_x);'
                assert old in support
                support = support.replace(old, old + ' auto* extra = new Tracked(x); delete extra;')
                (folder / 'negative.cpp').write_text(support)
                runner.run('negative-build', [*base, '-O0', '-c', folder / 'negative.cpp', '-o', folder / 'negative.o'])
                runner.run('negative-link', [*base, '-O0', folder / 'instrument.o', folder / 'scenario.o',
                                            folder / 'negative.o', '-o', folder / 'negative'])
                output = runner.run('negative-run', [folder / 'negative']).decode()
                negative = {p[0]: list(map(int, p[1:])) for p in (line.split() for line in output.splitlines())}
                assert negative['borrow'][0] > 0 and negative['borrow'][4] > 0, negative
                results['negative_control'] = negative
            return results
        modes = [('O0', ['-O0']), ('O2', ['-O2']),
                 ('sanitized', ['-O1', '-fsanitize=address,undefined', '-fno-omit-frame-pointer']),
                 ('no-elide', ['-O0', '-fno-elide-constructors'])]
        outcomes = run_tasks([Task(mode, lambda mode=mode, flags=flags: check(mode, flags)) for mode, flags in modes])
        summary = {'compiler': version, 'layouts': layouts,
                   'counter_columns': ['allocations', 'bytes', 'frees', 'constructed', 'copied', 'moved', 'destroyed'],
                   'results': {item.name: item.value for item in outcomes}}
        (out / 'summary.json').write_text(json.dumps(summary, indent=2) + '\n')
        print('PASS: direct and opaque counts match in all four configurations')


if __name__ == '__main__':
    main()

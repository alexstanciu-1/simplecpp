#!/usr/bin/env python3
"""Native composition proof, not production lowering or a second type registry.

Fixture bindings describe methods; one emitter supplies every concrete owner's
storage/lifecycle. Native traits and placement construction follow the existing
opaque representation contract. Dependent layouts are probed in explicit fixture
layers to expose the bootstrap requirement for production integration.
"""
import argparse
import json
from pathlib import Path
import shutil
import statistics
import sys
import time

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
FIXTURE = APP / 'tests/fixtures/prelower_composition'
INSTRUMENT = APP / 'tests/fixtures/prelower_boundary'
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks

RUNTIME = '''#include "scpp/vector_t.hpp"
#include "scpp/memory.hpp"
#include <memory>
#include <new>
#include <type_traits>
#include <utility>
'''
TRAITS = ['is_default_constructible', 'is_copy_constructible', 'is_move_constructible',
          'is_copy_assignable', 'is_move_assignable', 'is_nothrow_default_constructible',
          'is_nothrow_copy_constructible', 'is_nothrow_move_constructible',
          'is_nothrow_copy_assignable', 'is_nothrow_move_assignable', 'is_nothrow_destructible']


def vector_methods(element):
    # Test operation bindings only; no runtime algorithm is reproduced.
    return [('std::size_t', 'size() const noexcept', 'return unwrap(*this).size();'),
            ('void', 'reserve(std::size_t n)', 'unwrap(*this).reserve(n);'),
            ('void', f'append(const {element}& value)', 'unwrap(*this).append(value);'),
            ('void', f'append({element}&& value)', 'unwrap(*this).append(std::move(value));'),
            (element + '&', 'at(std::size_t index)', 'return unwrap(*this).at(index);'),
            ('const ' + element + '&', 'at(std::size_t index) const', 'return unwrap(*this).at(index);')]


BINDINGS = {
    'Integer': ('scpp::int_t<std::int64_t>', [('std::int64_t', 'native_value() const noexcept', 'return unwrap(*this).native_value();')]),
    'Vector_Int': ('scpp::vector_t<Integer>', vector_methods('Integer')),
    'Vector_Tracked': ('scpp::vector_t<Tracked>', vector_methods('Tracked')),
    'Vector_Invoice': ('scpp::vector_t<Invoice>', vector_methods('Invoice')),
    'Vector_Vector_Int': ('scpp::vector_t<Vector_Int>', vector_methods('Vector_Int')),
    'Shared_Invoice': ('scpp::shared_p<Invoice>', [('Invoice*', 'arrow() const noexcept', 'return unwrap(*this).arrow();')]),
    'Unique_Invoice': ('scpp::unique_p<Invoice>', [('Invoice*', 'arrow() const noexcept', 'return unwrap(*this).arrow();')]),
}
LAYERS = [['Integer'], ['Vector_Int', 'Vector_Tracked'], ['Invoice'],
          ['Vector_Invoice', 'Vector_Vector_Int', 'Shared_Invoice', 'Unique_Invoice']]
CONSTRUCTORS = {'Integer': [('std::int64_t value', 'value')]}
FACTORIES = [('Shared_Invoice', 'make_shared_invoice', 'scpp::shared<Invoice>()'),
             ('Unique_Invoice', 'make_unique_invoice', 'scpp::unique<Invoice>()')]


def concrete(name, layout):
    native, methods = BINDINGS[name]
    size, alignment, *traits = layout
    header = f'''class alignas({alignment}) {name} {{
    unsigned char storage_[{size}];
public:
    void* address() noexcept {{ return storage_; }}
    const void* address() const noexcept {{ return storage_; }}
    struct operation_result {{}};
    {name}(operation_result, void (*construct)(void*));
'''
    support = f'''using Native_{name} = {native};
static_assert(sizeof({name}) == sizeof(Native_{name}));
static_assert(alignof({name}) == alignof(Native_{name}));
Native_{name}& unwrap({name}& value) {{ return *std::launder(static_cast<Native_{name}*>(value.address())); }}
const Native_{name}& unwrap(const {name}& value) {{ return *std::launder(static_cast<const Native_{name}*>(value.address())); }}
{name}::{name}(operation_result, void (*construct)(void*)) {{ construct(address()); }}
'''
    signatures = [('', f'{name}()', f'::new(address()) Native_{name}();'),
                  ('', f'{name}(const {name}& other)', f'::new(address()) Native_{name}(unwrap(other));'),
                  ('', f'{name}({name}&& other)', f'::new(address()) Native_{name}(std::move(unwrap(other)));'),
                  (name + '& ', f'operator=(const {name}& other)', 'unwrap(*this) = unwrap(other); return *this;'),
                  (name + '& ', f'operator=({name}&& other)', 'unwrap(*this) = std::move(unwrap(other)); return *this;')]
    for index, (result, signature, body) in enumerate(signatures):
        signature += f' noexcept({traits[index + 5]})'
        header += f'    {result}{signature}' + (';\n' if traits[index] else ' = delete;\n')
        if traits[index]:
            support += f'{result}{name}::{signature} {{ {body} }}\n'
    assert traits[-1] == 1, (name, layout)
    header += f'    ~{name}() noexcept;\n'
    support += f'{name}::~{name}() noexcept {{ std::destroy_at(&unwrap(*this)); }}\n'
    for parameters, arguments in CONSTRUCTORS.get(name, []):
        header += f'    explicit {name}({parameters});\n'
        support += f'{name}::{name}({parameters}) {{ ::new(address()) Native_{name}({arguments}); }}\n'
    for result, signature, body in methods:
        header += f'    {result} {signature};\n'
        support += f'{result} {name}::{signature} {{ {body} }}\n'
    for trait, expected in zip(TRAITS, traits):
        support += f'static_assert(int(std::{trait}_v<{name}>) == {expected});\n'
    return header + '};\n', support


def emit(folder, variant, runner, base):
    folder.mkdir()
    for name in ['instrument.hpp', 'instrument.cpp']:
        shutil.copyfile(INSTRUMENT / name, folder / name)
    for name in ['invoice.hpp', 'invoice.cpp', 'scenario.cpp']:
        shutil.copyfile(FIXTURE / name, folder / name)
    header = '#pragma once\n#include "instrument.hpp"\n#include <cstdint>\n'
    if variant == 'direct':
        header += RUNTIME
    support = RUNTIME + '#include "interface.hpp"\n'
    layouts = {}
    for index, layer in enumerate(LAYERS):
        if layer == ['Invoice']:
            header += '#include "invoice.hpp"\n'
        (folder / 'interface.hpp').write_text(header)
        probe = RUNTIME + '#include "interface.hpp"\nint main() {\n'
        for name in layer:
            native = BINDINGS[name][0] if name in BINDINGS else name
            probe += f'using Probe_{name} = {native};\n'
            values = [f'sizeof(Probe_{name})', f'alignof(Probe_{name})']
            values += [f'std::{trait}_v<Probe_{name}>' for trait in TRAITS]
            probe += f'std::printf("{name}' + ' %zu' * len(values) + '\\n", '
            probe += ', '.join(f'static_cast<std::size_t>({value})' for value in values) + ');\n'
        (folder / 'probe.cpp').write_text(probe + '}\n')
        label = f'{variant}-layout-{index}'
        runner.run(label + '-build', [*base, folder / 'probe.cpp', '-o', folder / 'probe'])
        output = runner.run(label + '-read', [folder / 'probe']).decode()
        for line in output.splitlines():
            name, *values = line.split()
            layouts[name] = list(map(int, values))
            if name not in BINDINGS:
                continue
            if variant == 'direct':
                header += f'using {name} = {BINDINGS[name][0]};\n'
            else:
                declaration, implementation = concrete(name, layouts[name])
                header += declaration
                support += implementation
    for result, name, expression in FACTORIES:
        header += f'{result} {name}();\n'
        body = expression if variant == 'direct' else (
            f'{result}({result}::operation_result{{}}, +[](void* destination) {{ '
            f'::new(destination) Native_{result}({expression}); }})')
        support += f'{result} {name}() {{ return {body}; }}\n'
    support += '''void verify_traits() {
    static_assert(std::is_nothrow_move_constructible_v<Invoice>);
    static_assert(!std::is_copy_constructible_v<Unique_Invoice>);
    static_assert(std::is_copy_constructible_v<Shared_Invoice>);
    static_assert(std::is_same_v<decltype(std::declval<Invoice&>().amounts), Vector_Int>);
    static_assert(std::is_same_v<decltype(std::declval<Vector_Vector_Int&>().at(0)), Vector_Int&>);
}
'''
    (folder / 'interface.hpp').write_text(header)
    (folder / 'support.cpp').write_text(support)
    return layouts


def counters(output):
    return {parts[0]: list(map(int, parts[1:])) for parts in (line.split() for line in output.splitlines())}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    parser.add_argument('--timing-repeats', type=int, default=0)
    args = parser.parse_args()
    out = args.results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    base = ['clang++', '-std=c++23', '-g0', '-I' + str(ROOT / 'runtime/include')]
    with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
        # Layer dependencies stay sequential inside each independent variant task.
        layouts = run_tasks([Task(variant, lambda variant=variant: emit(out / variant, variant, runner, base))
                             for variant in ['direct', 'concrete']])
        assert layouts[0].value == layouts[1].value, [row.value for row in layouts]
        modes = [('O0', ['-O0']), ('O2', ['-O2']), ('no-elide', ['-O0', '-fno-elide-constructors']),
                 ('sanitized', ['-O1', '-fsanitize=address,undefined', '-fno-omit-frame-pointer', '-fno-elide-constructors'])]

        def check(mode, flags):
            results = {}
            for variant in ['direct', 'concrete']:
                folder = out / variant
                objects = []
                # No LTO or unity build: native operations remain separately compiled.
                for unit in ['instrument', 'support', 'invoice', 'scenario']:
                    obj = folder / f'{mode}-{unit}.o'
                    runner.run(f'{mode}-{variant}-{unit}', [*base, *flags, '-c', folder / f'{unit}.cpp', '-o', obj])
                    objects.append(obj)
                binary = folder / mode
                runner.run(f'{mode}-{variant}-link', [*base, *flags, *objects, '-o', binary])
                results[variant] = counters(runner.run(f'{mode}-{variant}-run',
                    ['env', 'ASAN_OPTIONS=detect_leaks=0', binary]).decode())
            assert results['direct'] == results['concrete'], results
            for phase in ['field_references', 'record_nested_references', 'concrete_nested_references']:
                assert results['concrete'][phase] == [0] * 7, (phase, results)
            assert results['concrete']['record_copy_failure'][4] == 1, results
            assert results['concrete']['record_copy_failure'][6] == 1, results
            return results

        outcomes = run_tasks([Task(mode, lambda mode=mode, flags=flags: check(mode, flags)) for mode, flags in modes])
        folder = out / 'concrete'
        expanded = runner.run('concrete-preprocess', [*base, '-E', folder / 'scenario.cpp']).decode()
        for forbidden in ['class vector_t', 'class int_t', 'class shared_p', 'class unique_p', 'scpp::']:
            assert forbidden not in expanded, forbidden
        # Sensitivity control: introduce an unnecessary native vector copy during a read.
        support = (folder / 'support.cpp').read_text()
        before = 'const Tracked& Vector_Tracked::at(std::size_t index) const { '
        assert before in support
        (folder / 'negative.cpp').write_text(support.replace(before, before + 'auto extra = unwrap(*this); '))
        runner.run('negative-build', [*base, '-O0', '-c', folder / 'negative.cpp', '-o', folder / 'negative.o'])
        runner.run('negative-link', [*base, '-O0', *[folder / f'O0-{unit}.o' for unit in ['instrument', 'invoice', 'scenario']],
                                     folder / 'negative.o', '-o', folder / 'negative'])
        negative = counters(runner.run('negative-run', [folder / 'negative']).decode())
        assert negative['field_references'][0] > 0 and negative['field_references'][4] > 0, negative
        summary = {'compiler': runner.run('clang-version', ['clang++', '--version']).decode(),
                   'layouts_and_traits': layouts[1].value, 'traits': ['size', 'alignment', *TRAITS],
                   'counter_columns': ['allocations', 'bytes', 'frees', 'constructed', 'copied', 'moved', 'destroyed'],
                   'results': {row.name: row.value for row in outcomes}, 'negative_control': negative}
        (out / 'summary.json').write_text(json.dumps(summary, indent=2) + '\n')
        if args.timing_repeats:
            timings = {}
            # Sequential paired samples, after correctness runs; warmed filesystem,
            # fresh object compilation, no PCH/LTO/compiler cache. Alternate order.
            for optimization in ['-O0', '-O2']:
                for repetition in range(args.timing_repeats):
                    order = ['direct', 'concrete'] if repetition % 2 == 0 else ['concrete', 'direct']
                    for variant in order:
                        folder = out / variant
                        for unit in ['support', 'invoice', 'scenario']:
                            label = f'timing-{optimization}-{repetition}-{variant}-{unit}'
                            started = time.perf_counter()
                            runner.run(label, [*base, optimization, '-c', folder / f'{unit}.cpp', '-o', folder / 'timing.o'])
                            timings.setdefault(f'{optimization}/{variant}/{unit}', []).append(
                                (time.perf_counter() - started) * 1000)
            (out / 'timings.json').write_text(json.dumps({key: {'milliseconds': values,
                'median_ms': statistics.median(values)} for key, values in timings.items()}, indent=2) + '\n')
        print('PASS: concrete fields and recursive composition preserve tested native behavior and counts')


if __name__ == '__main__':
    main()

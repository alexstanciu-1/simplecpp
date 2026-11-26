"""Specialize private copies of the generated Row decoder and JSON provider for this study."""
import json
from pathlib import Path
import shutil
import subprocess
import sys

here = Path(__file__).resolve().parent
root, work = map(lambda p: Path(p).resolve(), sys.argv[1:3])
app = work / 'app'
app.mkdir(exist_ok=True, parents=True)
(app / 'module.json').write_text('{"dependencies":{"runtime":{"modules":["json"]}},"general_api":true}')
(app / 'records.php').write_text('data struct Row {public readonly int $id; public int $x=3; public bool $ok=false; public int $tag=5;} data struct Packet {public vector<Row> $rows;}')
(app / 'main.php').write_text('$p Packet=json_decode_as("{}",Packet::class);return 0;')
result = subprocess.run(['php', root/'compiler/tests/incremental_executable.php', app, work/'out'],
                        input='"run"\n', text=True, capture_output=True, check=True)
assert 'error' not in json.loads(result.stdout), result.stdout
units = work/'out/units'
header, = [p for p in units.glob('*.hpp') if 'struct Packet {' in p.read_text()]
unit, = [p for p in units.glob('*.cpp') if 'Row::decode(::scpp::Decoder& d) {' in p.read_text()]

def replace(text, old, new):
    assert text.count(old) == 1, old
    return text.replace(old, new)

modes = sys.argv[3:] or ['baseline', 'field', 'mask', 'row', 'ordered']
for mode in modes:
    folder = work/mode
    src, inc = folder/'src', folder/'public'
    shutil.copytree(root/'runtime/src', src, dirs_exist_ok=True)
    shutil.copytree(root/'runtime/generated/public', inc, dirs_exist_ok=True)
    provider = (root/'runtime/generated/src/json.cpp').read_text()
    record = unit.read_text()
    if mode != 'baseline':
        decoder = inc/'scpp/decoder.hpp'
        api = 'virtual int row_field(unsigned& seen) = 0;'
        reader = src/'json/structural_reader.hpp'
        cursor = replace(reader.read_text(), 'bool next(bool object)', 'template<bool Row = false> bool next(bool object, int* field = nullptr, unsigned* seen = nullptr)')
        lookup = '''int index = -1;
            if constexpr (Row) {
                const std::string_view name = key_;
                if (name == "id") index = 0;
                else if (name == "x") index = 1;
                else if (name == "ok") index = 2;
                else if (name == "tag") index = 3;
                *field = index;
            }
            '''
        if mode == 'field':
            lookup += 'if (!scope.insert_key(key_))'
        else:
            lookup += '''bool unique;
            if constexpr (Row) {
                const unsigned bit = index >= 0 ? 1u << index : 0;
                unique = (*seen & bit) == 0;
                *seen |= bit;
            } else unique = scope.insert_key(key_);
            if (!unique)'''
        cursor = replace(cursor, 'if (!scope.insert_key(key_))', lookup)
        reader.write_text(replace(cursor, 'bool next_property()', '''int row_field(unsigned& seen) {
            int index = -1;
            if (checked([&] { return next<true>(true, &index, &seen); })) return index;
            if (at_root()) finish_document();
            return -2;
        }
        bool next_property()'''))
        method = 'int row_field(unsigned& seen) override { return cursor.row_field(seen); }'
        start = record.index('    d.begin_object();')
        end = record.index('    if (!(seen_0))', start)
        loop = record[start:end].replace('while (d.next_field()) {\n        const auto key = d.field_name();',
                                         'for (;;) {\n        const int key = d.row_field(seen);\n        if (key == -2) break;')
        for index, name in enumerate(['id', 'x', 'ok', 'tag']):
            loop = replace(loop, f'key == "{name}"', f'key == {index}')
        if mode in ('row', 'ordered'):
            signature = 'void row_fields(int64& field_0, int64& field_1, bool_t& field_2, int64& field_3, unsigned& seen)'
            api += '\n    virtual ' + signature + ' = 0;'
            # Presence is already available in the duplicate mask.
            for index in range(4):
                loop = loop.replace(f'seen_{index} = true;', '')
            if mode == 'ordered':
                reader.write_text(replace(reader.read_text(), 'int row_field(unsigned& seen)', (here/'ordered.inc').read_text() + '\n    int row_field(unsigned& seen)'))
                prefix = ''
                for index, (name, read) in reversed(list(enumerate([('id','read_integer'), ('x','read_integer'), ('ok','read_boolean'), ('tag','read_integer')]))):
                    prefix = (f'if (cursor.match_row_key(R"("{name}")")) {{\n'
                              f'    seen |= {1 << index}u; field_{index} = cursor.{read}();\n' + prefix + '}\n')
                loop = replace(loop, 'd.begin_object();', 'd.begin_object();\n' + prefix)
            method += '\n    ' + signature + ' override {\n' + loop.replace('d.', 'cursor.') + '    }'
            loop = '    d.row_fields(field_0, field_1, field_2, field_3, seen);\n'
        record = record[:start] + '    unsigned seen = 0;\n' + loop + record[end:]
        if mode in ('row', 'ordered'):
            for index in range(4):
                record = record.replace(f'    bool seen_{index} = false;\n', '', 1)
                record = record.replace(f'seen_{index}', f'(seen & {1 << index}u)')
        decoder.write_text(replace(decoder.read_text(), '    virtual void begin_object()', '    '+api+'\n    virtual void begin_object()'))
        provider = replace(provider, '    explicit record_reader', '    '+method+'\n    explicit record_reader')
    (folder/'provider.cpp').write_text(provider)
    (folder/'record.cpp').write_text(record)
    flags = ['clang++', '-std=c++23', '-O3', '-DNDEBUG', '-stdlib=libc++', '-I', inc, '-I', units]
    for name in ('json', 'boxed', 'hash', 'ownership', 'numeric'):
        flags.extend(['-I', src/name])
    objects = []
    for name in ('provider', 'record'):
        obj = folder/(name+'.o')
        subprocess.run([*flags, '-c', folder/(name+'.cpp'), '-o', obj], check=True)
        objects.append(obj)
    libs = [root/f'build/clang/libscpp_{n}.a' for n in ('json', 'runtime', 'boxed_storage', 'packed_storage')]
    for name, options in [('time', []), ('counts', ['-DCOUNTERS', '-Wl,--wrap=_Znwm'])]:
        subprocess.run([*flags, '-include', header, here/'main.cpp', *options, *objects, *libs, '-o', folder/name], check=True)

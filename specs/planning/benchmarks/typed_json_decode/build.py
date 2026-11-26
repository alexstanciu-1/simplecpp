"""Build independent probe binaries against an isolated, JSON-enabled Release publication."""
import argparse
import json
from pathlib import Path
import shutil
import subprocess

HERE = Path(__file__).resolve().parent
MODES = ['baseline', 'borrow', 'group', 'fused', 'staged', 'exact', 'transfer']
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('root', type=Path)
parser.add_argument('work', type=Path)
args = parser.parse_args()
root, work = args.root.resolve(), args.work.resolve()
work.mkdir(parents=True, exist_ok=True)
app = work / 'app'
app.mkdir(exist_ok=True)
(app / 'module.json').write_text('{"dependencies":{"runtime":{"modules":["json"]}},"general_api":true}')
(app / 'records.php').write_text('data struct Packet {public vector<int> $data; public vector<int> $extra; public vector<string> $words; public int $version=1;}')
(app / 'main.php').write_text('$p Packet=json_decode_as("{}",Packet::class);return $p->version;')
run = subprocess.run(['php', root / 'compiler/tests/incremental_executable.php', app, work / 'out'],
                     input='"run"\n', capture_output=True, text=True, check=True)
assert 'error' not in json.loads(run.stdout), run.stdout
units = work / 'out/units'
header, = [p for p in units.glob('*.hpp') if 'struct Packet {' in p.read_text()]
unit, = [p for p in units.glob('*.cpp') if 'Packet::decode(::scpp::Decoder& d) {' in p.read_text()]

def replace(text, before, after):
    assert text.count(before) == 1, before
    return text.replace(before, after)

for mode in MODES:
    folder = work / mode
    folder.mkdir(exist_ok=True)
    shutil.copytree(root / 'runtime/src', folder / 'src', dirs_exist_ok=True)
    includes = folder / 'public'
    shutil.copytree(root / 'runtime/generated/public', includes, dirs_exist_ok=True)
    source = (root / 'runtime/generated/src/json.cpp').read_text()
    record = unit.read_text()
    reader = folder / 'src/json/structural_reader.hpp'
    cursor = reader.read_text()
    if mode != 'baseline':
        # Only synchronous from_text borrows. Persistent cursor construction still owns its text.
        cursor = replace(cursor, 'input_ = source_;', 'initialize(source_, limit);\n\t}\n'
                         '\tpull_reader(std::string_view input, int limit) : json_reader({}), limit_(0) { initialize(input, limit); }\n'
                         '\tvoid initialize(std::string_view input, std::int64_t limit) {\n\t\tinput_ = input;')
        source = replace(source, 'std::string(text.data(), text.size()), "", 128',
                         'std::string_view(text.data(), text.size()), 128')
    if mode in ('group', 'fused', 'staged', 'exact', 'transfer'):
        decoder = includes / 'scpp/decoder.hpp'
        api = replace(decoder.read_text(), 'class Decoder {', 'class vector_int64;\nclass Decoder {')
        decoder.write_text(replace(api, '    virtual void begin_object()', '    virtual void integers(vector_int64&) = 0;\n    virtual void begin_object()'))
        for index in [0, 1]:
            slot = f'field_{index}'
            record = replace(record, f'd.begin_array();\n            while (d.next_element()) ({slot}).append(d.read_integer());',
                             f'd.integers({slot});')
        body = 'cursor.begin_array(); while (cursor.next_element()) items.append(cursor.read_integer());'
        if mode in ('fused', 'staged', 'exact', 'transfer'):
            cursor = replace(cursor, 'bool at_root()', (HERE / 'batch.inc').read_text() + '\n\tbool at_root()')
            body = 'cursor.read_integer_run([&](int64 value) { items.append(value); });'
        if mode == 'staged':
            body = '''
            std::array<int64, 16> first;
            unsigned count = 0;
            cursor.read_integer_run([&](int64 value) {
                if (count < first.size()) {
                    first[count++] = value;
                    if (count == first.size()) items = vector_int64::from_list(first.data(), count);
                } else items.append(value);
            });
            if (count < first.size()) items = vector_int64::from_list(first.data(), count);'''
        if mode == 'exact':
            cursor = replace(cursor, 'bool at_root()', '''std::size_t count_integers() {
                const auto start = pos_;
                std::size_t count = 0;
                read_integer_run([&](int64_t) { ++count; });
                pos_ = start;
                pending_ = true;
                return count;
            }
            bool at_root()''')
            body = 'items.reserve(static_cast<int64>(cursor.count_integers())); ' + body
        source = '#include <scpp/vectors.hpp>\n' + replace(source, '    explicit record_reader',
            '    void integers(vector_int64& items) override {' + body + '}\n    explicit record_reader')
    if mode in ('group', 'fused', 'staged', 'exact', 'transfer'):
        decoder.write_text(replace(decoder.read_text(), 'class vector_int64;', 'class vector_string;\nclass vector_int64;').replace(
            '    virtual void integers', '    virtual void strings(vector_string&) = 0;\n    virtual void integers'))
        record = replace(record, 'd.begin_array();\n            while (d.next_element()) (field_2).append(d.read_string());',
                         'd.strings(field_2);')
        string_read = 'detail::own_string(cursor.read_string())' if mode == 'transfer' else 'read_string()'
        source = replace(source, '    explicit record_reader', '    void strings(vector_string& items) override {'
                         ' cursor.begin_array(); while (cursor.next_element()) items.append(' + string_read + '); }\n    explicit record_reader')
    reader.write_text(cursor)
    (folder / 'provider.cpp').write_text(source)
    (folder / 'record.cpp').write_text(record)
    flags = ['clang++', '-std=c++23', '-O3', '-DNDEBUG', '-stdlib=libc++', '-I', includes, '-I', units]
    for name in ['json', 'boxed', 'hash', 'ownership', 'numeric']:
        flags.extend(['-I', folder / 'src' / name])
    libs = [root / f'build/clang/libscpp_{name}.a' for name in ['json', 'runtime', 'boxed_storage', 'packed_storage']]
    objects = []
    for name in ['provider', 'record']:
        obj = folder / (name + '.o')
        subprocess.run([*flags, '-c', folder / (name + '.cpp'), '-o', obj], check=True)
        objects.append(obj)
    for profile in [False, True]:
        counters = ['-DCOUNTERS', '-Wl,--wrap=_Znwm,--wrap=_ZN4scpp12vector_int64C1ERKS0_,--wrap=_ZN4scpp12vector_int64aSERKS0_'] if profile else []
        subprocess.run([*flags, '-include', header, HERE / 'main.cpp', *objects, *libs, *counters,
                        '-o', folder / ('counts' if profile else 'time')], check=True)

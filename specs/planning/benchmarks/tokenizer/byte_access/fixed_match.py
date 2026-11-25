"""Isolated fixed-pattern equality proof on the emitted bounded scanners. Run after building scpp_runtime."""
import json
from pathlib import Path
import subprocess
import sys
import tempfile

HERE = Path(__file__).resolve().parent
ROOT = HERE.parents[4]
sys.path.insert(0, str(ROOT / 'compiler/tests'))
from tokenizer_scanner_native import prepare


def prove(work, cache_pointer=False):
    prepare(work)
    helper = work / 'byte_match.hpp'
    helper.write_text("""#pragma once
#include <cstring>
#include <scpp/core/scalars.hpp>
inline bool bytes_match_at(const void* source, scpp::int64 size, scpp::int64 offset,
                           const void* pattern, scpp::int64 count) noexcept {
    if (offset < 0 || offset > size || count < 0 || count > size - offset) return false;
    return count == 0 || std::memcmp(static_cast<const unsigned char*>(source) + offset, pattern, count) == 0;
}
""")
    flags = ['clang++', '-std=c++23', '-stdlib=libc++', '-O2', '-I', ROOT/'runtime/generated/public', '-I', work]
    boundary = work / 'boundary.cpp'
    boundary.write_text("""#include "byte_match.hpp"
#include <cassert>
#include <limits>
#include <vector>
#include <scpp/string.hpp>
int main() {
    static constexpr unsigned char pattern[] = {0, 255, '7'};
    scpp::string text{"x\\0\\3777", 4};
    std::vector<unsigned char> bytes{'x', 0, 255, '7'};
    for (const void* src : {static_cast<const void*>(text.data()), static_cast<const void*>(bytes.data())}) {
        assert(bytes_match_at(src, 4, 1, pattern, sizeof pattern));
        assert(!bytes_match_at(src, 4, 0, pattern, sizeof pattern));
        assert(!bytes_match_at(src, 4, 2, pattern, sizeof pattern));
        assert(!bytes_match_at(src, 4, -1, pattern, sizeof pattern));
        assert(!bytes_match_at(src, 4, std::numeric_limits<scpp::int64>::max(), pattern, sizeof pattern));
        assert(bytes_match_at(src, 4, 4, nullptr, 0));
    }
    assert(bytes_match_at(nullptr, 0, 0, nullptr, 0));
}
""")
    lib = ROOT/'build/clang/libscpp_runtime.a'
    subprocess.run([*flags, boundary, lib, '-o', work/'boundary'], check=True)
    subprocess.run([work/'boundary'], check=True)
    for flavor in ('cpp', 'php', 'js'):
        data = work/flavor
        out = work/(flavor+'-out')
        unit = out/json.loads((out/'manifest.json').read_text())['sources'][f'{flavor}/scanner.{flavor}']
        source = unit.read_text()
        original, = [line for line in source.splitlines() if 'var_i_5 - var_start_7' in line and '105LL' in line]
        patterns = ['if', 'while'] + {'js':['let'], 'cpp':['auto'], 'php':[]}[flavor]
        checks = [f'((var_i_5 - var_start_7) == {len(p)} && bytes_match_at(var_out_2.source.data(), var_size_4, var_start_7, "{p}", sizeof("{p}") - 1))' for p in patterns]
        modified = data/'match.cpp'
        replacement = '\t\t\tif ('+' || '.join(checks)+') {'
        if cache_pointer:
            # The fixture has already transferred the immutable source into its result.
            anchor = ' = scpp_byte_view.size();'
            assert source.count(anchor) == 1
            source = source.replace(anchor, anchor+'\n\tconst char* const match_bytes = var_out_2.source.data();')
            replacement = replacement.replace('var_out_2.source.data()', 'match_bytes')
        modified.write_text('#include "byte_match.hpp"\n' + source.replace(original, replacement))
        header, = [p for p in unit.parent.glob('*.hpp') if 'Scan scan_(' in p.read_text()]
        (data/'adapter.hpp').write_text(f'#include "{header.name}"\n#include <string>\nusing Result=App::scpp_records::Scan;\n'
            'auto make_input(const std::string& s){return scpp::string{s.data(),long(s.size())};}\n'
            'auto run(const scpp::string& s,size_t hint){return App::scan_(s,hint);}\n')
        exe = data/'scan'
        subprocess.run([*flags, '-I', data, '-I', unit.parent, modified, HERE.parent/'driver.cpp', lib, '-o', exe], check=True)
        for i in range(11):
            result = subprocess.check_output([exe,'dump',data/f'input{i}'])
            assert result == (data/f'expected{i}').read_bytes(), (flavor, i)
        edits = range(4,8)
        result = subprocess.check_output([exe,'edits',data/'input3',*[data/f'input{i}' for i in edits]])
        assert result == b''.join((data/f'expected{i}').read_bytes()+b'|\n' for i in edits)
        print(flavor, 'keyword match parity and recovery passed')


if __name__ == '__main__':
    with tempfile.TemporaryDirectory() as directory:
        prove(Path(directory))

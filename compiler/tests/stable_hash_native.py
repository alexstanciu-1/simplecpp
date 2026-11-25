"""Verify both general string fingerprints through compiled source functions."""
import json
from pathlib import Path
import subprocess
import sys
import tempfile

from app_namespace_native import invoke, service

root = Path(__file__).resolve().parents[2]
cxx, linker, archive = sys.argv[1:]
flags = [cxx, '-std=c++23', '-stdlib=libc++', '-I', root/'runtime/generated/public']

with tempfile.TemporaryDirectory(prefix='stable-string-hash-') as tmp:
    work = Path(tmp)
    for operation, result_type in [('hash_string', 'string'), ('stable_hash_string_u64', 'uint64')]:
        declaration = f'''#include "scpp/str/{operation}.hpp"
#include <type_traits>
static_assert(std::is_same_v<decltype(&scpp::str::{operation}), scpp::{result_type}(*)(const scpp::string&)>);
'''
        subprocess.run([*flags, '-x', 'c++', '-fsyntax-only', '-'], input=declaration, text=True, check=True)

    app, out = work/'app', work/'out'
    app.mkdir()
    manifest = app/'module.json'
    manifest.write_text('{"general_api":true}')
    main = app/'main.php'
    main.write_text('return hash_string("abc")==="e71fa2190541574b" ? 0 : 1;')
    proc = service(app, out)
    try:
        result = invoke(proc)
        assert 'error' not in result, result
        unit, = (out/'units').glob('*.cpp')
        deps = subprocess.check_output([*flags, '-MM', unit], text=True)
        assert 'str/hash_string.hpp' in deps
        for header in ('/numeric/', 'stable_hash_string_u64.hpp', 'stable_string_hash.hpp', '/php/'):
            assert header not in deps, deps

        manifest.write_text('{}')
        assert 'Unknown function: hash_string' in invoke(proc).get('error', '')
        manifest.write_text('{"general_api":true}')
        main.write_text('stable_hash_string_u64(""); return 0;')
        assert 'requires module runtime-numeric' in invoke(proc).get('error', '')
        manifest.write_text(json.dumps({'general_api': True, 'dependencies': {'runtime': {'modules': ['numeric']}}}))
        main.write_text('bits_value("abc"); return hex_value("abc")==="e71fa2190541574b" ? 0 : 1;')
        (app/'hex.php').write_text('function hex_value(string $s): string {return hash_string($s);}')
        (app/'bits.php').write_text('function bits_value(string $s): uint64 {return stable_hash_string_u64($s);}')
        result = invoke(proc)
        assert 'error' not in result, result
    finally:
        proc.stdin.close()
        proc.wait()

    bundle = json.loads((out/'manifest.json').read_text())
    headers = [out/path for path in bundle['headers']]
    functions = [next(p for p in headers if name in p.read_text()) for name in ('hex_value_', 'bits_value_')]
    observer = work/'observer.cpp'
    observer.write_text(''.join(f'#include "{p}"\n' for p in functions) + r"""
#include <type_traits>
static_assert(std::is_same_v<decltype(&App::bits_value_), scpp::uint64(*)(scpp::string)>);
int main() {
    struct sample { const char* data; int size; const char* hex; scpp::uint64 bits; };
    const sample cases[] = {
        {"", 0, "cbf29ce484222325", 0xcbf29ce484222325ULL},
        {"abc", 3, "e71fa2190541574b", 0xe71fa2190541574bULL},
        {"a\0b", 3, "e5d29919042666b2", 0xe5d29919042666b2ULL},
        {"\xff\x80", 2, "0a9a2607b6f6e56a", 0x0a9a2607b6f6e56aULL},
        {"bcd", 3, "003f3219133dae62", 0x003f3219133dae62ULL}
    };
    for (const auto& s : cases) {
        scpp::string bytes{s.data, s.size};
        if (App::hex_value_(bytes) != scpp::string{s.hex} || App::bits_value_(bytes) != s.bits) return 1;
    }
}
""")
    subprocess.run([*flags, f'--ld-path={linker}', observer, *(p.with_suffix('.cpp') for p in functions),
                    archive, '-o', work/'probe'], check=True)
    subprocess.run([work/'probe'], check=True)

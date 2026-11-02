"""Standalone public output header, exact runtime output and immediate IO failure proof."""
from pathlib import Path
import re
import subprocess
import tempfile

root = Path(__file__).resolve().parents[1]

def run(*args):
    return subprocess.run(list(map(str,args)), capture_output=True, timeout=60)

def success(result):
    assert result.returncode == 0, result.stderr
    return result

with tempfile.TemporaryDirectory(prefix='scpp-output-api-') as directory:
    work = Path(directory)
    source = work / 'consumer.cpp'
    source.write_text('''#include <scpp/output.hpp>
int main() {
    scpp::echo_bytes("", 0);
    scpp::echo_bytes("a\\0b", 3);
    scpp::echo_int(-9223372036854775807LL - 1);
    scpp::echo_bytes("|", 1);
    scpp::echo_int(9223372036854775807LL);
    scpp::echo_bytes("|", 1);
    scpp::echo_int(0);
}
''')
    flags = ['clang++','-std=c++23','-stdlib=libc++','-Wall','-Wextra','-Werror','-I',root/'runtime/generated/public']
    deps = success(run(*flags,'-M',source)).stdout.decode()
    for forbidden in ['/charconv', '/system_error', '/string', '/stdexcept', '/cstdio']:
        assert forbidden not in deps, deps
    preprocessed = success(run(*flags, '-E', '-P', source)).stdout.decode()
    assert not re.search(r'\b(template|typename)\b', preprocessed), 'Templates leaked into public output closure'
    binary = work/'consumer'
    success(run(*flags,source,root/'build/clang/libscpp_runtime.a','-o',binary))
    result = success(run(binary))
    assert result.stdout == b'a\0b-9223372036854775808|9223372036854775807|0' and not result.stderr, result
    source.write_text('''#include <scpp/output.hpp>
#include <cstdio>
#include <stdexcept>
int main() {
    if (!std::freopen("/dev/full", "w", stdout)) return 2;
    if (std::setvbuf(stdout, nullptr, _IONBF, 0) != 0) return 3;
    try { scpp::echo_bytes("x", 1); }
    catch (const std::runtime_error&) { return 0; }
    return 1;
}
''')
    success(run(*flags,source,root/'build/clang/libscpp_runtime.a','-o',binary))
    success(run(binary))
print('Public output include closure, integer boundaries, exact bytes and short-write exception passed')

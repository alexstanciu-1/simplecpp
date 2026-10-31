"""All-flavor source-to-executable proof and the isolated dynamic exit boundary."""
import json
import os
from pathlib import Path
import subprocess
import tempfile
import time

root = Path(__file__).resolve().parents[2]
php = os.environ.get('PHP_EXECUTABLE', 'php')
main = root / 'compiler/main.php'
facts = root / 'build/clang/native-layout/facts.json'
include = root / 'runtime/generated/public'
library = root / 'build/clang/libscpp_runtime.a'


def run(*args):
    return subprocess.run(list(map(str, args)), capture_output=True, text=True)


def cli(*args):
    return run(php, main, *args)


def success(result):
    assert result.returncode == 0, result.stdout + result.stderr
    return result


assert facts.exists() and library.exists(), 'Build v0.2 with the default CMake preset first'
with tempfile.TemporaryDirectory(prefix='scpp-entry-') as directory:
    work = Path(directory)
    emitted = []
    for flavor in ['php', 'cpp', 'js']:
        text = (root / 'compiler/examples' / ('entry.' + flavor + '.scpp')).read_text()
        source = work / (flavor + '.source')
        source.write_text(text)
        parsed = json.loads(success(cli('parse', flavor, source)).stdout)
        assert [s['kind'] for s in parsed['statements']] == ['local', 'return']
        collected = json.loads(success(cli('collect', flavor, source)).stdout)
        assert collected['locals'][0]['name'] == 'ret'
        assert collected['references'][0]['name'] == 'ret'
        prepared = json.loads(success(cli('prepare', flavor, source, facts)).stdout)
        assert prepared['statements'][1]['value']['binding'] == prepared['statements'][0]['id']
        assert prepared['statements'][1]['conversion'] == 'proven'
        cpp = work / (flavor + '.cpp')
        binary = work / flavor
        start = time.perf_counter()
        success(cli('cpp', flavor, source, facts, cpp))
        source_to_cpp_ms = (time.perf_counter() - start) * 1000
        emitted.append(cpp.read_text())
        start = time.perf_counter()
        success(run('clang++', '-std=c++23', '-stdlib=libc++', '-Wall', '-Wextra', '-Werror',
                    '-I', include, cpp, library, '-o', binary))
        native_build_ms = (time.perf_counter() - start) * 1000
        start = time.perf_counter()
        assert run(binary).returncode == 8
        run_ms = (time.perf_counter() - start) * 1000
        print(json.dumps({'flavor': flavor, 'source_to_cpp_ms': round(source_to_cpp_ms, 3),
                          'native_build_ms': round(native_build_ms, 3), 'run_ms': round(run_ms, 3)}), flush=True)
        before = cpp.read_bytes()
        success(cli('cpp', flavor, source, facts, cpp))
        assert cpp.read_bytes() == before
        invalid = [(('return $missing;' if flavor == 'php' else 'return missing;'), ':1:8:'),
                   ('return 2147483648;', ':1:8:'), ('return ;', ':1:1: Entry requires a value return')]
        for text, expected in invalid:
            source.write_text(text)
            result = cli('cpp', flavor, source, facts, cpp)
            assert result.returncode == 1 and not result.stdout and expected in result.stderr, result.stderr
            assert cpp.read_bytes() == before, 'Failure replaced previous artifact'
            absent = work / 'not-published' / (flavor + '.cpp')
            assert cli('cpp', flavor, source, facts, absent).returncode == 1
            assert not absent.parent.exists(), 'Preparation failure created publication output'
        print('entry executable and failure publication passed:', flavor, flush=True)
    assert len(set(emitted)) == 1, 'Flavor grammars diverged after preparation'

    # The source slice has no runtime input expression yet. Exercise the exact
    # backend helper with a native test driver, without claiming new source syntax.
    helper = success(run(php, '-r',
        'require $argv[1]; echo \\scpp\\compiler\\Cpp_Exit::checked_helper();', root / 'compiler/boot.php')).stdout
    expression = success(run(php, '-r',
        'require $argv[1]; echo \\scpp\\compiler\\Cpp_Exit::expression('
        '\\scpp\\compiler\\exit_conversion::checked, "scpp::int64{std::stoll(argv[1])}");',
        root / 'compiler/boot.php')).stdout
    cpp = work / 'dynamic.cpp'
    cpp.write_text('#include <scpp/integers.hpp>\n#include <limits>\n#include <stdexcept>\n'
                   '#include <string>\n#include <iostream>\n' + helper
                   + '\nint main(int, char **argv) { try { std::cout << ' + expression
                   + '; return 0; } catch (const std::out_of_range &) { return 99; } }\n')
    binary = work / 'dynamic'
    success(run('clang++', '-std=c++23', '-stdlib=libc++', '-I', include, cpp, library, '-o', binary))
    digits = json.loads(facts.read_text())['native_integers']['int']['digits']
    maximum, minimum = (1 << digits) - 1, -(1 << digits)
    for value in [minimum, 0, 8, maximum]:
        result = success(run(binary, value))
        assert int(result.stdout) == value
    for value in [minimum - 1, maximum + 1]:
        assert run(binary, value).returncode == 99
    print('runtime-dependent checked exit boundary passed', flush=True)

    source = work / 'target.source'
    source.write_text('return 8;')
    mismatched = json.loads(facts.read_text())
    mismatched['native_integers']['int']['digits'] = 15 if digits != 15 else 31
    alternate = work / 'target.json'
    alternate.write_text(json.dumps(mismatched))
    cpp = work / 'target.cpp'
    success(cli('cpp', 'cpp', source, alternate, cpp))
    result = run('clang++', '-std=c++23', '-stdlib=libc++', '-I', include, '-fsyntax-only', cpp)
    assert result.returncode != 0 and 'Entry target does not match' in result.stderr
    print('target mismatch rejected by generated guard', flush=True)
print('End-to-end entry checks passed')

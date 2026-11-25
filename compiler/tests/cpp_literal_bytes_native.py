"""Compare the ported C++ string-literal emitter with PHP and round-trip its bytes through Clang."""
import json
from pathlib import Path
import subprocess
import tempfile

from collection_goal_native import revisions

HERE = Path(__file__).resolve().parent
EMITTER = HERE.parent / 'examples' / 'cpp_literal_bytes' / 'emitter.php.scpp'
BOOT = HERE.parent / 'boot.php'


def fixture(samples):
    hex_inputs = [sample.hex() for sample in samples]
    oracle = subprocess.check_output(['php', '-r', r'''
require $argv[1];
foreach (json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR) as $hex) {
    echo \scpp\compiler\Cpp_String_Literals::bytes(hex2bin($hex)), "\n";
}
''', BOOT], input=json.dumps(hex_inputs).encode())
    entry = ''.join(f'echo cpp_literal_bytes(hex2bin("{value}")), "\\n";' for value in hex_inputs)
    return entry + 'return 8;', oracle


def round_trip(work, samples):
    result = subprocess.run([work / 'php-native-2/program'], capture_output=True, check=False)
    assert result.returncode == 8, result
    spellings = result.stdout.splitlines()
    assert len(spellings) == len(samples)
    source = work / 'round_trip.cpp'
    declarations = []
    for i, (spelling, sample) in enumerate(zip(spellings, samples, strict=True)):
        declarations.extend((f'constexpr char bytes{i}[] = {spelling.decode("ascii")};',
                             f'static_assert(sizeof(bytes{i}) == {len(sample) + 1});'))
    writes = [f'if (std::fwrite(bytes{i}, 1, {len(sample)}, stdout) != {len(sample)}) return 1;'
              for i, sample in enumerate(samples)]
    source.write_text('#include <cstdio>\n' + '\n'.join(declarations) + '\nint main() {\n'
                      + '\n'.join(writes) + '\n}\n')
    binary = work / 'round-trip'
    subprocess.run(['clang++', '-std=c++23', '-Wall', '-Wextra', '-Werror', '-Wtrigraphs',
                    source, '-o', binary], check=True, capture_output=True)
    assert subprocess.check_output([binary]) == b''.join(samples)


if __name__ == '__main__':
    basic = [b'', b'\0', b'\x007', b'"\\\n\r\t', 'é🧪'.encode(), b'??/']
    extended = basic + [bytes(range(256)), b'\x008', b'????/', b'?x?', b'??']
    extended += [b'??' + bytes([ending]) for ending in b"=/'()!<>-"]
    initial, initial_output = fixture(basic)
    updated, updated_output = fixture(extended)
    with tempfile.TemporaryDirectory(prefix='scpp-cpp-literal-bytes-') as directory:
        work = Path(directory)
        revisions(work, 'php', {'emitter': EMITTER.read_text(), 'main': initial},
                  [(None, None, initial_output), (None, None, initial_output),
                   ('main', updated, updated_output), ('main', initial, initial_output)],
                  unaffected_source='emitter.php')
        round_trip(work, extended)

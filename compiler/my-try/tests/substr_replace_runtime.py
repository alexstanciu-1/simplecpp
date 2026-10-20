"""S09 replacement runtime baseline, contract table and existing runtime suite."""
import argparse, json, sys
from pathlib import Path
APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler
p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--results', type=Path, required=True)
p.add_argument('--baseline', action='store_true')
a = p.parse_args()
out = a.results.resolve()
out.mkdir(parents=True, exist_ok=False)
if a.baseline:
    source = r'''#include "lang/php/support/php_string.hpp"
#include <iostream>
int main() {
 using S=scpp::string_t; using I=scpp::int_t<>;
 std::cout << scpp::php::substr_replace(S{"abcdef"},S{"X"},I{2}).native_value() << "\n"
  << scpp::php::substr_replace(S{"abcdef"},S{"X"},I{2},I{-99}).native_value() << "\n"
  << scpp::php::substr_replace(S{"abcdef"},S{"X"},I{2},I{0}).native_value() << "\n"
  << scpp::php::substr_replace(S{"abcdef"},S{"X"},I{0},I{-6}).native_value() << "\n";
}'''
    expected = b'abX\nabXcdef\nabXcdef\nXabcdef\n'
else:
    source = '#include "' + str(APP / 'tests/substr_replace_support.hpp') + '"\n' + r'''
#include <iostream>
int main() {
 auto count=replace_probe::verify(
  [](const auto& s,const auto& r,const auto& o){return scpp::php::substr_replace(s,r,o);},
  [](const auto& s,const auto& r,const auto& o,const auto& l){return scpp::php::substr_replace(s,r,o,l);});
 std::cout << "replace_checks=" << count << "\n";
}'''
    expected = b'replace_checks=3312\n'
with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
    cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True)
    cpp.flags.extend(['-fsanitize=address,undefined', '-fno-sanitize-recover=undefined', '-fno-omit-frame-pointer'])
    def proof():
        path = out / 'runtime.cpp'
        path.write_text(source)
        cpp.compile('runtime-build', path, out / 'native')
        actual = runner.run('runtime-execute', ['env', 'ASAN_OPTIONS=detect_leaks=0', out / 'native'])
        assert actual == expected, actual
        assert (out / 'logs/runtime-execute.stderr').read_bytes() == b''
    def regression():
        cpp.compile('php-strings-build', ROOT / 'tests/runtime/native/test_php_string.cpp', out / 'php-strings')
        runner.run('php-strings-execute', ['env', 'ASAN_OPTIONS=detect_leaks=0', out / 'php-strings'])
    tasks = [Task('runtime', proof)]
    if not a.baseline:
        tasks.append(Task('php-strings', regression))
    run_tasks(tasks)
(out / 'summary.json').write_text(json.dumps({'passed': True, 'baseline': a.baseline,
    'observation': (out / 'logs/runtime-execute.stdout').read_text()}, indent=2) + '\n')

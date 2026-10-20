"""S09 runtime-first byte search probe, then the bounded contract table."""
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
 using scpp::string_t; using I=scpp::int_t<>;
 const string_t text{"éaéa"}, needle{"a"};
 std::cout << scpp::php::strpos(text,needle).value().native_value() << ","
           << scpp::php::strrpos(text,needle).value().native_value() << ","
           << scpp::str::byte_find(text,needle).value().native_value() << "\n";
 try { scpp::str::byte_find(text,needle,I{-1}); return 1; }
 catch(const scpp::ValueError&) { std::cout << "byte_find_rejects_negative\n"; }
}'''
    expected = b'1,3,2\nbyte_find_rejects_negative\n'
else:
    source = '#include "' + str(APP / 'tests/string_search_support.hpp') + '"\n' + r'''
#include <iostream>
int main() {
 auto count=search_probe::verify(
  [](const auto& h,const auto& n){return scpp::php::strpos_bytes(h,n);},
  [](const auto& h,const auto& n,const auto& o){return scpp::php::strpos_bytes(h,n,o);},
  [](const auto& h,const auto& n){return scpp::php::strrpos_bytes(h,n);},
  [](const auto& h,const auto& n,const auto& o){return scpp::php::strrpos_bytes(h,n,o);});
 search_probe::text_controls();
 std::cout << "search_checks=" << count << "\n";
}'''
    expected = None
with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
    cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True)
    cpp.flags.extend(['-fsanitize=undefined', '-fno-sanitize-recover=undefined'])
    def proof():
        path = out / 'runtime.cpp'
        path.write_text(source)
        cpp.compile('runtime-build', path, out / 'native')
        actual = runner.run('runtime-execute', [out / 'native'])
        if expected is not None:
            assert actual == expected, actual
        assert (out / 'logs/runtime-execute.stderr').read_bytes() == b''
    run_tasks([Task('runtime', proof)])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'baseline': a.baseline,
    'observation': (out / 'logs/runtime-execute.stdout').read_text()}, indent=2) + '\n')

"""Focused CMake aggregate/core/provider ownership checks; execute on dev002."""
import argparse
import json
import sys
from pathlib import Path

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks

p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--results', type=Path, required=True)
p.add_argument('--baseline', action='store_true')
a = p.parse_args()
out = a.results.resolve()
out.mkdir(parents=True, exist_ok=False)
with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
    def configuration(name, shared, optional, mysql='OFF'):
        source = out / name / 'source'
        source.mkdir(parents=True)
        build = source.parent / 'build'
        (source / 'CMakeLists.txt').write_text(f'''
cmake_minimum_required(VERSION 3.20)
project(composition_probe LANGUAGES CXX)
add_subdirectory("{ROOT / 'runtime'}" runtime)
add_executable(aggregate_document "{ROOT / 'tests/runtime/native/test_json_document.cpp'}")
target_link_libraries(aggregate_document PRIVATE scpp_runtime scpp_runtime_test_support)
''')
        if not a.baseline:
            (source / 'pool.cpp').write_text('''
#include "scpp/util/global_string_pool.hpp"
extern "C" void* provider_pool() { return &scpp::global_string_pool::instance(); }
extern "C" unsigned provider_intern() {
    return scpp::global_string_pool::instance().intern(scpp::string_t("provider-key"));
}
''')
            (source / 'state.cpp').write_text('''
#include "scpp/util/global_string_pool.hpp"
extern "C" void* provider_pool();
extern "C" unsigned provider_intern();
int main() {
    auto& pool = scpp::global_string_pool::instance();
    if (provider_pool() != &pool) return 1;
    const auto id = provider_intern();
    if (pool.resolve(id).native_value() != "provider-key") return 2;
    return pool.intern(scpp::string_t("provider-key")) == id ? 0 : 3;
}
''')
            (source / 'core.cpp').write_text('''
#include "scpp/util/global_string_pool.hpp"
int main() {
    auto& pool = scpp::global_string_pool::instance();
    return pool.resolve(pool.intern(scpp::string_t("core-only"))).native_value() == "core-only" ? 0 : 1;
}
''')
            with (source / 'CMakeLists.txt').open('a') as f:
                f.write(f'''
add_executable(direct_document "{ROOT / 'tests/runtime/native/test_json_document.cpp'}")
target_link_libraries(direct_document PRIVATE scpp_json scpp_runtime_test_support)
add_executable(core_only core.cpp)
target_link_libraries(core_only PRIVATE scpp_runtime_core)
add_library(state_provider pool.cpp)
target_link_libraries(state_provider PUBLIC scpp_runtime_core)
add_executable(shared_state state.cpp)
target_link_libraries(shared_state PRIVATE state_provider scpp_runtime_core)
''')
        runner.run(name + '-configure', ['cmake', '-S', source, '-B', build,
            '-DCMAKE_CXX_COMPILER=clang++', '-DBUILD_SHARED_LIBS=' + shared,
            '-DSCPP_WITH_MYSQLI=' + mysql, '-DSCPP_WITH_REGEX=' + optional,
            '-DSCPP_WITH_PROCESS=' + optional, '-DSCPP_WITH_CURL=OFF', '-DSCPP_WITH_UI=OFF'])
        targets = ['aggregate_document'] if a.baseline else [
            'aggregate_document', 'direct_document', 'core_only', 'shared_state',
            'test_json_document', 'test_core', 'test_nullable', 'test_dynamic_value']
        if not a.baseline and optional == 'ON':
            targets += ['test_regex', 'test_process']
        build_targets = targets + (['scpp_php_legacy'] if mysql == 'ON' else [])
        runner.run(name + '-build', ['cmake', '--build', build, '--target',
            *build_targets, '--parallel', '2', '--verbose'], expected=2 if a.baseline else 0, timeout=600)
        if a.baseline:
            stderr = (out / 'logs' / (name + '-build.stderr')).read_text()
            assert 'undefined reference' in stderr and 'scpp::' in stderr, stderr
        else:
            for target in targets:
                executable = build / target if not target.startswith('test_') else build / 'runtime/tests_runtime_native' / target
                runner.run(name + '-' + target + '-execute', [executable])
            core_link = (build / 'CMakeFiles/core_only.dir/link.txt').read_text()
            assert 'scpp_runtime_core' in core_link and not any(
                provider in core_link for provider in ('scpp_json', 'scpp_filesystem', 'scpp_datetime', 'scpp_regex'))
            runner.run(name + '-warm', ['cmake', '--build', build, '--target', *build_targets, '--parallel', '2'], timeout=600)
            (source.parent / 'link_commands.json').write_text(json.dumps({
                str(p.relative_to(build)): p.read_text() for p in build.rglob('link.txt')
            }, indent=2) + '\n')
    variants = [('static-default', 'OFF', 'OFF', 'OFF')] if a.baseline else [
        ('static-default', 'OFF', 'OFF', 'OFF'), ('shared-default', 'ON', 'OFF', 'OFF'),
        ('static-providers', 'OFF', 'ON', 'OFF'), ('shared-providers', 'ON', 'ON', 'OFF'),
        ('static-mysql-build', 'OFF', 'OFF', 'ON'), ('shared-mysql-build', 'ON', 'OFF', 'ON')]
    run_tasks([Task(n, lambda n=n, s=s, o=o, m=m: configuration(n, s, o, m)) for n, s, o, m in variants])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'baseline': a.baseline,
    'configurations': [v[0] for v in variants]}, indent=2) + '\n')

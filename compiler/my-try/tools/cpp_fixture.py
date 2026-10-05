"""Run-local C++ fixture compilation; prepare shared headers before scheduling jobs."""
from pathlib import Path


def build_runtime_object(runner, repository, output, *, cxx='clang++'):
    """Build the existing snippet runtime once, before parallel fixture execution."""
    artifact = Path(output) / 'fixture-runtime.o'
    runner.run('cpp-runtime-build', [cxx, '-std=c++23', '-DSCPP_LANGUAGE_TARGET_PHP=1',
                                   '-I', Path(repository) / 'runtime/include', '-pthread', '-c',
                                   Path(repository) / 'runtime/include/core/runtime.cpp', '-o', artifact],
               timeout=240)
    return artifact


class CppFixtureCompiler:
    """Own matching PCH/fixture flags and a private, freshly built runtime PCH."""

    def __init__(self, runner, repository, output, *, pch=True, return_type_errors=False, runtime=False,
                 cxx='clang++'):
        self.runner = runner
        self.flags = [cxx, '-std=c++20']
        if return_type_errors:
            self.flags.append('-Werror=return-type')
        self.flags.extend(['-I', Path(repository) / 'runtime/include'])
        self.link_flags = []
        if runtime:
            self.flags.extend(['-DSCPP_LANGUAGE_TARGET_PHP=1', '-pthread'])
            self.link_flags = [build_runtime_object(runner, repository, output, cxx=cxx)]
        self.pch_flags = []
        if pch:
            directory = Path(output).resolve() / 'cpp-pch'
            directory.mkdir(exist_ok=False)
            header = directory / 'runtime.hpp'
            header.write_text(
                '#include "scpp/int_t.hpp"\n'
                '#include "scpp/nullable.hpp"\n'
                '#include "scpp/cast.hpp"\n'
                '#include "lang/php/operators/conditional/condition_truthiness.hpp"\n'
                '#include "scpp/generated/operators.hpp"\n')
            artifact = directory / 'runtime.pch'
            runner.run('cpp-pch-build', [*self.flags, '-x', 'c++-header', header, '-o', artifact])
            self.pch_flags = ['-include-pch', artifact]

    def compile(self, name, source, executable):
        return self.runner.run(name, [*self.flags, *self.pch_flags, source, *self.link_flags, '-o', executable])

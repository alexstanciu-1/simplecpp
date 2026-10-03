"""Run-local C++ fixture compilation; prepare shared headers before scheduling jobs."""
from pathlib import Path


class CppFixtureCompiler:
    """Own matching PCH/fixture flags and a private, freshly built runtime PCH."""

    def __init__(self, runner, repository, output, *, pch=True, return_type_errors=False):
        self.runner = runner
        self.flags = ['clang++', '-std=c++20']
        if return_type_errors:
            self.flags.append('-Werror=return-type')
        self.flags.extend(['-I', Path(repository) / 'runtime/include'])
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
        return self.runner.run(name, [*self.flags, *self.pch_flags, source, '-o', executable])

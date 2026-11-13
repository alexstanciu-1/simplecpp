"""Exercise qualification cache validity and atomic refresh on a real compiler."""
import argparse
import json
from pathlib import Path
import tempfile
from unittest.mock import patch

import qualification as q


def check(directory, compiler):
    flags = ['-std=c++23', '-O2']
    cache = directory / 'cache'
    now = 1700000000
    with patch.object(q.time, 'time', return_value=now) as clock:
        key, traces, status = q.qualify(compiler, flags, cache)
        assert status == 'qualified' and q.valid_traces(traces)
        record = cache / (key + '.json')
        initial = json.loads(record.read_text())
        assert all(len(digest) == 64 for digest in initial['context']['components'].values())
        clock.return_value += q.WEEK - 1
        with patch.object(q, 'execute', side_effect=AssertionError('compiler invoked on cache hit')), \
                patch.object(q, 'file_hash', side_effect=AssertionError('binary hashed on cache hit')):
            assert q.qualify(compiler, flags, cache) == (key, traces, 'cached')
        assert json.loads(record.read_text()) == initial

        clock.return_value += 1
        original_execute = q.execute

        def failing_run(command, **kwargs):
            if Path(command[0]).parent.parent == cache:
                raise ValueError('zero-divisor probe failed')
            return original_execute(command, **kwargs)

        with patch.object(q, 'execute', side_effect=failing_run):
            try:
                q.qualify(compiler, flags, cache)
            except ValueError as error:
                assert 'zero-divisor probe failed' in str(error)
            else:
                raise AssertionError('Expired qualification accepted after probe failure')
        assert json.loads(record.read_text()) == initial
        assert q.qualify(compiler, flags, cache)[2] == 'qualified'
        assert json.loads(record.read_text())['tested_at'] == now + q.WEEK

        # Exercise an implementation binary changing behind identical driver/version strings.
        component = directory / 'implementation'
        component.write_text('revision one')
        with patch.object(q, 'compiler_components', side_effect=lambda *_: {str(component): q.file_hash(component)}):
            first = q.qualify(compiler, flags, directory / 'component-cache')[0]
            component.write_text('revision two')
            second, _, status = q.qualify(compiler, flags, directory / 'component-cache')
            assert first != second and status == 'qualified'

        alternate, _, status = q.qualify(compiler, ['-std=c++23', '-O0'], cache)
        assert alternate != key and status == 'qualified'
        assert q.qualify(compiler, flags, cache)[2] == 'cached'
        # A comment changes the probe revision even with identical preprocessor output.
        copy = directory / 'probe.cpp'
        copy.write_text(q.SOURCE.read_text().replace('../../runtime/src/numeric/scalar_semantics.hpp',
                        str((q.SOURCE.parent / '../../runtime/src/numeric/scalar_semantics.hpp').resolve())))
        with patch.object(q, 'SOURCE', copy):
            first = q.qualify(compiler, flags, cache)[0]
            with copy.open('a') as stream:
                stream.write('// revision\n')
            second, _, status = q.qualify(compiler, flags, cache)
            assert first != second and status == 'qualified'
        # A transitive header edit invalidates the fast path even if the probe itself is unchanged.
        header = directory / 'config.hpp'
        header.write_text('#define QUALIFICATION_TEST_VALUE 1\n')
        copy.write_text('#include "config.hpp"\nstatic_assert(QUALIFICATION_TEST_VALUE > 0);\n' + copy.read_text())
        with patch.object(q, 'SOURCE', copy):
            first = q.qualify(compiler, flags, cache)[0]
            header.write_text('#define QUALIFICATION_TEST_VALUE 2\n')
            second, _, status = q.qualify(compiler, flags, cache)
            assert first != second and status == 'qualified'
        record.write_text('{}')
        assert q.qualify(compiler, flags, cache)[2] == 'qualified'


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--cxx', required=True)
    with tempfile.TemporaryDirectory(prefix='scpp-qualification-') as work:
        check(Path(work), parser.parse_args().cxx)
    print('Qualification identity, weekly cache, failed refresh and recovery passed')

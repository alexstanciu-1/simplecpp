"""Guaranteed cleanup through normal, exceptional, and delayed-return paths."""
import json
import os
from pathlib import Path
import tempfile

from app_namespace_native import artifacts, invoke, service
from classes_native import fixture
from exceptions_native import catch, fresh_build, native
from extends_native import runtime, source_edit


def prove(work, flavor, library):
    form = fixture(flavor)
    v, member, infer = form['v'], form['access'], form['infer']
    function = form['function']
    factory = 'Exception.make' if flavor == 'js' else 'Exception::make'
    handler = catch(flavor, 'Exception', 'failure')

    def throw(message):
        return f'throw {factory}("{message}");'

    def local(name):
        declared = form['local'](name, '?Probe').removesuffix(';')
        return f'{declared} = new Probe();'

    app = work / 'app'
    app.mkdir(parents=True)
    output = work / 'output'
    entry = app / f'main.{flavor}'
    provider = app / f'finish.{flavor}'
    source_edit(app / 'module.json', json.dumps({'entry': entry.name}))
    finish = function('finish', 'void', [], 'echo "inner:";')
    source_edit(provider, finish)
    value = function('value', 'string', [], infer('text', '"saved"') + f'''
        try {{
            try {{ return {v}text; }}
            finally {{ finish(); {v}text = "changed"; }}
        }} finally {{ echo "outer:"; }}
    ''')
    void = function('voidReturn', 'void', [], f'''
        try {{ {throw("caught")} }} {handler} {{ echo {v}failure{member}get_message(),":"; return; }}
        finally {{ echo "void:"; }}
    ''')
    replace = function('replaceReturn', 'int', [], f'try {{ return 7; }} finally {{ {throw("replacement")} }}')
    normal = value + void + replace + f'''
        try {{ echo "normal:"; }} finally {{ echo "done:"; }}
        try {{}} finally {{}}
        voidReturn();
        echo value(),":";
        try {{ echo replaceReturn(); }} {handler} {{ echo {v}failure{member}get_message(),":"; }}
        try {{ return 0; }} finally {{ echo "entry"; }}
    '''
    source_edit(entry, normal)
    resident = service(app, output)
    try:
        initial = invoke(resident)
        assert 'error' not in initial, initial
        expected = (0, b'normal:done:caught:void:inner:outer:saved:replacement:entry', b'')
        native(output, work / 'normal', expected, library)
        before = artifacts(output)
        manifest = (output / 'manifest.json').read_bytes()

        def rejects(text, fragment):
            source_edit(entry, text)
            failed = invoke(resident)
            assert fragment in failed.get('error', ''), failed
            assert (output / 'manifest.json').read_bytes() == manifest
            assert artifacts(output) == before

        rejects(normal.replace('finish();', 'return "forbidden";'), 'Return cannot leave a finally block')
        rejects('try {} return 0;', 'Try requires catch or finally')
        rejects('while (true) { try {} finally { break; } } return 0;', 'cannot leave a finally block')
        rejects('while (true) { try {} finally { try { continue; } finally {} } } return 0;',
                'cannot leave a finally block')
        rejects(f'try {{ {infer("hidden", "1")} }} finally {{ echo {v}hidden; }} return 0;', 'not-yet-initialized')
        rejects(f'try {{}} {handler} {{}} finally {{ echo {v}failure; }} return 0;', 'not-yet-initialized')

        source_edit(entry, normal)
        provider.unlink()
        missing = invoke(resident)
        assert 'Unknown function: finish' in missing.get('error', ''), missing
        fresh = fresh_build(app, work / 'fresh-missing')
        assert missing['error'] == fresh.get('error'), (missing, fresh)
        assert invoke(resident).get('error') == missing['error']
        assert (output / 'manifest.json').read_bytes() == manifest
        assert artifacts(output) == before
        source_edit(provider, finish)

        recovered = invoke(resident)
        assert 'error' not in recovered, recovered
        fresh_output = work / 'fresh-recovered'
        assert 'error' not in fresh_build(app, fresh_output)
        assert artifacts(output) == artifacts(fresh_output) == before
        native(output, work / 'recovered', expected, library)

        scopes = form['record']('Probe').replace('struct ', 'class ', 1)
        for role in ['protectedGuard', 'caughtGuard', 'finallyGuard']:
            scopes += local(role) + infer(role + 'Watch', f'weak({v}{role})')
        scopes += f'''
            try {{
                try {{
                    {infer("held", v+"protectedGuard")}
                    {v}protectedGuard = null;
                    {throw("original")}
                }} {handler} {{
                    {infer("held", v+"caughtGuard")}
                    {v}caughtGuard = null;
                    throw {v}failure;
                }} finally {{
                    {infer("held", v+"finallyGuard")}
                    {v}finallyGuard = null;
                    if (lock({v}protectedGuardWatch) !== null || lock({v}caughtGuardWatch) !== null) {{
                        {throw("premature cleanup")}
                    }}
                    echo "cleaned:";
                }}
            }} {handler} {{
                if (lock({v}finallyGuardWatch) !== null) {{ return 5; }}
                echo {v}failure{member}get_message();
                return 0;
            }}
        '''
        source_edit(entry, scopes)
        result = invoke(resident)
        assert 'error' not in result, result
        native(output, work / 'rethrow', (0, b'cleaned:original', b''), library)

        replaced = f'''
            try {{
                try {{ {throw("original")} }} {handler} {{ throw {v}failure; }}
                finally {{ {throw("new")} }}
            }} {handler} {{ echo {v}failure{member}get_message(); return 0; }}
        '''
        source_edit(entry, replaced)
        result = invoke(resident)
        assert 'error' not in result, result
        native(output, work / 'replaced', (0, b'new', b''), library)

        pending_owner = form['record']('Probe').replace('struct ', 'class ', 1)
        pending_owner += form['record']('Observation', form['field']('view', 'weak<Probe>')).replace('struct ', 'class ', 1)
        pending_owner += function('discardReturn', 'Probe', [('observation', 'Observation')], f'''
            try {{
                {infer("value", "new Probe()")}
                {v}observation{member}view = weak({v}value);
                return {v}value;
            }} finally {{
                if (lock({v}observation{member}view) === null) {{ {throw("premature release")} }}
                {throw("discarded")}
            }}
        ''')
        pending_owner += infer('observation', 'new Observation()') + f'''
            try {{ discardReturn({v}observation); }} {handler} {{
                if (lock({v}observation{member}view) !== null) {{ return 6; }}
                echo {v}failure{member}get_message();
                return 0;
            }}
            return 7;
        '''
        source_edit(entry, pending_owner)
        result = invoke(resident)
        assert 'error' not in result, result
        native(output, work / 'discarded-return', (0, b'discarded', b''), library)

        guard = infer('zero', '0') + f'''
            try {{ echo 1 / {v}zero; return 0; }} {handler} {{ return 6; }} finally {{ echo "guard cleanup"; }}
        '''
        source_edit(entry, guard)
        result = invoke(resident)
        assert 'error' not in result, result
        native(output, work / 'guard', (1, b'guard cleanup', b'division_by_zero\n'), library)

        checked_return = function('outsideExitRange', 'int', [], 'return 2147483648;') + '''
            try { return outsideExitRange(); } finally { echo "return cleanup"; }
        '''
        source_edit(entry, checked_return)
        result = invoke(resident)
        assert 'error' not in result, result
        native(output, work / 'return-conversion', (1, b'return cleanup', b'Entry return is outside native int range\n'), library)

        counter = form['local']('i', 'int').removesuffix(';')
        loop = f'for ({counter} = 0; {v}i < 2; {v}i++) {{ continue; }}'
        source_edit(entry, f'try {{ {loop} echo "loop:"; }} finally {{ {loop} echo "cleanup"; }} return 0;')
        result = invoke(resident)
        assert 'error' not in result, result
        native(output, work / 'local-transfers', (0, b'loop:cleanup', b''), library)

        step = function('shouldContinue', 'bool', [('n', 'int')], f'echo "T",{v}n; return {v}n < 2;')
        transfers = step + infer('i', '0') + infer('step', '0') + f'''
            for ({v}i = 0; {v}i < 4; {v}i++) {{
                try {{
                    try {{
                        if ({v}i === 0) {{ continue; }}
                        if ({v}i === 1) {{ {throw("caught")} }}
                        break;
                    }} {handler} {{ if ({v}i <= 2) {{ continue; }} }}
                    finally {{ echo "I",{v}i; }}
                }} finally {{ echo "O",{v}i; }}
                echo "unexpected body suffix";
            }}
            echo ":",{v}i,":";
            do {{
                {v}step++;
                try {{ continue; }} finally {{ echo "D"; }}
            }} while (shouldContinue({v}step));
            return 0;
        '''
        source_edit(entry, transfers)
        result = invoke(resident)
        assert 'error' not in result, result
        native(output, work / 'crossing-transfers', (0, b'I0O0I1O1I2O2:2:DT1DT2', b''), library)
        valid_edit = transfers.replace('if (' + v + 'i === 0) { continue; }', 'if (' + v + 'i === 0) { break; }')
        source_edit(entry, valid_edit)
        result = invoke(resident)
        assert 'error' not in result, result
        fresh_output = work / 'fresh-transfer-edit'
        assert 'error' not in fresh_build(app, fresh_output)
        assert artifacts(output) == artifacts(fresh_output)
        native(output, work / 'edited-transfer', (0, b'I0O0:0:DT1DT2', b''), library)

        distinct_targets = infer('outer', '0') + infer('inner', '0') + f'''
            for ({v}outer = 0; {v}outer < 2; {v}outer++) {{
                try {{
                    for ({v}inner = 0; {v}inner < 3; {v}inner++) {{
                        try {{
                            if ({v}inner === 0) {{ continue; }}
                            break;
                        }} finally {{ echo "L",{v}inner; }}
                    }}
                    echo "S",{v}inner;
                    if ({v}outer === 0) {{ continue; }}
                    break;
                }} finally {{ echo "O",{v}outer; }}
            }}
            echo ":",{v}outer;
            return 0;
        '''
        source_edit(entry, distinct_targets)
        result = invoke(resident)
        assert 'error' not in result, result
        native(output, work / 'distinct-targets', (0, b'L0L1S1O0L0L1S1O1:1', b''), library)

        values = form['local']('values', 'vector<int>').removesuffix(';')
        replacement = f'{values} = [1, 2, 3];' + infer('i', '0') + f'''
            foreach ({v}values as {v}item) {{
                try {{
                    if ({v}item === 1) {{ continue; }}
                    if ({v}item === 2) {{ break; }}
                }} finally {{ echo {v}item; }}
                echo "unexpected item";
            }}
            try {{
                for ({v}i = 0; {v}i < 3; {v}i++) {{
                    try {{ continue; }} finally {{ {throw("replaced")} }}
                }}
            }} {handler} {{ echo ":",{v}failure{member}get_message(),":",{v}i; }}
            return 0;
        '''
        source_edit(entry, replacement)
        result = invoke(resident)
        assert 'error' not in result, result
        native(output, work / 'foreach-and-replacement', (0, b'12:replaced:0', b''), library)

        index = form['local']('idx', 'int').removesuffix(';')
        local_index = form['local']('localIndex', 'int').removesuffix(';')
        mixed_exits = function('chooseExit', 'string', [], f'''
            for ({index} = 0; {v}idx < 2; {v}idx++) {{
                try {{
                    if ({v}idx === 0) {{ continue; }}
                    return "saved";
                }} finally {{
                    for ({local_index} = 0; {v}localIndex < 2; {v}localIndex++) {{
                        try {{
                            if ({v}localIndex === 0) {{ continue; }}
                            break;
                        }} finally {{ echo "N",{v}localIndex; }}
                    }}
                    echo "M",{v}idx;
                }}
            }}
            return "unexpected";
        ''') + 'echo chooseExit(); return 0;'
        source_edit(entry, mixed_exits)
        result = invoke(resident)
        assert 'error' not in result, result
        native(output, work / 'mixed-exits', (0, b'N0N1M0N0N1M1saved', b''), library)

        duplicate = form['local']('failure', 'Exception').removesuffix(';')
        source_edit(entry, f'try {{}} {handler} {{ {duplicate} = {factory}("duplicate"); }} finally {{}} return 0;')
        failure = invoke(resident)
        assert 'Duplicate local: failure' in failure.get('error', ''), failure
    finally:
        resident.stdin.close()
        assert resident.wait() == 0


if __name__ == '__main__':
    os.environ['XDEBUG_MODE'] = 'off'
    with tempfile.TemporaryDirectory() as directory:
        work = Path(directory)
        library = runtime(work, 'clang++')
        for flavor in ['cpp', 'php', 'js']:
            prove(work / flavor, flavor, library)
            print(f'{flavor}: finally exits, cleanup order, and incremental recovery passed', flush=True)

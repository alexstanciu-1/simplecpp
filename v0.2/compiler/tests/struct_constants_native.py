"""Compare cross-unit member constant edits and recovery with fresh native builds."""
import argparse
import json
import os
from pathlib import Path
import tempfile

from app_namespace_native import artifacts, invoke, service
from structs_native import build


def prove(work, flavor):
    app = work / flavor
    app.mkdir()
    entry = app / ('main.' + flavor)
    model = app / ('model.' + flavor)
    (app / 'module.json').write_text(json.dumps({'entry': entry.name}))
    field, limit, label, use = {
        'php': ('public int $LIMIT;', 'public const int LIMIT = 2;', 'public const string LABEL = "ok";',
                'echo Point::LIMIT,Point::LABEL;'),
        'cpp': ('int LIMIT;', 'static constexpr int LIMIT = 2;', 'static constexpr string LABEL = "ok";',
                'echo Point::LIMIT,Point::LABEL;'),
        'js': ('LIMIT:int;', 'const LIMIT:int = 2;', 'const LABEL:string = "ok";',
               'function localRead():int { let Point:Point; Point.LIMIT=7; return Point.LIMIT; } '
               'function paramRead(Point:shared<Point>):int { Point.LIMIT=9; return Point.LIMIT; } '
               'echo localRead(),paramRead(new Point()),Point.LIMIT,Point.LABEL;'),
    }[flavor]
    sep = '.' if flavor == 'js' else '::'
    space_sep = {'php': '\\', 'cpp': '::', 'js': '.'}[flavor]
    (app / ('other.' + flavor)).write_text('namespace Other { struct Point { ' + limit.replace('2', '8') + ' } }')
    entry.write_text(use + f'echo Other{space_sep}Point{sep}LIMIT; return 8;')
    out = work / ('out-' + flavor)
    prefix = b'79' if flavor == 'js' else b''
    with service(app, out) as resident:
        for rev, (member, error, value) in enumerate([
            (limit, None, b'2ok'), (limit.replace('2', '3'), None, b'3ok'), ('', 'Unknown constant: Point::LIMIT', None),
            (limit + limit, 'Duplicate constant', None), (limit.replace('2', '1+2'), 'literal', None),
            (limit, None, b'2ok'),
        ]):
            model.write_text('struct Point { ' + field + member + label + ' }')
            os.utime(model, (1981000000 + rev,) * 2)
            previous = artifacts(out)
            current = invoke(resident)
            baseline = work / f'fresh-{flavor}-{rev}'
            with service(app, baseline) as fresh:
                expected = invoke(fresh)
                fresh.stdin.close()
            if error:
                assert error in current.get('error', '') and current['error'] == expected['error'], (current, expected)
                assert artifacts(out) == previous
            else:
                assert 'error' not in current and 'error' not in expected, (current, expected)
                assert artifacts(out) == artifacts(baseline)
                build(out, work / ('native-' + flavor), prefix + value + b'8')
                build(baseline, work / f'fresh-native-{flavor}-{rev}', prefix + value + b'8')
        resident.stdin.close()


def private_members(work, flavor):
    app = work / ('private-' + flavor)
    app.mkdir()
    entry, model = [app / (name + '.' + flavor) for name in ('main', 'counter')]
    (app / 'module.json').write_text(json.dumps({'entry': entry.name}))
    modifier = 'public: ' if flavor == 'cpp' else 'public '
    prop, const, helper, api, qualifier, scope, variable = {
        'php': ('static int $n=1;', 'const int STEP=2;', 'static function step():int',
                'static function advance():int', 'self::', '::', '$n'),
        'cpp': ('static int n=1;', 'static constexpr int STEP=2;', 'static int step()',
                'static int advance()', 'Self::', '::', 'n'),
        'js': ('static n:int=1;', 'const STEP:int=2;', 'static step():int',
               'static advance():int', 'class.', '.', 'n'),
    }[flavor]
    named = 'Counter'+scope
    members = [prop, const, helper+' { return '+qualifier+'STEP; }']
    body = (api+' { '+qualifier+variable+'='+named+variable+'+'+named+'step(); '
            +'return '+qualifier+variable+'+'+qualifier+'step()+'+named+'STEP; }')

    def record(private=()):
        return ('struct Counter { '+''.join((modifier.replace('public','private') if i in private else modifier)+m
                for i,m in enumerate(members))+modifier+body+' }')

    good = 'echo '+named+'advance(); return 8;'
    probes = [
        (2, 'echo '+named+'step();', 'Cannot call private method Counter::step', b'2'),
        (0, 'echo '+named+variable+';', 'Cannot access private static property Counter::n', b'1'),
        (0, named+variable+'=9; echo '+named+variable+';', 'Cannot access private static property Counter::n', b'9'),
        (0, named+variable+'+=4; echo '+named+variable+';', 'Cannot access private static property Counter::n', b'5'),
        (1, 'echo '+named+'STEP;', 'Cannot access private constant Counter::STEP', b'2'),
    ]
    model.write_text(record())
    revisions = []
    for index, use, error, stdout in probes:
        revisions.extend([(entry, use+'return 8;', None, stdout), (model, record([index]), error, None),
                          (model, record(), None, stdout)])
    revisions.extend([(entry, good, None, b'7'), (model, record(range(3)), None, b'7'),
                      (model, record(range(3))+'struct Outsider { '+modifier+api+' { return '+named+'step(); } }',
                       probes[0][2], None), (model, record(range(3)), None, b'7')])
    out = work / ('private-out-'+flavor)
    with service(app, out) as resident:
        for rev, (path, text, error, stdout) in enumerate(revisions):
            path.write_text(text)
            os.utime(path, (1981100000 + rev,) * 2)
            previous = artifacts(out)
            current = invoke(resident)
            baseline = work / f'private-fresh-{flavor}-{rev}'
            with service(app, baseline) as fresh:
                other = invoke(fresh)
                fresh.stdin.close()
            if error:
                assert error in current.get('error', '') and other['error'] == current['error'], (flavor, rev, current, other)
                assert artifacts(out) == previous
            else:
                assert 'error' not in current and 'error' not in other, (flavor, rev, current, other)
                assert artifacts(out) == artifacts(baseline)
                for bundle in (out, baseline):
                    build(bundle, work / ('build-' + bundle.name), stdout)
        resident.stdin.close()


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory() as tmp:
        work = (args.output or Path(tmp)).resolve()
        work.mkdir(exist_ok=True)
        for flavor in ('php', 'cpp', 'js'):
            prove(work, flavor)
            private_members(work, flavor)
            print(flavor, 'passed', flush=True)

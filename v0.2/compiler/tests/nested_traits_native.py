"""Nested lexical trait uses, diamond deduplication and transitive incremental invalidation."""
import argparse
from pathlib import Path
import tempfile

from collection_goal_native import revisions
from parent_methods_native import syntax


def prove(work, flavor):
    s, _, klass, _, method = syntax(flavor)
    sep = {'php': chr(92), 'cpp': '::', 'js': '.'}[flavor]
    def trait(name, body):
        return f'trait {name} {{\n{body}\n}}' + (';' if flavor == 'cpp' else '')
    def ns(name, body):
        return f'namespace {name} {{ {body} }}'
    core = ns('Lib', trait('Core', s['field']('n', 'int', '4') + method('read', f'return {s["v"]}this{s["access"]}n;')))
    left = ns('Lib', trait('Left', 'use Core;'))
    right = ns('Bridge', f'use Lib{sep}Core; ' + trait('Right', 'use Core;'))
    top = f'use Lib{sep}Left; use Bridge{sep}Right; ' + trait('Both', 'use Left, Right;')
    host = klass('Host', 'use Both;')
    main = s['infer']('h', 'new Host()') + f'echo {s["v"]}h{s["access"]}read(); return 8;'
    idle = s['function']('idle', 'int', [], 'return 0;')
    origin_error = f'right.{flavor}:2:'
    revisions(work, flavor, dict(core=core, left=left, right=right, top=top, host=host, main=main, idle=idle), [
        (None, None, b'4'), (None, None, b'4'),
        ('core', core.replace('=4', '=9'), b'9'),
        ('right', right.replace('use Core;', 'use Right;'), 'Cyclic trait use: Right'),
        (None, None, origin_error), ('right', right, b'9'),
        ('right', right.replace('use Core;', 'use Absent;'), 'Unknown trait: Absent'),
        ('right', right.replace('use Core;', 'use Core, Core;'), 'Duplicate trait use: Core'),
        ('right', right, b'9'),
        ('top', top.replace('Left, Right', 'Right'), b'9'),
        ('right', right.replace('use Core;', method('read', 'return 6;')), b'6'),
        ('top', top, 'Conflicting trait method read'),
        ('right', right, b'9'),
        ('left', ns('Lib', trait('Left', 'use Loop;') + trait('Loop', 'use Left;')), 'Cyclic trait use: Left'),
        ('left', left, b'9'),
        ('core', None, None), ('core', core, b'4'),
        ('unused', trait('Unused', 'use Unused;'), b'4'),
        ('host', klass('Host', 'use Both, Unused;'), 'Cyclic trait use: Unused'),
        ('host', host, b'4'),
    ], unaffected_source='idle.' + flavor)


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-nested-traits-') as tmp:
        work = args.output or Path(tmp)
        work.mkdir(exist_ok=True)
        for flavor in ('php', 'cpp', 'js'):
            prove(work, flavor)

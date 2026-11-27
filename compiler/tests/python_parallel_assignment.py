"""Flat parallel assignment through the Python frontend and native runtime."""
import sys
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import guard, revisions

helper = """class Failure(Exception):
    pass

class Cell:
    value: int = 0

def mark(log: Cell, digit: int) -> int:
    log.value = log.value * 10 + digit
    return digit

def fail(log: Cell) -> int:
    log.value = log.value * 10 + 2
    raise Failure()
"""
caller = """left: Cell = Cell()
right: Cell = Cell()
left.value = 3
right.value = 4
saved: Cell = left
left, right = right, left
saved.value = 5
if left.value != 4 or right.value != 5:
    return 1
log: Cell = Cell()
a: int = 3
b: int = 4
try:
    a, b = mark(log, 1), fail(log)
    return 2
except Failure as error:
    if a != 3 or b != 4 or log.value != 12:
        return 3
a, a = mark(log, 3), mark(log, 4)
if a != 4 or log.value != 1234:
    return 4
a, = 7,
numbers: vector<int> = []
boxed: dynamic = []
numbers, boxed = [2], [3]
if a != 7 or numbers[0] != 2 or boxed[0] != 3:
    return 5
return 8
"""

partial = """class WrongWriteOrder(Exception):
    pass

class Trace:
    value: int = 0

def last(log: Trace) -> int:
    log.value = 9
    return 41

first: int = 1
second: int = 2
third: int = 3
wrong: mixed = "oops"
log: Trace = Trace()
try:
    first, second, third = 7, wrong, last(log)
finally:
    if first != 7 or second != 2 or third != 3 or log.value != 9:
        raise WrongWriteOrder()
return 2
"""

with TemporaryDirectory(prefix='python-parallel-') as tmp:
    root = Path(tmp)
    # Three Clang builds took 4.8 s together and 6.2 s in isolation. Keep one
    # native run in the default suite and run edit/recovery and unwinding on demand.
    if '--quick' in sys.argv:
        revisions(root, 'py', {'main': caller, 'helper': helper}, [(None, None, b'')])
        sys.exit(0)
    bad_helper = helper.replace('digit: int) -> int:', 'digit: int) -> string:').replace('return digit', 'return "wrong"')
    revisions(root, 'py', {'main': caller, 'helper': helper}, [
        (None, None, b''),
        ('helper', bad_helper, 'Unsupported implicit conversion from string to int'),
        ('helper', helper, b''),
        ('main', caller.replace('left, right =', 'left, missing ='), 'requires an existing local'),
        ('main', caller.replace('right, left\n', 'right, left, right\n'), 'target and value counts differ'),
        ('main', caller.replace('right, left\n', 'right\n'), 'explicit list of RHS expressions'),
    ])
    guard(root, 'py', 'partial', partial, 'boxed_scalar_kind')

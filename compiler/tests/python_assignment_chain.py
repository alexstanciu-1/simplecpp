"""Original-RHS chain writes, tested through the Python frontend and native runtime."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import guard, revisions

helper = """class Failure(Exception):
    pass

def fail() -> int:
    raise Failure()

class Cell:
    value: int = 0

class Child(Cell):
    pass

def acquire(counter: Cell) -> Child:
    counter.value = counter.value + 1
    return Child()
"""
caller = """a: int = 1
b: int = 2
try:
    a = b = fail()
    return 6
except Failure as error:
    if a != 1 or b != 2:
        return 7
counter: Cell = Cell()
first: Cell = Cell()
last: Child = Child()
first = last = acquire(counter)
last.value = 7
if counter.value != 1 or first.value != 7:
    return 1
# Rebind the RHS name as the first target, then release that owner.
last = first = last
last = Child()
if first.value != 7:
    return 2
first = last = acquire(counter)
if counter.value != 2 or first.value != 0:
    return 3
numbers: dynamic = [1]
alias: dynamic = [2]
numbers = alias = [4]
numbers[0] = 5
if alias[0] != 5:
    return 4
x: nullable<int> = 1
y: nullable<int> = 2
x = y = None
if x is not None or y is not None:
    return 5
return 8
"""


# A wrong state in finally raises a distinct exception during native unwinding.
partial = """class WrongWriteOrder(Exception):
    pass

first: mixed = "old"
second: int = 17
third: mixed = "untouched"
source: mixed = "changed"
try:
    first = second = third = source
finally:
    if first != "changed" or second != 17 or third != "untouched":
        raise WrongWriteOrder()
return 2
"""


with TemporaryDirectory(prefix='python-chain-') as tmp:
    root = Path(tmp)
    revisions(root, 'py', {'main': caller, 'helper': helper}, [
        (None, None, b''),
        ('helper', helper.replace('-> Child:', '-> Cell:').replace('return Child()', 'return Cell()'), 'Unsupported implicit conversion from Cell to Child'),
        ('helper', helper, b''),
        ('main', caller.replace('first = last = acquire(counter)', 'first = missing = acquire(counter)'), 'requires an existing local'),
    ])
    guard(root, 'py', 'partial', partial, 'boxed_scalar_kind')

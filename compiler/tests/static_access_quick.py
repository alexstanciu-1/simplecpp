"""Inherited protected static data, lexical dispatch, late binding and access after edits."""
from pathlib import Path
import tempfile

from collection_goal_native import revisions


BASE = """
class Base {
    protected static int $value = 1;
    protected static function step():int { return 2; }
    public static function run():int {
        self::$value += static::step();
        return self::$value;
    }
}
"""
CHILD = """
class Child extends Base {
    public static function step():int { return 4; }
    public static function read():int { return parent::$value; }
}
"""
MAIN = 'echo Base::run(), Child::run(), Child::read(); return 8;'


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-static-access-') as work:
        revisions(Path(work), 'php', dict(main=MAIN, base=BASE, child=CHILD), [
            (None, None, b'377'),
            ('main', 'return Child::$value;', 'Cannot access protected static property'),
            ('main', MAIN, b'377'),
        ])

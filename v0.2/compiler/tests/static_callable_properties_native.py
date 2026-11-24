"""Static callable properties share one slot across files and snapshot the target before arguments."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import guard, revisions


def prove(work):
    holder = """
        class Handler {
            public static function<int(int)> $run;
            public static function install(int $offset):void {self::$run=fn($n)=>$n+$offset;}
            public static function current(int $n):int {return (self::$run)($n);}
        }
    """
    writer = 'function replaceTarget():int {Handler::$run=fn($n)=>$n+10;return 2;}'
    reader = 'function first():int {return (Handler::$run)(replaceTarget());}'
    main = """
        Slot::$make=fn()=>new Payload();
        $p=(Slot::$make)();echo $p->id,":";
        Handler::install(3);echo first(),":",Handler::current(2);
        return 8;
    """
    slot = 'struct Slot {public static function<Payload()> $make;}'
    payload = 'class Payload {public int $id=7;}'
    derived = """
        class Derived extends Handler {
            public static function inherited():int {return (parent::$run)(2);}
        }
    """
    trace = b'7:5:12'
    revisions(work, 'php', dict(main=main, holder=holder, writer=writer, reader=reader, slot=slot, payload=payload, derived=derived), [
        (None, None, trace), (None, None, trace),
        ('holder', holder.replace('fn($n)=>$n+$offset',
                                  'function(int $n) use (&$offset):int {return $n+$offset;}'),
         'cannot erase a borrowed environment'),
        ('holder', holder, trace),
        ('holder', holder.replace('function<int(int)>', 'function<int()>')
                         .replace('fn($n)=>$n+$offset', 'fn()=>$offset')
                         .replace('(self::$run)($n)', '(self::$run)()'), 'Argument count mismatch for function: Handler::run'),
        ('holder', holder, trace),
        ('payload', None, 'Unsupported callback signature component: Payload'),
        ('payload', payload, trace),
        ('main', 'Derived::install(7);echo Derived::inherited(),(Derived::$run)(2);return 8;', b'99'),
        ('holder', holder.replace('$run;', '$run=fn($n)=>$n;'), 'Static callable property starts absent'),
        ('holder', holder, b'99'),
    ])
    guard(work, 'php', 'absent', holder + """
        function install():int {echo "A";Handler::install(9);return 2;}
        (Handler::$run)(install());return 8;
    """, expected_stdout=b'A', exception_type='std::bad_function_call', exception_header='functional')


if __name__ == '__main__':
    with TemporaryDirectory(prefix='scpp-static-callable-properties-') as directory:
        prove(Path(directory))

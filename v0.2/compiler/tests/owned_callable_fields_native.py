"""Owned callable instance fields select their target before argument evaluation."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import guard, revisions


def prove(work):
    holder = """
        class Handler {
            public function<int(int)> $run;
            public function<Payload()> $create;
            public function run(int $n):int {return $n+100;}
        }
    """
    main = """
        function replaceHandler(Handler $h):int {$h->run=fn($n)=>$n+10;return 2;}
        function selectHandler(Handler $h):Handler {echo "S";return $h;}
        $slot Slot;$slot->read=fn()=>6;echo ($slot->read)(),":";
        $h=new Handler();$h->create=fn()=>new Payload();echo readCreated($h),":";
        $offset=3;$h->run=fn($n)=>$n+$offset;
        echo (selectHandler($h)->run)(replaceHandler($h)),":",runAt($h),":",$h->run(2);
        return 8;
    """
    payload = 'class Payload {public int $id=7;}'
    reader = """
        function readCreated(Handler $h):int {$p=($h->create)();return $p->id;}
        function runAt(Handler $h):int {return ($h->run)(2);}
    """
    slot = 'struct Slot {public function<int()> $read;}'
    trace = b'6:7:S5:12:102'
    revisions(work, 'php', dict(main=main, holder=holder, payload=payload, reader=reader, slot=slot), [
        (None, None, trace), (None, None, trace),
        ('main', main.replace('fn($n)=>$n+$offset', 'function(int $n) use (&$offset):int {return $n+$offset;}'),
         'cannot erase a borrowed environment'),
        ('main', main, trace),
        ('holder', holder.replace('function<int(int)>', 'function<int()>'), 'Argument count mismatch for function: run'),
        ('holder', holder, trace),
        ('payload', None, 'Unsupported callback signature component: Payload'),
        ('payload', payload, trace),
        ('main', """
            async function leaf():int {await async_sleep_ms(1);return 2;}
            async function invoke():int {
                $h=new Handler();$h->run=fn($n)=>$n+3;return ($h->run)(await leaf());
            }
            echo await invoke();return 8;
        """, b'5'),
    ])
    guard(work, 'php', 'absent', payload + holder + """
        function install(Handler $h):int {echo "A";$h->run=fn($n)=>$n;return 2;}
        $h=new Handler();($h->run)(install($h));return 8;
    """, expected_stdout=b'A', exception_type='std::bad_function_call', exception_header='functional')


if __name__ == '__main__':
    with TemporaryDirectory(prefix='scpp-owned-callable-fields-') as directory:
        prove(Path(directory))

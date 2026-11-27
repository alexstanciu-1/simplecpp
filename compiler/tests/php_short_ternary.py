"""PHP short ternary evaluates the left once and applies the ordinary arm contracts."""
from pathlib import Path
import sys
from tempfile import TemporaryDirectory

from collection_goal_native import revisions
from indexed_dynamic_compound import MODEL, SETUP, VALUES


def selection(work):
    api = 'function fallback():int {echo "F";return 8;}'
    main = """
        function probe(int $n):int {echo "P";return $n;}
        function fail():int {echo "X";throw Exception::make("unexpected");}
        function keep():int {return probe(7) ?: fail();}
        function recover():int {return probe(0) ?: fallback();}
        function converted(bool $b):int {return $b ?: 6;}
        echo keep(),recover(),converted(true),converted(false);return 8;
    """
    trace = b'P7PF816'
    revisions(work, 'php', dict(main=main, api=api), [
        (None, None, trace), ('api', 'function fallback():string {return "bad";}',
                              'Unsupported explicit conversion from string to int64'),
        ('api', api, trace)])


def inferred(work):
    main = '$s="0";$text=$s ?: "ok";$s="00";echo $text,":",$s ?: "bad";return 8;'
    revisions(work, 'php', dict(main=main), [
        ('main', main.replace('?: "ok"', '?: 6'), 'Ternary result types must be identical'),
        ('main', main, b'ok:00')])


def suspended(work):
    main = """
        async function probe(int $n):int {await async_sleep_ms(1);echo "P";return $n;}
        async function fallback():int {await async_sleep_ms(1);echo "F";return 8;}
        async function choose(int $n):int {return (await probe($n)) ?: (await fallback());}
        echo await choose(7),await choose(0);return 8;
    """
    revisions(work, 'php', dict(main=main), [(None, None, b'P7PF8')])


def index_key(work):
    api = MODEL + """
        function replaceChildAndReturnKey(Holder $holder, dynamic $fresh):string {
            echo "F"; $holder->data->child=$fresh; return "n";
        }
        function rhs():int { echo "R"; return 7; }
    """
    main = SETUP + """
        $fresh["n"]=90;
        $owners[ownerKey()]->data->child[preferredKey() ?: replaceChildAndReturnKey($original,$fresh)] += rhs();
    """ + VALUES + 'return 8;'

    def preferred(value):
        return f'function preferredKey():string {{ echo "P"; return "{value}"; }}'

    original = b'OPR|10/10/10/90'
    replacement = b'OPFR|97/97/3/97'
    revisions(work, 'php', dict(main=main, api=api, preference=preferred('n')), [
        (None, None, original),
        ('preference', preferred(''), replacement),
        ('preference', preferred('0'), replacement),
        ('preference', preferred('n'), original),
    ])


with TemporaryDirectory(prefix='php-short-ternary-') as tmp:
    {'selection': selection, 'inferred': inferred, 'suspended': suspended, 'index_key': index_key}[sys.argv[1]](Path(tmp))

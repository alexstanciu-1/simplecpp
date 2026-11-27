"""Single-field PHP nullsafe reads preserve absence, values and ordinary field access."""
from pathlib import Path
import sys
from tempfile import TemporaryDirectory

from collection_goal_native import revisions
from indexed_dynamic_compound import MODEL, SETUP


player = 'class Player {public int $score; public nullable<string> $bonus;}'


def reads(work):
    main = """
        function probe(?Player $p):?Player {echo "P";return $p;}
        function fail():?Player {echo "F";throw Exception::make("receiver");}
        function score(?Player $p):nullable<int> {return probe($p)?->score;}
        function bonus(?Player $p):nullable<string> {return probe($p)?->bonus;}
        $p=new Player();
        echo score(null) ?? 9,score($p) ?? 9,bonus(null) ?? "N",bonus($p) ?? "N";
        $p->bonus="";echo bonus($p) ?? "N",":";
        $p->bonus="set";echo bonus($p) ?? "N";
        try {$unused=fail()?->score;} catch (Exception $e) {echo "C";}
        return 8;
    """
    trace = b'P9P0PNPNP:PsetFC'
    revisions(work, 'php', dict(main=main, player=player), [
        (None, None, trace), ('player', player.replace('$score', '$points'), 'Unknown field Player.score'),
        ('player', player, trace)])


def nominal(work):
    types = """
        enum Rank {case First=1;}
        class Child {public int $id;}
        class ParentBox {
            public ?Child $child;
            public Rank $rank=Rank::First;
        }
    """
    main = """
        function child(?ParentBox $p):?Child {return $p?->child;}
        function rankOf(?ParentBox $p):?Rank {return $p?->rank;}
        $p=new ParentBox();$c=new Child();$c->id=7;
        echo (child(null) ? 1 : 0),(child($p) ? 1 : 0);
        $p->child=$c;$same=child($p);$same->id=8;echo $c->id,(int)isset(rankOf(null)),(int)isset(rankOf($p));return 8;
    """
    revisions(work, 'php', dict(main=main, types=types), [(None, None, b'00801')])


def admission(work):
    read = 'function read(?Player $p):nullable<int> {return $p?->score;} return 8;'
    revisions(work, 'php', dict(main=read, player=player), [
        ('player', player.replace('public int', 'private int'), 'Cannot access private field Player::score'),
        ('player', player.replace('int $score', 'function<int(int)> $score'), 'No optional representation for field type'),
        ('player', player, b'')])


def index_key(work):
    api = MODEL + """
    class Selector { public nullable<string> $key; }
    function probe(?Selector $s):?Selector { echo "P"; return $s; }
    function fallbackKey(Holder $h, dynamic $replacement):string {
        echo "F"; $h->data->child=$replacement; return "n";
    }
    function rhs():int { echo "R"; return 7; }
    function inspectChild(dynamic $node):void {
        echo "|",(int)$node[""],"/",(int)$node["0"],"/",(int)$node["n"];
    }
    """
    main = SETUP + """
    $child[""]=11; $child["0"]=22; $child["n"]=33;
    $fresh[""]=71; $fresh["0"]=82; $fresh["n"]=93;
    $selector=chooseSelector();
    $owners[ownerKey()]->data->child[probe($selector)?->key ?? fallbackKey($original,$fresh)] += rhs();
    inspectChild($child); inspectChild($fresh); inspectChild($owners["A"]->data->child);
    return 8;
    """
    def choice(body):
        return 'function chooseSelector():?Selector {' + body + '}'

    absent = b'OPFR|11/22/33|71/82/100|71/82/100'
    sources = dict(main=main, api=api, selector=choice('return null;'))
    revisions(work, 'php', sources, [
        (None, None, absent),
        ('selector', choice('$s=new Selector(); return $s;'), absent),
        ('selector', choice('$s=new Selector(); $s->key=""; return $s;'), b'OPR|18/22/33|71/82/93|18/22/33'),
        ('selector', choice('$s=new Selector(); $s->key="0"; return $s;'), b'OPR|11/29/33|71/82/93|11/29/33'),
    ])


with TemporaryDirectory(prefix='php-nullsafe-field-') as tmp:
    {'reads': reads, 'admission': admission, 'nominal': nominal, 'index_key': index_key}[sys.argv[1]](Path(tmp))

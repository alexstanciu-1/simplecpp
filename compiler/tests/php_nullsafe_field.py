"""Single-field PHP nullsafe reads preserve absence, values and ordinary field access."""
from pathlib import Path
import sys
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


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


with TemporaryDirectory(prefix='php-nullsafe-field-') as tmp:
    {'reads': reads, 'admission': admission, 'nominal': nominal}[sys.argv[1]](Path(tmp))

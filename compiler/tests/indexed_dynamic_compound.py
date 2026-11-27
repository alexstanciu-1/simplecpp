"""Trace dynamic compound selection when either the index or the RHS replaces a child."""
import sys
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import guard, revisions


MODEL = """
class Holder { public dynamic $data; }
function ownerKey():string { echo "O"; return "A"; }
function leafKey():string { echo "K"; return "n"; }
"""
SETUP = """
$original=new Holder(); $other=new Holder();
$root dynamic=[]; $child dynamic=[];
$child["n"]=3; $root->child=$child; $original->data=$root;
$freshRoot dynamic=[]; $fresh dynamic=[];
$freshRoot->child=$fresh; $other->data=$freshRoot;
$owners hash<Holder,string> = []; $owners["A"]=$original;
"""
TARGET = '$owners[ownerKey()]->data->child[leafKey()] += rhs($owners,$other);'
# Inspect the original holder, current hash entry and both independent child handles.
VALUES = """
echo "|", (int)$original->data->child["n"], "/", (int)$owners["A"]->data->child["n"],
     "/", (int)$child["n"], "/", (int)$fresh["n"];
"""
PRESENCE = """
echo "|", $original->data->child->has("n") ? 1 : 0, "/", $owners["A"]->data->child->has("n") ? 1 : 0,
     "/", $child->has("n") ? 1 : 0, "/", (int)$fresh["n"];
"""
CASES = {
    'replace_child': ('$owners["A"]->data->child=$other->data->child;', b'OKR|10/10/3/10'),
    'replace_holder': ('$owners["A"]=$other;', b'OKR|10/90/10/90'),
    'remove_leaf': ('$node=$owners["A"]->data->child; $node->remove("n");', b'OKR|0/0/0/90'),
}


def run(work, case):
    effect, expected = CASES[case]
    rhs = 'function rhs(hash<Holder,string> &$owners, Holder $other):int { echo "R";' + effect + 'return 7; }'
    source = MODEL + rhs + SETUP + '$fresh["n"]=90;'
    if case == 'remove_leaf':
        source += 'try {' + TARGET + '} finally {' + PRESENCE + '} return 0;'
        guard(work, 'php', case, source, 'boxed_missing_key', expected_stdout=expected)
    else:
        source += TARGET + VALUES + 'return 8;'
        revisions(work, 'php', {'main': source}, [(None, None, expected)])


def index_replacement(work, case):
    source = MODEL + """
    function replaceChildAndReturnKey(Holder $holder, dynamic $fresh):string {
        echo "K"; $holder->data->child=$fresh; return "n";
    }
    function rhs():int { echo "R"; return 7; }
    """ + SETUP
    target = '$owners[ownerKey()]->data->child[replaceChildAndReturnKey($original,$fresh)] += rhs();'
    if case == 'index_replace_present':
        source += '$fresh["n"]=90;' + target + VALUES + 'return 8;'
        revisions(work, 'php', {'main': source}, [(None, None, b'OKR|97/97/3/97')])
    else:
        check = """
        echo "|", $owners["A"]->data->child->has("n") ? 1 : 0, "/", $fresh->has("n") ? 1 : 0,
             "/", (int)$child["n"];
        """
        source += 'try {' + target + '} finally {' + check + '} return 0;'
        guard(work, 'php', case, source, 'boxed_missing_key', expected_stdout=b'OK|0/0/3')


if __name__ == '__main__':
    with TemporaryDirectory(prefix='indexed-dynamic-compound-') as tmp:
        case = sys.argv[1]
        if case in ('index_replace_present', 'index_replace_missing'):
            index_replacement(Path(tmp), case)
        else:
            run(Path(tmp), case)

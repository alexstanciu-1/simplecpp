"""An indexed holder stays selected while a dynamic compound rechecks its child after the RHS."""
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
$fresh["n"]=90; $freshRoot->child=$fresh; $other->data=$freshRoot;
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
    source = MODEL + rhs + SETUP
    if case == 'remove_leaf':
        source += 'try {' + TARGET + '} finally {' + PRESENCE + '} return 0;'
        guard(work, 'php', case, source, 'boxed_missing_key', expected_stdout=expected)
    else:
        source += TARGET + VALUES + 'return 8;'
        revisions(work, 'php', {'main': source}, [(None, None, expected)])


if __name__ == '__main__':
    with TemporaryDirectory(prefix='indexed-dynamic-compound-') as tmp:
        run(Path(tmp), sys.argv[1])

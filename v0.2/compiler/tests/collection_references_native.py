"""Run collection reference and incremental recovery fixtures through the instrumented native build proof."""
import argparse
from pathlib import Path
import tempfile
from application_vectors_native import measure
from strings_native import ROOT, prove, run, success


def prove_parameter_reassignment(work):
    app = work / 'reassignment'
    app.mkdir()
    (app / 'module.json').write_text('{"entry":"main.php"}')
    (app / 'main.php').write_text('''
        function replace_items(vector<int> $items): int {
            $replacement vector<int> = [71];
            $items = $replacement;
            return $items[0];
        }
        function replace_counts(hash<int,string> $counts): int {
            $replacement hash<int,string> = [];
            $replacement["other"] = 83;
            $counts = $replacement;
            return $counts["other"];
        }
        $items vector<int> = [3, 5];
        $counts hash<int,string> = [];
        $counts["original"] = 7;
        echo replace_items($items), ":", count($items), ":", $items[0], ":", $items[1], ";";
        echo replace_counts($counts), ":", count($counts), ":", $counts["original"];
        return 8;
    ''')
    output = work / 'reassignment-out'
    compiler = success(run(['php', ROOT / 'compiler/main.php', 'project', app,
                            ROOT / 'build/clang/native-layout/facts.json', output]))
    assert compiler.stderr == '', compiler.stderr
    measure(output, work / 'reassignment-native', b'71:2:3:5;83:1:7')


def prove_collections(work):
    prove(work, 'collection_references.php', check_copy=False)
    prove_parameter_reassignment(work)


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    if args.output:
        prove_collections(args.output.resolve())
    else:
        with tempfile.TemporaryDirectory(prefix='scpp-collection-references-native-') as directory:
            prove_collections(Path(directory))

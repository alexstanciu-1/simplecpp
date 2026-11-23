"""Read-only vector/hash borrows, alias visibility, and incremental signature changes."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


def prove(work):
    size = 'function size(const vector<int> &$v): int { return count($v); }'
    body = """
        function forward(const vector<int> &$v): int {
            $alias =& $v;
            $again =& $alias;
            $copy = $again;
            clean($copy);
            $n = 0;
            foreach ($again as $item) { $n++; }
            if ($n !== size($again)) { return 99; }
            return $n;
        }
        function clearAndCount(const vector<int> &$view, vector<int> &$writer): int {
            clean($writer);
            return forward($view);
        }
        function snapshot(vector<int> $view, vector<int> &$writer): int {
            clean($writer);
            return size($view);
        }
        function readHash(const hash<int,string> &$view, hash<int,string> &$writer): int {
            $writer["k"] = 8;
            return $view["k"];
        }
        $items vector<int> = [1, 2];
        if (forward($items) !== 2 || snapshot($items, $items) !== 2) { return 1; }
        $items = [1, 2];
        if (clearAndCount($items, $items) !== 0) { return 2; }
        $map hash<int,string> = ["k" => 1];
        return readHash($map, $map);
    """
    edits = [(None, None, b''), (None, None, b''),
             ('size', size.replace('const ', ''), 'Cannot write through read-only reference'),
             ('size', None, 'Unknown function'), ('size', size, b'')]
    for statement in ['clean($again);', '$alias = [2];', '$v[] = 2;', '$v[0] = 2;',
                      '$again[0] += 1;', '$v[0]++;', '$v->clear();', '$v->remove(0);',
                      '$rows vector<vector<int>> = []; foreach ($rows as $alias) {}']:
        edits.append(('main', body.replace('clean($copy);', statement), 'Cannot write through read-only reference'))
    edits.append(('main', body.replace('$writer["k"] = 8;', '$view["k"] = 8;'), 'Cannot write through read-only reference'))
    for argument, prefix, error in [
            ('makeItems()', 'function makeItems(): vector<int> { $x vector<int> = []; return $x; }', 'stable variable storage'),
            ('$rows[0]', '$rows vector<vector<int>> = [];', 'stable variable storage'),
            ('$strings', '$strings vector<string> = [];', 'exact storage type')]:
        edits.append(('main', prefix + 'return size(' + argument + ');', error))
    edits.extend([('size', 'function size(const int &$v): int { return $v; }', 'vector or hash'),
                  ('size', 'function size(const vector<int> &$v): Iterator<int> { yield count($v); }', 'Yield producers require by-value'),
                  ('size', size.replace('&$v', '$v'), '& after const parameter type'),
                  ('size', size.replace('&$v', '&$v = null'), 'Reference parameter defaults'),
                  ('size', 'class Reader { public ' + size + ' }', 'of a free function'),
                  ('size', size, 'exact storage type'), ('main', body, b'')])
    revisions(work, 'php', {'main': body, 'size': size}, edits)


if __name__ == '__main__':
    with TemporaryDirectory(prefix='scpp-readonly-parameters-') as directory:
        prove(Path(directory))

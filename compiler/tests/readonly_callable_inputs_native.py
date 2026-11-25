"""Collection borrows through local closures, function pointers and owned callables."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


def prove(work, collection, setup, update, read, trace):
    readonly = f'const {collection}&'
    signature = f'int,{readonly},{collection}&'
    owned = f'function<int({readonly},{collection}&)>'
    parameters = f'const {collection} &$view,{collection} &$writer'
    helper = f'function observe(const {collection} &$view):int {{return {read};}}'
    target = f'function inspect({parameters}):int {{{update}return observe($view);}}'
    static = f'class Inspector {{public static {target}}}'
    consumer = f'''
        function apply(callback<{signature}> $op,{collection} &$v):int {{return $op($v,$v);}}
        function relay({owned} $op,{collection} &$v):int {{return $op($v,$v);}}
    '''
    main = f'''
        $v {collection} = {setup};
        $local=function({parameters}):int {{{update}return observe($view);}};
        echo $local($v,$v);
        $v={setup};echo apply(inspect(...),$v);
        $v={setup};echo relay(Inspector::inspect(...),$v);
        $extra=1;
        $f {owned} = function(const &$view,&$writer) use ($extra) {{{update}return observe($view)+$extra;}};
        $v={setup};echo relay($f,$v);
        return 8;
    '''
    edits = [(None, None, trace)]
    for mode in [f'{collection} &$view', f'{collection} $view']:
        edits.extend([('target', target.replace(f'const {collection} &$view', mode), 'signature mismatch'),
                      (None, None, 'signature mismatch'), ('target', target, trace)])
    edits += [
        ('static', static.replace(f'const {collection} &$view', f'{collection} &$view'), 'signature mismatch'),
        ('static', static, trace),
        ('main', main.replace('$local($v,$v)', '$local(fresh(),$v)') + f'function fresh():{collection} {{return {setup};}}',
         'stable variable storage'),
        ('main', main.replace(update, 'clean($view);', 1), 'Cannot write through read-only reference'),
        ('main', main.replace('const &$view,&$writer', '$view,&$writer'), 'Expected exact callable signature'),
        ('main', main.replace('use ($extra)', 'use (&$extra)'), 'borrowed environment'),
        ('main', main.replace('apply(inspect(...),$v)', 'apply(function(const &$r,&$w) use ($v) {return 1;},$v)'), 'capture-free'),
        ('main', main, trace),
        ('consumer', consumer.replace(readonly, f'{collection}&'), 'signature'),
        ('consumer', consumer, trace),
    ]
    revisions(work, 'php', dict(main=main, consumer=consumer, target=target, helper=helper, static=static), edits)


def signatures(work):
    main = """
        function &selectSlot(const vector<int> &$view,vector<int> &$slot):vector<int> {return $slot;}
        function &relay(callback<vector<int>&,const vector<int>&,vector<int>&> $op,
                        const vector<int> &$view,vector<int> &$slot):vector<int> {return $op($view,$slot);}
        $v vector<int> = [1];$w vector<int> = [2,3];
        $slot =& relay(selectSlot(...),$v,$w);clean($slot);echo count($v),count($w);
        $f function<vector<int>&(const vector<int>&,vector<int>&)> = selectSlot(...);
        $again =& $f($w,$v);clean($again);echo count($v);
        $reads vector<function<int(const vector<int>&)>> = [fn(const &$view)=>count($view)];
        echo $reads[0]($w);return 8;
    """
    edits = [(None, None, b'1000')]
    for first, second in [('const vector<int>&', 'vector<int>&'), ('const vector<int>&', 'vector<int>'),
                          ('vector<int>&', 'const vector<int>&'), ('vector<int>', 'const vector<int>&')]:
        bad = f'$a vector<function<int({first})>> = [];$b vector<function<int({second})>> = $a;return 8;'
        edits.append(('main', bad, 'conversion'))
    edits += [('main', main.replace('return $slot;', 'return $view;'), 'must originate in its writable parameter'),
              ('main', '$f function<vector<int>&(const vector<int>&)>;return 8;', 'exactly one writable parameter'),
              ('main', '$f function<int(const int&)>;return 8;', 'vector or hash input'),
              ('main', main, b'1000')]
    revisions(work, 'php', dict(main=main), edits)


if __name__ == '__main__':
    with TemporaryDirectory(prefix='scpp-readonly-callable-signatures-') as directory:
        signatures(Path(directory))
    for case in [('vector<int>', '[1,2]', 'clean($writer);', 'count($view)', b'0001'),
                 ('hash<int,string>', '["k"=>3]', '$writer["k"]=8;', '$view["k"]', b'8889')]:
        with TemporaryDirectory(prefix='scpp-readonly-callables-') as directory:
            prove(Path(directory), *case)

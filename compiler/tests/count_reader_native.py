"""Private collection readers preserve snapshots, callbacks and observable cleanup across edits."""
import argparse
import json
import subprocess
import re
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import guard, revisions

reader = """
function width(vector<int> $items):int {return count($items);}
function first(vector<int> $items):int {return $items[0];}
function lookup(hash<int,int> $items):int {return $items[42];}
function keys(hash<int,int> $items):int {return count($items);}
"""
other = """
struct Mark {public int $x=0;public function __destruct(){echo "D";}}
function marked(vector<Mark> $items):int {return count($items);}
function textKeys(hash<int,string> $items):int {return count($items);}
function invokeWidth(callback<int,vector<int>> $cb,vector<int> &$v):int {return $cb($v);}
function snapshot(vector<int> $view,vector<int> &$writer):int {clean($writer);return count($view);}
"""
main = """
$v vector<int> = [1,2];$h hash<int,int> = [];$h[1]=1;
$h[42]=7;echo first($v),lookup($h),$v[0],$h[42];
try {echo width($v),invokeWidth(width(...),$v);} catch (Exception $e) {echo "E";}
echo count($v),keys($h),snapshot($v,$v),count($v);
$text hash<int,string> = [];$text["a"]=1;echo textKeys($text);
$m Mark;$marks vector<Mark> = [$m];echo "[";echo marked($marks);echo "]";
return 8;
"""
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--quick', action='store_true')
quick = parser.parse_args().quick
with TemporaryDirectory(prefix='scpp-private-count-') as directory:
    work = Path(directory)
    cleanup = b'1D[1D]DD'
    edits = [
        (None, None, b'1717222220' + cleanup),
        ('reader', reader.replace('return count($items);', 'clean($items);return count($items);', 1), b'1717002220' + cleanup),
        ('reader', reader.replace('return count($items);', 'clean($items);throw new Exception("stop");', 1), b'1717E2220' + cleanup),
        ('reader', reader.replace('return $items[0];', '$items[0]=8;return $items[0];')
                         .replace('return $items[42];', '$items[42]=9;return $items[42];'), b'8917222220' + cleanup),
        ('reader', None, None),
        ('reader', reader, b'1717222220' + cleanup),
    ]
    revisions(work, 'php', dict(main=main, reader=reader, other=other), edits[:1] if quick else edits)
    units = '\n'.join(p.read_text() for p in (work/'php-out/units').glob('*.cpp'))
    for symbol, operation in (('width', '.size()'), ('keys', '.size()'), ('first', '.at('), ('lookup', '.at(')):
        assert symbol + '_collection_reader(var_' in units
        body = re.search(symbol + r'_collection_reader\(const [^;{]+\)\s*\{([^}]+)', units)
        assert body and operation in body[1], (symbol, body)
    assert 'marked_collection_reader' not in units and 'textKeys_collection_reader' not in units
    assert '&width_' in units and '&width_collection_reader' not in units

keyed_reader = """
function element(vector<int> $items,int $key=0):int {return $items[$key];}
function byKey(hash<int,int> $items,int $key=0):int {return $items[$key];}
"""
keyed_main = """
function change(vector<int> &$items):int {$items[1]=9;echo "M";return 1;}
$v vector<int> = [4,6];$h hash<int,int> = [];$h[1]=8;$key=1;
echo element($v,$key),element($v,0),byKey($h,$key),byKey($h,1),":";
echo element($v),element(key:1,items:$v),":";
echo element($v,change($v)),":",$v[1],$h[1];
return 8;
"""
with TemporaryDirectory(prefix='scpp-keyed-reader-') as directory:
    work = Path(directory)
    expected = b'6488:46:M6:98'
    writer = keyed_reader.replace('return $items[$key];', '$items[$key]=2;return $items[$key];')
    changed_call = keyed_main.replace('element($v,$key)', 'element($v,change($v))')
    edits = [(None, None, expected), ('main', changed_call, b'M6488:49:M9:98'), ('main', keyed_main, expected),
             ('reader', writer, b'2222:22:M2:98'),
             ('reader', None, None), ('reader', keyed_reader, expected)]
    revisions(work, 'php', dict(main=keyed_main, reader=keyed_reader), edits[:1] if quick else edits)
    units = '\n'.join(p.read_text() for p in (work/'php-out/units').glob('*.cpp'))
    for symbol in ('element', 'byKey'):
        assert units.count(symbol + '_collection_reader(var_') == (4 if symbol == 'element' else 2), units
        assert re.search(symbol + r'_collection_reader\(const [^;{]+\)\s*\{\s*return [^;]+\.at\(', units)

forward_reader = """
function innerCount(vector<int> $items):int {return count($items);}
function innerVector(vector<int> $items):int {return $items[0];}
function innerHash(hash<int,int> $items,int $key):int {return $items[$key];}
"""
query_reader = """
function vecEmpty(vector<int> $items):bool {return empty($items);}
function hashEmpty(hash<int,int> $items):bool {return empty($items);}
function hasKey(hash<int,int> $items,int $key):bool {return hash_contains($items,$key);}
function localAt(vector<int> $items):int {$result=$items[0];return $result;}
function localLookup(hash<int,int> $items,int $key):int {$result=$items[$key];return $result;}
"""
query_writer = query_reader.replace('return empty', 'clean($items);return empty').replace(
    'return hash_contains', 'clean($items);return hash_contains').replace(
    '$result=$items', '$items[0]=8;$result=$items')
query_calls = """
function emptyReads():int {
    $v vector<int> = [];$h hash<int,int> = [];
    $both=10*(int)vecEmpty($v)+(int)hashEmpty($h);$v[]=4;$h[0]=6;
    $populated=10*(int)vecEmpty($v)+(int)hashEmpty($h);
    return 10000*$both+100*$populated+10*$v[0]+$h[0];
}
function presenceReads():int {
    $h hash<int,int> = [];$h[0]=6;$key=0;
    return 100*(int)hasKey(key:$key,items:$h)+10*(int)hasKey($h,1)+count($h);
}
function insertKey(hash<int,int> &$items):int {$items[1]=7;return 1;}
function presenceOrder():int {
    $h hash<int,int> = [];
    $first=hasKey(items:$h,key:insertKey($h));clean($h);
    $second=hasKey(key:insertKey($h),items:$h);
    return 100*(int)$first+10*(int)$second+$h[1];
}
function localReads():int {
    $v vector<int> = [4];$h hash<int,int> = [];$h[0]=6;
    return 1000*localAt($v)+100*localLookup($h,0)+10*$v[0]+$h[0];
}
"""
scalar_reader = """
function flagAt(vector<bool> $items):bool {return $items[0];}
function floatAt(vector<float> $items,int $key=1):float {return $items[$key];}
function convertedLocal(vector<int> $items):float {$result float=$items[0];return $result;}
function otherLocal(hash<int,int> $items,int $key):int {$result=$items[$key];return $key;}
function indirectLocal(hash<int,int> $items,int $key):bool {$result=hasKey($items,$key);return $result;}
function convertedAt(vector<int> $items):float {return $items[0];}
function convertedKeyAt(vector<float> $items,int $pos=1.75):float {return $items[$pos];}
"""
forward_calls = """
function fromRef(vector<int> &$refItems):int {
    $refItems[0]=6;$read=innerVector($refItems);$refItems[0]+=1;return $read;
}
function fromRefHash(hash<int,int> &$refItems,int $key):int {
    $refItems[$key]=4;$read=innerHash(key:$key,items:$refItems);$refItems[$key]+=1;return $read;
}
function mutableForwardReads():int {
    $v vector<int> = [1];$h hash<int,int> = [];$h[0]=2;
    $vr=fromRef($v);$hr=fromRefHash($h,0);
    return 1000*$vr+100*$hr+10*$v[0]+$h[0];
}
function fromReadonly(const vector<int> &$readItems):int {return innerVector($readItems);}
function fromReadonlyHash(const hash<int,int> &$items,int $key):int {return innerHash(key:$key,items:$items);}
function readonlyForwardReads():int {
    $v vector<int> = [5];$h hash<int,int> = [];$h[0]=7;
    $vr=fromReadonly($v);$hr=fromReadonlyHash($h,0);
    return 1000*$vr+100*$hr+10*$v[0]+$h[0];
}
function fromPack(int ...$packItems):int {
    $packItems[0]+=1;$read=innerVector($packItems);$packItems[0]+=1;
    return 10*$read+$packItems[0];
}
function explicitPackReads():int {return fromPack(4,8);}
function unpackedPackReads():int {
    $v vector<int> = [4,8];$read=fromPack(...$v);return 10*$read+$v[0];
}
function packCount(int ...$items):int {return innerCount($items);}
function emptyPackReads():int {return packCount();}
function readonlyPackCount(int &...$readPack):int {return innerCount($readPack);}
function emptyReadonlyPackReads():int {return readonlyPackCount();}
function fromReadonlyPack(int &...$readPack):int {
    $observed=innerVector($readPack);return 10*$observed+$readPack[0];
}
function explicitReadonlyPackReads():int {return fromReadonlyPack(4,8);}
function unpackedReadonlyPackReads():int {
    $v vector<int> = [4,8];$observed=fromReadonlyPack(...$v);return 10*$observed+$v[0];
}
function outerVector(vector<int> $items):int {
    $items[0]=6;$read=innerVector($items);return 10*$read+$items[0];
}
function outerHash(hash<int,int> $items):int {
    $items[0]=8;$read=innerHash(key:0,items:$items);return 10*$read+$items[0];
}
function convertedKeyReads():int {
    $v vector<float> = [0.5,2.5];
    return (int)(convertedKeyAt($v) === 2.5) + (int)(convertedKeyAt(pos:true,items:$v) === 2.5);
}
function scalarReads():int {
    $flags vector<bool> = [true];$values vector<float> = [0.5,2.5];$k=1;
    $flag=flagAt($flags);$value=floatAt(key:$k,items:$values);
    return 1000*(int)$flag+100*(int)($value === 2.5)+10*(int)$flags[0]+(int)($values[1] === 2.5);
}
function forwardReads():int {
    $v vector<int> = [5];$h hash<int,int> = [];$h[0]=7;
    $vr=outerVector($v);$hr=outerHash($h);return 10000*$vr+100*$hr+10*$v[0]+$h[0];
}
"""
key_driver = """
function positionalKeyProbe():int {
    $v vector<int> = [5,6];$h hash<int,int> = [];$h[1]=8;$k=0;
    $result=positionalLookup($v,$h,$k);return 10*$result+$k;
}
function reversedKeyProbe():int {
    $v vector<int> = [5,6];$h hash<int,int> = [];$h[1]=8;$k=0;
    $result=reversedLookup($v,$h,$k);return 10*$result+$k;
}
"""
named_calls = """
function positionalLookup(vector<int> $v,hash<int,int> $h,int $which):int {
    $which+=1;return 10*element($v,$which)+byKey($h,$which);
}
function reversedLookup(vector<int> $v,hash<int,int> $h,int $which):int {
    $which+=1;return 10*element(key:$which,items:$v)+byKey(key:$which,items:$h);
}
function namedReads():int {
    $v vector<int> = [5];$h hash<int,int> = [];$h[0]=7;$k=0;
    return element(items:$v,key:$k) + element(key:0,items:$v)
        + byKey(items:$h,key:0) + byKey(key:$k,items:$h);
}
function defaultReads():int {
    $v vector<int> = [5,6];$h hash<int,int> = [];$h[0]=7;$h[1]=8;
    return 1000 * element($v) + 100 * element(items:$v) + 10 * byKey($h) + byKey(items:$h);
}
function changeVector(vector<int> &$v):int {$v[0]=9;echo "V";return 0;}
function changeHash(hash<int,int> &$h):int {$h[0]=9;echo "H";return 0;}
function vectorAliasOrder(vector<int> &$items,vector<int> &$alias):void {
    echo element(items:$items,key:changeVector($alias));$items[0]=5;
    echo element(key:changeVector($alias),items:$items);
}
function hashAliasOrder(hash<int,int> &$items,hash<int,int> &$alias):void {
    echo byKey(items:$items,key:changeHash($alias));$items[0]=7;
    echo byKey(key:changeHash($alias),items:$items);
}
function orderedReads():int {
    $v vector<int> = [5];$h hash<int,int> = [];$h[0]=7;
    echo element(items:$v,key:changeVector($v));$v[0]=5;
    echo element(key:changeVector($v),items:$v);
    echo byKey(items:$h,key:changeHash($h));$h[0]=7;
    echo byKey(key:changeHash($h),items:$h);
    $v[0]=5;$h[0]=7;
    vectorAliasOrder($v,$v);hashAliasOrder($h,$h);
    return 8;
}
echo scalarReads(),"|",readonlyForwardReads(),"|",positionalKeyProbe(),"/",reversedKeyProbe(),"|",forwardReads(),":",namedReads(),":",defaultReads(),":";return orderedReads();
"""
with TemporaryDirectory(prefix='scpp-named-reader-') as directory:
    work = Path(directory)
    expected = b'1111|5757|680/680|668857:24:5577:V5V9H7H9V5V9H7H9'
    forward_writer = forward_reader.replace('return $items[', '$items[0]=9;return $items[')
    forward_writer += query_writer
    forward_writer += scalar_reader.replace('return $items[0];}', '$items[0]=false;return $items[0];}', 1).replace(
        'return $items[$key];}', '$items[$key]=-4.5;return $items[$key];}')
    edits = [(None, None, expected),
             ('forward_reader', forward_writer, b'11|9957|680/680|969857:24:5577:V5V9H7H9V5V9H7H9'),
             ('forward_reader', forward_reader + query_reader + scalar_reader, expected),
             ('main', named_calls.replace('int $which', 'int &$which'), expected.replace(b'680/680', b'681/681')),
             ('main', named_calls, expected),
             ('reader', keyed_reader.replace('$key=0', '$key=1'), b'1111|5757|680/680|668857:24:6688:V5V9H7H9V5V9H7H9'),
             ('reader', keyed_reader.replace('$key=0', '$key'), 'Argument count mismatch'),
             ('reader', keyed_reader, expected),
             ('reader', keyed_reader.replace('$key', '$offset'), 'Unknown named argument: key'),
             ('main', named_calls.replace('key:', 'offset:'), expected),
             ('forward', forward_calls.replace('$observed=innerVector($readPack);',
                                               '$readPack[0]=7;$observed=innerVector($readPack);'), 'read-only variadic pack'),
             ('forward', forward_calls, expected)]
    sources = dict(main=named_calls, reader=keyed_reader, forward=forward_calls, queries=query_calls,
                   forward_reader=forward_reader + query_reader + scalar_reader, driver=key_driver)
    revisions(work, 'php', sources, edits[:1] if quick else edits)
    out = work/'php-out'
    units = '\n'.join(p.read_text() for p in (out/'units').glob('*.cpp'))
    assert 'convertedKeyAt_collection_reader(var_' not in units
    for symbol in ('convertedLocal', 'otherLocal', 'indirectLocal'):
        assert re.search(symbol + r'_collection_reader\(const [^;{]+\)\s*\{\s*return ' + symbol + r'_\(', units)
    assert re.search(r'convertedAt_collection_reader\(const [^;{]+\)\s*\{\s*return convertedAt_\(', units)
    assert 'innerVector_collection_reader(var_refItems' in units
    assert 'innerHash_collection_reader(var_refItems' in units
    assert 'innerVector_collection_reader(var_packItems' in units
    assert 'innerCount_collection_reader(var_items' in units
    assert 'innerCount_collection_reader(var_readPack' in units
    assert 'innerVector_collection_reader(var_readPack' in units
    for symbol in ('vecEmpty', 'hashEmpty', 'hasKey', 'localAt', 'localLookup'):
        assert not re.search(symbol + r'_collection_reader\(const [^;{]+\)\s*\{\s*return ' + symbol + r'_\(', units)
        assert symbol + '_collection_reader(var_' in units
    for symbol in ('element', 'byKey'):
        assert units.count(symbol + '_collection_reader(var_') == 6, units
    if not quick:
        root = Path(__file__).resolve().parents[2]
        # Each fresh bundle was checked against the resident compiler's output at that revision.
        for bundle, state in ((work/'php-fresh-0', 'reader'), (work/'php-fresh-1', 'writer'),
                              (work/'php-fresh-2', 'restored-reader'), (work/'php-fresh-3', 'reference-key'), (work/'php-fresh-4', 'restored-key')):
            sources = json.loads((bundle/'manifest.json').read_text())['translation_units']
            probe = work/('copies-' + state)
            subprocess.run(['clang++', '-O2', '-std=c++23', '-stdlib=libc++', '-I', root/'runtime/generated/public',
                            *(bundle/unit for unit in sources if unit != 'entry.cpp'),
                            root/'compiler/tests/fixtures/named_reader_copies.cpp', root/'build/clang/libscpp_runtime.a',
                            '-Wl,--wrap=_ZN4scpp12vector_int64C1ERKS0_,--wrap=_ZN4scpp16hash_int64_int64C1ERKS0_',
                            '-Wl,--wrap=_ZN4scpp13vector_bool_tC1ERKS0_,--wrap=_ZN4scpp14vector_float64C1ERKS0_',
                            '-o', probe], check=True)
            subprocess.run([probe, state], check=True, stdout=subprocess.DEVNULL)

if not quick:
    with TemporaryDirectory(prefix='scpp-index-failure-') as directory:
        for function, collection, error in [('first', 'vector<int>', 'sequence_index_out_of_range'),
                                            ('lookup', 'hash<int,int>', 'hash_missing_key')]:
            guard(Path(directory), 'php', function, reader + f'$v {collection} = [];return {function}($v);',
                  error, exception_type='std::out_of_range')
        for function, collection, error in [('element', 'vector<int>', 'sequence_index_out_of_range'),
                                            ('byKey', 'hash<int,int>', 'hash_missing_key')]:
            guard(Path(directory), 'php', function, keyed_reader + f'$v {collection} = [];$key=1;return {function}($v,$key);',
                  error, exception_type='std::out_of_range')
        for function, collection, error, args in [('localAt', 'vector<int>', 'sequence_index_out_of_range', '$v'),
                                                   ('localLookup', 'hash<int,int>', 'hash_missing_key', '$v,0')]:
            guard(Path(directory), 'php', function, query_reader + f'$v {collection} = [];return {function}({args});',
                  error, exception_type='std::out_of_range')
    from classes_native import fixture
    for flavor in ('cpp', 'js'):
        forms = fixture(flavor)
        read = forms['function']('width', 'int', [('items', 'vector<int>')], 'return count(items);')
        read += forms['function']('first', 'int', [('items', 'vector<int>')], 'return items[0];')
        read += forms['function']('element', 'int', [('items', 'vector<int>'), ('key', 'int')], 'return items[key];')
        key_param = 'key:int' if flavor == 'js' else 'int key'
        read = read.replace(key_param, key_param + '=0')
        local = 'vector<int> v=[3,5];' if flavor == 'cpp' else 'let v:vector<int> = [3,5];'
        outer = forms['function']('outer', 'int', [('items', 'vector<int>'), ('k', 'int')],
                                  'return element(items,k)*10+items[k];')
        from_ref = forms['function']('fromRef', 'int', [('&items', 'vector<int>'), ('k', 'int')],
                                     'return element(items,k);')
        app = from_ref + outer + local + 'echo fromRef(v,1),outer(v,1),width(v),first(v),element(v,1),element(key:0,items:v),element(items:v,key:1),v[0],element(v),element(items:v);return 8;'
        with TemporaryDirectory(prefix='scpp-private-count-') as directory:
            revisions(Path(directory), flavor, dict(main=app, reader=read), [
                (None, None, b'55523535333'),
                ('reader', read.replace(key_param + '=0', key_param + '=1'), b'55523535355'),
                ('reader', read.replace(key_param + '=0', key_param), 'Argument count mismatch'),
                ('reader', read, b'55523535333'),
                ('reader', read.replace('return items[key]', 'items[key]=9;return items[key]').replace('return items[0]', 'items[0]=7;return items[0]'), b'99527999399'),
                ('reader', None, None),
                ('reader', read, b'55523535333'),
                ('reader', read.replace('key', 'offset'), 'Unknown named argument: key'),
                ('main', app.replace('key:', 'offset:'), b'55523535333'),
            ])


        flags = forms['function']('flag', 'bool', [('items', 'vector<bool>')], 'return items[0];')
        numbers = forms['function']('number', 'float', [('items', 'vector<float>')], 'return items[0];')
        locals_ = ('vector<bool> flags=[true];vector<float> nums=[2.5];' if flavor == 'cpp'
                   else 'let flags:vector<bool> = [true];let nums:vector<float> = [2.5];')
        app = locals_ + 'if(flag(flags)){echo "B";}if(number(nums) === 2.5){echo "F";}if(flags[0] && nums[0] === 2.5){echo "S";}return 8;'
        writers = flags.replace('return items[0]', 'items[0]=false;return items[0]') + numbers.replace(
            'return items[0]', 'items[0]=0.0;return items[0]')
        with TemporaryDirectory(prefix='scpp-scalar-reader-') as directory:
            revisions(Path(directory), flavor, dict(main=app, reader=flags + numbers), [
                (None, None, b'BFS'), ('reader', writers, b'S'), ('reader', flags + numbers, b'BFS'),
            ])

        local_decl = 'auto found=' if flavor == 'cpp' else 'let found='
        table = 'hash<int,int> table=[];' if flavor == 'cpp' else 'let table:hash<int,int> = [];'
        presence = forms['function']('present', 'bool', [('items', 'hash<int,int>'), ('key', 'int')],
                                     local_decl + 'hash_contains(items,key);return found;')
        vacant = forms['function']('vacant', 'bool', [('items', 'hash<int,int>')], 'return empty(items);')
        provider = presence + vacant
        writer = provider.replace(local_decl, 'items[1]=8;' + local_decl).replace('return empty', 'items[1]=8;return empty')
        app = table + 'if(vacant(table)){echo "E";}table[0]=6;if(present(table,1)){echo "P";}echo count(table);return 8;'
        with TemporaryDirectory(prefix='scpp-query-readers-') as directory:
            revisions(Path(directory), flavor, dict(main=app, reader=provider), [
                (None, None, b'E1'), ('reader', writer, b'P1'), ('reader', None, None), ('reader', provider, b'E1'),
            ])

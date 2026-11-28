"""Consecutive mixed reads select value snapshots, including across a suspending key."""
import sys
from pathlib import Path
from tempfile import TemporaryDirectory

from classes_native import fixture
from collection_goal_native import guard, revisions
from populated_vectors_native import declaration


def prove(work, flavor, case):
    f = fixture(flavor)
    v, access = f['v'], f['access']
    data = f'{v}h{access}data'
    setup = f['infer']('h', 'new Holder()') + declaration(flavor, 'root', 'mixed', '[]')
    setup += declaration(flavor, 'row', 'mixed', '[7]')
    setup += f'{v}root["a"]={v}row;{v}root["nil"]=null;{data}={v}root;'
    api = 'class Holder {public mixed $data;} function key():int {echo "K";return 0;}'
    if case == 'await':
        api += """
            function groupKey():string {echo "A";return "a";}
            async function replace(Holder $h):int {
                echo "S";await async_sleep_ms(1);$fresh mixed=[[9]];
                $h->data=$fresh;echo "R";return 0;
            }
        """
        main = setup + f'{v}root=null;{v}row=null;'
        main += f'echo (int){data}[groupKey()][await replace({v}h)];echo (int){data}[0][0];return 8;'
        revisions(work, flavor, dict(main=main), [(None, None, b'ASR79')], support_sources={'api.php': api})
        return
    main = setup + declaration(flavor, 'missing', 'null', f'{data}["absent"][key()]')
    main += declaration(flavor, 'storedNull', 'null', f'{data}["nil"][key()]')
    main += f'echo count({data});' + declaration(flavor, 'outer', 'mixed', f'[{v}root]')
    main += f['infer']('saved', f'{v}outer[0]["a"]')
    main += f'{v}saved[0]=2;echo (int){v}outer[0]["a"][0],(int){v}saved[0];'
    main += f'{v}root[(int){data}["a"][0]]={data}["a"][0];echo (int){v}root[7];'
    main += 'return 8;'
    invalid = main.replace('[key()]', '[true]', 1)
    revisions(work, flavor, dict(main=invalid), [(None, None, 'Boxed container key requires int or string'),
                                                 ('main', main, b'KK2727')], support_sources={'api.php': api})
    bad = declaration(flavor, 'items', 'mixed', '[1]') + f'echo (int){v}items[0][key()][key()];return 8;'
    guard(work, flavor, 'scalar', bad, 'boxed_expected_container', expected_stdout=b'K',
          support_sources={'api.php': api})


with TemporaryDirectory(prefix='nested-mixed-read-') as directory:
    prove(Path(directory), *sys.argv[1:])

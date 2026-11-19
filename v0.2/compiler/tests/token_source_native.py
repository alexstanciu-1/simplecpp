"""Valid-data translation of token snapshots and byte locations; exceptions/packing deferred."""
import argparse
import base64
import json
import re
import subprocess
from pathlib import Path

from classes_native import fixture
from collection_goal_native import revisions

COMPILER = Path(__file__).resolve().parents[1]
CORPUS = [b'', b'a', b'\n', b'a\nb\n', b'a\r\nb\rc', 'α\n🙂z'.encode(), b'a\0b\n\0', b'x' * 300 + b'\nend', b'a\n' * 40]


def oracle(line_delta=0):
    """Use production PHP records, including TokenList::text_at, as the native oracle."""
    php = r'''
require $argv[1] . '/boot.php';
use scpp\compiler\{source_file, flavor, token, token_kind, token_list};
foreach (json_decode(stream_get_contents(STDIN), true) as $encoded) {
    $source = new source_file('fixture', base64_decode($encoded), flavor::php);
    $tokens = [];
    for ($i = 0; $i < strlen($source->content); $i += 3) $tokens[] = new token($i, min(3, strlen($source->content) - $i), token_kind::identifier);
    $list = new token_list($source, $tokens);
    for ($i = 0; $i <= strlen($source->content); ++$i) {
        $loc = $source->location($i);
        echo $loc['line'] + (int)$argv[2], ':', $loc['column'], ';';
    }
    echo '|';
    foreach ($list->tokens as $i => $token) echo $token->offset, ':', $token->length, ':', $token->kind->value, ':', $list->text_at($i), ';';
    echo "\n";
}
'''
    return subprocess.check_output(['php', '-r', php, str(COMPILER), str(line_delta)], input=json.dumps([base64.b64encode(x).decode() for x in CORPUS]).encode())


def sources(flavor):
    s = fixture(flavor)
    v, a = s['v'], s['access']
    def decl(n, t, value):
        return {'php': f'${n} {t} = {value};', 'cpp': f'{t} {n} = {value};', 'js': f'let {n}:{t} = {value};'}[flavor]
    def ro(n, t):
        field = s['field'](n, t)
        return field.replace('public ', 'public readonly ', 1) if flavor == 'php' else 'readonly ' + field
    def ctor(name, params, body):
        args = ','.join(f'{n}:{t}' if flavor == 'js' else f'{t} {v}{n}' for n, t in params)
        head = {'php': 'public function __construct', 'cpp': name, 'js': 'constructor'}[flavor]
        return head + '(' + args + ') {' + body + '}\n'
    def method(name, result, params, body):
        text = s['function'](name, result, params, body)
        return ('public ' + text if flavor == 'php' else text.replace('function ', '') if flavor == 'js' else text) + '\n'
    def inline(n, t, args):
        return {'php': f'${n} {t}({args});', 'cpp': f'{t} {n}({args});', 'js': f'let {n}:{t}({args});'}[flavor]
    kinds = re.findall(r'case (\w+) = (\d+);', (COMPILER/'02_tokenize/structures.php').read_text())
    enum = 'enum TokenKind {' + ''.join(('case ' if flavor == 'php' else '') + n + '=' + i + (';' if flavor == 'php' else ',') for n, i in kinds) + '}\n'
    token = s['record']('Token', ro('offset', 'int') + ro('length', 'int') + ro('kind', 'TokenKind') + ctor('Token', [('offset', 'int'), ('length', 'int'), ('kind', 'TokenKind')], ''.join(f'{v}this{a}{n}={v}{n};' for n in ['offset', 'length', 'kind'])))
    location = s['record']('SourceLocation', ro('line', 'int') + ro('column', 'int') + ctor('SourceLocation', [('line', 'int'), ('column', 'int')], f'{v}this{a}line={v}line;{v}this{a}column={v}column;'))
    body = decl('starts', 'vector<int>', '[]') + f'{v}starts[]=0;' + s['infer']('i', '0')
    body += f'while ({v}i < strlen({v}content)) {{if ({v}content[{v}i] == 10) {{{v}starts[]={v}i+1;}}{v}i++;}}'
    body += f'{v}this{a}content={v}content;{v}this{a}line_starts={v}starts;'
    lookup = s['infer']('low', '0') + s['infer']('high', f'count({v}this{a}line_starts)')
    lookup += f'while ({v}low+1 < {v}high) {{' + s['infer']('middle', f'({v}low+{v}high)/2')
    lookup += f'if ({v}this{a}line_starts[{v}middle] <= {v}offset) {{{v}low={v}middle;}} else {{{v}high={v}middle;}}}}'
    lookup += inline('result', 'SourceLocation', f'{v}low+1,{v}offset-{v}this{a}line_starts[{v}low]+1') + f'return {v}result;'
    source = s['record']('SourceFile', ro('content', 'string') + ro('line_starts', 'vector<int>') + ctor('SourceFile', [('content', 'string')], body) + method('location', 'SourceLocation', [('offset', 'int')], lookup))
    # Text extraction uses the same byte substring operation through each flavor adapter.
    text = s['infer']('item', f'{v}this{a}tokens[{v}index]')
    value = f'{v}this{a}source{a}content'
    call = f'substr({value},{v}item{a}offset,{v}item{a}length)' if flavor == 'php' else f'{value}.substr({v}item{a}offset,{v}item{a}length)' if flavor == 'cpp' else f'{value}.slice({v}item.offset,{v}item.offset+{v}item.length)'
    token_list = s['record']('TokenList', ro('source', 'shared<SourceFile>') + ro('tokens', 'vector<Token>') + ctor('TokenList', [('source', 'shared<SourceFile>'), ('tokens', 'vector<Token>')], f'{v}this{a}source={v}source;{v}this{a}tokens={v}tokens;') + method('text_at', 'string', [('index', 'int')], text + 'return ' + call + ';'))
    kind = 'TokenKind.identifier' if flavor == 'js' else 'TokenKind::identifier'
    make = s['infer']('source', f'new SourceFile({v}content)') + decl('items', 'vector<Token>', '[]') + s['infer']('i', '0')
    make += f'while ({v}i < strlen({v}content)) {{' + s['infer']('length', '3') + f'if ({v}i+{v}length > strlen({v}content)) {{{v}length=strlen({v}content)-{v}i;}}' + inline('item', 'Token', f'{v}i,{v}length,{kind}') + f'{v}items[]={v}item;{v}i+=3;}}'
    make += inline('result', 'TokenList', f'{v}source,{v}items') + f'return {v}result;'
    use = s['infer']('i', '0') + f'while ({v}i <= strlen({v}list{a}source{a}content)) {{' + s['infer']('loc', f'{v}list{a}source{a}location({v}i)') + f'echo {v}loc{a}line,":",{v}loc{a}column,";";{v}i++;}}echo "|";'
    use += f'foreach ({v}list{a}tokens as {v}index => {v}item) {{echo {v}item{a}offset,":",{v}item{a}length,":",enum_value({v}item{a}kind),":",{v}list{a}text_at({v}index),";";}}echo "\\n";'
    api = s['function']('makeTokens', 'TokenList', [('content', 'string')], make) + s['function']('show', 'void', [('list', 'TokenList')], use)
    main = ''
    for i, content in enumerate(CORPUS):
        literal = json.dumps(content.decode(), ensure_ascii=False).replace('\\u0000', '\\0')
        main += s['infer']('list'+str(i), 'makeTokens('+literal+')') + f'show({v}list{i});\n'
    return {'types': '/** Logical token kind IDs from the production vocabulary. */\n' + enum + '/** Valid byte span; native packing is deferred. */\n' + token + '/** One-based line and byte column, including EOF. */\n' + location + '/** Immutable byte snapshot with an LF line-start index. */\n' + source + '/** Owns token values and retains their source snapshot. */\n' + token_list, 'api': api, 'main': main + 'return 8;'}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path, required=True)
    args = parser.parse_args()
    args.output.mkdir(parents=True, exist_ok=True)
    expected = oracle()
    for flavor in ['php', 'cpp', 'js']:
        src = sources(flavor)
        edited = src['types'].replace(('$low+1,' if flavor == 'php' else 'low+1,'), ('$low+2,' if flavor == 'php' else 'low+2,'))
        assert edited != src['types']
        revisions(args.output, flavor, src, [(None, None, expected), (None, None, expected),
            ('types', edited, oracle(1)), ('types', None, None), (None, None, None),
            ('types', src['types'], expected), (None, None, expected)])


if __name__ == '__main__':
    main()

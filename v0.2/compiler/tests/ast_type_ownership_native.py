"""Production type-reference ownership across replacement of one source slot."""
import json
from pathlib import Path
import subprocess
import tempfile

from collection_goal_native import revisions

COMPILER = Path(__file__).resolve().parents[1]
OLD = '/* παλιά */\n$slot hash<int, vector<string>> = []; return 8;'
NEW = '/* new */\n$slot hash<int,  vector<float64>> = []; return 8;'

# Minimal native base-node and file owners for production type-reference methods.
# Lexical roots in this family have no trait origins to redirect.
BOUNDARY = '''
class ast_node {
    public readonly int $start;
    public readonly int $end;
    private weak<ast_node> $parent;

    public function __construct(int $start, int $end) {
        $this->start = $start;
        $this->end = $end;
    }

    public virtual function parent_node(): ?ast_node {
        return lock($this->parent);
    }

    public static function attach(ast_node $child, ast_node $parent): void {
        if ($child instanceof ast_file_node || !$child->parent->is_empty()) {
            throw Exception::make("AST parent is already settled");
        }
        $ancestor ?ast_node = $parent;
        while ($ancestor !== null) {
            if ($ancestor === $child) {
                throw Exception::make("AST ownership cycle");
            }
            $ancestor = $ancestor->parent_node();
        }
        $child->parent = weak($parent);
    }

    public static function lexical_root(ast_node $syntax): ast_file_node {
        $node ast_node = $syntax;
        while (!($node instanceof ast_file_node)) {
            $parent = $node->parent_node();
            if ($parent === null) {
                throw Exception::make("AST node is not attached to a file");
            }
            $node = $parent;
        }
        return object_cast($node, ast_file_node::class);
    }
}

final class source_file {
    public readonly string $path;
    public readonly string $content;
    public function __construct(string $path, string $content) {
        $this->path = $path;
        $this->content = $content;
    }
}

final class token {
    public readonly int $offset;
    public readonly int $length;
    public function __construct(int $offset, int $length) {
        $this->offset = $offset;
        $this->length = $length;
    }
}

final class token_list {
    public readonly source_file $source;
    public readonly vector<token> $tokens;
    public function __construct(source_file $source, vector<token> $tokens) {
        $this->source = $source;
        $this->tokens = $tokens;
    }
}

final class ast_file_node extends ast_node {
    public readonly token_list $tokens;
    public readonly ast_type_reference $type;
    public function __construct(token_list $tokens, ast_type_reference $type) {
        parent::__construct(0, count($tokens->tokens));
        $this->tokens = $tokens;
        $this->type = $type;
    }

    public function byte_at(int $position): int {
        return $this->tokens->tokens[$position]->offset;
    }
}

function check(bool $ok, string $message): void {
    if (!$ok) {
        throw Exception::make($message);
    }
}
'''

ENTRY = '''
$current = old_source();
$old_root ?ast_file_node = $current;
$old_root_observer = weak($current);
$old_source_observer = weak($current->tokens->source);
$old_parent_observer = weak($current->type);
$first ?ast_type_reference = $current->type->first_child;
$child ast_type_reference = $first->next_sibling;
$child_observer = weak($child);
$leaf ast_type_reference = $child->first_child;
check($child->parent_node() === $current->type, "Original parent identity");
check($first->parent_node() === $child->parent_node(), "Siblings share the containing type");
check($leaf->parent_node() === $child, "Nested argument parent");

$current = replacement_source();
check($old_root !== $current && $old_root->tokens->source !== $current->tokens->source, "Distinct generations");
check($old_root->tokens->source->path === $current->tokens->source->path, "Same source slot");
check(ast_node::lexical_root($leaf) === $old_root, "Retained child reaches the original root");
check($child->parent_node() === $old_root->type && $child->parent_node() === lock($old_parent_observer),
    "Retained child reaches the identical old parent");
check($child->parent_node() !== $current->type, "Replacement did not reparent old syntax");
check(source_text($old_root->type) === "hash<int, vector<string>>", "Old root type bytes");
check(source_text($child) === "vector<string>" && source_text($leaf) === "string", "Old child type bytes");
check(source_text($current->type) === "hash<int,  vector<float64>>", "Replacement type bytes");
check($old_root->type->spelling() === "hash<int,vector<string>>", "Old canonical spelling");
check($current->type->spelling() === "hash<int,vector<float64>>", "Replacement canonical spelling");

$first = null;
$old_root = null;
check(lock($old_root_observer) === null && lock($old_source_observer) === null, "Old root and source expired");
check(lock($old_parent_observer) === null && $child->parent_node() === null, "Old parent expired");
check(lock($child_observer) === $child && $child->first_child === $leaf, "Retained subtree survived");
check($leaf->parent_node() === $child && $child->spelling() === "vector<string>", "Surviving subtree is intact");
check(source_text($current->type) === "hash<int,  vector<float64>>", "Replacement still owns its bytes");
$rejected = false;
try {
    ast_node::attach($child, $current->type);
} catch (Exception $failure) {
    $rejected = $failure->get_message() === "AST parent is already settled";
}
check($rejected && $child->parent_node() === null, "An expired parent slot is still settled");
echo "type reference ownership";
return 8;
'''


def production_fixture():
    # The PHP oracle parses both generations and supplies token spans for native
    # construction. Native code builds the type nodes and exercises their lifetimes.
    return json.loads(subprocess.check_output(['php', '-r', r'''
require $argv[1];
use scpp\compiler\{ast_type_reference, ast_node, source_file, Source_Frontend, flavor};

function method_source(string $class, string $method): string {
    $reflection = new ReflectionMethod($class, $method);
    return implode('', array_slice(file($reflection->getFileName()), $reflection->getStartLine() - 1,
        $reflection->getEndLine() - $reflection->getStartLine() + 1));
}

function snapshot($root, ast_type_reference $type): array {
    $first = $type->first_child;
    $second = $first->next_sibling;
    return [
        'tokens' => array_map(static fn($token) => [$token->offset, $token->length], $root->tokens->tokens),
        'ranges' => array_map(static fn($node) => [$node->start, $node->end], [$type, $first, $second, $second->first_child]),
    ];
}

$frontend = new Source_Frontend();
$current = $frontend->parse(new source_file('same.php', $argv[2], flavor::php));
$old = $current;
$type = $old->main()->body()->statements[0]->type_reference;
$first = $type->first_child;
$child = $first->next_sibling;
$leaf = $child->first_child;
$current = $frontend->parse(new source_file('same.php', $argv[3], flavor::php));
$new_type = $current->main()->body()->statements[0]->type_reference;
if ($old === $current || $child->root() !== $old || $first->parent !== $type || $child->parent !== $type ||
    $leaf->parent !== $child || $type->source_text() !== 'hash<int, vector<string>>' ||
    $child->source_text() !== 'vector<string>' || $leaf->source_text() !== 'string' ||
    $new_type->source_text() !== 'hash<int,  vector<float64>>') {
    throw new RuntimeException('PHP type reference generation mismatch');
}
echo json_encode([
    'constructor' => method_source(ast_type_reference::class, '__construct'),
    'spelling' => method_source(ast_type_reference::class, 'spelling'),
    'source_text' => method_source(ast_node::class, 'source_text'),
    'snapshots' => [snapshot($old, $type), snapshot($current, $new_type)],
], JSON_THROW_ON_ERROR);
''', COMPILER / 'boot.php', OLD, NEW]))


def native_types(fixture):
    constructor = fixture['constructor']
    links = constructor.index('\t\tfor ($child = $first_child;')
    fields, attach_loop = constructor[:links], constructor[links:constructor.rindex('\n\t}')]
    attach_loop = attach_loop.replace('$child->attach($this);', 'ast_node::attach($child, $owner);')
    # C++ installs the shared owner after its constructor returns. At that point
    # run the production constructor's child-attachment loop on that owner.
    factory = '''
    public static function make(int $start, int $end, string $name,
        ?ast_type_reference $first_child = null, ?ast_type_reference $next_sibling = null): ast_type_reference
    {
        $owner = new ast_type_reference($start, $end, $name, $first_child, $next_sibling);
''' + attach_loop + '''
        return $owner;
    }
'''
    declaration = '''
class ast_type_reference extends ast_node {
    public readonly string $name;
    public readonly ?ast_type_reference $first_child;
    public readonly ?ast_type_reference $next_sibling;
'''
    source_text = fixture['source_text'].replace('public function source_text()', 'function source_text(ast_node $syntax)')
    source_text = source_text.replace('$this->lexical_root()', 'ast_node::lexical_root($syntax)').replace('$this->', '$syntax->')
    return declaration + fields + '\t}\n' + factory + fixture['spelling'] + '}\n' + source_text


def native_source(name, text, snapshot, leaf):
    tokens = ', '.join(f'new token({start}, {length})' for start, length in snapshot['tokens'])
    outer, first, nested, last = [f'{start}, {end}' for start, end in snapshot['ranges']]
    content = json.dumps(text, ensure_ascii=False).replace("$", "\\$")
    return f'''
function {name}(): ast_file_node {{
    $tokens vector<token> = [{tokens}];
    $token_list = new token_list(new source_file("same.php", {content}), $tokens);
    $leaf = ast_type_reference::make({last}, "{leaf}");
    $vector = ast_type_reference::make({nested}, "vector", $leaf);
    $first = ast_type_reference::make({first}, "int", null, $vector);
    $type = ast_type_reference::make({outer}, "hash", $first);
    $root = new ast_file_node($token_list, $type);
    ast_node::attach($type, $root);
    return $root;
}}
'''


if __name__ == '__main__':
    fixture = production_fixture()
    old, new = fixture['snapshots']
    files = {
        'types': BOUNDARY + native_types(fixture),
        'snapshots': native_source('old_source', OLD, old, 'string')
                     + native_source('replacement_source', NEW, new, 'float64'),
        'main': ENTRY,
    }
    with tempfile.TemporaryDirectory(prefix='scpp-type-reference-ownership-') as directory:
        revisions(Path(directory), 'php', files, [(None, None, b'type reference ownership')])

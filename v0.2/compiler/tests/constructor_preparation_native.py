"""Compare the production constructor-preparation decision with its native port."""
import json
from pathlib import Path
import subprocess
import tempfile

from collection_goal_native import revisions

COMPILER = Path(__file__).resolve().parents[1]
EXPECTED = b'absent|skip\nunrelated|skip\nmethod|skip\nsetup|constructor|same\n'

# Keep the AST boundary small and make initialization observable at construction.
BOUNDARY = '''
class ast_node {
    public virtual function node_kind(): int { return 0; }
}
final class ast_file_node extends ast_node {}
final class ast_record_declaration extends ast_node {}

final class Constructor_Initialization {
    private weak<ast_record_declaration> $record_owner;

    public function __construct(ast_record_declaration $record) {
        $this->record_owner = weak($record);
        echo "setup|";
    }

    public function record(): ?ast_record_declaration {
        return lock($this->record_owner);
    }
}
'''

ENTRY = '''
function check_setup(string $label, ast_function_declaration $function, ?ast_record_declaration $expected): void {
    $setup = select_constructor_initialization($function);
    if ($expected === null) {
        if ($setup !== null) {
            throw Exception::make("Unexpected constructor setup");
        }
        echo $label, "|skip\\n";
        return;
    }
    if ($setup === null || $setup->record() !== $expected) {
        throw Exception::make("Constructor setup lost the original record");
    }
    echo $label, "|same\\n";
}

$file = new ast_file_node();
$record = new ast_record_declaration();
check_setup("absent", new ast_function_declaration("__construct", null), null);
check_setup("unrelated", new ast_function_declaration("__construct", $file), null);
check_setup("method", new ast_function_declaration("ordinary", $record), null);
check_setup("constructor", new ast_function_declaration("__construct", $record), $record);
return 8;
'''


def production_fixture():
    return json.loads(subprocess.check_output(['php', '-r', r'''
require $argv[1];
use scpp\compiler\{ast_function_declaration, Constructor_Initialization, entry_target,
    flavor, Preparation, source_file, Source_Frontend};

function method_source(string $class, string $name): string {
    $method = new ReflectionMethod($class, $name);
    return implode('', array_slice(file($method->getFileName()), $method->getStartLine() - 1,
        $method->getEndLine() - $method->getStartLine() + 1));
}

$body = method_source(Preparation::class, 'body');
if (preg_match('/\$this->constructor_initialization = ([^;]+);/', $body, $match) !== 1) {
    throw new RuntimeException('Constructor preparation decision moved');
}
$decision = $match[1];
// Evaluate the production RHS on real PHP compiler nodes at the setup boundary.
$select = eval('namespace scpp\compiler; return static function(ast_function_declaration $function): ?Constructor_Initialization {
    return ' . $decision . ';
};');

$source = new source_file('constructor.php', <<<'SOURCE'
class Box {
    public readonly string $tag = "seed";
    public function __construct() {}
    public function ordinary(): void {}
}
return 8;
SOURCE, flavor::php);
$file = (new Source_Frontend())->prepare($source, new entry_target(31))->file;
$record = $file->declarations['class:Box'];
$absent = new ast_function_declaration('__construct', null, 0, 0);
$unrelated = new ast_function_declaration('__construct', null, 0, 0);
$unrelated->attach($file);

$trace = '';
foreach (['absent' => $absent, 'unrelated' => $unrelated, 'method' => $record->methods['ordinary'],
    'constructor' => $record->methods['__construct']] as $label => $function) {
    $setup = $select($function);
    if ($setup === null) {
        $trace .= $label . "|skip\n";
        continue;
    }
    $original = (new ReflectionProperty(Constructor_Initialization::class, 'record'))->getValue($setup);
    if ($original !== $record || $setup->states !== ['tag' => 1]) {
        throw new RuntimeException('PHP constructor initialization disagrees with its record');
    }
    $trace .= 'setup|' . $label . "|same\n";
}
echo json_encode(['predicate' => method_source(ast_function_declaration::class, 'is_constructor'),
    'decision' => $decision, 'trace' => $trace], JSON_THROW_ON_ERROR);
''', COMPILER / 'boot.php']))


def native_path(fixture):
    predicate = fixture['predicate']
    parent = '($this->parent ?? null)'
    assert predicate.count(parent) == 1
    # Native optional fields represent an unattached parent as an empty handle.
    predicate = predicate.replace(parent, '$this->parent')
    decision = fixture['decision']
    assert decision.count('$function->parent') == 2
    decision = decision.replace('$function->parent', '$parent')
    operand = 'new Constructor_Initialization($parent)'
    assert decision.count(operand) == 1
    decision = decision.replace(operand, 'new Constructor_Initialization(object_cast($parent, ast_record_declaration::class))')
    # The nullable object result uses ordinary returns in the native port.
    test, choices = decision.split('?', 1)
    setup, separator, absent = choices.strip().rpartition(' : ')
    assert separator and absent == 'null'
    return '''
final class ast_function_declaration {
    public readonly string $short_name;
    public readonly ?ast_node $parent;

    public function __construct(string $name, ?ast_node $parent) {
        $this->short_name = $name;
        $this->parent = $parent;
    }
''' + predicate + '''
}

function select_constructor_initialization(ast_function_declaration $function): ?Constructor_Initialization {
    $parent ?ast_node = $function->parent;
    if (''' + test.strip() + ''') {
        return ''' + setup + ''';
    }
    return null;
}
'''


if __name__ == '__main__':
    fixture = production_fixture()
    assert fixture['trace'].encode() == EXPECTED, fixture['trace']
    with tempfile.TemporaryDirectory(prefix='scpp-constructor-preparation-') as directory:
        revisions(Path(directory), 'php',
                  {'setup': BOUNDARY + native_path(fixture), 'main': ENTRY},
                  [(None, None, EXPECTED)])

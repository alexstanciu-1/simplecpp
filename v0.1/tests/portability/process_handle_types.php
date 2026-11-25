<?php
require_once __DIR__ . '/../../tools/php_portability/src/converter.php';
$source = <<<'SOURCE'
<?php
namespace proof;
final class Job {
    public ?\scpp\Process_Handle $handle = null;
    public function __construct(public \scpp\Process_Handle $input) {}
    public function accept(\scpp\Process_Handle $value): \scpp\Process_Handle {
        $alias /** \scpp\Process_Handle */ = $value;
        $optional /** nullable<\scpp\Process_Handle> */ = null;
        $this->handle = $alias;
        return $alias;
    }
}
SOURCE;
foreach (['v0.1', 'v0.2'] as $target) {
    $converter = new scpp\portability\Converter(require __DIR__ . '/../../tools/php_portability/function_map.php', $target);
    $text = $converter->convert($source, 'process_types.php');
    if (str_contains($text, '\\scpp\\Process_Handle') || substr_count($text, '\\process_handle') < 6) {
        throw new LogicException('Explicit process handle boundaries were not mapped: ' . $text);
    }
}
if (scpp\portability\Type_Names::name('Process_Handle') !== 'Process_Handle'
    || scpp\portability\Type_Names::name('scpp\\Process_Handle') !== 'scpp\\Process_Handle') {
    throw new LogicException('Type binding captured an unqualified user name');
}
echo "Process handle fields, parameters, promotion, locals and returns: both converter targets passed\n";

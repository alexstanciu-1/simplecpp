<?php
declare(strict_types=1);
require_once __DIR__ . '/../../bin/bootstrap.php';

use Scpp\S2S\Analysis\DeclarationCatalogBuilder;

$path = sys_get_temp_dir() . '/accessor-catalog.phs';
$source = <<<'PHS'
namespace first;
use models\facts as result_type;
use contracts\root_node as root_type;
class node extends root_type {
    public function item(): result_type { return new result_type(); }
    protected function protected_item(): result_type { return new result_type(); }
    private function private_item(): result_type { return new result_type(); }
    public static function static_item(): result_type { return new result_type(); }
    public function with_parameter(int $x): result_type { return new result_type(); }
    public function scalar(): int { return 1; }
}
namespace second;
class node { public function item(): \other\facts { return new \other\facts(); } }
PHS;
$catalog = (new DeclarationCatalogBuilder())->buildCatalogFromSources([$path], [$path => $source]);
$first = $catalog['accessor_declarations']['first\\node'];
$second = $catalog['accessor_declarations']['second\\node'];
if ($first !== ['parents' => ['contracts\\root_node'], 'methods' => ['item' => 'models\\facts', 'protected_item' => 'models\\facts']]
    || $second !== ['parents' => [], 'methods' => ['item' => 'other\\facts']]) {
    throw new LogicException('Accessor catalog lost qualification or eligibility boundaries');
}
echo "Accessor catalog: namespace/import identities and method eligibility passed\n";

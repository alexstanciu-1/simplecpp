<?php
declare(strict_types=1);
// Host-only data bridge. Execute the preserved solver and graph query unchanged.
namespace check_bodies {
    enum statement_kind { case local_declaration; }
    enum flow_end { case jump; case branch; case return_exit; case fallthrough; }
    class typed_block {
        public function __construct(public int $statement_start,public int $statement_count,public int $scope_id,public flow_end $end,public int $first,public int $second) {}
    }
    class Checked_Body {
        public array $blocks=[]; public array $statements=[]; public object $names;
        public function __construct(private int $parameters) {}
        public function entry_parameter_count(): int { return $this->parameters; }
    }
}
namespace {
    require __DIR__.'/../../reference/pre-rewrite/src/04_analyze/check_bodies/utilities/flow_graph.php';
    require __DIR__.'/../../reference/pre-rewrite/src/04_analyze/analyze_lifetimes/flow.php';
    $results=[];
    foreach(json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR) as $graph) {
        $body=new \check_bodies\Checked_Body($graph['parameters']);
        $body->names=new class($graph) {
            public function __construct(private array $graph) {}
            public function scope_for(int $id): object { return (object)['parent_scope_id'=>$this->graph['parents'][$id-1]]; }
            public function local_for(int $id): object { return (object)['scope_id'=>$this->graph['locals'][$id-1]]; }
        };
        $ends=[1=>\check_bodies\flow_end::jump,2=>\check_bodies\flow_end::branch,3=>\check_bodies\flow_end::return_exit,4=>\check_bodies\flow_end::fallthrough];
        foreach($graph['blocks'] as [$start,$count,$scope,$end,$first,$second]) { $body->blocks[]=new \check_bodies\typed_block($start,$count,$scope,$ends[$end],$first,$second); }
        foreach($graph['statements'] as [$scope,$local]) { $body->statements[]=(object)['scope_id'=>$scope,'kind'=>$local ? \check_bodies\statement_kind::local_declaration : null,'target'=>$local ? (object)['local_id'=>$local] : null]; }
        $entries=\analyze_lifetimes\Local_Flow::entries($body);$rows=[];
        for($i=1;$i<=count($body->blocks);$i++) { $facts=null;if(isset($entries[$i])){$facts=[];foreach($entries[$i] as $id=>$statement){$facts[]=[$id,$statement];}}$rows[]=$facts; }
        $results[]=$rows;
    }
    echo json_encode($results,JSON_THROW_ON_ERROR),"\n";
}

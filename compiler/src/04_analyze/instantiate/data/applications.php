<?php
declare(strict_types=1);
namespace instantiate;
/** One bound source application in a fixed concrete argument environment. */
final class Application_Task {
    public function __construct(public readonly Instance_Context $context,
        public readonly \resolve_symbols\Template_Application_Binding $application) {}
}
/** Either complete ordered arguments or explicit missing source-node prerequisites. */
final class Application_Result {
    private array $ordered_arguments /** vector<Template_Argument> */ = [];
    private array $prerequisites /** vector<int> */ = [];
    public function __construct(public readonly Application_Task $task,
        array $arguments /** vector<Template_Argument> */, array $prerequisites /** vector<int> */) {
        if((q_count($arguments)!==0)&&(q_count($prerequisites)!==0)){throw new \LogicException('Pending application cannot expose partial arguments');}
        $seen /** hash<bool,int> */ = [];
        foreach($prerequisites as $node){if($node<1){throw new \LogicException('Invalid application prerequisite');}if(isset($seen[$node])){throw new \LogicException('Duplicate application prerequisite');}$seen[$node]=true;$this->prerequisites[]=$node;}
        foreach($arguments as $argument){$this->ordered_arguments[]=$argument;}
    }
    public function ready(): bool { return q_count($this->prerequisites)===0; }
    public function argument_count(): int { return q_count($this->ordered_arguments); }
    public function argument_at(int $index): Template_Argument {
        if(($index<0)||($index>=q_count($this->ordered_arguments))){throw new \OutOfBoundsException('Missing prepared argument');}return $this->ordered_arguments[$index];
    }
    public function arguments(): array /** vector<Template_Argument> */ { return $this->ordered_arguments; }
    public function prerequisite_count(): int { return q_count($this->prerequisites); }
    public function prerequisite_at(int $index): int {
        if(($index<0)||($index>=q_count($this->prerequisites))){throw new \OutOfBoundsException('Missing application prerequisite');}return $this->prerequisites[$index];
    }
}

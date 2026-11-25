<?php
declare(strict_types=1);
namespace check_templates;
const TERM_NAMED = 1;
const TERM_PARAMETER = 2;
const TERM_APPLICATION = 3;
const TERM_CONSTANT = 4;
const TERM_ARRAY = 5;

/** Symbolic identity only: no canonical type ID, layout, or concrete substitution. */
final class Type_Term {
    private array $arguments /** vector<Type_Term> */ = [];
    public readonly bool $dependent;
    public function __construct(public readonly int $kind, public readonly int $symbol_id,
        public readonly int $parameter_slot, public readonly string $text_key,
        public readonly ?\type_model\Named_Definition $named_definition,
        public readonly ?\type_model\Record_Declaration $record_definition,
        array $arguments /** vector<Type_Term> */) {
        if (($kind<\check_templates\TERM_NAMED) || ($kind>\check_templates\TERM_ARRAY)) { throw new \InvalidArgumentException('Unknown symbolic term kind'); }
        if (($symbol_id<0) || ($symbol_id>\collect_symbols\MAX_SYMBOL_ID) || ($parameter_slot<0)) { throw new \InvalidArgumentException('Invalid symbolic declaration identity'); }
        $payloads=0;
        if ($symbol_id!==0) { $payloads=$payloads+1; }
        if ($named_definition!==null) { $payloads=$payloads+1; }
        if ($record_definition!==null) { $payloads=$payloads+1; }
        if ($text_key!=='') { $payloads=$payloads+1; }
        if ($kind===\check_templates\TERM_NAMED) {
            if (($payloads!==1) || ($text_key!=='') || ($parameter_slot!==0) || (q_count($arguments)!==0)) { throw new \InvalidArgumentException('Named term requires one authoritative target'); }
        } elseif (($kind===\check_templates\TERM_PARAMETER) || ($kind===\check_templates\TERM_APPLICATION)) {
            if (($payloads!==1) || ($symbol_id===0)) { throw new \InvalidArgumentException('Formal/application requires a declaration symbol'); }
            if ($kind===\check_templates\TERM_PARAMETER) {
                if (q_count($arguments)!==0) { throw new \InvalidArgumentException('Formal parameter has no argument list'); }
            } else {
                if ($parameter_slot!==0) { throw new \InvalidArgumentException('Application has no formal slot'); }
            }
        } elseif ($kind===\check_templates\TERM_CONSTANT) {
            if (($payloads!==1) || ($text_key==='') || ($parameter_slot!==0) || (q_count($arguments)!==0)) { throw new \InvalidArgumentException('Symbolic constant requires exact provenance'); }
        } else {
            if (($payloads!==0) || ($parameter_slot!==0) || (q_count($arguments)!==1)) { throw new \InvalidArgumentException('Symbolic array requires one element term'); }
        }
        $dependent=$kind===\check_templates\TERM_PARAMETER;
        foreach ($arguments as $argument) { $this->arguments[]=$argument; if ($argument->dependent) { $dependent=true; } }
        $this->dependent=$dependent;
    }
    public static function source(int $id): Type_Term {
        $none /** vector<Type_Term> */ = []; return new Type_Term(\check_templates\TERM_NAMED,$id,0,'',null,null,$none);
    }
    public static function named(\type_model\Named_Definition $definition): Type_Term {
        $none /** vector<Type_Term> */ = []; return new Type_Term(\check_templates\TERM_NAMED,0,0,'',$definition,null,$none);
    }
    public static function record(\type_model\Record_Declaration $definition): Type_Term {
        $none /** vector<Type_Term> */ = []; return new Type_Term(\check_templates\TERM_NAMED,0,0,'',null,$definition,$none);
    }
    public static function parameter(int $owner, int $slot): Type_Term {
        $none /** vector<Type_Term> */ = []; return new Type_Term(\check_templates\TERM_PARAMETER,$owner,$slot,'',null,null,$none);
    }
    public static function application(int $owner, array $arguments /** vector<Type_Term> */): Type_Term {
        return new Type_Term(\check_templates\TERM_APPLICATION,$owner,0,'',null,null,$arguments);
    }
    public static function constant(string $identity): Type_Term {
        $none /** vector<Type_Term> */ = []; return new Type_Term(\check_templates\TERM_CONSTANT,0,0,$identity,null,null,$none);
    }
    public static function array_type(Type_Term $element): Type_Term {
        $one /** vector<Type_Term> */ = [$element]; return new Type_Term(\check_templates\TERM_ARRAY,0,0,'',null,null,$one);
    }
    public function argument_count(): int { return q_count($this->arguments); }
    public function argument_at(int $index): Type_Term {
        if (($index<0) || ($index>=q_count($this->arguments))) { throw new \InvalidArgumentException('Invalid symbolic argument position'); }
        return $this->arguments[$index];
    }
    /** Full symbolic identity, iteratively; no structural comparison of provider objects. */
    public static function same(Type_Term $left, Type_Term $right): bool {
        $a_pending /** vector<Type_Term> */ = [$left]; $b_pending /** vector<Type_Term> */ = [$right]; $size=1;
        while ($size>0) {
            $size=$size-1; $a=$a_pending[$size]; $b=$b_pending[$size];
            if (($a->kind!==$b->kind) || ($a->symbol_id!==$b->symbol_id) || ($a->parameter_slot!==$b->parameter_slot)
                || ($a->text_key!==$b->text_key) || ($a->named_definition!==$b->named_definition)
                || ($a->record_definition!==$b->record_definition) || ($a->argument_count()!==$b->argument_count())) { return false; }
            for ($i=0;$i<$a->argument_count();$i++) {
                if ($size===q_count($a_pending)) { $a_pending[]=$a->argument_at($i); $b_pending[]=$b->argument_at($i); }
                else { $a_pending[$size]=$a->argument_at($i); $b_pending[$size]=$b->argument_at($i); }
                $size=$size+1;
            }
        }
        return true;
    }
}

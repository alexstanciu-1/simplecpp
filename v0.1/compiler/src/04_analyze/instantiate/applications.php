<?php
declare(strict_types=1);
namespace instantiate;
/** Normalize fixed explicit arguments; allocation and adoption belong to the accepting join. */
final class Application_Worker {
    public static function run(Application_Task $task, Bindings $reader): Application_Result {
        $context=$task->context;$target=$task->application->definition;
        $reader->instances->template_checks()->require_definition($target,$reader->annotations->names,$reader->catalog);
        $tree=$context->definition->source_frontend()->tree;
        $node=(int)\parse\Syntax_Access::template_application_parts($tree,$task->application->use_node_id)->first_argument_id;
        if(!$target->is_source()){
            $provider=$target->provider();
            if($provider->kind()===\collect_symbols\PROVIDER_FAMILY){return Application_Worker::family_arguments($task,$reader);}
            if(($provider->kind()!==\collect_symbols\PROVIDER_STORAGE_FAMILY)&&($provider->kind()!==\collect_symbols\PROVIDER_STORAGE_FUNCTION)){throw new \LogicException('Unsupported bound provider application');}
            if($node===0){throw new \LogicException('Bound storage application lost its argument');}
            if((int)$tree->row($node)->next_sibling!==0){throw new \LogicException('Bound storage application has extra arguments');}
            $type=$reader->type($context,$node);
            if($type===null){return Application_Worker::pending($task,$node);}
            if(!\resolve_types\Storage_Definitions::eligible($type)){$reader->annotations->fail($context->definition,$node,'Storage elements require supported copying/cleanup and no compiler-tracked allocation ownership');}
            $arguments /** vector<Template_Argument> */ = [new Template_Argument($type)];$none /** vector<int> */ = [];
            return new Application_Result($task,$arguments,$none);
        }
        $arguments /** vector<Template_Argument> */ = [];$bindings=$reader->annotations->bindings($target);
        for($position=0;$position<$bindings->parameters_count();$position++){
            $parameter=$bindings->parameters_at($position);
            if($node===0){throw new \LogicException('Bound application lost a required argument');}
            if((int)$parameter->type_syntax_id===0){
                $type=$reader->type($context,$node);
                if($type===null){return Application_Worker::pending($task,$node);}
                Application_Worker::require_contract($type,(int)$parameter->contract,$context->definition,$node,$reader);
                $arguments[]=new Template_Argument($type);
            }else{
                $annotation=(int)$parameter->type_syntax_id;
                if((int)$target->source_frontend()->tree->row($annotation)->kind===\parse\SYNTAX_TEMPLATE_APPLICATION){$reader->annotations->fail($target,$annotation,'Only the language integer contract is supported for value template parameters');}
                $binding=$bindings->name_for($annotation);
                $type=$reader->catalog->integer_literal_type;
                if($binding->kind===\resolve_symbols\REFERENCE_TEMPLATE_PARAMETER){
                    if($binding->target_id>=q_count($arguments)){$reader->annotations->fail($target,$annotation,'Only the language integer contract is supported for value template parameters');}
                    $type=$arguments[$binding->target_id]->type;
                }else{
                    $declared=$reader->annotations->bound_definition($target,$annotation);
                    if($declared===null){$reader->annotations->fail($target,$annotation,'Only the language integer contract is supported for value template parameters');}$type=$declared;
                }
                if($type!==$reader->catalog->integer_literal_type){$reader->annotations->fail($target,$annotation,'Only the language integer contract is supported for value template parameters');}
                $argument=$reader->value($context,$node);
                if($argument->type!==$type){$reader->annotations->fail($context->definition,$node,'Integer template argument type mismatch');}
                $arguments[]=$argument;
            }
            $node=(int)$tree->row($node)->next_sibling;
        }
        if($node!==0){throw new \LogicException('Bound application has extra arguments');}
        $none /** vector<int> */ = [];return new Application_Result($task,$arguments,$none);
    }
    public static function family_arguments(Application_Task $task, Bindings $reader): Application_Result {
        $owner=$task->context->definition;$tree=$owner->source_frontend()->tree;
        $node=(int)\parse\Syntax_Access::template_application_parts($tree,$task->application->use_node_id)->first_argument_id;
        $arguments /** vector<Template_Argument> */ = [];$missing /** vector<int> */ = [];
        $family=$task->application->definition->provider()->family();
        for($position=0;$position<$family->definition->parameter_count();$position++){
            if($node===0){throw new \LogicException('Bound family application lost a required type argument');}
            $type=$reader->type($task->context,$node);
            if($type===null){$missing[]=$node;}
            else{
                Application_Worker::require_contract($type,$family->definition->parameter_at($position)->contract,$owner,$node,$reader);
                $arguments[]=new Template_Argument($type);
            }
            $node=(int)$tree->row($node)->next_sibling;
        }
        if($node!==0){throw new \LogicException('Bound family application has surplus type arguments');}
        if(q_count($missing)!==0){$empty /** vector<Template_Argument> */ = [];return new Application_Result($task,$empty,$missing);}
        return new Application_Result($task,$arguments,$missing);
    }
    private static function require_contract(\type_model\Named_Definition $type, int $contract,
        \collect_symbols\Symbol_Record $owner, int $node, Bindings $reader): void {
        $missing=\type_model\Generic_Contracts::missing($type,$contract);$reason /** string */ = '';
        if(take_nullable($reason,$missing)){$reader->annotations->fail($owner,$node,'Default generic contract requires supported '.$reason);}
    }
    private static function pending(Application_Task $task, int $node): Application_Result {
        $empty /** vector<Template_Argument> */ = [];$missing /** vector<int> */ = [$node];return new Application_Result($task,$empty,$missing);
    }
}

<?php
declare(strict_types=1);
namespace context_test;
final class Probe {
    private static function check(bool $ok): void { echo $ok ? "true\n" : "false\n"; }
    public static function integer(int $bits, bool $signed): \type_model\Named_Definition {
        $p=new \type_model\Lifetime_Policy(); $p->copy=1; $p->construction=1; $p->assignment=1; $p->expiring=1;
        $ops /** vector<\type_model\Lifecycle_Operation> */ = [];
        return new \type_model\Named_Definition('word','',\type_model\Representation::integer($bits),new \type_model\Lifetime_Contract($p,$ops),$signed,'',false,false,true);
    }
    public static function run(): void {
        $u8=Probe::integer(8,false); $i8=Probe::integer(8,true); $u128=Probe::integer(128,false);
        Probe::check(\check_bodies\Integer_Literals::resolve('000',$u8)==='0');
        Probe::check(\check_bodies\Integer_Literals::resolve('000255',$u8)==='255');
        Probe::check(\check_bodies\Integer_Literals::resolve('127',$i8)==='127');
        Probe::check(\check_bodies\Integer_Literals::resolve('340282366920938463463374607431768211455',$u128)==='340282366920938463463374607431768211455');
        Probe::check(\check_bodies\Integer_Literals::resolve('0',Probe::integer(1,true))==='0');
        $bad_text /** vector<string> */ = ['','-1','+1','1.0',' 1','1 ','1e2','0x10','١','1' . string_byte_from_int(0)];
        foreach ($bad_text as $text) {
            $failed=false; try { \check_bodies\Integer_Literals::resolve($text,$u128); } catch (\LogicException $error) { $failed=true; } Probe::check($failed);
        }
        $overflow=false; try { \check_bodies\Integer_Literals::resolve('128',$i8); } catch (\RangeException $error) { $overflow=true; } Probe::check($overflow);
        $overflow=false; try { \check_bodies\Integer_Literals::resolve('340282366920938463463374607431768211456',$u128); } catch (\RangeException $error) { $overflow=true; } Probe::check($overflow);
        $source=new \read_sources\Source_Buffer(); $source->path='/context.phs';
        $source->content='function plain(): int { return 0; } template<typename T> struct Bag { public T $x; public function get(): T { return $this->x; } }';
        $file=\parse\File_Parser::parse(\tokenize\File_Tokenizer::tokenize($source));
        if (!$file->valid) { throw new \LogicException($file->error_reason); }
        $frontends=new \parse\Frontend_Set(); $frontends->add($file); $frontends->entry_index=0;
        $refresh=\collect_symbols\Declaration_Collector::collect($frontends,new \collect_symbols\Symbol_Store(1),false);
        if (!$refresh->valid) { throw new \LogicException($refresh->error_reason); } $symbols=$refresh->current;
        $plain=$symbols->symbol_by_id($symbols->find_symbol('plain',\collect_symbols\SYMBOL_FUNCTION,0));
        $bag=$symbols->symbol_by_id($symbols->find_symbol('Bag',\collect_symbols\SYMBOL_TEMPLATE_STRUCT,0));
        $method=$symbols->symbol_by_id($symbols->find_symbol('get',\collect_symbols\SYMBOL_TEMPLATE_FUNCTION,$bag->symbol_id));
        $ordinary=\instantiate\Instance_Context::ordinary($plain);
        Probe::check($ordinary->context_id===$plain->symbol_id); Probe::check($ordinary->argument_count()===0);
        $type_arg=new \instantiate\Template_Argument($u8); $value_arg=new \instantiate\Template_Argument($u128,'340282366920938463463374607431768211455');
        $arguments /** vector<\instantiate\Template_Argument> */ = [$type_arg,$value_arg];
        $instance=new \instantiate\Instance_Context($bag,7,$arguments); $arguments[0]=$value_arg;
        Probe::check($instance->definition===$bag); Probe::check($instance->context_id===4294967302);
        Probe::check($instance->type_name()==='instance_7'); Probe::check($instance->type_namespace()===(string_byte_from_int(0) . 'template_instance'));
        Probe::check($instance->argument_at(0)===$type_arg); Probe::check($instance->argument_at(0)->value===null);
        Probe::check($instance->argument_at(1)->value==='340282366920938463463374607431768211455');
        $member=new \instantiate\Instance_Context($method,8,$arguments,$u8); Probe::check($member->receiver_type===$u8);
        $empty /** vector<\instantiate\Template_Argument> */ = [];
        for ($case=0;$case<7;$case++) {
            $failed=false;
            try {
                if ($case===0) { $bad=\instantiate\Instance_Context::ordinary($bag); }
                elseif ($case===1) { $bad=new \instantiate\Instance_Context($plain,0,$arguments); }
                elseif ($case===2) { $bad=new \instantiate\Instance_Context($plain,1,$empty); }
                elseif ($case===3) { $bad=new \instantiate\Instance_Context($bag,-1,$empty); }
                elseif ($case===4) { $bad=new \instantiate\Instance_Context($bag,4294967296,$empty); }
                elseif ($case===5) { $bad=new \instantiate\Instance_Context($bag,1,$empty,$u8); }
                else { $instance->argument_at(2); }
            } catch (\LogicException $error) { $failed=true; }
            Probe::check($failed);
        }
        $maximum=new \instantiate\Instance_Context($bag,4294967295,$empty); Probe::check($maximum->context_id===8589934590);
    }
}

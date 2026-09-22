<?php
declare(strict_types=1);

/*
 * Role: Run selected tasks and joins in compiler phase order.
 * Used by: Compiler_Session::compile()
 * Call map:
 *   Phases::run_tokenization() ... run_emission()
 *     -> [each owning step] init(); run(); finalize(); result()
 */

namespace compile;

use read_sources\Source_Reader;
use parse\Parser;
use resolve_symbols\Symbol_Resolver;
use tokenize\Tokenizer;

/**
 * @compiler-internal Session orchestration helpers, not semantic APIs for other stages.
 * Each run_* method selects, executes and joins through the owning process API.
 * Execution is serial; tasks do not retain the mutable update-control object.
 * Results are candidates until Compiler_Session accepts the complete update.
 */
class Phases
{
    // Select each complete batch before execution. Workers consume only their
    // task/snapshot; joins alone assemble candidates. Execution is serial today.
    /** @compiler-internal Read and join selected source snapshots, then tokenize/join against those exact buffers. */
    public static function run_tokenization(
        \read_sources\Source_Set $sources,
        \tokenize\Token_Set $previous,
        Update_Context $update
    ): Tokenization_Phase_Result
    {
        $reader = new Source_Reader($sources, $update->full_rebuild);
        $reader->init();
        $reader->run();
        $reader->finalize();
        $sources = $reader->result();

        $step = new Tokenizer($sources, $previous, $update->full_rebuild);
        $step->init();
        $step->run();
        $step->finalize();
        $tokens = $step->result();
        return new Tokenization_Phase_Result($sources, $tokens);
    }

    /** @compiler-internal Select fixed token buffers, parse and join current file frontends; no project symbols yet. */
    public static function run_parsing(
        \read_sources\Source_Set $sources, \tokenize\Token_Set $tokens,
        \parse\Frontend_Set $previous, Update_Context $update
    ): \parse\Frontend_Set
    {
        $step = new Parser($sources, $tokens, $previous, $update->full_rebuild);
        $step->init();
        $step->run();
        $step->finalize();
        return $step->result();
    }

    // Declarations and catalog are fixed before selecting independent source-owner tasks.
    /** @compiler-internal Bind selected declarations and bodies against fixed symbols/catalog, then join. */
    public static function run_symbols(\collect_symbols\Symbol_Store $symbols,
        \resolve_symbols\Resolution_Set $previous, Update_Context $update, \type_model\Type_Catalog $catalog): \resolve_symbols\Resolution_Set
    {
        $step = new \resolve_symbols\Symbol_Resolver($symbols, $previous, $update->full_rebuild, $catalog);
        $step->init();
        $step->run();
        $step->finalize();
        return $step->result();
    }

    /** @compiler-internal Read the completed type snapshot, select/check bodies and join; no type materialization here. */
    public static function run_bodies(\collect_symbols\Symbol_Store $symbols,
        \resolve_symbols\Resolution_Set $names, \resolve_types\Type_Resolution $types,
        \check_bodies\Body_Set $previous, Update_Context $update): \check_bodies\Body_Set
    {
        $step = new \check_bodies\Body_Checker($symbols, $names, $types, $previous, $update->full_rebuild);
        $step->init();
        $step->run();
        $step->finalize();
        return $step->result();
    }

    /** @compiler-internal Select fixed checked bodies, analyze privately and join the current analysis set. */
    public static function run_lifetimes(\check_bodies\Body_Set $bodies,
        \analyze_lifetimes\Lifetime_Set $previous, Update_Context $update, ?\type_model\Type_Store $types = null): \analyze_lifetimes\Lifetime_Set
    {
        $step = new \analyze_lifetimes\Lifetime_Analyzer($bodies, $previous, $update->full_rebuild, $types);
        $step->init();
        $step->run();
        $step->finalize();
        return $step->result();
    }

    /** @compiler-internal Select analyzed bodies and lower against a fixed backend; join complete callable plans. */
    public static function run_lowering(\analyze_lifetimes\Lifetime_Set $lifetimes, \prepare_backend\Backend_Context $backend,
        \lower\Lowered_Set $previous, Update_Context $update): \lower\Lowered_Set
    {
        $step = new \lower\Lowerer($lifetimes, $backend, $previous, $update->full_rebuild);
        $step->init();
        $step->run();
        $step->finalize();
        return $step->result();
    }

    /** @compiler-internal Join function IR, then select/assemble/join independent file tasks; no native publication. */
    public static function run_emission(\lower\Lowered_Set $lowered, \prepare_backend\Backend_Context $backend,
        \lower\native_entry_plan $entry, ?\emit_llvm\Emitted_Program $previous, Update_Context $update): \emit_llvm\Emitted_Program
    {
        $step = new \emit_llvm\LLVM_Emitter($lowered, $backend, $entry, $previous, $update->full_rebuild);
        $step->init();
        $step->run();
        $step->finalize();
        $functions = $step->result();
        $modules = new \emit_llvm\Module_Assembler($functions, $previous, $update->full_rebuild);
        $modules->init();
        $modules->run();
        $modules->finalize();
        return $modules->result();
    }
}

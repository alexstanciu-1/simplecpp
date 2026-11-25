<?php
declare(strict_types=1);
namespace analyze_lifetimes;
const END_DISCARD = 1;
const END_CONDITION = 2;
const END_RETURN_COPY = 3;
const END_RETURN_CONSTRUCT = 4;
const END_LOCAL_COPY = 5;
const END_ARGUMENT_COPY = 6;
const END_ARGUMENT_BORROW = 7;
const END_LOCAL_CONSTRUCT = 8;
const END_COPY_SOURCE = 9;
const END_ASSIGNMENT_SOURCE = 10;
const END_CONVERSION_INPUT = 11;
const END_OPERATION_INPUT = 12;
const END_INDEX_INPUT = 13;
const END_TARGET_INDEX = 14;
const LOCAL_SCOPE_EXIT = 1;
const LOCAL_RETURN_EXIT = 2;
const CLEANUP_LOCAL = 1;
const CLEANUP_TEMPORARY = 2;
/** Stable export vocabulary; numeric tags are internal and never inferred from source spelling. */
final class Lifetime_Ends {
    public static function name(int $end): string {
        $names /** vector<string> */ = ['discard','condition','return_copy','return_construct','local_copy','argument_copy','argument_borrow','local_construct','copy_source','assignment_source','conversion_input','operation_input','index_input','target_index'];
        if (($end < 1) || ($end > q_count($names))) { throw new \InvalidArgumentException('Invalid lifetime end'); }
        return $names[$end-1];
    }
    public static function requires_consumer(int $end): bool {
        if (($end < 1) || ($end > 14)) { throw new \InvalidArgumentException('Invalid lifetime end'); }
        return ($end === \analyze_lifetimes\END_ARGUMENT_COPY) || ($end === \analyze_lifetimes\END_ARGUMENT_BORROW)
            || ($end === \analyze_lifetimes\END_CONVERSION_INPUT) || ($end === \analyze_lifetimes\END_OPERATION_INPUT)
            || ($end === \analyze_lifetimes\END_INDEX_INPUT);
    }
    public static function local_name(int $end): string {
        $name = '';
        if ($end === \analyze_lifetimes\LOCAL_SCOPE_EXIT) { $name = 'scope_exit'; }
        else if ($end === \analyze_lifetimes\LOCAL_RETURN_EXIT) { $name = 'return_exit'; }
        else { throw new \InvalidArgumentException('Invalid local lifetime end'); }
        return $name;
    }
    public static function subject_name(int $subject): string {
        $name = '';
        if ($subject === \analyze_lifetimes\CLEANUP_LOCAL) { $name = 'local'; }
        else if ($subject === \analyze_lifetimes\CLEANUP_TEMPORARY) { $name = 'temporary'; }
        else { throw new \InvalidArgumentException('Invalid cleanup subject'); }
        return $name;
    }
}
/** A reached checked value's one consumption boundary, not a copied type or cleanup policy. */
final class Value_Lifetime {
    public function __construct(public readonly int $value_id, public readonly int $statement_id,
        public readonly int $end, public readonly int $consumer_id = 0) {
        $consumer = Lifetime_Ends::requires_consumer($end);
        if (($value_id < 1) || ($statement_id < 1) || ($consumer_id < 0) || ($consumer !== ($consumer_id !== 0))) {
            throw new \LogicException('Invalid temporary consumption boundary');
        }
    }
}
/** Read-only exit row; complete body/range/initialization checks belong to Analyzed_Body. */
final class Local_Lifetime {
    public function __construct(public readonly int $local_id, public readonly int $initialized_statement_id,
        public readonly int $end_after_statement, public readonly int $end, public readonly int $block_id = 1) {
        Lifetime_Ends::local_name($end);
    }
}
/** Private worker row shared by its ordered live stack and local-ID index. */
final class Active_Local {
    public function __construct(public readonly int $local_id, public readonly int $initialized_statement_id) {}
}
/** Owned local/temporary destruction at a reached block boundary; implementation remains in the body. */
final class Cleanup_Obligation {
    public function __construct(public readonly int $subject, public readonly int $subject_id,
        public readonly int $after_statement, public readonly int $block_id) {
        Lifetime_Ends::subject_name($subject);
        if (($subject_id < 1) || ($after_statement < 0) || ($block_id < 1)) { throw new \InvalidArgumentException('Invalid cleanup obligation'); }
    }
}

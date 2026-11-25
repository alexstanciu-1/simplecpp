<?php
declare(strict_types=1);
namespace lifetime_records_test;
final class Probe {
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($i = 0; $i < $cases->size(); $i++) {
            $case_data = $cases->at($i); $kind = $case_data->member('kind')->text(); $tag = $case_data->member('tag')->integer();
            $a = $case_data->member('a')->integer(); $b = $case_data->member('b')->integer(); $c = $case_data->member('c')->integer(); $d = $case_data->member('d')->integer();
            $result = 'false';
            try {
                if ($kind === 'value') {
                    $value = new \analyze_lifetimes\Value_Lifetime($a,$b,$tag,$c);
                    $result = '["value",'.$value->value_id.','.$value->statement_id.','.json_quote(\analyze_lifetimes\Lifetime_Ends::name($value->end)).','.$value->consumer_id.']';
                } elseif ($kind === 'cleanup') {
                    $cleanup = new \analyze_lifetimes\Cleanup_Obligation($tag,$a,$b,$c);
                    $result = '["cleanup",'.json_quote(\analyze_lifetimes\Lifetime_Ends::subject_name($cleanup->subject)).','.$cleanup->subject_id.','.$cleanup->after_statement.','.$cleanup->block_id.']';
                } elseif ($kind === 'local') {
                    $local = new \analyze_lifetimes\Local_Lifetime($a,$b,$c,$tag,$d);
                    $result = '["local",'.$local->local_id.','.$local->initialized_statement_id.','.$local->end_after_statement.','.json_quote(\analyze_lifetimes\Lifetime_Ends::local_name($local->end)).','.$local->block_id.']';
                } else {
                    $active = new \analyze_lifetimes\Active_Local($a,$b);
                    $result = '["active",'.$active->local_id.','.$active->initialized_statement_id.']';
                }
            } catch (\LogicException $error) { $result = 'false'; }
            echo $result . "\n";
        }
    }
}

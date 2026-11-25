<?php
declare(strict_types=1);
namespace tool_run_test;
final class Probe {
    public static function run(string $text): void {
        dt_sleep_ms(0); dt_sleep_ms(-1); dt_sleep_ms(1);
        $cases = json_read($text);
        for ($index = 0; $index < $cases->size(); $index++) {
            $row = $cases->at($index); $command /** vector<string> */ = []; $arguments = $row->member('command');
            for ($j = 0; $j < $arguments->size(); $j++) { $command[] = $arguments->at($j)->text(); }
            $expected_error = $row->member('error')->text(); $ok = true;
            try {
                $output = \prepare_backend\Tool_Run::run($command,$row->member('input')->text(),$row->member('timeout')->integer());
                if ($expected_error !== '') { $ok = false; }
                elseif ($row->member('contains')->boolean()) {
                    $position /** int */ = 0; $found = q_strpos($output,$row->member('want')->text());
                    if (!take_false($position,$found)) { $ok = false; }
                } elseif ($output !== $row->member('want')->text()) { $ok = false; }
            } catch (\InvalidArgumentException $error) { if ($expected_error !== 'invalid') { $ok = false; } }
            catch (\RuntimeException $error) {
                if ($expected_error === '') { $ok = false; }
                if ($expected_error !== '*') { if ($error->getMessage() !== $expected_error) { $ok = false; } }
            }
            echo $ok ? "true\n" : "false\n";
        }
    }
}

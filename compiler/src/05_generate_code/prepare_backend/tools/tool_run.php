<?php
declare(strict_types=1);
namespace prepare_backend;

/** One synchronous compiler tool invocation. The framework owns process groups, streams and reaping. */
final class Tool_Run {
    public static function run(array $command /** vector<string> */, string $input, int $timeout_ms): string {
        if (q_count($command) === 0) { throw new \InvalidArgumentException('Tool command must name an executable'); }
        if ($timeout_ms < 1) { throw new \InvalidArgumentException('Tool invocation requires a positive deadline'); }
        $arguments /** vector<string> */ = [];
        for ($index = 1; $index < q_count($command); $index++) { $arguments[] = $command[$index]; }
        $process = process_spawn($command[0],$arguments,$input,$timeout_ms,''); $text = '';
        try {
            while (!process_poll($process)) { dt_sleep_ms(1); }
            $output = process_output($process);
            process_close($process);
            if ($output->timed_out) { throw new \RuntimeException('Clang backend operation timed out'); }
            if (($output->exit_code !== 0) || ($output->signal !== 0) || $output->stopped) {
                throw new \RuntimeException('Clang backend operation failed: ' . $output->stderr_text);
            }
            $text = $output->stdout_text;
        } catch (\RuntimeException $error) { process_close($process); throw $error; }
        return $text;
    }
}

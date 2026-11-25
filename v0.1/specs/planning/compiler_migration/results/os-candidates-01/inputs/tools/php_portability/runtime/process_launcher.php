<?php
declare(strict_types=1);
// Private child executable. The parent owns the directory and protocol, not users.
$control = null;
try {
    $config = unserialize(file_get_contents($argv[2]), ['allowed_classes'=>false]);
    $control = fopen($config['control'], 'we');
    if ($control === false) { exit(125); }
    if (posix_setsid() < 0) { throw new RuntimeException('Cannot establish process group'); }
    if ($config['cwd'] !== '' && !chdir($config['cwd'])) { throw new RuntimeException('Cannot change directory'); }
    for ($signal = 1; $signal < 65; ++$signal) {
        if (!in_array($signal, [SIGKILL, SIGSTOP, 32, 33], true)) { @pcntl_signal($signal, SIG_DFL); }
    }
    pcntl_sigprocmask(SIG_SETMASK, []);
    $ready = 'READY ' . getmypid() . "\n";
    if (fwrite($control, $ready) !== strlen($ready) || !fflush($control)) { exit(125); }
    $ack = fopen($config['ack'], 're');
    if ($ack === false || fread($ack, 1) !== 'G') { exit(125); }
    fclose($ack);
    // Only this stream reports setup/exec errors. It cannot survive successful exec.
    @pcntl_exec($config['executable'], $config['args']);
    throw new RuntimeException('Cannot execute target');
} catch (Throwable $error) {
    if (is_resource($control)) { @fwrite($control, 'ERROR ' . $error->getMessage() . "\n"); @fflush($control); }
    exit(125);
}

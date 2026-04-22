#!/usr/bin/env bash
set -euo pipefail
ssh -T alex-ai@dev002.travelfuse.ro <<'REMOTE'
set -euo pipefail
cd /home/alex-ai/simple_cpp
run_root="/home/alex-ai/operator_matrix_server_runs"
mkdir -p "$run_root"
run_id="php_matrix_only_$(date +%Y%m%d_%H%M%S)"
log="${run_root}/${run_id}.log"
pidfile="${run_root}/${run_id}.pid"
nohup bash -lc '
set -e
cd /home/alex-ai/simple_cpp
printf "[%s] reset php-matrix start\n" "$(date -Iseconds)"
php8.5 tests/tools/run_tests.php reset --suite=php-matrix
printf "[%s] run php-matrix start\n" "$(date -Iseconds)"
php8.5 tests/tools/run_tests.php run --suite=php-matrix --jobs=24
printf "[%s] job complete\n" "$(date -Iseconds)"
' > "$log" 2>&1 < /dev/null &
echo $! > "$pidfile"
printf 'RUN_ID=%s\nPID=%s\nLOG=%s\nPIDFILE=%s\n' "$run_id" "$(cat "$pidfile")" "$log" "$pidfile"
REMOTE

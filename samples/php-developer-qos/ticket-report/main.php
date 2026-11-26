// Summarize ticket counts by queue for each requested state. All queries are read-only.
$args = cli_args();
if (cli_argc() < 3) { echo "usage: ticket-report SOCKET STATE...\n"; return 2; }
$user_result = getenv("TICKETS_DB_USER");
if (!isset($user_result)) { echo "set TICKETS_DB_USER\n"; return 2; }
$user string = $user_result;
$password = "";
$secret = getenv("TICKETS_DB_PASSWORD");
if (isset($secret)) { $password = $secret; }
$socket string = $args[1];
$db = new mysqli("localhost", $user, $password, "tickets", 0, $socket);
if ($db->connect_errno !== 0) { echo "connection failed\n"; return 1; }
$state = "";
$stmt mysqli_stmt = $db->prepare("SELECT queue, COUNT(*) AS n FROM tickets WHERE state=? GROUP BY queue ORDER BY queue");
if (!$stmt->bind_param("s", $state)) { echo "bind failed\n"; return 1; }
for ($i = 2; $i < cli_argc(); $i += 1) {
    $state = $args[$i];
    if (!$stmt->execute()) { echo "read failed\n"; return 1; }
    $result mysqli_result = $stmt->get_result();
    echo $state, ":\n";
    while (true) {
        $row = $result->fetch_assoc();
        if ($row === null) { break; }
        $column dynamic = $row["queue"];
        $queue = "(unassigned)";
        if ($column !== null) { $queue = $column; }
        $total int = $row["n"];
        echo "  ", $queue, " ", $total, "\n";
    }
}
$stmt->close();
$db->close();
return 0;

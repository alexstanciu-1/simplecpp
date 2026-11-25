// The mysqli query surface distinguishes two status sentinels and a row set.
$args = cli_args();
if (count($args) !== 2) { return 2; }
$socket string = $args[1];
$user string = getenv("TICKETS_DB_USER");
$db = new mysqli("localhost", $user, "", "tickets", 0, $socket);
if ($db->connect_errno !== 0) { return 1; }
$queries vector<string> = [
    "SELECT * FROM no_such_table",
    "UPDATE tickets SET state=state WHERE 1=0",
    "SELECT COUNT(*) AS n FROM tickets",
];
foreach ($queries as $query) {
    $outcome = $db->query($query);
    if ($outcome === false) { echo "error\n"; continue; }
    if ($outcome === true) { echo "command\n"; continue; }
    $rows mysqli_result = $outcome;
    $row = $rows->fetch_assoc();
    $n int = $row["n"];
    echo "count ", $n, "\n";
}
return 0;

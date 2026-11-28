// Alternate entry using general_api. A failed prepare is an ordinary caller-handled outcome.
$args = cli_arguments();
if (count($args) !== 3) { return 2; }
$user string = getenv("TICKETS_DB_USER");
$db = new mysqli("localhost", $user, "", "tickets", 0, $args[1]);
if ($db->connect_errno !== 0) { echo "connection failed\n"; return 1; }
$pending = $db->prepare("SELECT COUNT(*) AS n FROM tickets WHERE state=?");
if (!isset($pending)) { echo "prepare failed\n"; return 1; }
$stmt mysqli_stmt = $pending;
$params dynamic = [$args[2]];
if (!db_execute($stmt, "s", $params)) { echo "read failed\n"; return 1; }
$result mysqli_result = $stmt->get_result();
$row = $result->fetch_assoc();
$total int = $row["n"];
echo $total, "\n";
return 0;

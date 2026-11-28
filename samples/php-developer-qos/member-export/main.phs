use Format\Formatter;
use Format\JsonFormat;
use Format\TextFormat;
use function Directory\members;

function export_members(Formatter $format, vector<string> $inputs): void {
    foreach (members($inputs) as $member) {
        echo $format->line($member), "\n";
    }
}
$args = cli_arguments();
if (count($args) < 2) { echo "usage: member-export text|jsonl ROLE:NAME...\n"; return 2; }
$inputs vector<string> = [];
for ($i = 2; $i < count($args); $i += 1) { $inputs[] = $args[$i]; }
if ($args[1] === "text") { export_members(new TextFormat(), $inputs); }
else if ($args[1] === "jsonl") { export_members(new JsonFormat(), $inputs); }
else { echo "unknown format\n"; return 2; }
return 0;

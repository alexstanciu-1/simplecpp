"""A change in the hash payload must reach the previously prepared PHP isset caller."""
from pathlib import Path
import tempfile
from collection_goal_native import revisions

CALLER = """
$numbers hash<int,string> = ["zero" => 0];
echo (int)isset($numbers["zero"]), (int)isset($numbers["missing"]), count($numbers);
$users = users();
echo (int)isset($users["user"]), (int)isset($users["missing"]), count($users);
$users["user"]=new User();
echo (int)isset($users["user"]);
return 8;
"""
REQUIRED = """
function users():hash<User,string> {
    $users hash<User,string> = []; $users["user"]=new User(); return $users;
}
"""
NULLABLE = REQUIRED.replace('hash<User,string>', 'hash<?User,string>').replace('new User()', 'null')

if __name__ == '__main__':
    with tempfile.TemporaryDirectory() as directory:
        work = Path(directory)
        revisions(work, 'php', {'main': CALLER, 'model': 'class User {}', 'api': REQUIRED}, [
            (None, None, b'1011011'),
            ('api', NULLABLE, b'1010011'),
            ('main', CALLER.replace('"user"', '1'), 'requires string'),
            ('main', 'struct Point { public int $x; } $values hash<Point,string> = []; echo isset($values["x"]); return 8;', 'requires a carrier or owned callable'),
            ('main', CALLER, b'1010011'),
        ])

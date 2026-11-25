"""A change in the hash payload must reach the previously prepared PHP isset caller."""
from pathlib import Path
import tempfile
from collection_goal_native import revisions

CALLER = """
$users = users();
echo (int)isset($users["user"]), (int)isset($users["missing"]), count($users);
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
            (None, None, b'101'),
            ('api', NULLABLE, b'001'),
            ('main', CALLER.replace('"user"', '1'), 'requires string'),
            ('main', CALLER, b'001'),
        ])

namespace Directory;

enum Role: string {
    case Member = "member";
    case Admin = "admin";
}

class Member {
    public string $name;
    public Role $role = Role::Member;

    public function __construct(string $name, Role $role) {
        $this->name = $name;
        $this->role = $role;
    }
}

function members(vector<string> $inputs): Iterator<Member> {
    foreach ($inputs as $input) {
        $parts = explode(":", $input, 2);
        if (count($parts) !== 2 || $parts[1] === "") { continue; }
        $found = enum_try_from_value(Role::class, $parts[0]);
        if (!isset($found)) { continue; }
        $role Role = $found;
        yield new Member($parts[1], $role);
    }
}

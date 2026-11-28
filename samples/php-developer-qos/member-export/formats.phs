namespace Format;
use Directory\Member;

interface Formatter {
    public function line(Member $member): string;
}

class TextFormat implements Formatter {
    public override function line(Member $member): string {
        return enum_value($member->role) . ": " . $member->name;
    }
}

class JsonFormat implements Formatter {
    public override function line(Member $member): string {
        $row mixed = ["name" => $member->name, "role" => enum_value($member->role)];
        $encoded string = json_encode($row);
        return $encoded;
    }
}

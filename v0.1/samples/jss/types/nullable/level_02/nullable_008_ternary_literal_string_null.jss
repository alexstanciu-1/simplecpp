function maybeName(flag: bool): ?string {
    let value: ?string = flag ? "ok" : null;
    return value;
}

let yesValue: ?string = maybeName(true);
let noValue: ?string = maybeName(false);

print("yes=", yesValue ?? "", "\n");
print("no=", noValue ?? "", "\n");

function ok(): mixed {
    return 7;
}

function fail(): mixed {
    return null;
}

let a: mixed = ok();
let b: mixed = fail();

print(a ?? 0, "\n");
print(b ?? 0, "\n");

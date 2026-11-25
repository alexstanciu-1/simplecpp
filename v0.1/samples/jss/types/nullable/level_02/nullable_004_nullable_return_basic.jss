function pick(flag: bool): ?int {
	if (flag === true) {
		return 7;
	}

	return null;
}

let a: ?int = pick(true);
let b: ?int = pick(false);

if (a === null) {
	print("0\n");
} else {
	print(a, "\n");
}

if (b === null) {
	print("0\n");
} else {
	print(b, "\n");
}

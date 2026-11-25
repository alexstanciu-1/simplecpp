function maybe_label(row: hash<mixed>): ?string {
	if (row["label"] === null) {
		return null;
	}

	return row["label"];
}

let row: hash<mixed> = {};
row["label"] = null;

let label: ?string = maybe_label(row);
if (label === null) {
	print("missing\n");
}

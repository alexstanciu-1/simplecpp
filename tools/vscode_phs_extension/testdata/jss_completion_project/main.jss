namespace Editor;

class FixtureUser {
	name: string = "alex";
}

function describe(user: FixtureUser): string {
	let label: string = `user:${user.name}`;
	return label;
}

let user: FixtureUser = new FixtureUser();
let text: string = "";
let err: error;

if (take(text, err, fs.get("data.json"))) {
	let data: mixed;
	let encoded: string = "";
	if (take(data, err, json.decode(text)) && take(encoded, err, json.encode(data))) {
		print(describe(user), "\n");
		print(encoded, "\n");
	}
}

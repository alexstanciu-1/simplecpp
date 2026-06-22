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
	let data: dynamic = json.decode(text);
	print(describe(user), "\n");
	print(json.encode(data), "\n");
}

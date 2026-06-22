let text: string = "";
let err: error;
if (take(text, err, fs.get("data.json"))) {
	let data: dynamic = json.decode(text);
	print("jss:", json.encode(data), "\n");
} else {
	print("error\n");
}

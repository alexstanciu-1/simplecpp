let text: string = "";
let err: error;
if (take(text, err, fs.get("data.json"))) {
	let data: mixed;
	let encoded: string = "";
	if (take(data, err, json.decode(text)) && take(encoded, err, json.encode(data))) {
		print("jss:", encoded, "\n");
	}
} else {
	print("error\n");
}

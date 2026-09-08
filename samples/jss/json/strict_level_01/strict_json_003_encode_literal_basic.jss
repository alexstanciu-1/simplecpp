function show(): void {
    let row: hash<mixed> = { "name": "Ada", "id": 3 };
    let encoded: string = "";
    let jsonError: error;
    if (take(encoded, jsonError, json.encode(row))) {
        print(encoded, "\n");
    } else {
        print("encode_error\n");
    }
}

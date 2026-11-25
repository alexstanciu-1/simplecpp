function roundTrip(text: string): void {
    let value: mixed;
    let jsonError: error;
    if (!take(value, jsonError, json.decode(text))) {
        print("json_error\n");
        return;
    }
    let encoded: string = "";
    if (!take(encoded, jsonError, json.encode(value))) {
        print("encode_error\n");
        return;
    }
    print(encoded, "\n");
}

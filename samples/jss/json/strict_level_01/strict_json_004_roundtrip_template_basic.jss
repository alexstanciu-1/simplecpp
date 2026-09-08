function normalize(text: string): void {
    let decoded: mixed;
    let jsonError: error;
    if (!take(decoded, jsonError, json.decode(text))) {
        print("json_error\n");
        return;
    }
    let encoded: string = "";
    if (!take(encoded, jsonError, json.encode(decoded))) {
        print("encode_error\n");
        return;
    }
    print(`json ${encoded}`, "\n");
}

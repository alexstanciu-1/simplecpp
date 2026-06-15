function normalize(text: string): void {
    let decoded: dynamic = json.decode(text);
    let encoded: string = json.encode(decoded);
    print(`json ${encoded}`, "\n");
}

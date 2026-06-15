function roundTrip(text: string): void {
    let value: dynamic = json.decode(text);
    let encoded: string = json.encode(value);
    print(encoded, "\n");
}

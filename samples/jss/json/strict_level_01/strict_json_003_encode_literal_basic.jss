function show(): void {
    let row: hash<mixed> = { "name": "Ada", "id": 3 };
    print(json.encode(row), "\n");
}

function show(text: string): void {
    let stamp: int = 0;
    let err: error;
    if (!take(stamp, err, dt.parse(text))) {
        return;
    }
    print(dt.format("Y-m-d H:i:s", stamp), "\n");
}

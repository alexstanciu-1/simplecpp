function show(text: string): void {
    let stamp: int = 0;
    let err: error;
    if (!take(stamp, err, dt.parse(text))) {
        return;
    }
    print(dt.format("Y-m-d", stamp), "\n");
    print(dt.format_iso_utc(stamp), "\n");
    print(dt.format_now("H:i"), "\n");
}

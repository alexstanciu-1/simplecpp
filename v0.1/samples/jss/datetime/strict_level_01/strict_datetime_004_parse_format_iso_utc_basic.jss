function show(text: string): void {
    let stamp: int = 0;
    let err: error;
    if (!take(stamp, err, dt.parse_iso_utc(text))) {
        return;
    }
    print(dt.format_iso_utc(stamp), "\n");
}

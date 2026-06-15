function inspect(path: string): void {
    let fh: resource_handle;
    if (!take(fh, io.open(path, "rb"))) {
        return;
    }
    let moved: ?int = io.seek(fh, 2);
    print(moved ?? 0, "\n");
    io.close(fh);
}

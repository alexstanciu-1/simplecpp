function inspect(path: string): void {
    let fh: resource_handle;
    if (!take(fh, io.open(path, "rb"))) {
        return;
    }
    let pos: int = 0;
    take(pos, io.tell(fh));
    print(pos, "\n");
    print(io.eof(fh) ? "E" : "N", "\n");
    io.close(fh);
}

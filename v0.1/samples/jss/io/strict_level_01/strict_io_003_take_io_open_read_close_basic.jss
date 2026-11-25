function readFirstLine(path: string): void {
    let fh: resource_handle;
    if (!take(fh, io.open(path, "rb"))) {
        return;
    }
    let line: string = "";
    take(line, io.read_line(fh));
    let closed: bool = io.close(fh);
    print(line, "\n");
}

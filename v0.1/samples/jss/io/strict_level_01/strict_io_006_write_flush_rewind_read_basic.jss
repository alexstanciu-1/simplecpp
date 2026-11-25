function mirror(path: string): void {
    let fh: resource_handle;
    if (!take(fh, io.open(path, "wb+"))) {
        return;
    }
    let wrote: int = 0;
    take(wrote, io.write(fh, "alpha"));
    io.flush(fh);
    io.rewind(fh);
    let text: string = "";
    take(text, io.read(fh, 5));
    io.close(fh);
    print(wrote, ":", text, "\n");
}

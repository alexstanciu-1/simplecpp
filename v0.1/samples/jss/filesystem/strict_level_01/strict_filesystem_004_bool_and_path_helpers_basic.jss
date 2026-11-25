function show(path: string): void {
    print(fs.exists(path) ? "Y" : "N", "\n");
    print(fs.basename(path), "\n");
    print(fs.dirname(path), "\n");
}

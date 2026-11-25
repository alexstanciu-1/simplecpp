function inspect(root: string, path: string): void {
    let written: int = 0;
    let err: error;
    take(written, err, fs.put(path, "alpha"));
    let files: vector<string> = [];
    take(files, err, fs.scan(root));
    let resolved: string = "";
    take(resolved, err, fs.realpath(path));
    print(written, ":", count(files), ":", fs.basename(resolved), "\n");
}

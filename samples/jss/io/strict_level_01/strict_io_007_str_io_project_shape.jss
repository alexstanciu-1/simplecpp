function run(): void {
    let root: string = "strict_str_io_root";
    let path: string = root + "/data.txt";
    if (!fs.mkdir(root)) {
        print("MKDIR_FAIL\n");
    } else {
        let fh: resource_handle;
        print(take(fh, io.open(path, "wb+")) ? "T\n" : "F\n");
        let bytes: int = 0;
        if (!take(bytes, io.write(fh, implode("|", explode(",", "a,b,c"))))) {
            print("WRITE_FAIL\n");
        } else {
            print(bytes, "\n");
            print(io.rewind(fh) ? "R\n" : "r\n");
            let line: string = "";
            if (!take(line, io.read(fh, 64))) {
                print("READ_FAIL\n");
            } else {
                print(strtoupper(line), "\n");
            }
        }
        print(io.close(fh) ? "C\n" : "c\n");
        print(fs.remove(path) ? "U\n" : "u\n");
        print(fs.rmdir(root) ? "D\n" : "d\n");
    }
}
run();

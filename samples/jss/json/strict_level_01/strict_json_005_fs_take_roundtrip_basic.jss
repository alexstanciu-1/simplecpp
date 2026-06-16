function run(): void {
    let file: string = "sample_strict_fs_json.txt";
    let err: error;
    let written: int = 0;
    if (!take(written, err, fs.put(file, "{\"name\":\"alex\",\"count\":2}\n"))) {
        print("write_error\n");
        return;
    }

    let data: string = "";
    if (!take(data, err, fs.get(file))) {
        print("read_error\n");
        return;
    }

    let decoded: dynamic = json.decode(data);
    print(written, "\n");
    print(strlen(data), "\n");
    print(decoded["name"], "\n");
    print(decoded["count"], "\n");

    if (!fs.remove(file)) {
        print("remove_error\n");
    }
}

run();

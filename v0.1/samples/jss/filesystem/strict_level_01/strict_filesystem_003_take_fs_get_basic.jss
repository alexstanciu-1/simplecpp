function load(path: string): void {
    let text: string = "";
    let err: error;
    if (!take(text, err, fs.get(path))) {
        return;
    }
    print(text, "\n");
}

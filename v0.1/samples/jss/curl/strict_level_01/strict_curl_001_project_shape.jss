let root: string = "strict_curl_root";
let path: string = root + "/payload.bin";
let payload: string = "";
let written: int = 0;
let real: string = "";
let err: error;
let ok: bool = false;
let ch: curl_handle;
let resp: curl_response;

if (!fs.mkdir(root)) {
    print("MKDIR_FAIL\n");
} else {
    if (!take(payload, hex2bin("4100420A"))) {
        print("HEX_FAIL\n");
    } else if (!take(written, err, fs.put(path, payload))) {
        print("WRITE_FAIL\n");
    } else if (!take(real, err, fs.realpath(path))) {
        print("REAL_FAIL\n");
    } else {
        print(written, "\n");

        let url: string = "file://" + real;
        if (!take(ch, err, curl_init(url))) {
            print("INIT_FAIL\n");
        } else {
            let headers: vector<string> = [];
            headers.push("X-Test: strict-curl");

            print(take(ok, err, curl_setopt(ch, CURLOPT_TIMEOUT, 5)) ? "T\n" : "F\n");
            print(take(ok, err, curl_setopt(ch, CURLOPT_FOLLOWLOCATION, true)) ? "T\n" : "F\n");
            print(take(ok, err, curl_setopt(ch, CURLOPT_USERAGENT, "simplecpp-strict-curl/1.0")) ? "T\n" : "F\n");
            print(take(ok, err, curl_setopt(ch, CURLOPT_HTTPHEADER, headers)) ? "T\n" : "F\n");

            if (!take(resp, err, curl_exec(ch))) {
                print("EXEC_FAIL\n");
                print(curl_errno(ch), "\n");
                print(curl_error(ch), "\n");
            } else {
                print(resp.status_code, "\n");
                print(fs.basename(resp.effective_url), "\n");
                print(bin2hex(resp.body), "\n");
                print(strlen(resp.body), "\n");
                print(curl_errno(ch), "\n");
                print(curl_error(ch), "\n");
            }

            print(take(ok, err, curl_close(ch)) ? "T\n" : "F\n");
        }
    }

    print(fs.remove(path) ? "U\n" : "u\n");
    print(fs.rmdir(root) ? "D\n" : "d\n");
}

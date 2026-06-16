let err: error;
let text: string = "seed";
print(take(text, err, fs.get("missing_strict_sample.txt")) ? "T\n" : "F\n");
print(text, "\n");

print(take(text, hex2bin("4142")) ? "T\n" : "F\n");
print(text, "\n");

print(take(text, hex2bin("4")) ? "T\n" : "F\n");
print(text, "\n");

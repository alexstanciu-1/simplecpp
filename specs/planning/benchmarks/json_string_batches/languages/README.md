# String-vector decode across languages

Doc Status: planning

All seven decoders ran on CPU 7 of dev002 (Ryzen 9 5950X). Each received the same compact JSON object with a single array named `value`. The baseline is Simple C++ commit f66b7b943. The grouped and fused implementations are the two isolated prototypes from the [batching study](../README.md). The C++ driver for nlohmann/json 3.11.3 used Clang 18.1.3, C++23, libc++ and O3. Runtime versions and individual samples are in the [raw results](dev002.json).

Each decoder checked the number, order and decoded contents of all elements before timing. Each timed iteration decoded the complete input and observed the resulting vector or array length. Simple C++ constructs an owning typed record and checks its schema and duplicate fields. PHP uses associative arrays, Node.js and Python use their ordinary in-memory objects, and nlohmann/json constructs its general-purpose JSON tree. Garbage collection and object lifetimes follow each runtime's normal behavior. These workloads measure decoding and counting, without subsequent traversal of every decoded string.

Rates are decimal MB of input JSON per elapsed second. They use the median time per document over seven rounds with shuffled implementation order. Each process warmed up before timing. Startup, input construction and correctness checks are excluded. Plain and escaped vectors produce byte-identical decoded values. Each escaped element replaces the middle ASCII character with one six-byte Unicode escape, increasing the wire size by five bytes per element. Use within-column comparisons for equal work.

## Results

| Vector | Spelling | Unbatched | Grouped | Fused | PHP | Node.js | Python | nlohmann/json |
|---|---|---:|---:|---:|---:|---:|---:|---:|
| 0x8 | plain | 78.7 | 79.1 | 76.8 | 66.4 | 70.4 | 13.4 | 45.2 |
| 1x8 | plain | 89.8 | 88.2 | 110.9 | 93.9 | 90.3 | 22.8 | 64.2 |
| 1x8 | escaped | 91.0 | 91.2 | 111.1 | 106.8 | 105.9 | 26.1 | 72.8 |
| 3x8 | plain | 114.0 | 110.1 | 172.2 | 139.0 | 143.2 | 42.7 | 82.7 |
| 3x8 | escaped | 121.1 | 117.7 | 170.3 | 157.9 | 165.9 | 49.6 | 99.9 |
| 16x8 | plain | 145.8 | 149.3 | 318.8 | 207.2 | 237.7 | 134.1 | 129.4 |
| 16x8 | escaped | 150.3 | 153.7 | 252.8 | 238.6 | 251.3 | 128.2 | 153.1 |
| 16x129 | plain | 476.8 | 531.7 | 641.3 | 762.5 | 1,707.8 | 737.6 | 266.8 |
| 16x129 | escaped | 272.6 | 291.4 | 319.7 | 612.5 | 1,370.7 | 556.1 | 261.8 |
| 16x4096 | plain | 849.0 | 1,041.0 | 1,056.3 | 1,079.7 | 3,267.7 | 1,411.7 | 336.4 |
| 16x4096 | escaped | 378.6 | 414.0 | 417.6 | 846.2 | 2,110.8 | 1,047.8 | 334.5 |
| 128x8 | plain | 162.2 | 171.3 | 458.1 | 247.7 | 305.5 | 308.9 | 135.2 |
| 128x8 | escaped | 165.3 | 168.1 | 302.6 | 283.3 | 322.9 | 214.7 | 162.5 |
| 128x129 | plain | 487.8 | 538.8 | 676.6 | 801.8 | 2,082.1 | 1,066.0 | 278.5 |
| 128x129 | escaped | 279.4 | 296.4 | 328.1 | 633.9 | 1,605.6 | 708.0 | 272.9 |

## Reproduction

Build the Simple C++ variants as described in the [parent study](../README.md). In this directory compile the C++ comparison driver, then run the matrix on dev002:

```sh
clang++ -O3 -DNDEBUG -std=c++23 -stdlib=libc++ -I "$nlohmann_include" bench.cpp -o nlohmann
taskset -c 7 python3 compare.py "$bin" > dev002.json
```

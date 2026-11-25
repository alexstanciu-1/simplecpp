# String benchmark tables

Doc Status: supporting

All values below are median microseconds per operation across [14] batches per case. Lower is faster. These include each language’s loop/result-consumption costs; see the methodology and caveats in the accompanying report.

## ASCII, nominal 64 input bytes

| Implementation | Replace absent | Replace dense | Replace growing | Compare equal | Compare early | Compare late | Length-loop control |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| Simple C++ / my-try default | 0.105 | 0.213 | 0.277 | 0.094 | 0.080 | 0.095 | 0.049 |
| my-try ordinary lowering | 0.050 | 0.173 | 0.296 | 0.059 | 0.025 | 0.079 | 0.015 |
| Direct runtime helper | 0.032 | 0.143 | 0.191 | 0.024 | 0.005 | 0.024 | 0.001 |
| Runtime + argument copies | 0.052 | 0.160 | 0.214 | 0.049 | 0.031 | 0.058 | 0.015 |
| Native C++ find/append + compare | 0.035 | 0.142 | 0.197 | 0.005 | 0.006 | 0.007 | 0.001 |
| C++ reserved output | 0.022 | 0.099 | 0.163 | 0.005 | 0.005 | 0.007 | 0.001 |
| PHP | 0.042 | 0.206 | 0.206 | 0.062 | 0.067 | 0.071 | 0.008 |
| Rust | 0.070 | 0.255 | 0.354 | 0.005 | 0.005 | 0.006 | 0.001 |
| Python str | 0.093 | 0.238 | 0.260 | 0.104 | 0.071 | 0.079 | 0.046 |
| Python bytes | 0.099 | 0.236 | 0.226 | 0.088 | 0.065 | 0.084 | 0.052 |
| JavaScript | 0.087 | 0.534 | 0.547 | 0.134 | 0.050 | 0.171 | 0.010 |
| JS + UTF-8 output materialization | 0.497 | 1.017 | 1.072 | 0.141 | 0.080 | 0.146 | 0.006 |
| Java | 0.015 | 0.119 | 0.144 | 0.006 | 0.002 | 0.008 | 0.001 |

## ASCII, nominal 4096 input bytes

| Implementation | Replace absent | Replace dense | Replace growing | Compare equal | Compare early | Compare late | Length-loop control |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| Simple C++ / my-try default | 0.246 | 6.461 | 8.333 | 1.285 | 0.190 | 1.384 | 0.103 |
| my-try ordinary lowering | 0.163 | 6.320 | 7.726 | 2.244 | 0.119 | 2.693 | 0.061 |
| Direct runtime helper | 0.116 | 5.850 | 7.942 | 1.081 | 0.006 | 1.288 | 0.001 |
| Runtime + argument copies | 0.191 | 5.843 | 7.835 | 1.394 | 0.147 | 1.299 | 0.070 |
| Native C++ find/append + compare | 0.101 | 5.640 | 7.680 | 0.049 | 0.005 | 0.055 | 0.001 |
| C++ reserved output | 0.098 | 5.252 | 7.021 | 0.049 | 0.005 | 0.053 | 0.001 |
| PHP | 1.111 | 9.827 | 9.306 | 0.095 | 0.064 | 0.097 | 0.008 |
| Rust | 0.533 | 6.885 | 6.578 | 0.047 | 0.005 | 0.046 | 0.001 |
| Python str | 0.423 | 7.413 | 7.182 | 0.173 | 0.069 | 0.177 | 0.050 |
| Python bytes | 0.422 | 7.046 | 7.121 | 0.155 | 0.065 | 0.152 | 0.051 |
| JavaScript | 0.126 | 27.441 | 27.396 | 3.757 | 0.050 | 4.011 | 0.006 |
| JS + UTF-8 output materialization | 8.204 | 56.421 | 38.216 | 4.557 | 0.050 | 4.016 | 0.006 |
| Java | 0.358 | 7.349 | 9.501 | 0.072 | 0.002 | 0.078 | 0.001 |

## ASCII, nominal 65536 input bytes

| Implementation | Replace absent | Replace dense | Replace growing | Compare equal | Compare early | Compare late | Length-loop control |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| Simple C++ / my-try default | 3.527 | 97.961 | 339.369 | 19.366 | 2.321 | 25.815 | 1.205 |
| my-try ordinary lowering | 2.709 | 91.529 | 380.346 | 36.128 | 2.198 | 45.969 | 1.093 |
| Direct runtime helper | 1.543 | 88.014 | 298.845 | 17.114 | 0.005 | 20.849 | 0.001 |
| Runtime + argument copies | 2.809 | 90.719 | 380.116 | 19.378 | 2.298 | 20.992 | 1.097 |
| Native C++ find/append + compare | 1.917 | 87.084 | 290.357 | 1.097 | 0.005 | 1.268 | 0.001 |
| C++ reserved output | 1.498 | 82.080 | 295.102 | 1.060 | 0.005 | 1.082 | 0.001 |
| PHP | 17.033 | 157.037 | 151.902 | 1.118 | 0.063 | 1.176 | 0.008 |
| Rust | 6.939 | 99.533 | 138.591 | 1.226 | 0.005 | 1.069 | 0.001 |
| Python str | 19.448 | 131.470 | 120.759 | 2.449 | 0.071 | 2.914 | 0.051 |
| Python bytes | 18.945 | 137.805 | 127.400 | 2.341 | 0.064 | 2.230 | 0.051 |
| JavaScript | 1.180 | 487.103 | 502.244 | 57.995 | 0.050 | 60.453 | 0.006 |
| JS + UTF-8 output materialization | 85.995 | 737.775 | 872.698 | 76.802 | 0.056 | 61.152 | 0.007 |
| Java | 5.516 | 130.358 | 170.836 | 1.336 | 0.002 | 1.120 | 0.001 |

## UTF8, nominal 64 input bytes

| Implementation | Replace absent | Replace dense | Replace growing | Compare equal | Compare early | Compare late | Length-loop control |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| Simple C++ / my-try default | 0.105 | 0.210 | 0.322 | 0.108 | 0.077 | 0.090 | 0.042 |
| my-try ordinary lowering | 0.050 | 0.161 | 0.212 | 0.063 | 0.034 | 0.067 | 0.012 |
| Direct runtime helper | 0.032 | 0.136 | 0.178 | 0.025 | 0.005 | 0.025 | 0.001 |
| Runtime + argument copies | 0.050 | 0.149 | 0.198 | 0.048 | 0.030 | 0.055 | 0.015 |
| Native C++ find/append + compare | 0.032 | 0.142 | 0.178 | 0.006 | 0.005 | 0.007 | 0.001 |
| C++ reserved output | 0.023 | 0.099 | 0.130 | 0.006 | 0.006 | 0.007 | 0.001 |
| PHP | 0.042 | 0.192 | 0.188 | 0.064 | 0.064 | 0.064 | 0.008 |
| Rust | 0.074 | 0.236 | 0.251 | 0.005 | 0.005 | 0.006 | 0.001 |
| Python str | 0.124 | 0.145 | 0.310 | 0.089 | 0.084 | 0.079 | 0.043 |
| Python bytes | 0.099 | 0.232 | 0.287 | 0.082 | 0.063 | 0.071 | 0.041 |
| JavaScript | 0.091 | 0.708 | 0.536 | 0.103 | 0.054 | 0.111 | 0.006 |
| JS + UTF-8 output materialization | 0.364 | 1.096 | 1.024 | 0.103 | 0.050 | 0.151 | 0.007 |
| Java | 0.013 | 0.030 | 0.125 | 0.006 | 0.002 | 0.008 | 0.001 |

## UTF8, nominal 4096 input bytes

| Implementation | Replace absent | Replace dense | Replace growing | Compare equal | Compare early | Compare late | Length-loop control |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| Simple C++ / my-try default | 0.235 | 5.895 | 10.697 | 1.490 | 0.217 | 1.574 | 0.095 |
| my-try ordinary lowering | 0.163 | 7.175 | 6.668 | 2.271 | 0.136 | 3.011 | 0.058 |
| Direct runtime helper | 0.119 | 5.277 | 6.534 | 1.082 | 0.005 | 1.378 | 0.001 |
| Runtime + argument copies | 0.192 | 5.722 | 6.401 | 1.222 | 0.146 | 1.500 | 0.062 |
| Native C++ find/append + compare | 0.101 | 5.184 | 6.283 | 0.049 | 0.005 | 0.050 | 0.001 |
| C++ reserved output | 0.096 | 4.618 | 5.542 | 0.050 | 0.005 | 0.050 | 0.001 |
| PHP | 1.113 | 9.719 | 8.406 | 0.100 | 0.063 | 0.110 | 0.009 |
| Rust | 0.525 | 6.549 | 6.694 | 0.048 | 0.005 | 0.047 | 0.001 |
| Python str | 0.488 | 1.541 | 9.455 | 0.180 | 0.077 | 0.196 | 0.047 |
| Python bytes | 0.463 | 7.011 | 7.278 | 0.162 | 0.076 | 0.134 | 0.091 |
| JavaScript | 0.103 | 25.145 | 30.451 | 1.842 | 0.056 | 1.849 | 0.007 |
| JS + UTF-8 output materialization | 6.352 | 35.727 | 43.186 | 1.950 | 0.051 | 1.841 | 0.006 |
| Java | 0.318 | 1.515 | 9.141 | 0.069 | 0.002 | 0.073 | 0.001 |

## UTF8, nominal 65536 input bytes

| Implementation | Replace absent | Replace dense | Replace growing | Compare equal | Compare early | Compare late | Length-loop control |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| Simple C++ / my-try default | 3.099 | 102.776 | 315.398 | 26.272 | 2.758 | 21.298 | 1.283 |
| my-try ordinary lowering | 2.716 | 83.532 | 409.582 | 37.592 | 2.266 | 41.617 | 1.207 |
| Direct runtime helper | 1.543 | 82.656 | 275.375 | 16.909 | 0.005 | 20.319 | 0.001 |
| Runtime + argument copies | 2.647 | 80.714 | 329.218 | 19.510 | 2.268 | 19.948 | 1.097 |
| Native C++ find/append + compare | 1.519 | 85.515 | 279.005 | 1.071 | 0.005 | 1.072 | 0.001 |
| C++ reserved output | 1.862 | 73.948 | 95.951 | 1.064 | 0.005 | 1.086 | 0.001 |
| PHP | 17.407 | 140.399 | 142.045 | 1.129 | 0.066 | 1.191 | 0.009 |
| Rust | 8.869 | 100.329 | 121.134 | 1.061 | 0.005 | 1.134 | 0.001 |
| Python str | 5.294 | 20.686 | 176.114 | 2.927 | 0.085 | 3.165 | 0.056 |
| Python bytes | 19.227 | 137.420 | 136.142 | 3.931 | 0.066 | 2.200 | 0.054 |
| JavaScript | 0.572 | 429.545 | 465.099 | 26.677 | 0.054 | 28.056 | 0.006 |
| JS + UTF-8 output materialization | 81.111 | 863.643 | 960.608 | 26.303 | 0.051 | 32.431 | 0.006 |
| Java | 4.965 | 25.752 | 152.915 | 1.051 | 0.002 | 0.998 | 0.001 |


# String benchmark tables

Doc Status: supporting

All values below are median microseconds per operation across [14] batches per case. Lower is faster. These include each language’s loop/result-consumption costs; see the methodology and caveats in the accompanying report.

## ASCII, nominal 64 input bytes

| Implementation | Replace absent | Replace dense | Replace growing | Compare equal | Compare early | Compare late | Length-loop control |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| Simple C++ / my-try default | 0.082 | 0.208 | 0.279 | 0.079 | 0.070 | 0.087 | 0.054 |
| my-try ordinary lowering | 0.052 | 0.184 | 0.223 | 0.031 | 0.027 | 0.034 | 0.012 |
| Direct runtime helper | 0.049 | 0.216 | 0.221 | 0.016 | 0.011 | 0.007 | 0.002 |
| Runtime + argument copies | 0.053 | 0.162 | 0.220 | 0.032 | 0.032 | 0.044 | 0.016 |
| Native C++ find/append + compare | 0.031 | 0.143 | 0.195 | 0.006 | 0.005 | 0.012 | 0.002 |
| C++ reserved output | 0.025 | 0.099 | 0.161 | 0.006 | 0.005 | 0.007 | 0.002 |
| PHP | 0.044 | 0.241 | 0.238 | 0.063 | 0.065 | 0.085 | 0.008 |
| Rust | 0.071 | 0.257 | 0.358 | 0.005 | 0.005 | 0.012 | 0.001 |
| Python str | 0.091 | 0.230 | 0.242 | 0.078 | 0.068 | 0.070 | 0.042 |
| Python bytes | 0.097 | 0.244 | 0.226 | 0.082 | 0.063 | 0.069 | 0.040 |
| JavaScript | 0.087 | 0.534 | 0.522 | 0.164 | 0.050 | 0.145 | 0.006 |
| JS + UTF-8 output materialization | 0.337 | 0.886 | 0.938 | 0.134 | 0.051 | 0.147 | 0.007 |
| Java | 0.014 | 0.117 | 0.158 | 0.006 | 0.002 | 0.008 | 0.001 |

## ASCII, nominal 4096 input bytes

| Implementation | Replace absent | Replace dense | Replace growing | Compare equal | Compare early | Compare late | Length-loop control |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| Simple C++ / my-try default | 0.293 | 7.078 | 11.737 | 0.244 | 0.190 | 0.279 | 0.097 |
| my-try ordinary lowering | 0.166 | 6.142 | 7.207 | 0.155 | 0.125 | 0.158 | 0.061 |
| Direct runtime helper | 0.122 | 6.233 | 8.129 | 0.089 | 0.006 | 0.059 | 0.001 |
| Runtime + argument copies | 0.190 | 5.884 | 10.107 | 0.199 | 0.174 | 0.211 | 0.085 |
| Native C++ find/append + compare | 0.103 | 5.968 | 14.131 | 0.049 | 0.009 | 0.077 | 0.002 |
| C++ reserved output | 0.108 | 5.229 | 6.870 | 0.055 | 0.005 | 0.068 | 0.001 |
| PHP | 1.161 | 11.560 | 10.084 | 0.097 | 0.075 | 0.097 | 0.008 |
| Rust | 0.553 | 6.699 | 12.658 | 0.048 | 0.008 | 0.061 | 0.001 |
| Python str | 0.417 | 7.246 | 7.147 | 0.146 | 0.069 | 0.157 | 0.052 |
| Python bytes | 0.421 | 7.211 | 6.966 | 0.145 | 0.063 | 0.152 | 0.049 |
| JavaScript | 0.127 | 27.272 | 26.781 | 3.734 | 0.050 | 3.908 | 0.006 |
| JS + UTF-8 output materialization | 5.979 | 42.264 | 37.608 | 3.729 | 0.050 | 3.934 | 0.006 |
| Java | 0.342 | 7.573 | 9.064 | 0.070 | 0.002 | 0.077 | 0.001 |

## ASCII, nominal 65536 input bytes

| Implementation | Replace absent | Replace dense | Replace growing | Compare equal | Compare early | Compare late | Length-loop control |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| Simple C++ / my-try default | 2.667 | 90.064 | 344.192 | 3.633 | 2.331 | 4.438 | 1.129 |
| my-try ordinary lowering | 2.734 | 94.018 | 370.978 | 3.375 | 2.306 | 3.132 | 1.088 |
| Direct runtime helper | 2.124 | 134.440 | 292.923 | 1.667 | 0.009 | 1.078 | 0.002 |
| Runtime + argument copies | 2.638 | 93.273 | 353.777 | 3.143 | 2.396 | 3.399 | 1.100 |
| Native C++ find/append + compare | 1.513 | 88.489 | 568.603 | 1.066 | 0.006 | 1.756 | 0.001 |
| C++ reserved output | 1.603 | 83.042 | 280.854 | 1.088 | 0.005 | 1.072 | 0.002 |
| PHP | 17.312 | 163.325 | 151.237 | 1.513 | 0.062 | 1.154 | 0.010 |
| Rust | 7.379 | 102.808 | 213.914 | 1.060 | 0.005 | 1.265 | 0.001 |
| Python str | 18.818 | 140.278 | 122.356 | 2.337 | 0.068 | 2.232 | 0.051 |
| Python bytes | 18.768 | 120.789 | 119.501 | 2.235 | 0.063 | 2.219 | 0.052 |
| JavaScript | 1.155 | 488.848 | 494.525 | 58.180 | 0.051 | 58.924 | 0.006 |
| JS + UTF-8 output materialization | 76.405 | 542.731 | 902.903 | 57.978 | 0.050 | 58.570 | 0.006 |
| Java | 5.972 | 118.839 | 167.028 | 1.071 | 0.002 | 1.088 | 0.001 |

## UTF8, nominal 64 input bytes

| Implementation | Replace absent | Replace dense | Replace growing | Compare equal | Compare early | Compare late | Length-loop control |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| Simple C++ / my-try default | 0.106 | 0.264 | 0.257 | 0.111 | 0.110 | 0.073 | 0.042 |
| my-try ordinary lowering | 0.052 | 0.169 | 0.214 | 0.028 | 0.031 | 0.041 | 0.012 |
| Direct runtime helper | 0.033 | 0.136 | 0.191 | 0.007 | 0.011 | 0.011 | 0.002 |
| Runtime + argument copies | 0.057 | 0.185 | 0.255 | 0.033 | 0.047 | 0.036 | 0.016 |
| Native C++ find/append + compare | 0.037 | 0.245 | 0.300 | 0.006 | 0.010 | 0.007 | 0.001 |
| C++ reserved output | 0.032 | 0.096 | 0.130 | 0.009 | 0.011 | 0.007 | 0.001 |
| PHP | 0.045 | 0.191 | 0.187 | 0.076 | 0.066 | 0.067 | 0.010 |
| Rust | 0.080 | 0.445 | 0.434 | 0.005 | 0.008 | 0.006 | 0.001 |
| Python str | 0.125 | 0.141 | 0.311 | 0.086 | 0.078 | 0.079 | 0.040 |
| Python bytes | 0.098 | 0.240 | 0.255 | 0.081 | 0.063 | 0.069 | 0.046 |
| JavaScript | 0.086 | 0.651 | 0.523 | 0.102 | 0.050 | 0.107 | 0.006 |
| JS + UTF-8 output materialization | 0.348 | 1.057 | 1.033 | 0.104 | 0.051 | 0.106 | 0.006 |
| Java | 0.013 | 0.029 | 0.113 | 0.006 | 0.002 | 0.008 | 0.001 |

## UTF8, nominal 4096 input bytes

| Implementation | Replace absent | Replace dense | Replace growing | Compare equal | Compare early | Compare late | Length-loop control |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| Simple C++ / my-try default | 0.231 | 5.394 | 6.883 | 0.303 | 0.254 | 0.269 | 0.083 |
| my-try ordinary lowering | 0.165 | 5.394 | 6.479 | 0.155 | 0.116 | 0.157 | 0.055 |
| Direct runtime helper | 0.191 | 5.180 | 6.495 | 0.056 | 0.007 | 0.050 | 0.001 |
| Runtime + argument copies | 0.199 | 5.790 | 8.215 | 0.203 | 0.164 | 0.207 | 0.107 |
| Native C++ find/append + compare | 0.103 | 8.707 | 12.156 | 0.048 | 0.009 | 0.080 | 0.002 |
| C++ reserved output | 0.095 | 4.658 | 5.926 | 0.050 | 0.009 | 0.049 | 0.001 |
| PHP | 1.328 | 9.121 | 8.174 | 0.101 | 0.069 | 0.107 | 0.008 |
| Rust | 0.545 | 14.602 | 12.996 | 0.047 | 0.007 | 0.091 | 0.001 |
| Python str | 0.441 | 1.413 | 8.737 | 0.165 | 0.075 | 0.160 | 0.049 |
| Python bytes | 0.495 | 6.860 | 7.313 | 0.164 | 0.063 | 0.133 | 0.049 |
| JavaScript | 0.103 | 24.616 | 24.551 | 1.739 | 0.050 | 1.810 | 0.007 |
| JS + UTF-8 output materialization | 9.796 | 35.647 | 43.824 | 1.811 | 0.051 | 1.842 | 0.006 |
| Java | 0.304 | 1.511 | 9.934 | 0.067 | 0.002 | 0.070 | 0.001 |

## UTF8, nominal 65536 input bytes

| Implementation | Replace absent | Replace dense | Replace growing | Compare equal | Compare early | Compare late | Length-loop control |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| Simple C++ / my-try default | 2.739 | 79.818 | 215.355 | 3.537 | 2.386 | 3.747 | 1.528 |
| my-try ordinary lowering | 2.807 | 87.296 | 406.597 | 3.127 | 2.451 | 3.130 | 1.093 |
| Direct runtime helper | 1.510 | 116.034 | 272.193 | 1.073 | 0.006 | 1.098 | 0.002 |
| Runtime + argument copies | 2.739 | 79.002 | 398.606 | 4.588 | 2.458 | 3.433 | 1.118 |
| Native C++ find/append + compare | 2.422 | 81.322 | 534.847 | 1.408 | 0.009 | 1.088 | 0.002 |
| C++ reserved output | 1.491 | 78.403 | 93.032 | 1.071 | 0.005 | 1.313 | 0.002 |
| PHP | 17.286 | 169.634 | 133.190 | 1.161 | 0.069 | 1.153 | 0.009 |
| Rust | 14.233 | 112.917 | 211.395 | 1.628 | 0.008 | 1.196 | 0.001 |
| Python str | 4.952 | 20.469 | 147.143 | 2.951 | 0.076 | 2.929 | 0.052 |
| Python bytes | 18.868 | 115.409 | 116.211 | 2.206 | 0.063 | 2.207 | 0.051 |
| JavaScript | 0.576 | 429.249 | 430.054 | 26.093 | 0.050 | 27.159 | 0.006 |
| JS + UTF-8 output materialization | 83.494 | 554.324 | 952.626 | 26.086 | 0.050 | 26.796 | 0.006 |
| Java | 5.257 | 24.629 | 198.894 | 1.189 | 0.004 | 0.962 | 0.001 |


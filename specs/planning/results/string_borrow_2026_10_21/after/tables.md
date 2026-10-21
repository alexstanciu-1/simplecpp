# String benchmark tables

Doc Status: supporting

All values below are median microseconds per operation across [14] batches per case. Lower is faster. These include each language’s loop/result-consumption costs; see the methodology and caveats in the accompanying report.

## ASCII, nominal 64 input bytes

| Implementation | Replace absent | Replace dense | Replace growing | Compare equal | Compare early | Compare late | Length-loop control |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| Simple C++ / my-try default | 0.096 | 0.286 | 0.390 | 0.069 | 0.050 | 0.052 | 0.037 |
| my-try ordinary lowering | 0.030 | 0.151 | 0.209 | 0.003 | 0.003 | 0.003 | 0.000 |
| Direct runtime helper | 0.062 | 0.241 | 0.296 | 0.009 | 0.010 | 0.007 | 0.002 |
| Runtime + argument copies | 0.055 | 0.174 | 0.332 | 0.033 | 0.039 | 0.058 | 0.035 |
| Native C++ find/append + compare | 0.033 | 0.280 | 0.203 | 0.008 | 0.006 | 0.007 | 0.002 |
| C++ reserved output | 0.049 | 0.108 | 0.305 | 0.010 | 0.012 | 0.019 | 0.002 |
| PHP | 0.049 | 0.217 | 0.209 | 0.071 | 0.068 | 0.083 | 0.011 |
| Rust | 0.117 | 0.426 | 0.609 | 0.007 | 0.008 | 0.006 | 0.001 |
| Python str | 0.117 | 0.251 | 0.452 | 0.151 | 0.127 | 0.134 | 0.069 |
| Python bytes | 0.101 | 0.306 | 0.242 | 0.135 | 0.100 | 0.071 | 0.042 |
| JavaScript | 0.143 | 0.773 | 0.922 | 0.221 | 0.100 | 0.243 | 0.008 |
| JS + UTF-8 output materialization | 0.300 | 0.900 | 0.897 | 0.134 | 0.051 | 0.163 | 0.006 |
| Java | 0.014 | 0.111 | 0.139 | 0.006 | 0.002 | 0.009 | 0.001 |

## ASCII, nominal 4096 input bytes

| Implementation | Replace absent | Replace dense | Replace growing | Compare equal | Compare early | Compare late | Length-loop control |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| Simple C++ / my-try default | 0.260 | 9.526 | 12.154 | 0.141 | 0.039 | 0.120 | 0.025 |
| my-try ordinary lowering | 0.116 | 5.908 | 6.946 | 0.049 | 0.005 | 0.045 | 0.000 |
| Direct runtime helper | 0.240 | 11.758 | 7.981 | 0.061 | 0.006 | 0.054 | 0.001 |
| Runtime + argument copies | 0.367 | 13.130 | 9.468 | 0.244 | 0.227 | 0.417 | 0.139 |
| Native C++ find/append + compare | 0.102 | 6.819 | 8.622 | 0.053 | 0.005 | 0.051 | 0.002 |
| C++ reserved output | 0.182 | 11.464 | 13.659 | 0.076 | 0.014 | 0.080 | 0.003 |
| PHP | 1.153 | 9.605 | 13.957 | 0.117 | 0.063 | 0.134 | 0.015 |
| Rust | 0.749 | 7.984 | 10.836 | 0.078 | 0.006 | 0.066 | 0.001 |
| Python str | 0.628 | 11.562 | 14.106 | 0.231 | 0.123 | 0.268 | 0.061 |
| Python bytes | 0.524 | 8.189 | 7.395 | 0.252 | 0.066 | 0.155 | 0.051 |
| JavaScript | 0.239 | 52.535 | 49.183 | 6.683 | 0.100 | 6.326 | 0.012 |
| JS + UTF-8 output materialization | 5.799 | 35.019 | 44.380 | 3.736 | 0.055 | 3.921 | 0.006 |
| Java | 0.343 | 7.475 | 9.452 | 0.070 | 0.002 | 0.075 | 0.001 |

## ASCII, nominal 65536 input bytes

| Implementation | Replace absent | Replace dense | Replace growing | Compare equal | Compare early | Compare late | Length-loop control |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| Simple C++ / my-try default | 2.794 | 162.517 | 323.662 | 1.725 | 0.059 | 1.766 | 0.037 |
| my-try ordinary lowering | 1.601 | 95.359 | 542.291 | 1.194 | 0.003 | 1.078 | 0.000 |
| Direct runtime helper | 2.062 | 169.254 | 296.324 | 1.552 | 0.010 | 1.094 | 0.002 |
| Runtime + argument copies | 4.121 | 106.147 | 798.587 | 3.617 | 2.350 | 4.003 | 1.733 |
| Native C++ find/append + compare | 1.544 | 99.865 | 329.502 | 1.727 | 0.011 | 1.135 | 0.001 |
| C++ reserved output | 2.291 | 114.927 | 787.406 | 1.864 | 0.009 | 2.122 | 0.003 |
| PHP | 19.140 | 169.019 | 258.582 | 1.230 | 0.071 | 1.543 | 0.009 |
| Rust | 9.179 | 170.805 | 134.439 | 1.647 | 0.009 | 1.208 | 0.001 |
| Python str | 26.660 | 178.494 | 215.819 | 3.097 | 0.078 | 2.883 | 0.100 |
| Python bytes | 20.148 | 212.088 | 122.967 | 3.393 | 0.112 | 2.345 | 0.053 |
| JavaScript | 1.601 | 877.127 | 1303.204 | 102.452 | 0.100 | 91.932 | 0.010 |
| JS + UTF-8 output materialization | 77.596 | 573.527 | 968.816 | 59.047 | 0.055 | 60.090 | 0.006 |
| Java | 5.329 | 120.048 | 167.639 | 1.082 | 0.002 | 1.081 | 0.001 |

## UTF8, nominal 64 input bytes

| Implementation | Replace absent | Replace dense | Replace growing | Compare equal | Compare early | Compare late | Length-loop control |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| Simple C++ / my-try default | 0.093 | 0.262 | 0.229 | 0.062 | 0.055 | 0.062 | 0.039 |
| my-try ordinary lowering | 0.031 | 0.132 | 0.307 | 0.003 | 0.003 | 0.004 | 0.000 |
| Direct runtime helper | 0.062 | 0.149 | 0.185 | 0.011 | 0.006 | 0.016 | 0.002 |
| Runtime + argument copies | 0.095 | 0.256 | 0.239 | 0.052 | 0.071 | 0.039 | 0.020 |
| Native C++ find/append + compare | 0.033 | 0.130 | 0.190 | 0.007 | 0.006 | 0.015 | 0.003 |
| C++ reserved output | 0.055 | 0.192 | 0.294 | 0.011 | 0.011 | 0.012 | 0.001 |
| PHP | 0.048 | 0.317 | 0.187 | 0.067 | 0.111 | 0.065 | 0.009 |
| Rust | 0.113 | 0.276 | 0.370 | 0.008 | 0.006 | 0.010 | 0.001 |
| Python str | 0.227 | 0.201 | 0.434 | 0.132 | 0.168 | 0.113 | 0.042 |
| Python bytes | 0.126 | 0.240 | 0.279 | 0.087 | 0.064 | 0.107 | 0.066 |
| JavaScript | 0.121 | 1.315 | 0.987 | 0.182 | 0.083 | 0.195 | 0.006 |
| JS + UTF-8 output materialization | 0.362 | 1.058 | 1.115 | 0.133 | 0.051 | 0.106 | 0.006 |
| Java | 0.012 | 0.028 | 0.113 | 0.007 | 0.002 | 0.008 | 0.001 |

## UTF8, nominal 4096 input bytes

| Implementation | Replace absent | Replace dense | Replace growing | Compare equal | Compare early | Compare late | Length-loop control |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| Simple C++ / my-try default | 0.231 | 9.598 | 7.509 | 0.124 | 0.064 | 0.117 | 0.023 |
| my-try ordinary lowering | 0.098 | 5.279 | 9.264 | 0.044 | 0.003 | 0.046 | 0.000 |
| Direct runtime helper | 0.225 | 5.508 | 7.048 | 0.080 | 0.006 | 0.051 | 0.001 |
| Runtime + argument copies | 0.252 | 9.653 | 11.255 | 0.321 | 0.297 | 0.328 | 0.106 |
| Native C++ find/append + compare | 0.292 | 5.655 | 7.222 | 0.050 | 0.005 | 0.053 | 0.002 |
| C++ reserved output | 0.103 | 9.614 | 13.530 | 0.089 | 0.010 | 0.081 | 0.003 |
| PHP | 1.180 | 12.798 | 11.696 | 0.095 | 0.075 | 0.135 | 0.009 |
| Rust | 1.021 | 6.666 | 6.880 | 0.072 | 0.008 | 0.049 | 0.001 |
| Python str | 0.540 | 2.440 | 16.020 | 0.323 | 0.127 | 0.207 | 0.097 |
| Python bytes | 0.887 | 7.650 | 7.429 | 0.204 | 0.064 | 0.153 | 0.050 |
| JavaScript | 0.125 | 31.083 | 32.637 | 2.025 | 0.105 | 3.118 | 0.008 |
| JS + UTF-8 output materialization | 5.775 | 36.324 | 43.903 | 1.734 | 0.050 | 1.880 | 0.007 |
| Java | 0.308 | 1.532 | 9.801 | 0.067 | 0.003 | 0.070 | 0.001 |

## UTF8, nominal 65536 input bytes

| Implementation | Replace absent | Replace dense | Replace growing | Compare equal | Compare early | Compare late | Length-loop control |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| Simple C++ / my-try default | 1.714 | 153.421 | 223.498 | 1.130 | 0.039 | 1.581 | 0.033 |
| my-try ordinary lowering | 2.612 | 77.361 | 517.651 | 1.609 | 0.005 | 1.093 | 0.000 |
| Direct runtime helper | 1.551 | 153.680 | 281.440 | 1.113 | 0.006 | 1.574 | 0.002 |
| Runtime + argument copies | 4.410 | 89.731 | 550.093 | 5.146 | 3.836 | 6.088 | 1.351 |
| Native C++ find/append + compare | 1.743 | 79.719 | 314.931 | 1.122 | 0.007 | 1.077 | 0.001 |
| C++ reserved output | 2.781 | 124.447 | 267.877 | 2.075 | 0.013 | 1.483 | 0.002 |
| PHP | 23.289 | 152.624 | 170.419 | 1.158 | 0.097 | 1.223 | 0.011 |
| Rust | 7.799 | 207.412 | 137.244 | 1.526 | 0.005 | 1.714 | 0.001 |
| Python str | 6.784 | 35.016 | 240.692 | 4.521 | 0.144 | 4.438 | 0.061 |
| Python bytes | 19.536 | 224.705 | 154.768 | 2.237 | 0.066 | 2.385 | 0.060 |
| JavaScript | 0.960 | 768.954 | 810.443 | 47.308 | 0.099 | 38.953 | 0.012 |
| JS + UTF-8 output materialization | 91.143 | 568.901 | 981.943 | 27.700 | 0.055 | 28.203 | 0.007 |
| Java | 4.691 | 24.554 | 156.863 | 0.971 | 0.002 | 0.971 | 0.001 |


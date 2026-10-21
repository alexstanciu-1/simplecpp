# Bulk comparison: before and after

Doc Status: supporting

Median microseconds/op; speedup is before divided by after. Same corpus and timed source, separate process runs. Full samples and ranges remain in each run’s summary.

## Nominal 64-byte inputs

| Path | Input | Case | Before µs | After µs | Speedup |
| --- | --- | --- | ---: | ---: | ---: |
| runtime | ascii | compare_equal | 0.024 | 0.016 | 1.46× |
| runtime | ascii | compare_late | 0.024 | 0.007 | 3.38× |
| runtime | ascii | compare_early | 0.005 | 0.011 | 0.46× |
| runtime | utf8 | compare_equal | 0.025 | 0.007 | 3.72× |
| runtime | utf8 | compare_late | 0.025 | 0.011 | 2.14× |
| runtime | utf8 | compare_early | 0.005 | 0.011 | 0.45× |
| runtime_snapshot | ascii | compare_equal | 0.049 | 0.032 | 1.51× |
| runtime_snapshot | ascii | compare_late | 0.058 | 0.044 | 1.33× |
| runtime_snapshot | ascii | compare_early | 0.031 | 0.032 | 0.95× |
| runtime_snapshot | utf8 | compare_equal | 0.048 | 0.033 | 1.44× |
| runtime_snapshot | utf8 | compare_late | 0.055 | 0.036 | 1.53× |
| runtime_snapshot | utf8 | compare_early | 0.030 | 0.047 | 0.65× |
| mytry_concrete | ascii | compare_equal | 0.094 | 0.079 | 1.19× |
| mytry_concrete | ascii | compare_late | 0.095 | 0.087 | 1.09× |
| mytry_concrete | ascii | compare_early | 0.080 | 0.070 | 1.15× |
| mytry_concrete | utf8 | compare_equal | 0.108 | 0.111 | 0.97× |
| mytry_concrete | utf8 | compare_late | 0.090 | 0.073 | 1.23× |
| mytry_concrete | utf8 | compare_early | 0.077 | 0.110 | 0.70× |
| mytry_ordinary | ascii | compare_equal | 0.059 | 0.031 | 1.91× |
| mytry_ordinary | ascii | compare_late | 0.079 | 0.034 | 2.29× |
| mytry_ordinary | ascii | compare_early | 0.025 | 0.027 | 0.94× |
| mytry_ordinary | utf8 | compare_equal | 0.063 | 0.028 | 2.24× |
| mytry_ordinary | utf8 | compare_late | 0.067 | 0.041 | 1.64× |
| mytry_ordinary | utf8 | compare_early | 0.034 | 0.031 | 1.12× |
| cpp | ascii | compare_equal | 0.005 | 0.006 | 0.97× |
| cpp | ascii | compare_late | 0.007 | 0.012 | 0.57× |
| cpp | ascii | compare_early | 0.006 | 0.005 | 1.04× |
| cpp | utf8 | compare_equal | 0.006 | 0.006 | 0.89× |
| cpp | utf8 | compare_late | 0.007 | 0.007 | 1.06× |
| cpp | utf8 | compare_early | 0.005 | 0.010 | 0.55× |

## Nominal 4096-byte inputs

| Path | Input | Case | Before µs | After µs | Speedup |
| --- | --- | --- | ---: | ---: | ---: |
| runtime | ascii | compare_equal | 1.081 | 0.089 | 12.16× |
| runtime | ascii | compare_late | 1.288 | 0.059 | 21.67× |
| runtime | ascii | compare_early | 0.006 | 0.006 | 0.96× |
| runtime | utf8 | compare_equal | 1.082 | 0.056 | 19.16× |
| runtime | utf8 | compare_late | 1.378 | 0.050 | 27.79× |
| runtime | utf8 | compare_early | 0.005 | 0.007 | 0.69× |
| runtime_snapshot | ascii | compare_equal | 1.394 | 0.199 | 6.99× |
| runtime_snapshot | ascii | compare_late | 1.299 | 0.211 | 6.14× |
| runtime_snapshot | ascii | compare_early | 0.147 | 0.174 | 0.85× |
| runtime_snapshot | utf8 | compare_equal | 1.222 | 0.203 | 6.02× |
| runtime_snapshot | utf8 | compare_late | 1.500 | 0.207 | 7.24× |
| runtime_snapshot | utf8 | compare_early | 0.146 | 0.164 | 0.89× |
| mytry_concrete | ascii | compare_equal | 1.285 | 0.244 | 5.27× |
| mytry_concrete | ascii | compare_late | 1.384 | 0.279 | 4.95× |
| mytry_concrete | ascii | compare_early | 0.190 | 0.190 | 1.00× |
| mytry_concrete | utf8 | compare_equal | 1.490 | 0.303 | 4.92× |
| mytry_concrete | utf8 | compare_late | 1.574 | 0.269 | 5.85× |
| mytry_concrete | utf8 | compare_early | 0.217 | 0.254 | 0.85× |
| mytry_ordinary | ascii | compare_equal | 2.244 | 0.155 | 14.45× |
| mytry_ordinary | ascii | compare_late | 2.693 | 0.158 | 17.01× |
| mytry_ordinary | ascii | compare_early | 0.119 | 0.125 | 0.95× |
| mytry_ordinary | utf8 | compare_equal | 2.271 | 0.155 | 14.60× |
| mytry_ordinary | utf8 | compare_late | 3.011 | 0.157 | 19.16× |
| mytry_ordinary | utf8 | compare_early | 0.136 | 0.116 | 1.18× |
| cpp | ascii | compare_equal | 0.049 | 0.049 | 1.01× |
| cpp | ascii | compare_late | 0.055 | 0.077 | 0.72× |
| cpp | ascii | compare_early | 0.005 | 0.009 | 0.59× |
| cpp | utf8 | compare_equal | 0.049 | 0.048 | 1.00× |
| cpp | utf8 | compare_late | 0.050 | 0.080 | 0.62× |
| cpp | utf8 | compare_early | 0.005 | 0.009 | 0.57× |

## Nominal 65536-byte inputs

| Path | Input | Case | Before µs | After µs | Speedup |
| --- | --- | --- | ---: | ---: | ---: |
| runtime | ascii | compare_equal | 17.114 | 1.667 | 10.27× |
| runtime | ascii | compare_late | 20.849 | 1.078 | 19.34× |
| runtime | ascii | compare_early | 0.005 | 0.009 | 0.57× |
| runtime | utf8 | compare_equal | 16.909 | 1.073 | 15.76× |
| runtime | utf8 | compare_late | 20.319 | 1.098 | 18.50× |
| runtime | utf8 | compare_early | 0.005 | 0.006 | 0.84× |
| runtime_snapshot | ascii | compare_equal | 19.378 | 3.143 | 6.17× |
| runtime_snapshot | ascii | compare_late | 20.992 | 3.399 | 6.18× |
| runtime_snapshot | ascii | compare_early | 2.298 | 2.396 | 0.96× |
| runtime_snapshot | utf8 | compare_equal | 19.510 | 4.588 | 4.25× |
| runtime_snapshot | utf8 | compare_late | 19.948 | 3.433 | 5.81× |
| runtime_snapshot | utf8 | compare_early | 2.268 | 2.458 | 0.92× |
| mytry_concrete | ascii | compare_equal | 19.366 | 3.633 | 5.33× |
| mytry_concrete | ascii | compare_late | 25.815 | 4.438 | 5.82× |
| mytry_concrete | ascii | compare_early | 2.321 | 2.331 | 1.00× |
| mytry_concrete | utf8 | compare_equal | 26.272 | 3.537 | 7.43× |
| mytry_concrete | utf8 | compare_late | 21.298 | 3.747 | 5.68× |
| mytry_concrete | utf8 | compare_early | 2.758 | 2.386 | 1.16× |
| mytry_ordinary | ascii | compare_equal | 36.128 | 3.375 | 10.71× |
| mytry_ordinary | ascii | compare_late | 45.969 | 3.132 | 14.68× |
| mytry_ordinary | ascii | compare_early | 2.198 | 2.306 | 0.95× |
| mytry_ordinary | utf8 | compare_equal | 37.592 | 3.127 | 12.02× |
| mytry_ordinary | utf8 | compare_late | 41.617 | 3.130 | 13.30× |
| mytry_ordinary | utf8 | compare_early | 2.266 | 2.451 | 0.92× |
| cpp | ascii | compare_equal | 1.097 | 1.066 | 1.03× |
| cpp | ascii | compare_late | 1.268 | 1.756 | 0.72× |
| cpp | ascii | compare_early | 0.005 | 0.006 | 0.92× |
| cpp | utf8 | compare_equal | 1.071 | 1.408 | 0.76× |
| cpp | utf8 | compare_late | 1.072 | 1.088 | 0.99× |
| cpp | utf8 | compare_early | 0.005 | 0.009 | 0.59× |


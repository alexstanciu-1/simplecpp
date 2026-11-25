# Safe string argument borrowing: before and after

Doc Status: supporting

Median microseconds/op; speedup is before divided by after. Same corpus and timed source, separate process runs. Full samples and ranges remain in each run’s summary.

## Nominal 64-byte inputs

| Path | Input | Case | Before µs | After µs | Speedup |
| --- | --- | --- | ---: | ---: | ---: |
| mytry_concrete | ascii | compare_equal | 0.099 | 0.069 | 1.44× |
| mytry_concrete | ascii | compare_late | 0.075 | 0.052 | 1.46× |
| mytry_concrete | ascii | compare_early | 0.076 | 0.050 | 1.53× |
| mytry_concrete | utf8 | compare_equal | 0.080 | 0.062 | 1.29× |
| mytry_concrete | utf8 | compare_late | 0.078 | 0.062 | 1.26× |
| mytry_concrete | utf8 | compare_early | 0.072 | 0.055 | 1.30× |
| mytry_ordinary | ascii | compare_equal | 0.032 | 0.003 | 10.53× |
| mytry_ordinary | ascii | compare_late | 0.038 | 0.003 | 11.08× |
| mytry_ordinary | ascii | compare_early | 0.031 | 0.003 | 12.07× |
| mytry_ordinary | utf8 | compare_equal | 0.053 | 0.003 | 19.75× |
| mytry_ordinary | utf8 | compare_late | 0.045 | 0.004 | 10.27× |
| mytry_ordinary | utf8 | compare_early | 0.032 | 0.003 | 12.43× |

## Nominal 4096-byte inputs

| Path | Input | Case | Before µs | After µs | Speedup |
| --- | --- | --- | ---: | ---: | ---: |
| mytry_concrete | ascii | compare_equal | 0.248 | 0.141 | 1.76× |
| mytry_concrete | ascii | compare_late | 0.210 | 0.120 | 1.75× |
| mytry_concrete | ascii | compare_early | 0.284 | 0.039 | 7.35× |
| mytry_concrete | utf8 | compare_equal | 0.214 | 0.124 | 1.73× |
| mytry_concrete | utf8 | compare_late | 0.205 | 0.117 | 1.75× |
| mytry_concrete | utf8 | compare_early | 0.169 | 0.064 | 2.62× |
| mytry_ordinary | ascii | compare_equal | 0.182 | 0.049 | 3.75× |
| mytry_ordinary | ascii | compare_late | 0.175 | 0.045 | 3.88× |
| mytry_ordinary | ascii | compare_early | 0.117 | 0.005 | 24.78× |
| mytry_ordinary | utf8 | compare_equal | 0.191 | 0.044 | 4.30× |
| mytry_ordinary | utf8 | compare_late | 0.161 | 0.046 | 3.53× |
| mytry_ordinary | utf8 | compare_early | 0.150 | 0.003 | 55.42× |

## Nominal 65536-byte inputs

| Path | Input | Case | Before µs | After µs | Speedup |
| --- | --- | --- | ---: | ---: | ---: |
| mytry_concrete | ascii | compare_equal | 3.873 | 1.725 | 2.25× |
| mytry_concrete | ascii | compare_late | 3.420 | 1.766 | 1.94× |
| mytry_concrete | ascii | compare_early | 2.467 | 0.059 | 41.52× |
| mytry_concrete | utf8 | compare_equal | 3.411 | 1.130 | 3.02× |
| mytry_concrete | utf8 | compare_late | 3.520 | 1.581 | 2.23× |
| mytry_concrete | utf8 | compare_early | 2.502 | 0.039 | 64.24× |
| mytry_ordinary | ascii | compare_equal | 3.667 | 1.194 | 3.07× |
| mytry_ordinary | ascii | compare_late | 3.600 | 1.078 | 3.34× |
| mytry_ordinary | ascii | compare_early | 2.434 | 0.003 | 803.88× |
| mytry_ordinary | utf8 | compare_equal | 3.301 | 1.609 | 2.05× |
| mytry_ordinary | utf8 | compare_late | 4.104 | 1.093 | 3.75× |
| mytry_ordinary | utf8 | compare_early | 2.289 | 0.005 | 493.73× |


# Literal constant batch evidence
Doc Status: planning

22 PHP/native scenarios and 10 host purity assertions pass. Native build one
passed without corrections. Exact hashes in native-pass/summary.json were audited
against final production source. Timings retain the first passing PHP checkpoint.
Workers decode/range-check literals; joins check complete exact provenance and
preserve task ordering without mutating inputs. Expression evaluation, constant
selection/reuse coordination and application joins remain unfinished.

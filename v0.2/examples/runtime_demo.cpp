#include <scpp/short_names.hpp>
#include <scpp/native_bridge.hpp>
#include <cmath>
#include <cstdio>

int main() {
    // Keep float_t local: C math headers also declare a global native float_t.
    using scpp::float_t;
    int_t total{20};
    total += 1;
    float_t ratio = static_cast<float_t>(total) / float64{3.0};
    bool_t ready = total > int64{3};
    null_t absent = null;

    vector_string names;
    names.append("Ada");
    string first = names.at(0);
    names.set(0, "Grace"); // first remains an independent copy.

    vector_int64 numbers;
    numbers.append(total);
    string line = first + string{": "} + static_cast<string>(numbers.at(0))
        + string{"; ratio="} + static_cast<string>(ratio);
    if (ready && absent == null && names.at(0) == string{"Grace"}) {
        std::puts(line.data());
        return 0;
    }
    return 1;
}

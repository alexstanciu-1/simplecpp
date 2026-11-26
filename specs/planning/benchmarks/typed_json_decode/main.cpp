// The driver is built against the compiler-emitted Packet header.
#include <chrono>
#include <iostream>
#include <string>

using App::record_result_Packet;
static unsigned long allocations, allocated_bytes, vector_copies;
#ifdef COUNTERS
extern "C" void* __real__Znwm(std::size_t);
extern "C" void* __wrap__Znwm(std::size_t bytes) {
    ++allocations;
    allocated_bytes += bytes;
    return __real__Znwm(bytes);
}
extern "C" void __real__ZN4scpp12vector_int64C1ERKS0_(void*, const void*);
extern "C" void __wrap__ZN4scpp12vector_int64C1ERKS0_(void* to, const void* from) {
    ++vector_copies;
    __real__ZN4scpp12vector_int64C1ERKS0_(to, from);
}
extern "C" void* __real__ZN4scpp12vector_int64aSERKS0_(void*, const void*);
extern "C" void* __wrap__ZN4scpp12vector_int64aSERKS0_(void* to, const void* from) {
    ++vector_copies;
    return __real__ZN4scpp12vector_int64aSERKS0_(to, from);
}
#endif

std::string array(int count) {
    std::string text = "[";
    for (int i = 0; i < count; ++i) {
        if (i) text += ", ";
        text += std::to_string(i % 127 - 63);
    }
    return text + "]";
}

int main(int argc, char** argv) {
    if (argc == 1) {
        // Compare complete values or complete checked diagnostics, one JSON document per line.
        for (std::string line; std::getline(std::cin, line);) {
            scpp::string input(line.data(), static_cast<scpp::int64>(line.size()));
            auto r = record_result_Packet::from_text(input);
            if (r.has_error()) {
                auto msg = r.error().get_message();
                std::cout.write(msg.data(), msg.size());
            } else {
                std::cout << r.value().version;
                for (auto* v : {&r.value().data, &r.value().extra}) {
                    std::cout << '|';
                    for (scpp::int64 i = 0; i < v->size(); ++i) std::cout << v->at(i) << ',';
                }
            }
            if (r.has_value()) {
                const auto& words = r.value().words;
                std::cout << '|';
                for (scpp::int64 i = 0; i < words.size(); ++i) {
                    auto word = words.at(i);
                    for (auto c : std::string_view(word.data(), word.size())) std::cout << unsigned(static_cast<unsigned char>(c)) << ',';
                    std::cout << '/';
                }
            }
            std::cout << '\n';
        }
        return 0;
    }
    if (argc != 4) return 2;
    int n = std::stoi(argv[1]), m = std::stoi(argv[2]), loops = std::stoi(argv[3]);
    std::string words = "[";
    if (n == -2) {
        for (int i = 0; i < 16; ++i) {
            if (i) words += ",";
            words += "\"" + std::string(m, 'x') + "\\u0061\"" ;
        }
    }
    words += "]";
    const auto text = n == -2 ? "{\"words\":" + words + "}" : n < 0 ? std::string{"{\"version\":7}"} :
        "{\"extra\":" + array(m) + ",\"version\":7,\"data\":" + array(n) + "}";
    const scpp::string input(text.data(), static_cast<scpp::int64>(text.size()));
    unsigned long sum = 0;
    auto start = std::chrono::steady_clock::now();
    allocations = allocated_bytes = vector_copies = 0;
    for (int i = 0; i < loops; ++i) {
        auto result = record_result_Packet::from_text(input);
        const auto& p = result.value();
        sum += p.version + p.data.size() + p.extra.size();
        if (n > 0) sum += p.data.at(n - 1) + 64;
        if (n == -2) sum += p.words.size();
        if (n >= 0 && m > 0) sum += p.extra.at(0) + 64;
    }
    const auto ns = std::chrono::duration_cast<std::chrono::nanoseconds>(std::chrono::steady_clock::now() - start).count();
    std::cout << text.size() << ' ' << double(ns) / loops << ' ' << allocations / loops
              << ' ' << allocated_bytes / loops << ' ' << vector_copies / loops << ' ' << sum << '\n';
}

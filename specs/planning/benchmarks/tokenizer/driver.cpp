#include "adapter.hpp"
#include <chrono>
#include <fstream>
#include <iostream>
#include <sstream>
#include <iterator>
#include <string_view>
#ifdef COUNT
extern std::size_t calls, requested;
#endif
static auto input(const char* path) {
    std::ifstream file(path, std::ios::binary);
    if (!file) throw std::runtime_error(path);
    std::string bytes{std::istreambuf_iterator<char>(file), {}};
    return make_input(bytes);
}
static std::string dump(const Result& r) {
    std::ostringstream out;
    if (r.error >= 0) {
        if (r.tokens.size()) throw std::runtime_error("partial publication");
        out << "error " << r.error << ' ' << std::string_view(r.reason.data(), r.reason.size()) << '\n';
    }
    for (long i = 0; i < long(r.tokens.size()); ++i) {
        auto t = r.tokens.at(i);
        out << t.offset << ' ' << t.length << ' ' << t.kind << ' '
            << std::string_view(r.source.data()+t.offset, t.length) << '\n';
    }
    return out.str();
}
int main(int argc, char** argv) {
    auto text = input(argv[2]);
    std::string_view mode(argv[1]);
    if (mode == "dump") { std::cout << dump(run(text,argc>3 ? run(input(argv[3]),0).tokens.size() : 0)); return 0; }
    if (mode == "edits") {
        const auto sibling = run(text,0);
        const auto original = dump(sibling);
        const auto previous = run(input(argv[3]),0);
        const auto previous_dump = dump(previous);
        auto last_count = sibling.tokens.size();
        for (int i=3;i<argc;++i) {
            auto result = run(input(argv[i]),last_count);
            if (result.error < 0) last_count = result.tokens.size();
            std::cout << dump(result) << "|\n";
        }
        return dump(sibling) == original && dump(previous) == previous_dump ? 0 : 1;
    }
    auto warm = run(text,0);
    const auto n = warm.tokens.size();
    const auto hint = argc > 3 ? run(input(argv[3]),0).tokens.size() : 0;
#ifdef COUNT
    calls = requested = 0;
    auto measured = run(text,hint);
    auto c=calls, b=requested;
    if (dump(warm)!=dump(measured)) return 2;
    std::cout << c << ' ' << b << '\n';
#else
    using clock = std::chrono::steady_clock;
    for (int round=0;round<9;++round) {
        const auto start=clock::now();
        for (int j=0;j<128;++j) {
            auto value=run(text,hint);
            if (value.tokens.size()!=n || value.error>=0) return 3;
        }
        std::cout << std::chrono::duration<double>(clock::now()-start).count()/128 << '\n';
    }
#endif
}

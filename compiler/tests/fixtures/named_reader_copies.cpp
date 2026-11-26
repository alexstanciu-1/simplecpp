// Observe the actual source call sites. The effectful keys exercise both counters.
#include <scpp/core/scalars.hpp>
#include <cassert>
#include <string_view>

namespace App {
scpp::int64 namedReads_();
scpp::int64 defaultReads_();
scpp::int64 orderedReads_();
scpp::int64 forwardReads_();
scpp::int64 positionalKeyProbe_();
scpp::int64 reversedKeyProbe_();
}

static int vector_copies;
static int hash_copies;

extern "C" void __real__ZN4scpp12vector_int64C1ERKS0_(void*, const void*);
extern "C" void __wrap__ZN4scpp12vector_int64C1ERKS0_(void* dest, const void* source) {
    ++vector_copies;
    __real__ZN4scpp12vector_int64C1ERKS0_(dest, source);
}
extern "C" void __real__ZN4scpp16hash_int64_int64C1ERKS0_(void*, const void*);
extern "C" void __wrap__ZN4scpp16hash_int64_int64C1ERKS0_(void* dest, const void* source) {
    ++hash_copies;
    __real__ZN4scpp16hash_int64_int64C1ERKS0_(dest, source);
}

int main(int argc, char** argv) {
    assert(argc == 2);
    const bool writer = std::string_view(argv[1]) == "writer";
    assert(App::namedReads_() == 24);
    assert(vector_copies == 0 && hash_copies == 0);
    assert(App::defaultReads_() == 5577);
    assert(vector_copies == 0 && hash_copies == 0);
    assert(App::orderedReads_() == 8);
    assert(vector_copies == 2 && hash_copies == 2);
    vector_copies = hash_copies = 0;
    assert(App::forwardReads_() == (writer ? 969857 : 668857));
    const int expected = writer ? 2 : 1;
    assert(vector_copies == expected && hash_copies == expected);

    const bool reference_key = std::string_view(argv[1]) == "reference-key";
    const int key_copies = reference_key ? 2 : 1;
    const auto check_key = [&](auto probe) {
        vector_copies = hash_copies = 0;
        assert(probe() == (reference_key ? 681 : 680));
        assert(vector_copies == key_copies && hash_copies == key_copies);
    };
    check_key(App::positionalKeyProbe_);
    check_key(App::reversedKeyProbe_);
}

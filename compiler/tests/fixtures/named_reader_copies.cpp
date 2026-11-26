// Observe the actual source call sites. The effectful keys exercise both counters.
#include <scpp/core/scalars.hpp>
#include <cassert>
#include <string_view>

namespace App {
scpp::int64 explicitPackReads_();
scpp::int64 unpackedPackReads_();
scpp::int64 emptyPackReads_();
scpp::int64 scalarReads_();
scpp::int64 convertedKeyReads_();
scpp::int64 namedReads_();
scpp::int64 defaultReads_();
scpp::int64 orderedReads_();
scpp::int64 forwardReads_();
scpp::int64 readonlyForwardReads_();
scpp::int64 mutableForwardReads_();
scpp::int64 positionalKeyProbe_();
scpp::int64 reversedKeyProbe_();
}

static int vector_copies;
static int hash_copies;
static int bool_vector_copies;
static int float_vector_copies;

extern "C" void __real__ZN4scpp13vector_bool_tC1ERKS0_(void*, const void*);
extern "C" void __wrap__ZN4scpp13vector_bool_tC1ERKS0_(void* dest, const void* source) {
    ++bool_vector_copies;
    __real__ZN4scpp13vector_bool_tC1ERKS0_(dest, source);
}
extern "C" void __real__ZN4scpp14vector_float64C1ERKS0_(void*, const void*);
extern "C" void __wrap__ZN4scpp14vector_float64C1ERKS0_(void* dest, const void* source) {
    ++float_vector_copies;
    __real__ZN4scpp14vector_float64C1ERKS0_(dest, source);
}

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
    assert(App::scalarReads_() == (writer ? 11 : 1111));
    assert(bool_vector_copies == (writer ? 1 : 0));
    assert(float_vector_copies == (writer ? 1 : 0));
    float_vector_copies = 0;
    assert(App::convertedKeyReads_() == 2);
    assert(float_vector_copies == 2);
    assert(App::namedReads_() == 24);
    assert(vector_copies == 0 && hash_copies == 0);
    assert(App::defaultReads_() == 5577);
    assert(vector_copies == 0 && hash_copies == 0);
    assert(App::orderedReads_() == 8);
    assert(vector_copies == 4 && hash_copies == 4);
    vector_copies = hash_copies = 0;
    assert(App::forwardReads_() == (writer ? 969857 : 668857));
    const int expected = writer ? 2 : 1;
    assert(vector_copies == expected && hash_copies == expected);

    vector_copies = hash_copies = 0;
    assert(App::readonlyForwardReads_() == (writer ? 9957 : 5757));
    const int readonly_copies = writer ? 1 : 0;
    assert(vector_copies == readonly_copies && hash_copies == readonly_copies);

    vector_copies = hash_copies = 0;
    assert(App::mutableForwardReads_() == (writer ? 9975 : 6475));
    const int mutable_copies = writer ? 1 : 0;
    assert(vector_copies == mutable_copies && hash_copies == mutable_copies);

    vector_copies = 0;
    assert(App::explicitPackReads_() == (writer ? 96 : 56));
    assert(vector_copies == (writer ? 1 : 0));
    vector_copies = 0;
    assert(App::unpackedPackReads_() == (writer ? 964 : 564));
    assert(vector_copies == (writer ? 2 : 1));
    vector_copies = 0;
    assert(App::emptyPackReads_() == 0);
    assert(vector_copies == 0);

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

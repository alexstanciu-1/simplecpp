// Observe the actual source call sites. The effectful keys exercise both counters.
#include <scpp/core/scalars.hpp>
#include <cassert>
#include <string_view>

namespace App {
scpp::int64 namedReads_();
scpp::int64 defaultReads_();
scpp::int64 orderedReads_();
scpp::int64 forwardReads_();
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
}

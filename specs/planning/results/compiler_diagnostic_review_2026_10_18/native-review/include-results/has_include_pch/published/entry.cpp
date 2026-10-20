#if __has_include("optional.hpp")
static_assert(false, "newly selected invalid code");
#endif
int main() { return 7; }

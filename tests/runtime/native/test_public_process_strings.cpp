#include "public_api_guard.hpp"
#include "scpp/process/getenv.hpp"
#include "scpp/process/shell_exec.hpp"
#include <cstdio>
#include <cstdlib>
#include <new>
#include <stdexcept>
#include <string>

unsigned process_allocations = 0;
void *operator new(std::size_t size)
{
    ++process_allocations;
    if (auto memory = std::malloc(size ? size : 1)) return memory;
    throw std::bad_alloc();
}
void operator delete(void *memory) noexcept { std::free(memory); }
void operator delete(void *memory, std::size_t) noexcept { std::free(memory); }
unsigned native_getenv_allocations(const char *name);
bool verify_pipe_failure_and_cleanup();
#define CHECK(value) do { if (!(value)) { std::fprintf(stderr, "process strings failure %d\n", __LINE__); std::abort(); } } while(false)

static void environment(const char *name, const char *value)
{
#if defined(_WIN32)
    CHECK(_putenv_s(name, value ? value : "") == 0);
#else
    CHECK(value ? setenv(name, value, 1) == 0 : unsetenv(name) == 0);
#endif
}

int main()
{
    using namespace scpp;
    const char *key = "SCPP_PUBLIC_PROCESS_STRING_PROOF";
    const auto old_raw = std::getenv(key);
    const bool had_old = old_raw != nullptr;
    const std::string old = old_raw ? old_raw : "";
    const string name(key);
    environment(key, nullptr);
    const auto absent_before = process_allocations;
    CHECK(scpp::getenv(name).is_false());
    CHECK(process_allocations == absent_before);
#if !defined(_WIN32)
    environment(key, "");
    auto empty_value = scpp::getenv(name);
    CHECK(empty_value.has_value() && empty_value.value().empty());
#endif
    const char *long_value = "a long environment value that owns its bytes independently of the environment";
    environment(key, long_value);
    const auto native_count = native_getenv_allocations(key);
    const auto before = process_allocations;
    auto retained = scpp::getenv(name);
    const auto public_count = process_allocations - before;
    CHECK(public_count == native_count && retained.has_value());
    environment(key, "replacement");
    CHECK(retained.value().equals(long_value));
    const char invalid[] = "SCPP_PUBLIC_PROCESS_STRING_PROOF\0ignored";
    bool rejected = false;
    try { (void)scpp::getenv(string(invalid, uint64(sizeof(invalid)-1))); }
    catch (const std::runtime_error &) { rejected = true; }
    CHECK(rejected);
    environment(key, had_old ? old.c_str() : nullptr);

#if !defined(_WIN32)
    auto bytes = scpp::shell_exec(string("printf 'a\\000b'; exit 7"));
    CHECK(bytes.has_value());
    auto text = bytes.value();
    CHECK(text.byte_size() == uint64(3));
    CHECK(text.byte_at(uint64(0)).native_value() == 'a');
    CHECK(text.byte_at(uint64(1)).native_value() == 0);
    CHECK(text.byte_at(uint64(2)).native_value() == 'b');
    auto empty_output = scpp::shell_exec(string("exit 9"));
    CHECK(empty_output.has_value() && empty_output.value().empty());
#endif
    CHECK(verify_pipe_failure_and_cleanup());
    std::printf("PASS: environment states/lifetime/NUL rejection, shell bytes/status, pipe cleanup; native/public getenv allocations=%u/%u\n", native_count, public_count);
}

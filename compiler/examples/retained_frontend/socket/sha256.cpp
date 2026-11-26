#include "frontend_host.hpp"

#include <openssl/evp.h>

#include <array>
#include <cstdio>
#include <cstring>
#include <memory>
#include <stdexcept>
#include <string>

namespace {
class Sha256 {
    std::unique_ptr<EVP_MD_CTX, decltype(&EVP_MD_CTX_free)> context{EVP_MD_CTX_new(), EVP_MD_CTX_free};

public:
    Sha256() {
        if (!context || EVP_DigestInit_ex(context.get(), EVP_sha256(), nullptr) != 1)
            throw std::runtime_error("Cannot initialize SHA-256");
    }

    void update(const void* bytes, std::size_t length) {
        if (EVP_DigestUpdate(context.get(), bytes, length) != 1)
            throw std::runtime_error("Cannot update SHA-256");
    }

    scpp::string finish() {
        unsigned char digest[EVP_MAX_MD_SIZE];
        unsigned int length = 0;
        if (EVP_DigestFinal_ex(context.get(), digest, &length) != 1 || length != 32)
            throw std::runtime_error("Cannot finalize SHA-256");
        constexpr char alphabet[] = "0123456789abcdef";
        char hex[64];
        for (unsigned int i = 0; i < length; ++i) {
            hex[2 * i] = alphabet[digest[i] >> 4];
            hex[2 * i + 1] = alphabet[digest[i] & 15];
        }
        return scpp::string(hex, sizeof(hex));
    }
};
}

namespace App {
scpp::string host_sha256_bytes_(scpp::string bytes) {
    Sha256 digest;
    digest.update(bytes.data(), bytes.size());
    return digest.finish();
}

scpp::string host_sha256_file_(scpp::string path) {
    if (std::memchr(path.data(), '\0', path.size()))
        throw std::runtime_error("Source path contains a NUL byte");
    std::unique_ptr<std::FILE, decltype(&std::fclose)> file(std::fopen(path.data(), "rb"), std::fclose);
    if (!file) throw std::runtime_error(std::string("Cannot open source: ") + path.data());
    Sha256 digest;
    std::array<char, 65536> buffer;
    while (auto length = std::fread(buffer.data(), 1, buffer.size(), file.get()))
        digest.update(buffer.data(), length);
    if (std::ferror(file.get())) throw std::runtime_error(std::string("Cannot read source: ") + path.data());
    return digest.finish();
}
}

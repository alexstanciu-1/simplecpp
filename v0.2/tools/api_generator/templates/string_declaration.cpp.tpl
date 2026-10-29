#pragma once
#include "@types@"
#include "@vals@"
namespace scpp {
namespace detail { struct string_access; }
class @n@ final {
public:
@lh@    explicit @n@(const char*);
    explicit @n@(const char*, @count@);

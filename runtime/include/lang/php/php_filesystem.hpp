#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/result_or_false.hpp"
#include "scpp/string_t.hpp"

namespace scpp::php {

[[nodiscard]] bool_t is_file(const string_t &path);
[[nodiscard]] bool_t is_dir(const string_t &path);
[[nodiscard]] bool_t is_link(const string_t &path);
[[nodiscard]] bool_t file_exists(const string_t &path);
[[nodiscard]] result_or_false<string_t> file_get_contents(const string_t &path);
[[nodiscard]] result_or_false<int_t> file_put_contents(const string_t &path, const string_t &data);
[[nodiscard]] bool_t mkdir(const string_t &path);
[[nodiscard]] result_or_false<hash_t<mixed_t>> scandir(const string_t &path);
[[nodiscard]] result_or_false<int_t> filesize(const string_t &path);
[[nodiscard]] result_or_false<int_t> filemtime(const string_t &path);
[[nodiscard]] bool_t touch(const string_t &path);
[[nodiscard]] bool_t rmdir(const string_t &path);
[[nodiscard]] bool_t unlink(const string_t &path);
[[nodiscard]] bool_t copy(const string_t &source, const string_t &dest);
[[nodiscard]] bool_t rename(const string_t &source, const string_t &dest);
[[nodiscard]] result_or_false<string_t> realpath(const string_t &path);
[[nodiscard]] string_t dirname(const string_t &path);
[[nodiscard]] string_t basename(const string_t &path);

} // namespace scpp::php

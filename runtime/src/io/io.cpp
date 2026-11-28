#include "io_provider.hpp"
#include "scpp/value_error.hpp"
#include "../sequence/csv_controls.hpp"
#include <atomic>
#include <cstdio>
#include <string>
#include <utility>

namespace scpp::detail {
struct file_stream_state {
    std::atomic<size_t> references{1};
    FILE* file{};
    bool readable{}, writable{};
    ~file_stream_state() { if (file) std::fclose(file); }
};
}

namespace scpp::io {
static detail::file_stream_state& state(const file_stream& stream) {
    auto* s = detail::file_stream_access::get(stream);
    if (!s || !s->file) throw ValueError("stream is closed");
    return *s;
}

result_or_false_file_stream open(const string& path, const string& mode) {
    std::string m(mode.data(), mode.size()), p(path.data(), path.size());
    if (m.empty() || m.find_first_of("rwa") != 0) throw ValueError("unsupported stream mode");
    auto suffix = m.substr(1);
    if (suffix != "" && suffix != "+" && suffix != "b" && suffix != "+b" && suffix != "b+")
        throw ValueError("unsupported stream mode");
    if (p.find('\0') != p.npos) return {};
    auto* s = new detail::file_stream_state;
    auto handle = detail::file_stream_access::adopt(s);
    s->file = std::fopen(p.c_str(), m.c_str());
    if (!s->file) return {};
    s->readable = m.front() == 'r' || m.find('+') != m.npos;
    s->writable = m.front() != 'r' || m.find('+') != m.npos;
    return result_or_false_file_stream{std::move(handle)};
}
nullable_int64 seek(const file_stream& stream, int64 offset, int64 origin) {
    auto* f = state(stream).file;
    if (origin < 0 || origin > 2 || !std::in_range<long>(offset)) return {};
    constexpr int origins[]{SEEK_SET, SEEK_CUR, SEEK_END};
    if (std::fseek(f, static_cast<long>(offset), origins[origin])) return {};
    std::clearerr(f);
    return nullable_int64{int64{0}};
}
nullable_int64 seek(const file_stream& stream, int64 offset) { return seek(stream, offset, 0); }
result_or_false_int64 tell(const file_stream& stream) {
    long p = std::ftell(state(stream).file);
    if (p < 0) return {};
    return result_or_false_int64{static_cast<int64>(p)};
}
static result_or_false_int64 write_bytes(const file_stream& stream, std::string_view bytes) {
    auto& s = state(stream);
    if (!s.writable) throw ValueError("stream is not writable");
    auto n = std::fwrite(bytes.data(), 1, bytes.size(), s.file);
    if (!n && !bytes.empty() && std::ferror(s.file)) return {};
    return result_or_false_int64{static_cast<int64>(n)};
}
result_or_false_int64 write(const file_stream& stream, const string& bytes) {
    return write_bytes(stream, {bytes.data(), static_cast<std::size_t>(bytes.size())});
}

result_or_false_int64 write_csv(const file_stream& stream, const vector_string& fields,
                                const string& separator, const string& enclosure, const string& escape,
                                const string& ending) {
    auto view = [](const string& s) { return std::string_view{s.data(), static_cast<std::size_t>(s.size())}; };
    const detail::csv_controls controls{view(separator), view(enclosure), view(escape)};
    std::string row;
    int64 position = 0;
    const auto limit = fields.iteration_limit();
    string value;
    bool first = true;
    while (fields.iteration_next(position, limit, value)) {
        if (!first) row.push_back(controls.separator);
        first = false;
        const auto field = view(value);
        const bool quoted = field.find_first_of(" \t\r\n") != field.npos ||
            field.find(controls.separator) != field.npos || field.find(controls.enclosure) != field.npos ||
            field.find_first_of(controls.escape) != field.npos;
        if (!quoted) {
            row.append(field);
            continue;
        }
        row.push_back(controls.enclosure);
        bool escaped = false;
        for (char ch : field) {
            if (!controls.escapes(ch) && ch == controls.enclosure && !escaped) row.push_back(ch);
            row.push_back(ch);
            escaped = controls.escapes(ch);
        }
        row.push_back(controls.enclosure);
    }
    row.append(view(ending));
    return write_bytes(stream, row);
}

result_or_false_int64 write_csv(const file_stream& stream, const vector_string& fields,
                                const string& sep, const string& enc, const string& esc) {
    return write_csv(stream, fields, sep, enc, esc, string{"\n"});
}
result_or_false_int64 write_csv(const file_stream& stream, const vector_string& fields,
                                const string& sep, const string& enc) {
    return write_csv(stream, fields, sep, enc, string{"\\"});
}
result_or_false_int64 write_csv(const file_stream& stream, const vector_string& fields, const string& sep) {
    return write_csv(stream, fields, sep, string{"\""});
}
result_or_false_int64 write_csv(const file_stream& stream, const vector_string& fields) {
    return write_csv(stream, fields, string{","});
}

static FILE* reader(const file_stream& stream) {
    auto& s = state(stream);
    if (!s.readable) throw ValueError("stream is not readable");
    return s.file;
}
result_or_false_string read(const file_stream& stream, int64 length) {
    auto* f = reader(stream);
    if (!std::in_range<size_t>(length)) throw ValueError("invalid byte count");
    std::string bytes(static_cast<size_t>(length), '\0');
    auto n = std::fread(bytes.data(), 1, bytes.size(), f);
    if (!n && length && std::ferror(f)) return {};
    return result_or_false_string{string{bytes.data(), static_cast<int64>(n)}};
}
static result_or_false_string line(FILE* f, uint64_t remaining) {
    std::string bytes;
    while (remaining--) {
        int ch = std::fgetc(f);
        if (ch == EOF) {
            if (bytes.empty()) return {};
            break;
        }
        bytes.push_back(static_cast<char>(ch));
        if (ch == '\n') break;
    }
    return result_or_false_string{string{bytes.data(), static_cast<int64>(bytes.size())}};
}
result_or_false_string read_line(const file_stream& stream) { return line(reader(stream), UINT64_MAX); }
result_or_false_string read_line(const file_stream& stream, int64 width) {
    if (width < 1) throw ValueError("line width must be positive");
    return line(reader(stream), width - 1);
}
bool_t rewind(const file_stream& stream) { return seek(stream, 0).has_value(); }
bool_t flush(const file_stream& stream) { return std::fflush(state(stream).file) == 0; }
bool_t eof(const file_stream& stream) { return std::feof(state(stream).file) != 0; }
bool_t close(const file_stream& stream) { return std::fclose(std::exchange(state(stream).file, nullptr)) == 0; }
}
#include "io_values.hpp"

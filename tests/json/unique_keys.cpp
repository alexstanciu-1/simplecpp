#include <scpp/json.hpp>
#include <scpp/json/document.hpp>
#include <scpp/json/pull_reader.hpp>
#include <fstream>
#include <iostream>
#include <stdexcept>
#include <string>

using namespace scpp;

static std::string bytes(const string& value) {
    return {value.data(), static_cast<std::size_t>(value.size())};
}

static void require(bool condition, const std::string& message) {
    if (!condition) throw std::runtime_error(message);
}

template<class Result>
static auto checked(const Result& result) {
    if (result.has_error()) throw result.error();
    return result.value();
}

static void consume(const json_pull_reader& reader) {
    const auto kind = bytes(checked(reader.kind()));
    if (kind == "object") {
        checked(reader.begin_object());
        while (checked(reader.next_property())) consume(reader);
    } else if (kind == "array") {
        checked(reader.begin_array());
        while (checked(reader.next_element())) consume(reader);
    } else if (kind == "string") {
        checked(reader.read_string());
    } else if (kind == "number") {
        checked(reader.read_number());
    } else if (kind == "boolean") {
        checked(reader.read_boolean());
    } else {
        require(kind == "null", "unexpected pull reader kind: " + kind);
        checked(reader.read_null());
    }
}

template<class Result>
static void check_boxed(const Result& result, int offset, const std::string& family) {
    if (offset < 0) {
        require(result.has_value(), family + " rejected a valid input");
        return;
    }
    require(result.has_error(), family + " accepted a duplicate");
    const auto message = bytes(result.error().get_message());
    require(message.find("byte " + std::to_string(offset) + ": duplicate") != std::string::npos,
            family + " diagnostic: " + message);
}

static void check_row(const string& input, int offset) {
    check_boxed(json::decode_mixed(input), offset, "mixed");
    check_boxed(json::decode(input), offset, "dynamic");

    nullable_json_parse_error diagnostic;
    auto document = json::document_parse(input, diagnostic);
    if (offset < 0) {
        require(document.has_value() && !diagnostic.has_value(), "document rejected a valid input");
    } else {
        require(document.has_error() && diagnostic.has_value(), "missing document error");
        const auto error = diagnostic.value();
        require(bytes(error.category) == "duplicate_key" && error.byte_offset == offset,
                "document diagnostic: " + bytes(error.message));
    }

    json_pull_reader reader;
    try {
        checked(reader.start(input, string{"corpus"}, 128));
        consume(reader);
        checked(reader.finish_document());
    } catch (const scpp::error_t& error) {
        const auto message = bytes(error.get_message());
        require(offset >= 0 && message.starts_with("corpus: byte " + std::to_string(offset) + " ")
                && message.find(": duplicate object key ") != std::string::npos,
                "pull reader diagnostic: " + message);
        return;
    }
    require(offset < 0, "pull reader accepted a duplicate");
}

int main(int argc, char** argv) {
    if (argc != 2) return 1;
    std::ifstream table(argv[1], std::ios::binary);
    std::string row;
    int rows = 0;
    try {
        while (std::getline(table, row)) {
            ++rows;
            const auto tab = row.find('\t');
            require(tab != std::string::npos, "missing tab");
            const auto text = row.substr(tab + 1);
            check_row(string{text.data(), static_cast<int64>(text.size())}, std::stoi(row.substr(0, tab)));
        }
        require(table.eof() && !table.bad() && rows > 0, "could not read corpus");
    } catch (const std::exception& error) {
        std::cerr << argv[1] << ':' << rows << ": " << error.what() << '\n' << row << '\n';
        return 1;
    }
}

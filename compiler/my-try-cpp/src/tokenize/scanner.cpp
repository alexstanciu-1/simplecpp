#include "tokenize/scanner.hpp"
#include "scpp_compiler/host.hpp"
#include "scpp_compiler/textbuilder.hpp"

namespace scpp::compiler
{
	static boolean letter(int32 byte)
	{
		return byte == '_' || (byte >= 'a' && byte <= 'z') || (byte >= 'A' && byte <= 'Z');
	}

	static boolean digit(int32 byte)
	{
		return byte >= '0' && byte <= '9';
	}

	/**
	 * Resolve byte-based coordinates only on failure; keep successful scans unchanged.
	 */
	static void reject(const token_buffer &source, const char *reason, uint64 offset)
	{
		textbuilder message;
		message.append(reason);
		message.append(" at byte ");
		message.append_number(offset);
		uint64 line = 1;
		uint64 column = 1;
		for (uint64 index = 0; index < offset; ++index)
		{
			const auto byte = source.peek(index);
			if (byte == 13 || (byte == 10 && (index == 0 || source.peek(index - 1) != 13))) {
				++line;
				column = 1;
			}
			else if (byte != 10) {
				++column;
			}
		}
		message.append(" (line ");
		message.append_number(line);
		message.append(", column ");
		message.append_number(column);
		message.append(")");
		fail(message.finish());
	}

	/**
	 * Skip trivia while preserving exact byte separation between syntax tokens.
	 */
	static uint64 skip_trivia(const token_buffer &source, uint64 offset, uint64 size)
	{
		while (offset < size)
		{
			const auto byte = source.peek(offset);
			if (byte == 32 || byte == 9 || byte == 13 || byte == 10) {
				++offset;
				continue;
			}
			const auto next = source.peek(offset + 1);
			if ((byte == '#' && next != '[') || (byte == '/' && next == '/')) {
				while (offset < size && source.peek(offset) != 13 && source.peek(offset) != 10) {
					++offset;
				}
				continue;
			}
			if (byte == '/' && next == '*')
			{
				const auto start = offset;
				offset += 2;
				while (offset < size && !(source.peek(offset) == '*' && source.peek(offset + 1) == '/')) {
					++offset;
				}
				if (offset == size) {
					reject(source, "Unterminated block comment", start);
				}
				offset += 2;
				continue;
			}
			break;
		}
		return offset;
	}

	/**
	 * Keep quotes and escapes as source bytes; decoding belongs to later stages.
	 */
	static uint64 quoted_end(const token_buffer &source, uint64 start, uint64 size)
	{
		const auto quote = source.peek(start);
		auto offset = start + 1;
		while (offset < size)
		{
			const auto byte = source.peek(offset);
			if (byte == quote) {
				return offset + 1;
			}
			if (byte == '\\') {
				++offset;
			}
			++offset;
		}
		reject(source, "Unterminated quoted string", start);
		return size;
	}

	/**
	 * Scan decimal mantissas/exponents once and classify without rescanning spelling.
	 */
	static uint64 numeric_end(const token_buffer &source, uint64 start, token_kind &kind)
	{
		auto offset = start;
		kind = token_kind::integer;
		while (digit(source.peek(offset))) {
			++offset;
		}
		if (source.peek(offset) == '.') {
			kind = token_kind::floating;
			++offset;
			while (digit(source.peek(offset))) {
				++offset;
			}
		}
		const auto next = source.peek(offset);
		if (next == 'e' || next == 'E')
		{
			kind = token_kind::floating;
			++offset;
			if (source.peek(offset) == '+' || source.peek(offset) == '-') {
				++offset;
			}
			const auto exponent = offset;
			while (digit(source.peek(offset))) {
				++offset;
			}
			if (offset == exponent) {
				reject(source, "Expected exponent digits", start);
			}
		}
		if (letter(source.peek(offset)) || source.peek(offset) == '.') {
			reject(source, "Unsupported numeric literal", start);
		}
		return offset;
	}

	/**
	 * Build a private compact generation from an already checked immutable source.
	 */
	token_sequence scan_tokens(const token_buffer &source, int64 previous_count)
	{
		token_builder builder(source, previous_count);
		const auto size = source.byte_size();
		uint64 offset = 0;
		while (offset < size)
		{
			offset = skip_trivia(source, offset, size);
			if (offset == size) {
				break;
			}
			const auto start = offset;
			auto byte = source.peek(offset);
			auto kind = token_kind::identifier;
			if (byte == '$')
			{
				kind = token_kind::variable;
				byte = source.peek(++offset);
				if (!letter(byte)) {
					reject(source, "Expected variable name", start);
				}
			}
			if (letter(byte))
			{
				++offset;
				while (letter(source.peek(offset)) || digit(source.peek(offset))) {
					++offset;
				}
				if (kind == token_kind::identifier) {
					kind = classify_word(source, start, offset - start);
				}
			}
			else if (byte == '\'' || byte == '"') {
				kind = byte == '\'' ? token_kind::single_string : token_kind::double_string;
				offset = quoted_end(source, start, size);
			}
			else if (digit(byte) || (byte == '.' && digit(source.peek(offset + 1)))) {
				offset = numeric_end(source, start, kind);
			}
			else
			{
				uint64 length = 0;
				kind = scan_operator(source, start, length);
				if (kind == token_kind::reserved) {
					reject(source, "Unsupported token", start);
				}
				offset += length;
			}
			if (offset - start > 16777215) {
				reject(source, "Token length exceeds 24-bit capacity", start);
			}
			builder.append(start, offset - start, kind);
		}
		return builder.finish();
	}
}

#include "modules/tokenizer/tokenizer.hpp"
#include "core/stdio.hpp"
#include <fcntl.h>
#include <iostream>
#include <filesystem>
#include <limits>

int main(int argc, char**) {
	using namespace scpp;
	using I = int_t<>;
	using S = string_t;
	int checks = 0, failures = 0;
	auto check = [&](bool ok, const char* label) {
		++checks;
		if (!ok) { ++failures; std::cout << "FAIL " << label << '\n'; }
	};
	auto throws = [&](auto action, const char* label) {
		bool caught = false;
		try { action(); } catch (const std::exception&) { caught = true; }
		check(caught, label);
	};
	for (auto factory : {tokenizer::phs_tokenize_buffer, tokenizer::jss_tokenize_buffer,
		tokenizer::phs_tokenize, tokenizer::jss_tokenize}) {
		S text{"foo 12\nbar"};
		auto tokens = factory(text);
		auto alias = tokens;
		text = S{"changed"};
		check(alias.get() == tokens.get(), "token handle shares owned data");
		check(tokenizer::token_buffer_count(tokens).native_value() == 4, "token count includes EOF");
		const int kinds[] = {1,3,1,0}, starts[] = {0,4,7,10}, lengths[] = {3,2,3,0};
		const int lines[] = {1,1,2,2}, columns[] = {1,5,1,4}, flags[] = {0,1,3,0};
		for (int n=0;n<4;++n) {
			check(tokenizer::token_buffer_kind_id(tokens,I{n}).native_value()==kinds[n], "kind");
			check(tokenizer::token_buffer_start_offset(tokens,I{n}).native_value()==starts[n], "byte offset");
			check(tokenizer::token_buffer_length(tokens,I{n}).native_value()==lengths[n], "length");
			check(tokenizer::token_buffer_line(tokens,I{n}).native_value()==lines[n], "line");
			check(tokenizer::token_buffer_column(tokens,I{n}).native_value()==columns[n], "column");
			check(tokenizer::token_buffer_flags(tokens,I{n}).native_value()==flags[n], "flags");
		}
		auto readable = tokenizer::token_buffer_to_mixed(tokens);
		check(readable.kind()==mixed_t::kind_t::table_v, "readable adapter owns mixed table");
		for (auto accessor : {tokenizer::token_buffer_kind_id, tokenizer::token_buffer_start_offset,
			tokenizer::token_buffer_length, tokenizer::token_buffer_line,
			tokenizer::token_buffer_column, tokenizer::token_buffer_flags}) {
			throws([&]{static_cast<void>(accessor(tokens,I{-1}));}, "negative token index");
			throws([&]{static_cast<void>(accessor(tokens,I{4}));}, "past EOF token index");
		}
		auto empty = factory(S{""});
		check(tokenizer::token_buffer_count(empty).native_value()==1, "empty source EOF");
		auto long_token = factory(S{std::string(70000,'a')});
		check(tokenizer::token_buffer_length(long_token,I{0}).native_value()==70000
			&& tokenizer::token_buffer_flags(long_token,I{0}).native_value()==4, "extended length");
	}
	const S path{"resource.bin"};
	std::filesystem::remove(path.native_value());
	auto missing = io::open(S{"missing/absent.bin"}, S{"rb"});
	check(missing.is_false().native_value(), "open failure sentinel");
	auto file = io::open(path,S{"wb+"});
	check(file.has_value().native_value(), "open");
	auto alias = file.value();
	const int fd = fileno(require_file_resource(file,"probe").native_handle());
	check(io::write(file,S{std::string("a\0b\nlast",8)}).value().native_value()==8, "binary write");
	check(io::flush(file).native_value() && io::rewind(file).native_value(), "flush rewind");
	check(io::read(file,I{4}).value().native_value()==std::string("a\0b\n",4), "binary read");
	check(io::tell(alias).value().native_value()==4, "shared cursor");
	check(io::seek(file,I{0}).value().native_value()==0, "seek zero result");
	auto line=io::read_line(file);
	check(line.has_value().native_value() && line.value().native_value()==std::string("a\0b\n",4), "binary unbounded line");
	io::rewind(file);
	line=io::read_line(file,I{4});
	check(line.has_value().native_value() && line.value().native_value()==std::string("a\0b",3), "binary bounded line");
	io::rewind(file);
	line=io::read_line(file,I{1});
	check(line.has_value().native_value() && line.value().native_value().empty()
		&& io::tell(file).value().native_value()==0, "length one reads no bytes");
	throws([&]{static_cast<void>(io::read_line(file,I{0}));}, "zero line width");
	throws([&]{static_cast<void>(io::read(file,I{-1}));}, "negative read length");
	check(io::read(file,I{0}).value().native_value().empty(), "zero byte read is present");
	check(io::seek(file,I{-1}).has_value().native_value()==false, "seek failure null");
	check(io::seek(file,I{0},I{SEEK_END}).has_value().native_value(), "explicit seek origin");
	check(io::read(file,I{1}).value().native_value().empty() && io::eof(file).native_value(), "EOF read empty");
	check(io::read_line(file).is_false().native_value(), "EOF line false");
	check(io::seek(file,I{0}).has_value().native_value() && !io::eof(file).native_value(), "seek resets EOF");
	check(io::close(alias).native_value() && fcntl(fd,F_GETFD)==-1, "close via alias releases descriptor");
	throws([&]{static_cast<void>(io::tell(file));}, "closed alias use");
	throws([&]{static_cast<void>(io::close(file));}, "duplicate close");
	resource_handle_t absent;
	throws([&]{static_cast<void>(io::tell(absent));}, "absent handle");
	throws([&]{static_cast<void>(io::tell(missing));}, "false wrapper");
	throws([&]{static_cast<void>(io::open(path,S{"invalid"}));}, "invalid mode");
	int unwind_fd = -1;
	try {
		auto owned=io::open(path,S{"rb"});
		unwind_fd=fileno(require_file_resource(owned,"unwind").native_handle());
		auto copy=owned.value();
		throw std::runtime_error("unwind");
	} catch (const std::runtime_error&) {}
	check(fcntl(unwind_fd,F_GETFD)==-1, "last owner closes during unwind");
	auto readonly=io::open(path,S{"rb"});
	throws([&]{static_cast<void>(io::write(readonly,S{"x"}));}, "write readonly");
	io::close(readonly);
	std::filesystem::remove(path.native_value());
	std::cout<<"checks="<<checks<<" failures="<<failures<<'\n';
	return argc>1 ? 0 : (failures==0 ? 0 : 1);
}

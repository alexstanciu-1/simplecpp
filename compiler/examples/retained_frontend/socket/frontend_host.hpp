#pragma once

#include <scpp/string.hpp>

namespace App {
scpp::string host_sha256_bytes_(scpp::string bytes);
scpp::string host_sha256_file_(scpp::string path);
bool host_executable_(scpp::string path);
void host_directory_();
void host_listen_();
scpp::string host_request_();
void host_reply_(scpp::string response);
void host_close_();
scpp::int64 host_pid_();
scpp::string host_exchange_(scpp::string app, scpp::string command);
}

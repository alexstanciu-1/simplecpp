#pragma once

#include "scpp/runtime.hpp"
// Register lookup overloads before PHP forwarding templates bind qualified names.
#include "lang/php/php_compiler.hpp"

#include "lang/php/php_exceptions.hpp"
#include "lang/php/php.hpp"
#include "lang/php/php_resource.hpp"
#include "lang/php/php_stdio.hpp"
#include "lang/php/php_filesystem.hpp"
#include "lang/php/php_json.hpp"
#include "lang/php/php_datetime.hpp"
#include "lang/php/php_process.hpp"
#include "lang/php/php_mysqli.hpp"
#include "lang/php/php_regex.hpp"
#include "lang/php/php_curl.hpp"
#include "modules/source/source.hpp"

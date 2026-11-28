# Concrete module target depends on its publication's core; never the reverse.
function(scpp_add_datetime target core generated)
  add_library(${target} STATIC ${generated}/src/datetime.cpp
    ${PROJECT_SOURCE_DIR}/runtime/src/datetime/datetime.cpp)
  if(ARGV3)
    target_sources(${target} PRIVATE ${generated}/src/php_date.cpp
      ${PROJECT_SOURCE_DIR}/runtime/src/datetime/php_date.cpp)
  endif()
  if(ARGV4)
    set(timelib_dir ${PROJECT_SOURCE_DIR}/runtime/src/datetime/timelib)
    target_sources(${target} PRIVATE ${generated}/src/php_strtotime.cpp
      ${PROJECT_SOURCE_DIR}/runtime/src/datetime/php_strtotime.cpp
      ${timelib_dir}/parse_date.c ${timelib_dir}/parse_posix.c ${timelib_dir}/parse_tz.c
      ${timelib_dir}/timelib.c ${timelib_dir}/tm2unixtime.c ${timelib_dir}/unixtime2tm.c)
    target_compile_definitions(${target} PRIVATE $<$<COMPILE_LANGUAGE:C>:HAVE_STRTOLL=1>)
  endif()
  if(ARGV3 OR ARGV4)
    target_sources(${target} PRIVATE
      ${PROJECT_SOURCE_DIR}/runtime/src/datetime/timelib/utc.cpp
      ${PROJECT_SOURCE_DIR}/runtime/src/datetime/timelib/dow.cpp)
  endif()
  target_link_libraries(${target} PUBLIC ${core})
  target_include_directories(${target} PRIVATE ${PROJECT_SOURCE_DIR}/runtime/src/datetime ${PROJECT_SOURCE_DIR}/runtime/src/numeric)
  target_compile_features(${target} PUBLIC cxx_std_23)
  find_package(Threads REQUIRED)
  target_link_libraries(${target} PRIVATE Threads::Threads)
endfunction()

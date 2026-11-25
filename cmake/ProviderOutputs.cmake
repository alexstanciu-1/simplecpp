# Keep CMake byproducts on the same metadata/publication placement path as PHP rendering.
function(scpp_provider_outputs output generated publication)
  set_property(DIRECTORY APPEND PROPERTY CMAKE_CONFIGURE_DEPENDS
    ${runtime_metadata} ${publication} ${generator_sources})
  execute_process(COMMAND ${PHP_EXECUTABLE} ${php_generator_source}
    provider-headers ${runtime_metadata} ${publication}
    RESULT_VARIABLE status OUTPUT_VARIABLE headers ERROR_VARIABLE errors)
  if(NOT status EQUAL 0)
    message(FATAL_ERROR "Cannot resolve runtime provider outputs: ${errors}")
  endif()
  string(JSON count LENGTH "${headers}")
  set(paths)
  if(count GREATER 0)
    math(EXPR last "${count} - 1")
    foreach(index RANGE 0 ${last})
      string(JSON header GET "${headers}" ${index})
      list(APPEND paths ${generated}/public/${header})
    endforeach()
  endif()
  set(${output} ${paths} PARENT_SCOPE)
endfunction()

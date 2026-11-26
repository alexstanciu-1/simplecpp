execute_process(COMMAND "${PHP}" "${EMIT}" OUTPUT_FILE "${OUT}.tmp" COMMAND_ERROR_IS_FATAL ANY)
configure_file("${OUT}.tmp" "${OUT}" COPYONLY)
file(REMOVE "${OUT}.tmp")

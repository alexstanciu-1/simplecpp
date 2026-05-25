#!/usr/bin/env php
<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/project_services.php';

handle_stan_lsp_serve(getcwd());

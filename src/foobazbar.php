<?php

$output = shell_exec("env");
file_put_contents('/var/app/current/cron_env_shell_dump.txt', $output);
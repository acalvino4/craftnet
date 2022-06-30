<?php

$output = shell_exec("source /opt/elasticbeanstalk/deployment/envvars;env");
file_put_contents('/var/app/current/cron_env_shell_dump.txt', $output);
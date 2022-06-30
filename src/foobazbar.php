<?php

exec("env",$o);
file_put_contents('/var/app/current/cron_env_shell_dump.txt', print_r($o));
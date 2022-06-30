#!/usr/bin/env bash

# Crontab will place cron tasks as root if the user doesn't have a home directory.
mkdir -p /home/webapp ; chown -R webapp:webapp /home/webapp

# Create CRON files

# After the deployment finishes, set up a Crontab for craftnet
echo "* * * * * webapp bash -c "\"" . <(source /opt/elasticbeanstalk/deployment/envvars) && php /var/app/current/src/foobazbar.php 1>> /dev/null 2>&1"\"" " | sudo tee /etc/cron.d/craftnet
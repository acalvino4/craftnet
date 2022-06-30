#!/usr/bin/env bash

# Crontab will place cron tasks as root if the user doesn't have a home directory.
mkdir -p /home/webapp ; chown -R webapp:webapp /home/webapp

# Create CRON files

# After the deployment finishes, set up a Crontab for craftnet
echo "* * * * * webapp source /opt/elasticbeanstalk/deployment/envvars; php /var/app/current/craft schedule/run --scheduleFile=@config/schedule.php" | sudo tee /etc/cron.d/craftnet
#!/bin/bash

# Create CRON files

# After the deployment finishes, set up a Crontab for craftnet
echo "* * * * * webapp bash -c "\"" . <(sed -E -n 's/[^#]+/export &/ p' /opt/elasticbeanstalk/deployment/envvars) && php /var/app/current/craft schedule/run --scheduleFile=@config/schedule.php 1>> /dev/null 2>&1"\"" " | sudo tee /etc/cron.d/craftnet
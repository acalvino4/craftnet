#!/usr/bin/env bash

# Create a copy of the environment variable file in the correct format.
sed -r -n 's/.+=/export &"/p' /opt/elasticbeanstalk/deployment/env | sed -r -n 's/.+/&"/p' > /opt/elasticbeanstalk/deployment/envvars

# Set permissions to the custom env var file so this file can be accessed by any user on the instance.
chmod 644 /opt/elasticbeanstalk/deployment/envvars
chown webapp:webapp /opt/elasticbeanstalk/deployment/envvars

# Remove duplicate files upon deployment
rm -f /opt/elasticbeanstalk/deployment/*.bak
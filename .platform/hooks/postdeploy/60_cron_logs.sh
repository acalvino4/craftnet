#!/usr/bin/env bash

touch /var/app/current/cron/licenses-process-expired-licenses.log
touch /var/app/current/cron/licenses-send-reminders.log
touch /var/app/current/cron/packages-update-deps.log
touch /var/app/current/cron/payouts-update.log
touch /var/app/current/cron/plugins-update-install-counts.log
touch /var/app/current/cron/plugins-update-issue-stats.log
chmod 777 /var/app/current/cron/licenses-process-expired-licenses.log
chmod 777 /var/app/current/cron/licenses-send-reminders.log
chmod 777 /var/app/current/cron/packages-update-deps.log
chmod 777 /var/app/current/cron/payouts-update.log
chmod 777 /var/app/current/cron/plugins-update-install-counts.log
chmod 777 /var/app/current/cron/plugins-update-issue-stats.log
chown webapp:webapp /var/app/current/cron/licenses-process-expired-licenses.log
chown webapp:webapp /var/app/current/cron/licenses-send-reminders.log
chown webapp:webapp /var/app/current/cron/packages-update-deps.log
chown webapp:webapp /var/app/current/cron/payouts-update.log
chown webapp:webapp /var/app/current/cron/plugins-update-install-counts.log
chown webapp:webapp /var/app/current/cron/plugins-update-issue-stats.log
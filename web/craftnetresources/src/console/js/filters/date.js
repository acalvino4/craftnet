import {DateTime} from 'luxon';

export function formatDate(date) {
    return parseDate(date).toLocaleString(DateTime.DATETIME_MED)
}

export function getDateTime() {
    return DateTime
}

export function parseDate(date) {
    const jsDate = new Date(date);
    return getDateTime().fromJSDate(jsDate);
}
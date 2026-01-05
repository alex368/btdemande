import { Calendar } from '@fullcalendar/core'
import dayGridPlugin from '@fullcalendar/daygrid'
import icalendarPlugin from '@fullcalendar/icalendar'
import * as ICAL from 'ical.js'

document.addEventListener('DOMContentLoaded', () => {
    const calendar = new Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',
        plugins: [dayGridPlugin, icalendarPlugin],
        events: {
            url: '/icloud.ics',
            format: 'ics'
        }
    });

    calendar.render();
});

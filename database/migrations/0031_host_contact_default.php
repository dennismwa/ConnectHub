<?php
/**
 * The agent profile opens in the contact way by default: services are named (a price only when the agent adds one) and
 * visitors reach the agent on WhatsApp, by phone or by message, instead of a booking panel with rates, availability and a
 * cancellation policy.
 *
 * The admin chooses on Admin > Hosts (the switch at the top of the page). Switching is not destructive: rates, packages,
 * availability and past bookings stay in the database, so the booking way can come back with one click.
 *
 * Only a site that never made the choice is moved. A value an admin saved (it carries the admin's id) is left alone.
 * Safe to run twice.
 */
return function (): void {
    q("UPDATE site_settings SET setting_value = 'contact' WHERE setting_key = 'host_module_mode' AND setting_value = 'booking' AND updated_by IS NULL");
    mig_setting_default('host_module_mode', 'contact');
};

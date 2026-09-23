<?php

if (! function_exists('ticket_label')) {
    /**
     * شماره قبض قابل‌نمایش: ticket_no ترجیح دارد، وگرنه receipt_no.
     */
    function ticket_label(mixed $reception): string
    {
        if (! $reception) {
            return '';
        }
        if (is_object($reception) && method_exists($reception, 'ticketLabel')) {
            return (string) $reception->ticketLabel();
        }
        $ticket = trim((string) ($reception->ticket_no ?? ''));
        if ($ticket !== '') {
            return $ticket;
        }

        return trim((string) ($reception->receipt_no ?? ''));
    }
}

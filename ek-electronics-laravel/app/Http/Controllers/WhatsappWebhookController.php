<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\WhatsApp;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        if (! Setting::bool('whatsapp_webhook_enabled', false)) {
            return response('Webhook disabled', 403);
        }

        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $token = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));
        $expected = Setting::getValue('whatsapp_webhook_verify_token', 'ek-wa-verify');

        if ($mode === 'subscribe' && hash_equals((string) $expected, (string) $token)) {
            return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    public function receive(Request $request): Response
    {
        if (! Setting::bool('whatsapp_webhook_enabled', false)) {
            return response('Webhook disabled', 403);
        }

        $payload = $request->all();
        Log::info('whatsapp.webhook', ['keys' => array_keys($payload)]);

        $entries = data_get($payload, 'entry', []);
        foreach ($entries as $entry) {
            $changes = data_get($entry, 'changes', []);
            foreach ($changes as $change) {
                $messages = data_get($change, 'value.messages', []);
                $contacts = collect(data_get($change, 'value.contacts', []))->keyBy('wa_id');
                foreach ($messages as $message) {
                    $from = (string) data_get($message, 'from', '');
                    $body = (string) (
                        data_get($message, 'text.body')
                        ?: data_get($message, 'button.text')
                        ?: data_get($message, 'interactive.button_reply.title')
                        ?: '[non-text message]'
                    );
                    $name = (string) data_get($contacts->get($from), 'profile.name', $from);
                    if ($from !== '' && $body !== '') {
                        WhatsApp::logInbound($body, $from, $name);
                    }
                }
            }
        }

        // Twilio-style form posts
        if ($request->filled('Body') && $request->filled('From')) {
            $from = preg_replace('/\D+/', '', (string) $request->input('From'));
            WhatsApp::logInbound((string) $request->input('Body'), $from ?: 'unknown', $request->input('ProfileName'));
        }

        return response('EVENT_RECEIVED', 200);
    }
}

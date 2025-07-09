<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ContactFormMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;

class ContactController extends Controller
{
    /**
     * Send a contact form email.
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'subject' => 'required|string|max:100',
            'message' => 'required|string|max:2000',
        ]);

        try {
            Mail::to(config('mail.from.address'))->send(new ContactFormMail($validated));

            return response()->json([
                'success' => true,
                'message' => 'Your message has been sent successfully!'
            ]);
        } catch (Exception $e) {
            Log::error('Contact form mail failed to send', [
                'error' => $e->getMessage(),
                'email_data' => $validated
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send your message. Please try again later.'
            ], 500);
        }
    }
}

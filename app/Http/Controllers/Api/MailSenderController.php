<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Mail\Message;

class MailSenderController extends Controller
{
    /**
     * Send email via API
     *
     * Expected request payload:
     * {
     *   "to": "recipient@example.com",
     *   "subject": "Email Subject",
     *   "body": "<html>Email body</html>",
     *   "attachment_base64": "base64_encoded_file_content", // optional
     *   "attachment_name": "filename.pdf", // optional
     *   "smtp_settings": { // optional, if not provided uses default
     *     "host": "smtp.gmail.com",
     *     "port": 587,
     *     "username": "user@gmail.com",
     *     "password": "password",
     *     "encryption": "tls",
     *     "from_email": "from@example.com",
     *     "from_name": "Sender Name"
     *   }
     * }
     */
    public function sendEmail(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'to' => 'required|email',
            'subject' => 'required|string',
            'body' => 'required|string',
            'attachment_base64' => 'nullable|string',
            'attachment_name' => 'nullable|string',
            'smtp_settings' => 'nullable|array',
            'smtp_settings.host' => 'required_with:smtp_settings|string',
            'smtp_settings.port' => 'required_with:smtp_settings|integer',
            'smtp_settings.username' => 'required_with:smtp_settings|string',
            'smtp_settings.password' => 'required_with:smtp_settings|string',
            'smtp_settings.encryption' => 'required_with:smtp_settings|string|in:tls,ssl',
            'smtp_settings.from_email' => 'required_with:smtp_settings|email',
            'smtp_settings.from_name' => 'required_with:smtp_settings|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Configure SMTP if custom settings provided
            if ($request->has('smtp_settings')) {
                $smtp = $request->smtp_settings;
                Config::set('mail.default', 'smtp');
                Config::set('mail.mailers.smtp.host', $smtp['host']);
                Config::set('mail.mailers.smtp.port', $smtp['port']);
                Config::set('mail.mailers.smtp.username', $smtp['username']);
                Config::set('mail.mailers.smtp.password', $smtp['password']);
                Config::set('mail.mailers.smtp.encryption', $smtp['encryption']);
                Config::set('mail.from.address', $smtp['from_email']);
                Config::set('mail.from.name', $smtp['from_name']);
            }

            // Handle attachment if provided
            $attachmentPath = null;
            if ($request->has('attachment_base64') && $request->has('attachment_name')) {
                $fileData = base64_decode($request->attachment_base64);
                $fileName = $request->attachment_name;
                $attachmentPath = storage_path('app/temp/' . uniqid() . '_' . $fileName);

                // Create temp directory if not exists
                if (!file_exists(storage_path('app/temp'))) {
                    mkdir(storage_path('app/temp'), 0755, true);
                }

                file_put_contents($attachmentPath, $fileData);
            }

            // Send email
            Mail::html($request->body, function (Message $message) use ($request, $attachmentPath) {
                $message->to($request->to)
                        ->subject($request->subject);

                if ($attachmentPath && file_exists($attachmentPath)) {
                    $message->attach($attachmentPath, [
                        'as' => $request->attachment_name
                    ]);
                }
            });

            // Clean up attachment file
            if ($attachmentPath && file_exists($attachmentPath)) {
                unlink($attachmentPath);
            }

            return response()->json([
                'success' => true,
                'message' => 'Email sent successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Email sending failed: ' . $e->getMessage());

            // Clean up attachment file if exists
            if (isset($attachmentPath) && file_exists($attachmentPath)) {
                unlink($attachmentPath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to send email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Health check endpoint
     */
    public function healthCheck()
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'hrmailsender',
            'timestamp' => now()->toDateTimeString()
        ]);
    }
}

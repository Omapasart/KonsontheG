<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConfirmationEmailController extends Controller
{
    public function __construct(private readonly AdminRegistrationService $adminRegistrations) {}

    public function index(): View
    {
        return view('admin.emails.index', [
            'applicationCount' => $this->adminRegistrations->confirmationRecipientCount('application'),
            'paymentCount' => $this->adminRegistrations->confirmationRecipientCount('payment'),
            'slotCount' => $this->adminRegistrations->confirmationRecipientCount('slot'),
            'transferCount' => $this->adminRegistrations->confirmationRecipientCount('transfer'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $type = $request->validate([
            'type' => ['required', 'in:application,payment,slot,transfer'],
        ])['type'];

        set_time_limit(0);

        try {
            $result = $this->adminRegistrations->sendConfirmationEmails($request->user(), $type);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        $label = $this->adminRegistrations->emailTypeLabel($type);

        $message = 'Sent '.$result['sent'].' '.$label.' email'.($result['sent'] === 1 ? '' : 's').'.';

        if ($result['failed'] > 0) {
            return back()->with('success', $message)->with('warning', $result['failed'].' email'.($result['failed'] === 1 ? '' : 's').' failed. Check storage/logs/laravel.log.');
        }

        return back()->with('success', $message);
    }
}

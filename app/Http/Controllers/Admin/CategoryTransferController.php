<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryTransferRequest;
use App\Models\Registration;
use App\Services\CategoryTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class CategoryTransferController extends Controller
{
    public function __construct(private readonly CategoryTransferService $transfers) {}

    public function store(StoreCategoryTransferRequest $request, Registration $registration): RedirectResponse
    {
        try {
            $this->transfers->request(
                $registration,
                $request->user(),
                $request->validated('requested_category')
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        return back()->with('success', 'A category transfer request has been emailed to '.$registration->fullName().'. The current category will not change until the applicant agrees.');
    }
}

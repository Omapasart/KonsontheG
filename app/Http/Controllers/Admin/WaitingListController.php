<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EntryLevel;
use App\Enums\SlotStatus;
use App\Http\Controllers\Controller;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WaitingListController extends Controller
{
    public function __invoke(Request $request): View
    {
        $query = Registration::query()
            ->where('slot_status', SlotStatus::Waiting)
            ->orderBy('entry_level')
            ->orderBy('waiting_list_position')
            ->orderBy('created_at');

        if ($request->filled('entry_level')) {
            $query->where('entry_level', $request->string('entry_level'));
        }

        return view('admin.waiting-list.index', [
            'applicants' => $query->paginate(20)->withQueryString(),
            'levels' => EntryLevel::cases(),
            'entryLevel' => $request->query('entry_level', ''),
        ]);
    }
}

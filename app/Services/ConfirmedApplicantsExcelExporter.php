<?php

namespace App\Services;

use App\Enums\EntryLevel;
use App\Enums\RegistrationStatus;
use App\Enums\SlotStatus;
use App\Models\Registration;
use App\Support\SimpleXlsxWorkbook;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConfirmedApplicantsExcelExporter
{
    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return [
            'Registration Number',
            'Last Name',
            'First Name',
            'Middle Initial',
            'Full Name',
            'Contact Number',
            'Email Address',
            'Address',
            'Category',
            'Slot Status',
            'Registration Status',
            'Payment Status',
            'Registration Date',
            'Payment Verification Date',
            'Registration Approval Date',
        ];
    }

    /**
     * @return Collection<int, Registration>
     */
    public function confirmedApplicants(EntryLevel $level): Collection
    {
        return Registration::query()
            ->where('entry_level', $level)
            ->where('slot_status', SlotStatus::Confirmed)
            ->where('registration_status', '!=', RegistrationStatus::Rejected)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('id')
            ->get();
    }

    public function filename(EntryLevel $level, ?\DateTimeInterface $date = null): string
    {
        $stamp = ($date ?? now())->format('Y-m-d');

        return 'KONSONTHEGO_Confirmed_'.$level->label().'_Applicants_'.$stamp.'.xlsx';
    }

    public function download(EntryLevel $level): StreamedResponse
    {
        $applicants = $this->confirmedApplicants($level);
        $exportedAt = now();
        $binary = (new SimpleXlsxWorkbook)->build(
            'KONSONTHEGO — Confirmed '.$level->label().' Applicants',
            'Export date: '.$exportedAt->format('F j, Y'),
            $this->headers(),
            $applicants->map(fn (Registration $applicant) => $this->row($applicant))->all(),
            [22, 18, 18, 12, 28, 18, 28, 36, 16, 16, 18, 16, 22, 24, 24],
        );

        return response()->streamDownload(function () use ($binary): void {
            echo $binary;
        }, $this->filename($level, $exportedAt), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return list<string>
     */
    private function row(Registration $applicant): array
    {
        return [
            (string) $applicant->registration_number,
            (string) $applicant->last_name,
            (string) $applicant->first_name,
            (string) ($applicant->middle_initial ?? ''),
            $applicant->fullName(),
            (string) $applicant->contact_number,
            (string) $applicant->email,
            (string) $applicant->address,
            $applicant->entry_level->label(),
            $applicant->slot_status->label(),
            $applicant->registration_status->value,
            $applicant->payment_status->label(),
            optional($applicant->created_at)?->format('Y-m-d H:i') ?? '',
            optional($applicant->payment_reviewed_at)?->format('Y-m-d H:i') ?? '',
            optional($applicant->approved_at)?->format('Y-m-d H:i') ?? '',
        ];
    }
}

<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Pagination\LengthAwarePaginator;

class ContactService
{
    public function getPaginatedContacts(
        int $perPage = 20,
        array $filters = []
    ): LengthAwarePaginator {
        return Contact::query()
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where(function ($query) use ($status) {
                    $query->where('status', $status);
                });
            })
            ->paginate($perPage);
    }

    public function createContact(array $data): Contact
    {
        // return $this->repository->create($data);
    }

    public function updateContact(Contact $contact, array $data): Contact
    {
        // return $this->repository->update($contact, $data);
    }

    public function deleteContact(Contact $contact): bool
    {
        // return $this->repository->delete($contact);
    }

    public function importContacts(array $contacts): int
    {
        $imported = 0;

        foreach ($contacts as $contactData) {
            try {
                // $this->repository->firstOrCreate(
                //     ['email' => $contactData['email']],
                //     $contactData
                // );
                $imported++;
            } catch (\Exception $e) {
                continue;
            }
        }

        return $imported;
    }
}

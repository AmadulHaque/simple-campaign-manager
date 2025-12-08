<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Pagination\CursorPaginator;

class ContactService
{
    public function getPaginatedContacts(
        int $perPage = 20,
        array $filters = []
    ): CursorPaginator {
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
            ->orderBy('id', 'desc')->cursorPaginate($perPage);
    }

    public function createContact(array $data): Contact
    {
        return Contact::create($data);
    }

    public function updateContact(Contact $contact, array $data): bool
    {
        return $contact->update($data);
    }

    public function deleteContact(Contact $contact): bool
    {
        return $contact->delete();
    }

    public function importContacts(array $contacts): int
    {
        $validContacts = array_filter($contacts, function ($contact) {
            return ! empty($contact['name']) && filter_var($contact['email'], FILTER_VALIDATE_EMAIL);
        });

        $data = array_map(function ($contact) {
            return [
                'name'       => trim($contact['name']),
                'email'      => strtolower(trim($contact['email'])),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $validContacts);

        return Contact::insert($data);
    }
}

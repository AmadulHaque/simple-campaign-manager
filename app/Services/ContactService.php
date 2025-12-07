<?php
namespace App\Services;

use App\Models\Contact;
use App\Repositories\ContactRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class ContactService
{
    public function __construct(
        private ContactRepository $repository
    ) {}

    public function getPaginatedContacts(
        int $perPage = 20,
        array $filters = []
    ): LengthAwarePaginator {
        return $this->repository->paginate($perPage, $filters);
    }

    public function createContact(array $data): Contact
    {
        return $this->repository->create($data);
    }

    public function updateContact(Contact $contact, array $data): Contact
    {
        return $this->repository->update($contact, $data);
    }

    public function deleteContact(Contact $contact): bool
    {
        return $this->repository->delete($contact);
    }

    public function importContacts(array $contacts): int
    {
        $imported = 0;
        
        foreach ($contacts as $contactData) {
            try {
                $this->repository->firstOrCreate(
                    ['email' => $contactData['email']],
                    $contactData
                );
                $imported++;
            } catch (\Exception $e) {
                continue;
            }
        }
        
        return $imported;
    }
}
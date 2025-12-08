<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Models\Contact;
use App\Services\ContactService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function __construct(
        private ContactService $service
    ) {}

    public function index(Request $request): Response
    {
        $filters  = $request->only(['search', 'status']);
        $contacts = $this->service->getPaginatedContacts(20, $filters);

        return Inertia::render('Contacts/Index', [
            'contacts' => $contacts,
            'filters'  => $filters,
        ]);
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        try {
            $contact = $this->service->createContact(data: $request->validated());

            return redirect()
                ->route('contacts.index')
                ->with('success', 'Contact created successfully.');

        } catch (Exception $e) {
            Log::error('Failed to create contact.', ['error' => $e->getMessage()]);

            return back()->with('error', 'Failed to create contact.');
        }
    }

    public function update(ContactRequest $request, Contact $contact): RedirectResponse
    {
        $this->service->updateContact($contact, $request->validated());

        return redirect()
            ->route('contacts.index')
            ->with('success', 'Contact updated successfully.');
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $this->service->deleteContact($contact);

        return redirect()
            ->route('contacts.index')
            ->with('success', 'Contact deleted successfully.');
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'contacts'         => 'required|array',
            'contacts.*.name'  => 'required|string',
            'contacts.*.email' => 'required|email',
        ]);

        $imported = $this->service->importContacts($request->contacts);

        return response()->json([
            'message'  => "Successfully imported {$imported} contacts.",
            'imported' => $imported,
        ]);
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'exists:contacts,id',
        ]);

        $this->service->bulkDeleteContacts($request->ids);

        return redirect()
            ->route('contacts.index')
            ->with('success', 'Selected contacts deleted successfully.');
    }
}

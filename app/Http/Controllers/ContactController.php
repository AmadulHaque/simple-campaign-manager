<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\Contact;
use Illuminate\Http\Request;
use App\Services\ContactService;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\ContactRequest;
use Illuminate\Http\RedirectResponse;

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
        $contact = $this->service->createContact($request->validated());

        return redirect()
            ->route('contacts.index')
            ->with('success', 'Contact created successfully.');
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
}

# simple-campaign-manager — Step‑by‑step implementation

This document provides a complete, production-minded, step-by-step implementation plan for **Simple Email Campaign Manager** using **Laravel (backend)**, **React + InertiaJS (frontend)** and **shadcn/ui** components. It contains migrations, models, controllers, services, queued job, React/Inertia pages, and README-style setup & architecture notes you can copy straight into your repository.

---

## Overview

Features to implement:

* Contacts: seeded list (name + email), table with selection single/multi/select all.
* Campaigns: subject + body, select recipients, trigger sending using a queued job (fake sending).
* Email status tracking: per recipient `pending | sent | failed`. Campaign history and per-campaign results.
* Clean architecture: service classes, actions, DTOs, FormRequests, React components organized, shadcn/ui.

Assumptions:

* Laravel 10+ and Node 18+.
* Use `inertiajs/inertia-laravel` and React adapter.
* Use database queue driver for job processing.
* Fake sending implemented in job (no external SMTP required).

---

## Project scaffold (commands)

```bash
# create laravel app
composer create-project laravel/laravel simple-campaign-manager
cd simple-campaign-manager

# install inertia + react
composer require inertiajs/inertia-laravel
npm install @inertiajs/inertia @inertiajs/inertia-react react react-dom

# optional: Laravel Breeze (React + Inertia) quickstart to bootstrap auth and structure
composer require laravel/breeze --dev
php artisan breeze:install react
npm install && npm run dev

# install shadcn/ui — if using tailwind + shadcn component system
# we'll use shadcn patterns (Tailwind required)
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p
# configure tailwind per the docs

# install shadcn/ui packages as needed (ui components typically come from your component set)
# You can implement shadcn-style components or reuse existing shadcn packages

# prepare queue table
php artisan queue:table
php artisan migrate

# create git repo (locally), then push to GitHub
git init
git add .
git commit -m "Initial scaffold"
# create GitHub repo `simple-campaign-manager` and push

```

---

## Database design (migrations)

Create three main tables: `contacts`, `campaigns`, `campaign_recipients`.

### Migration: create_contacts_table

`database/migrations/2025_01_01_000001_create_contacts_table.php`

```php
public function up() {
    Schema::create('contacts', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamps();
    });
}
```

### Migration: create_campaigns_table

`2025_01_01_000002_create_campaigns_table.php`

```php
public function up() {
    Schema::create('campaigns', function (Blueprint $table) {
        $table->id();
        $table->string('subject');
        $table->text('body'); // store HTML or plain text
        $table->unsignedBigInteger('created_by')->nullable();
        $table->timestamps();
    });
}
```

### Migration: create_campaign_recipients_table

`2025_01_01_000003_create_campaign_recipients_table.php`

```php
public function up() {
    Schema::create('campaign_recipients', function (Blueprint $table) {
        $table->id();
        $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
        $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
        $table->enum('status', ['pending','sent','failed'])->default('pending');
        $table->text('error_message')->nullable();
        $table->timestamp('sent_at')->nullable();
        $table->timestamps();
        $table->unique(['campaign_id','contact_id']);
    });
}
```

After creating migrations, run `php artisan migrate`.

---

## Models

`app/Models/Contact.php`

```php
class Contact extends Model {
    protected $fillable = ['name','email'];

    public function campaignRecipients() { return $this->hasMany(CampaignRecipient::class); }
}
```

`app/Models/Campaign.php`

```php
class Campaign extends Model {
    protected $fillable = ['subject','body','created_by'];

    public function recipients() { return $this->hasMany(CampaignRecipient::class); }
}
```

`app/Models/CampaignRecipient.php`

```php
class CampaignRecipient extends Model {
    protected $fillable = ['campaign_id','contact_id','status','error_message','sent_at'];

    public function campaign() { return $this->belongsTo(Campaign::class); }
    public function contact() { return $this->belongsTo(Contact::class); }
}
```

---

## Factories & Seeder

Create `ContactFactory` to seed sample contacts (50 records). Create `DatabaseSeeder` to call it.

`database/factories/ContactFactory.php`

```php
public function definition() {
    return [
        'name' => $this->faker->name,
        'email' => $this->faker->unique()->safeEmail,
    ];
}
```

`DatabaseSeeder.php`

```php
Contact::factory()->count(50)->create();
```

Run `php artisan migrate:fresh --seed` to prepare local data.

---

## Validation (FormRequests)

`app/Http/Requests/StoreCampaignRequest.php`

```php
public function rules() {
    return [
        'subject' => 'required|string|max:255',
        'body' => 'required|string',
        'recipients' => 'required|array|min:1',
        'recipients.*' => 'exists:contacts,id',
    ];
}
```

---

## Service Layer, DTO and Action

Structure for clarity and testability:

* `App\Services\CampaignService` — high level orchestration (create campaign, create recipients, dispatch jobs).
* `App\DTOs\CampaignData` — simple value object for campaign create payload.
* `App\Jobs\SendCampaignEmail` — queued job to "send" email to a single contact.

`app/DTOs/CampaignData.php`

```php
class CampaignData {
    public string $subject;
    public string $body;
    public array $recipientIds;

    public function __construct(array $attrs) {
        $this->subject = $attrs['subject'];
        $this->body = $attrs['body'];
        $this->recipientIds = $attrs['recipients'] ?? [];
    }
}
```

`app/Services/CampaignService.php`

```php
class CampaignService {
    public function createAndDispatch(CampaignData $data, ?int $userId = null) {
        return DB::transaction(function() use($data,$userId) {
            $campaign = Campaign::create([
                'subject'=>$data->subject,
                'body'=>$data->body,
                'created_by'=>$userId,
            ]);

            foreach($data->recipientIds as $contactId) {
                $recipient = CampaignRecipient::create([
                    'campaign_id'=>$campaign->id,
                    'contact_id'=>$contactId,
                    'status'=>'pending'
                ]);
                // dispatch job for each recipient
                SendCampaignEmail::dispatch($recipient);
            }

            return $campaign;
        });
    }
}
```

`app/Jobs/SendCampaignEmail.php`

```php
class SendCampaignEmail implements ShouldQueue {
    public $recipient;
    public function __construct(CampaignRecipient $recipient) { $this->recipient = $recipient; }

    public function handle() {
        // Refresh to prevent stale data
        $this->recipient->refresh();

        // Fake sending: emulate external latency and random failure for demo
        try {
            // emulate network delay
            sleep(rand(0,2));

            // fake failure chance (5%): in production remove randomness
            if(rand(1,100) <= 5) {
                throw new \Exception('Simulated send failure');
            }

            // Mark as sent
            $this->recipient->update(['status' => 'sent', 'sent_at' => now(), 'error_message' => null]);

            // Optionally: store logs or events
        } catch (\Exception $e) {
            $this->recipient->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        }
    }
}
```

Notes: The job receives a `CampaignRecipient` instance; since it's queued, ensure model serialization uses `SerializesModels` trait (Laravel Job skeleton does this). Use queue worker locally: `php artisan queue:work`.

---

## Controllers (Inertia)

`app/Http/Controllers/ContactController.php`

```php
public function index() {
    $contacts = Contact::query()->paginate(25);
    return Inertia::render('Contacts/Index', ['contacts' => $contacts]);
}
```

`app/Http/Controllers/CampaignController.php`

```php
public function index() {
    $campaigns = Campaign::withCount(['recipients as sent_count' => function($q){ $q->where('status','sent'); }])->paginate(10);
    return Inertia::render('Campaigns/Index', compact('campaigns'));
}

public function create() {
    $contacts = Contact::all(['id','name','email']);
    return Inertia::render('Campaigns/Create', compact('contacts'));
}

public function store(StoreCampaignRequest $request, CampaignService $service) {
    $dto = new CampaignData($request->validated());
    $campaign = $service->createAndDispatch($dto, auth()->id());
    return redirect()->route('campaigns.show', $campaign->id)->with('success','Campaign created and queued');
}

public function show(Campaign $campaign) {
    $campaign->load('recipients.contact');
    return Inertia::render('Campaigns/Show', ['campaign' => $campaign]);
}
```

Routes in `routes/web.php` (grouped with auth and inertia middleware):

```php
Route::resource('contacts', ContactController::class)->only('index');
Route::resource('campaigns', CampaignController::class)->only(['index','create','store','show']);
```

---

## Frontend (React + Inertia + shadcn/ui)

Project structure (inside `resources/js`):

```
resources/js/
  Pages/
    Contacts/Index.jsx
    Campaigns/
      Index.jsx
      Create.jsx
      Show.jsx
  Components/
    Table.jsx
    MultiSelectToolbar.jsx
  app.jsx (Inertia root)
```

Below are condensed snippets highlighting critical behavior (selection, submit, status polling).

### Contacts table — `Contacts/Index.jsx`

* Render table rows with checkboxes
* Support `selectAll` using header checkbox
* Expose selected IDs to Campaign Create page via querystring or global state (simple: pass contacts to Campaign Create and select there)

```jsx
import { Inertia } from '@inertiajs/inertia';
import { useState } from 'react';

export default function Index({ contacts }){
  const [selected, setSelected] = useState(new Set());
  const toggle = (id) => {
    const next = new Set(selected);
    next.has(id) ? next.delete(id) : next.add(id);
    setSelected(next);
  }
  const selectAll = () => {
    if(selected.size === contacts.data.length) setSelected(new Set());
    else setSelected(new Set(contacts.data.map(c => c.id)));
  }

  return (
    <div>
      <table>
        <thead>
          <tr><th><input type="checkbox" onChange={selectAll} checked={selected.size===contacts.data.length} /></th> ...</tr>
        </thead>
        <tbody>
          {contacts.data.map(c => (
            <tr key={c.id}>
              <td><input type="checkbox" checked={selected.has(c.id)} onChange={() => toggle(c.id)} /></td>
              <td>{c.name}</td>
              <td>{c.email}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}
```

### Campaign Create — `Campaigns/Create.jsx`

* Form for subject, body (textarea or rich text component).
* List contacts (or pass selected). Multiselect checkboxes.
* Submit via `Inertia.post('/campaigns', form)`.

```jsx
import { Inertia } from '@inertiajs/inertia';
import { useState } from 'react';

export default function Create({ contacts }){
  const [subject,setSubject] = useState('');
  const [body,setBody] = useState('');
  const [selected,setSelected] = useState([]);

  function toggle(id){ setSelected(prev => prev.includes(id) ? prev.filter(x=>x!==id) : [...prev,id]); }

  function submit(e){
    e.preventDefault();
    Inertia.post(route('campaigns.store'), { subject, body, recipients: selected });
  }

  return (
    <form onSubmit={submit}>
      <input value={subject} onChange={e=>setSubject(e.target.value)} placeholder="Subject" />
      <textarea value={body} onChange={e=>setBody(e.target.value)} placeholder="Body"></textarea>

      <div>
        {contacts.map(c => (
          <label key={c.id}><input type="checkbox" onChange={()=>toggle(c.id)} />{c.name} — {c.email}</label>
        ))}
      </div>
      <button type="submit">Create & Send</button>
    </form>
  )
}
```

### Campaign Show — `Campaigns/Show.jsx`

* Display campaign subject/body and table of recipients with status.
* Polling optional: we recommend just using incremental refresh (button "Refresh") or small polling via `setInterval` to call `route('campaigns.show')` as long as there are `pending` recipients.

```jsx
export default function Show({ campaign }){
  return (
    <div>
      <h1>{campaign.subject}</h1>
      <div dangerouslySetInnerHTML={{__html: campaign.body}} />

      <table>
        <thead><tr><th>Contact</th><th>Email</th><th>Status</th><th>Error</th></tr></thead>
        <tbody>
          {campaign.recipients.map(r => (
            <tr key={r.id}><td>{r.contact.name}</td><td>{r.contact.email}</td><td>{r.status}</td><td>{r.error_message}</td></tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}
```

Styling: replace raw elements with shadcn/ui components—Cards, Tables, Buttons, Inputs for a polished look.

---

## Queue & Workers

Use `database` queue driver in `.env` for simplicity:

```
QUEUE_CONNECTION=database
```

Start worker in dev:

```bash
php artisan queue:work
# or for a single-run processing
php artisan queue:listen
```

---

## Error handling & validation

* FormRequest handles validation on server. Inertia responses automatically send back validation errors which you can show inside React using `usePage().props.errors`.
* Job updates recipient status to `failed` with message. You can also broadcast job progress via events to show live UI.
* Use database transactions in service to avoid partial states during campaign creation.

---

## Tests (suggested)

* Unit: CampaignService behaviour (creates campaign, creates recipients, dispatches jobs). Mock the queue.
* Feature: storing campaign with invalid recipients returns validation errors.
* Integration: run a worker and ensure recipients change from pending to sent/failed.

---

## README (example content to put in repo root)

```md
# simple-campaign-manager

Minimal email campaign manager built with Laravel, React and Inertia.

## Setup

1. Clone: `git clone git@github.com:<you>/simple-campaign-manager.git`
2. Install dependencies: `composer install && npm install`
3. Copy env: `cp .env.example .env` and set DB credentials.
4. Migrate & seed: `php artisan migrate --seed`
5. Build assets: `npm run dev`
6. Start queue worker: `php artisan queue:work`
7. Serve app: `php artisan serve`

## Architecture choices

- **Service layer (CampaignService)**: keeps controllers slim; encapsulates transaction and orchestration logic.
- **DTO (CampaignData)**: explicit payload shape; decouples controller request from service.
- **Jobs**: each recipient is processed by a queued job to allow horizontal scaling and retries.
- **Inertia + React**: keeps routing server-driven while letting React manage UI state.
- **Shadcn/ui**: for consistent, accessible UI components. Replace raw HTML with these for production.

## Notes

This project uses *fake sending* in the queued job for simplicity. Replace the job body with `Mail::to($contact)->send(new CampaignMailable(...))` and configure SMTP for real sending.
```

---

## Production considerations

* Use a real reliable queue (Redis) and workers supervised by systemd / supervisor.
* Use a transactional email provider (Sendgrid, Postmark) with webhooks for real delivery tracking (opened, bounced).
* For large recipient lists: chunking and rate limiting; avoid dispatching millions of jobs at once.
* Add metrics and observability (logs, Sentry) and retry policies for transient errors.

---

## Next steps you can ask me to do

* Generate the full file tree with content for each file so you can paste into your repo.
* Create the GitHub repo and produce a ready-to-run zip (I can provide everything to paste).
* Swap fake send for a `Mailable` and show sample `CampaignMailable` code.

---

*End of implementation guide.*

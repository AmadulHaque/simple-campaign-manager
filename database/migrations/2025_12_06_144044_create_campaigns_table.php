<?php

use App\Enums\CampaignStatus;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('subject');
            $table->text('body');

            $table->integer('status')->default(CampaignStatus::DRAFT->value);

            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();

            $table->integer('total_recipients')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);

            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};

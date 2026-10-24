<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('business_registration_number', 60)->nullable()->unique();
            $table->string('industry')->nullable();
            $table->string('business_phone', 30)->nullable();
            $table->string('registered_address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('postcode', 12)->nullable();
            $table->string('state', 40)->nullable();
            $table->string('ssm_certificate_path')->nullable();
            $table->string('ssm_profile_path')->nullable();
            $table->timestamp('terms_accepted_at')->nullable();
            $table->string('account_status', 30)->default('pending');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'business_registration_number',
                'industry',
                'business_phone',
                'registered_address',
                'city',
                'postcode',
                'state',
                'ssm_certificate_path',
                'ssm_profile_path',
                'terms_accepted_at',
                'account_status',
            ]);
        });
    }
};

<?php

namespace Tests\Feature;

use App\Models\BusinessNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_one_notification_only_marks_that_notification_as_read(): void
    {
        $business = User::factory()->create(['account_status' => 'approved']);
        $selected = BusinessNotification::send($business->id, 'Selected notification', 'Inventory update');
        $other = BusinessNotification::send($business->id, 'Another notification', 'Order update');

        $this->actingAs($business)->postJson(route('business.notifications.read', $selected->id))
            ->assertOk()
            ->assertJsonPath('success', true);

        $selected->refresh();
        $this->assertTrue($selected->is_read);
        $this->assertNotNull($selected->read_at);
        $this->assertFalse($other->refresh()->is_read);
    }

    public function test_opening_another_business_notification_returns_404(): void
    {
        $business = User::factory()->create(['account_status' => 'approved']);
        $owner = User::factory()->create(['account_status' => 'approved']);
        $notification = BusinessNotification::send($owner->id, 'Private notification', 'Inventory update');

        $this->actingAs($business)->postJson(route('business.notifications.read', $notification->id))
            ->assertNotFound();

        $this->assertFalse($notification->refresh()->is_read);
    }
}

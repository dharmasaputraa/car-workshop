<?php

namespace Tests\Unit\Listeners;

use App\Events\MechanicAssigned;
use App\Listeners\SendMechanicAssignedNotificationListener;
use App\Models\MechanicAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendMechanicAssignedNotificationListenerTest extends TestCase
{
    use RefreshDatabase;

    private SendMechanicAssignedNotificationListener $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->listener = new SendMechanicAssignedNotificationListener();
    }

    /*
    |--------------------------------------------------------------------------
    | Sends Notification to Mechanic
    |--------------------------------------------------------------------------
    */

    public function test_handle_sends_notification_to_mechanic(): void
    {
        Notification::fake();

        // Create test data
        $mechanic = User::factory()->create();
        $assignment = MechanicAssignment::factory()->create([
            'mechanic_id' => $mechanic->id,
        ]);

        $event = new MechanicAssigned($assignment);

        // Handle the event
        $this->listener->handle($event);

        // Assert notification was sent to the mechanic
        Notification::assertSentTo(
            $mechanic,
            \App\Notifications\MechanicAssignedNotification::class,
            function ($notification, $channels) use ($assignment) {
                return $notification->assignment->id === $assignment->id &&
                    in_array('mail', $channels);
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Does Not Crash When Mechanic is Null
    |--------------------------------------------------------------------------
    */

    public function test_handle_does_not_crash_when_mechanic_is_null(): void
    {
        Notification::fake();

        // Create a mock assignment with null mechanic (edge case)
        $assignment = $this->createMock(MechanicAssignment::class);
        $assignment->mechanic_id = null;

        // Mock the mechanic relationship to return null
        $assignment->method('getAttribute')->willReturnMap([
            ['mechanic', null],
        ]);

        $event = new MechanicAssigned($assignment);

        // Should not throw exception
        $this->listener->handle($event);

        // Assert no notification was sent
        Notification::assertNothingSent();
    }

    /*
    |--------------------------------------------------------------------------
    | Uses Email Queue
    |--------------------------------------------------------------------------
    */

    public function test_listener_uses_email_queue(): void
    {
        Notification::fake();

        $mechanic = User::factory()->create();
        $assignment = MechanicAssignment::factory()->create([
            'mechanic_id' => $mechanic->id,
        ]);

        $event = new MechanicAssigned($assignment);
        $this->listener->handle($event);

        Notification::assertSentTo(
            $mechanic,
            \App\Notifications\MechanicAssignedNotification::class
        );

        // Check that notification is queued (ShouldQueue interface)
        $this->assertContains('emails', [$this->listener->queue]);
    }
}

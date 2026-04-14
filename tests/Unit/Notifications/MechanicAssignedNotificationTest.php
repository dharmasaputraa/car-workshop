<?php

namespace Tests\Unit\Notifications;

use App\Models\Car;
use App\Models\MechanicAssignment;
use App\Models\Service;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderService;
use App\Notifications\MechanicAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MechanicAssignedNotificationTest extends TestCase
{
    use RefreshDatabase;

    private MechanicAssignment $assignment;
    private MechanicAssignedNotification $notification;
    private User $mechanic;

    protected function setUp(): void
    {
        parent::setUp();

        // Set config for frontend URL
        Config::set('app.frontend_url', 'http://localhost:3000');
        Config::set('app.name', 'Car Workshop');

        // Create test data with all relationships
        $car = Car::factory()->create();

        $workOrder = WorkOrder::factory()
            ->for($car)
            ->create([
                'order_number' => 'WO-2024-001',
            ]);

        $service = Service::factory()->create([
            'name' => 'Oil Change',
        ]);

        $workOrderService = WorkOrderService::factory()
            ->for($workOrder)
            ->for($service)
            ->create();

        $mechanic = User::factory()->create([
            'name' => 'John Doe',
        ]);

        $this->assignment = MechanicAssignment::factory()
            ->for($workOrderService)
            ->for($mechanic, 'mechanic')
            ->create();

        $this->notification = new MechanicAssignedNotification($this->assignment);
        $this->mechanic = $mechanic;
    }

    /*
    |--------------------------------------------------------------------------
    | Via Channels
    |--------------------------------------------------------------------------
    */

    public function test_via_returns_mail_channel(): void
    {
        $channels = $this->notification->via($this->mechanic);

        $this->assertIsArray($channels);
        $this->assertContains('mail', $channels);
    }

    /*
    |--------------------------------------------------------------------------
    | To Mail
    |--------------------------------------------------------------------------
    */

    public function test_to_mail_returns_correct_mail_message(): void
    {
        $mail = $this->notification->toMail($this->mechanic);

        $this->assertInstanceOf(MailMessage::class, $mail);

        // Check subject
        $this->assertEquals('New Task Assigned - Car Workshop', $mail->subject);

        // Check greeting (stored in $mail->greeting, not introLines)
        $this->assertStringContainsString('Hello, John Doe!', $mail->greeting);

        // Check content
        $contentLines = implode(' ', $mail->introLines);
        $this->assertStringContainsString('work order', strtolower($contentLines));
        $this->assertStringContainsString('WO-2024-001', $contentLines);
        $this->assertStringContainsString('Oil Change', $contentLines);
    }

    public function test_to_mail_contains_vehicle_information(): void
    {
        $mail = $this->notification->toMail($this->mechanic);
        $car = $this->assignment->workOrderService->workOrder->car;

        $contentLines = implode(' ', $mail->introLines);

        $this->assertStringContainsString($car->brand, $contentLines);
        $this->assertStringContainsString($car->model, $contentLines);
        $this->assertStringContainsString((string) $car->year, $contentLines);
        $this->assertStringContainsString($car->plate_number, $contentLines);
    }

    public function test_to_mail_contains_action_button(): void
    {
        $mail = $this->notification->toMail($this->mechanic);

        $actionUrl = 'http://localhost:3000/mechanic/tasks/' . $this->assignment->id;
        $actionText = 'View Task Details';

        $this->assertEquals($actionUrl, $mail->actionUrl);
        $this->assertEquals($actionText, $mail->actionText);
    }

    /*
    |--------------------------------------------------------------------------
    | To Array
    |--------------------------------------------------------------------------
    */

    public function test_to_array_returns_correct_data_structure(): void
    {
        $array = $this->notification->toArray($this->mechanic);

        $this->assertIsArray($array);
        $this->assertArrayHasKey('assignment_id', $array);
        $this->assertArrayHasKey('message', $array);

        $this->assertEquals($this->assignment->id, $array['assignment_id']);

        $car = $this->assignment->workOrderService->workOrder->car;
        $this->assertStringContainsString($car->plate_number, $array['message']);
        $this->assertStringContainsString('new task', strtolower($array['message']));
    }

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */

    public function test_notification_uses_email_queue(): void
    {
        $this->assertContains('emails', [$this->notification->queue]);
    }
}

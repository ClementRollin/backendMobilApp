<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\AccessService;
use PHPUnit\Framework\TestCase;

class AccessServiceRoleTest extends TestCase
{
    public function test_role_helpers_detect_expected_roles(): void
    {
        $service = new AccessService();

        $cto = new User(['role' => UserRole::CTO->value]);
        $lead = new User(['role' => UserRole::LEAD_DEV->value]);
        $developer = new User(['role' => UserRole::DEVELOPER->value]);
        $po = new User(['role' => UserRole::PO->value]);

        $this->assertTrue($service->isCto($cto));
        $this->assertTrue($service->isLead($lead));
        $this->assertTrue($service->isDeveloper($developer));
        $this->assertTrue($service->isPo($po));
        $this->assertFalse($service->isCto($developer));
    }
}


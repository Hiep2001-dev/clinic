<?php

namespace Tests\Feature;

use Tests\TestCase;

class ClinicRouteTest extends TestCase
{
    public function test_public_clinic_page_is_available(): void
    {
        $response = $this->get('/clinic');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_admin_login_page_is_available(): void
    {
        $response = $this->get('/clinic/admin/login');

        $response->assertOk();
        $response->assertSee('Đăng nhập quản trị');
    }
}

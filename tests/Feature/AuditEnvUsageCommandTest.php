<?php

namespace Primalmaxor\LaravelEnvDoctor\Tests\Feature;

use Orchestra\Testbench\TestCase;
use Primalmaxor\LaravelEnvDoctor\LaravelEnvDoctorServiceProvider;

class AuditEnvUsageCommandTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            LaravelEnvDoctorServiceProvider::class,
        ];
    }

    /** @test */
    public function it_can_register_the_audit_command()
    {
        $this->artisan('list')->assertExitCode(0);
    }
} 
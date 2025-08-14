<?php

namespace Primalmaxor\LaravelEnvDoctor\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Primalmaxor\LaravelEnvDoctor\Console\Commands\CompareEnvCommand;

class EnvFileParserTest extends TestCase
{
    private CompareEnvCommand $command;

    protected function setUp(): void
    {
        $this->command = new CompareEnvCommand();
    }

    /** @test */
    public function it_can_parse_env_file_content()
    {
        $content = "APP_NAME=Laravel\nAPP_ENV=local\nAPP_DEBUG=true\n# Comment\n\nDB_HOST=127.0.0.1";
        
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('parseEnvFile');
        $method->setAccessible(true);
        
        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'env_test');
        file_put_contents($tempFile, $content);
        
        $result = $method->invoke($this->command, $tempFile);
        
        unlink($tempFile);
        
        $expected = [
            'APP_NAME' => 'Laravel',
            'APP_ENV' => 'local',
            'APP_DEBUG' => 'true',
            'DB_HOST' => '127.0.0.1'
        ];
        
        $this->assertEquals($expected, $result);
    }
} 
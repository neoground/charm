<?php
declare(strict_types=1);

namespace Tests\Framework;

use Charm\Vivid\C;
use PHPUnit\Framework\TestCase;

final class BootTest extends TestCase
{
    public function testKernelBoots(): void
    {
        $k = C::App();
        $this->assertNotNull($k);
        $this->assertTrue(is_dir(C::Storage()->getCachePath()));
        $this->assertTrue(is_dir(C::Storage()->getDataPath()));
        $this->assertTrue(is_dir(C::Storage()->getVarPath()));
    }
}

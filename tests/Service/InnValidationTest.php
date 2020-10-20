<?php

namespace App\Tests\Service;

use App\Service\InnValidation;
use PHPUnit\Framework\TestCase;

class InnValidationTest extends TestCase
{
    public function testCheckIndividualInn(): void
    {
        $innValidation = new InnValidation();
        $result = $innValidation->checkIndividualInn('665805954074');

        $this->assertEquals(true, $result);
    }

    public function testCheckCompanyInn(): void
    {
        $innValidation = new InnValidation();
        $result = $innValidation->checkCompanyInn('7736207543');

        $this->assertEquals(true, $result);
    }
}

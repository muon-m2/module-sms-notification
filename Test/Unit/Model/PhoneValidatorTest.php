<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Muon\SMSNotification\Model\PhoneValidator;

class PhoneValidatorTest extends TestCase
{
    private PhoneValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new PhoneValidator();
    }

    /**
     * @dataProvider phoneDataProvider
     */
    public function testIsValid(string $phone, bool $expected): void
    {
        $this->assertEquals($expected, $this->validator->isValid($phone));
    }

    public static function phoneDataProvider(): array
    {
        return [
            ['+1234567890', true],
            ['+15555555555', true],
            ['+442071838750', true],
            ['1234567890', false], // No plus
            ['+01234567890', false], // Starts with 0 after plus
            ['+1', true], // Minimum length? E.164 says up to 15 digits.
            ['+123456789012345', true], // 15 digits
            ['+1234567890123456', false], // 16 digits
            ['+1abc', false], // Non-digits
            ['', false]
        ];
    }
}

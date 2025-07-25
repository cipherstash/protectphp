<?php

declare(strict_types=1);

namespace CipherStash\Protect\Tests\Unit\Exceptions;

use CipherStash\Protect\Exceptions\EncryptException;
use PHPUnit\Framework\TestCase;

class EncryptExceptionTest extends TestCase
{
    public function test_failed_to_encrypt(): void
    {
        $reason = 'Invalid configuration provided';
        $exception = EncryptException::failedToEncrypt($reason);

        $this->assertInstanceOf(EncryptException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
    }

    public function test_failed_to_encrypt_attributes(): void
    {
        $reason = 'Invalid attribute encryption request';
        $exception = EncryptException::failedToEncryptAttributes($reason);

        $this->assertInstanceOf(EncryptException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
    }
}

<?php

declare(strict_types=1);

namespace CipherStash\Protect\Tests\Unit\Exceptions;

use CipherStash\Protect\Exceptions\DecryptException;
use PHPUnit\Framework\TestCase;

class DecryptExceptionTest extends TestCase
{
    public function test_failed_to_decrypt(): void
    {
        $reason = 'Invalid ciphertext format';
        $exception = DecryptException::failedToDecrypt($reason);

        $this->assertInstanceOf(DecryptException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
    }

    public function test_failed_to_decrypt_attributes(): void
    {
        $reason = 'Invalid attribute decryption request';
        $exception = DecryptException::failedToDecryptAttributes($reason);

        $this->assertInstanceOf(DecryptException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
    }
}

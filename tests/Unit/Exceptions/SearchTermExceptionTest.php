<?php

declare(strict_types=1);

namespace CipherStash\Protect\Tests\Unit\Exceptions;

use CipherStash\Protect\Exceptions\SearchTermException;
use PHPUnit\Framework\TestCase;

class SearchTermExceptionTest extends TestCase
{
    public function test_failed_to_create_search_terms(): void
    {
        $reason = 'Invalid search term format';
        $exception = SearchTermException::failedToCreateSearchTerms($reason);

        $this->assertInstanceOf(SearchTermException::class, $exception);
        $this->assertNotEmpty($exception->getMessage());
    }
}

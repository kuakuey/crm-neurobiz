<?php

namespace Tests\Unit;

use App\Support\PhoneNormalizer;
use PHPUnit\Framework\TestCase;

class PhoneNormalizerTest extends TestCase
{
    public function test_normalizes_ecuador_local_mobile(): void
    {
        $this->assertSame('+593980918462', PhoneNormalizer::toE164('0980918462'));
        $this->assertSame('+593980918462', PhoneNormalizer::toE164('098 091 8462'));
        $this->assertSame('+593980918462', PhoneNormalizer::toE164('980918462'));
        $this->assertSame('+593980918462', PhoneNormalizer::toE164('+593 98 091 8462'));
        $this->assertSame('+593980918462', PhoneNormalizer::toE164('593980918462'));
    }

    public function test_returns_null_for_empty(): void
    {
        $this->assertNull(PhoneNormalizer::toE164(''));
        $this->assertNull(PhoneNormalizer::toE164(null));
    }
}

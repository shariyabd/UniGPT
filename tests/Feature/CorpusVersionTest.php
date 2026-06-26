<?php

namespace Tests\Feature;

use App\Domain\RAG\Support\CorpusVersion;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CorpusVersionTest extends TestCase
{
    public function test_bump_invalidates_by_advancing_the_version(): void
    {
        Cache::flush();

        $initial = CorpusVersion::current();
        $this->assertSame($initial, CorpusVersion::current(), 'version is stable until bumped');

        CorpusVersion::bump();
        $this->assertGreaterThan($initial, CorpusVersion::current());

        $afterFirstBump = CorpusVersion::current();
        CorpusVersion::bump();
        $this->assertGreaterThan($afterFirstBump, CorpusVersion::current());
    }
}

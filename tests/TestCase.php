<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Livewire\Livewire;

abstract class TestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        // Livewire keeps a few per-request flags in static properties (for
        // example "a component rendered, so auto-inject the assets"). In a real
        // request the process ends and they reset; in the test runner the
        // process is shared, so a Livewire render in one test leaks into the
        // next one's plain HTML response — the public assessment form picked up
        // `<!-- Livewire Styles -->` and looked like it used Livewire.
        Livewire::flushState();

        parent::tearDown();
    }
}

<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Unit tests are pure and need no Laravel application, so they bind to the
| base PHPUnit test case. When Feature tests are added later, create a
| Tests\TestCase (extending Illuminate\Foundation\Testing\TestCase) and bind
| it here with ->in('Feature').
|
*/

uses(PHPUnit\Framework\TestCase::class)->in('Unit');

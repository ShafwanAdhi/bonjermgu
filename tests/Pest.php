<?php

use Tests\Support\SerializesTestingDatabase;
use Tests\TestCase;

uses(TestCase::class, SerializesTestingDatabase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

<?php

namespace DeptOfScrapyardRobotics\Tests;

it('has the ext-ftdi extension loaded', function (): void {
    expect(extension_loaded('ftdi'))->toBeTrue();

    $version = phpversion('ftdi');
    expect($version)->toBeString();
    expect($version)->toMatch('/^\d+\.\d+\.\d+/');
});

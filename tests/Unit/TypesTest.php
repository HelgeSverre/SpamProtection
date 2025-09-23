<?php

declare(strict_types=1);

use Helge\SpamProtection\Types;

test('email enum value')
    ->expect(Types::EMAIL->value)->toBe('email');

test('username enum value')
    ->expect(Types::USERNAME->value)->toBe('username');

test('IP enum value')
    ->expect(Types::IP->value)->toBe('ip');

test('enum cases')
    ->tap(function () {
        $cases = Types::cases();
        expect($cases)->toHaveCount(3);
        expect($cases)->toContain(Types::EMAIL);
        expect($cases)->toContain(Types::USERNAME);
        expect($cases)->toContain(Types::IP);
    });

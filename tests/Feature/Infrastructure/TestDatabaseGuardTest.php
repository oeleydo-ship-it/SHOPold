<?php

// Safety net: RefreshDatabase wipes the database it runs against, so it must never be the live one.
test('tests run against the separate test database, never the live store', function () {
    expect(config('database.default'))->toBe('mysql')
        ->and(config('database.connections.mysql.database'))->toBe('shoping_test');
});

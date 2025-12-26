<?php

declare(strict_types=1);

use App\Support\Format;

\uses()->group('unit');

\covers(Format::class);

/**
 * @covers \App\Support\Format
 */
test('Format::number() formats with Danish format', function () {
    expect(Format::number(1234.56))->toBe('1.234,56');
    expect(Format::number(100.00))->toBe('100');
    expect(Format::number(1000))->toBe('1.000');
    expect(Format::number(0.5))->toBe('0,5');
    expect(Format::number(0.01))->toBe('0,01');
});

/**
 * @covers \App\Support\Format
 */
test('Format::number() handles different decimal places', function () {
    expect(Format::number(1234.567, 0))->toBe('1.235');
    expect(Format::number(1234.567, 1))->toBe('1.234,6');
    expect(Format::number(1234.567, 2))->toBe('1.234,57');
    expect(Format::number(1234.567, 3))->toBe('1.234,567');
});

/**
 * @covers \App\Support\Format
 */
test('Format::number() removes trailing zeros', function () {
    expect(Format::number(100.00))->toBe('100');
    expect(Format::number(100.10))->toBe('100,1');
    expect(Format::number(100.01))->toBe('100,01');
    expect(Format::number(100.50))->toBe('100,5');
});

/**
 * @covers \App\Support\Format
 */
test('Format::number() handles null values', function () {
    expect(Format::number(null))->toBe('');
    expect(Format::number(null, 0))->toBe('');
});

/**
 * @covers \App\Support\Format
 */
test('Format::number() handles zero', function () {
    expect(Format::number(0))->toBe('0');
    expect(Format::number(0.0))->toBe('0');
    expect(Format::number(0.00))->toBe('0');
});

/**
 * @covers \App\Support\Format
 */
test('Format::number() handles large numbers', function () {
    expect(Format::number(1234567.89))->toBe('1.234.567,89');
    expect(Format::number(999999.99))->toBe('999.999,99');
});

/**
 * @covers \App\Support\Format
 */
test('Format::dkk() formats monetary values', function () {
    expect(Format::dkk(1234.56))->toBe('1.234,56 kr.');
    expect(Format::dkk(100.00))->toBe('100 kr.');
    expect(Format::dkk(0.50))->toBe('0,5 kr.');
    expect(Format::dkk(0))->toBe('0 kr.');
});

/**
 * @covers \App\Support\Format
 */
test('Format::dkk() removes trailing zeros', function () {
    expect(Format::dkk(100.00))->toBe('100 kr.');
    expect(Format::dkk(100.10))->toBe('100,1 kr.');
    expect(Format::dkk(100.50))->toBe('100,5 kr.');
});

/**
 * @covers \App\Support\Format
 */
test('Format::dkk() handles null values', function () {
    expect(Format::dkk(null))->toBe('');
});

/**
 * @covers \App\Support\Format
 */
test('Format::pct() formats percentage values', function () {
    expect(Format::pct(50))->toBe('50 %');
    expect(Format::pct(50.00))->toBe('50 %');
    expect(Format::pct(50.5))->toBe('50,5 %');
    expect(Format::pct(0.5))->toBe('0,5 %');
    expect(Format::pct(100))->toBe('100 %');
});

/**
 * @covers \App\Support\Format
 */
test('Format::pct() removes trailing zeros', function () {
    expect(Format::pct(50.00))->toBe('50 %');
    expect(Format::pct(50.10))->toBe('50,1 %');
    expect(Format::pct(50.01))->toBe('50,01 %');
});

/**
 * @covers \App\Support\Format
 */
test('Format::pct() handles null values', function () {
    expect(Format::pct(null))->toBe('');
});

/**
 * @covers \App\Support\Format
 */
test('Format::pct() always shows % suffix with space', function () {
    expect(Format::pct(0))->toBe('0 %');
    expect(Format::pct(100))->toBe('100 %');
});

/**
 * @covers \App\Support\Format
 */
test('Format::integer() formats integers with thousand separators', function () {
    expect(Format::integer(1234))->toBe('1.234');
    expect(Format::integer(1000))->toBe('1.000');
    expect(Format::integer(1234567))->toBe('1.234.567');
    expect(Format::integer(0))->toBe('0');
    expect(Format::integer(999))->toBe('999');
});

/**
 * @covers \App\Support\Format
 */
test('Format::integer() handles suffix', function () {
    expect(Format::integer(1234, 'stk.'))->toBe('1.234 stk.');
    expect(Format::integer(1000, 'stk.'))->toBe('1.000 stk.');
    expect(Format::integer(0, 'stk.'))->toBe('0 stk.');
});

/**
 * @covers \App\Support\Format
 */
test('Format::integer() handles null values', function () {
    expect(Format::integer(null))->toBe('');
    expect(Format::integer(null, 'stk.'))->toBe('');
});

/**
 * @covers \App\Support\Format
 */
test('Format::integer() handles large numbers', function () {
    expect(Format::integer(1234567890))->toBe('1.234.567.890');
    expect(Format::integer(999999999))->toBe('999.999.999');
});


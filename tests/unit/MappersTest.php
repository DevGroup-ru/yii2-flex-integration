<?php

use Codeception\Specify;
use DevGroup\FlexIntegration\abstractEntity\mappers\Replace;
use DevGroup\FlexIntegration\abstractEntity\mappers\Typecast;
use DevGroup\FlexIntegration\abstractEntity\mappers\UppercaseString;

class MappersTest extends \Codeception\Test\Unit
{
    use Specify;
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function testReplace()
    {
        $mapper = new Replace([
            'search' => '.',
            'replace' => ','
        ]);
        $this->assertSame(
            '123,,94,,758',
            $mapper->map('123.,94..758')
        );

        $mapper = new Replace([
            'search' => '/[^0-9]/i',
            'replace' => '',
            'isRegExp' => true,
        ]);
        $this->assertSame(
            '12394758',
            $mapper->map('123.,94..758')
        );

        $mapper = new Replace([
            'search' => 'foo',
            'replace' => 'bar',
            'caseInsensitive' => true,
        ]);
        $this->assertSame(
            'barbarJJJbarO',
            $mapper->map('fooFoOJJJFOOO')
        );
        $mapper->caseInsensitive = false;
        $this->assertSame(
            'barFoOJJJFOOO',
            $mapper->map('fooFoOJJJFOOO')
        );
    }

    public function testTypecast()
    {
        $mapper = new Typecast();
        $mapper->type = Typecast::TYPE_INT;
        $this->assertSame(
            123,
            $mapper->map('123.1')
        );
        $this->assertSame(
            123,
            $mapper->map(123.1)
        );
        $mapper->type = Typecast::TYPE_FLOAT;
        $this->assertSame(
            123.1,
            $mapper->map('123.1')
        );
        $this->assertSame(
            123.1,
            $mapper->map(123.1)
        );
        $mapper->type = Typecast::TYPE_BOOL;
        $this->assertSame(
            true,
            $mapper->map('123.1')
        );
        $this->assertSame(
            true,
            $mapper->map(1)
        );
        $this->assertSame(
            true,
            $mapper->map('1')
        );
        $this->assertSame(
            false,
            $mapper->map('0')
        );
        $this->assertSame(
            false,
            $mapper->map(0)
        );
        $this->assertSame(
            true,
            $mapper->map(-1)
        );
        $this->assertSame(
            true,
            $mapper->map(-123)
        );
        $this->assertSame(
            false,
            $mapper->map('')
        );
    }

    public function testUppercase()
    {
        $mapper = new UppercaseString();
        $this->assertSame('FOO', $mapper->map('FoO'));
    }
}
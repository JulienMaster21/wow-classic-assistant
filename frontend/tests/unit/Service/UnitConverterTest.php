<?php

namespace App\Tests\Unit\Service;

use App\Service\UnitConverter;
use PHPUnit\Framework\TestCase;

class UnitConverterTest extends TestCase {

    protected UnitConverter $unitConverter;

    public function setUp(): void {

        $this->unitConverter = new UnitConverter();
    }

    /**
     * Check if the returned values are correct
     */
    public function testIntToMoneyReturnsRightValue() {

        $values = [
            [
                'input'     => 0,
                'expected'  => '0<span class="money money-copper ml-1 mr-2"></span>'
            ],
            [
                'input'     => 1,
                'expected'  => '1<span class="money money-copper ml-1 mr-2"></span>'
            ],
            [
                'input'     => 10,
                'expected'  => '10<span class="money money-copper ml-1 mr-2"></span>'
            ],
            [
                'input'     => 100,
                'expected'  => '1<span class="money money-silver ml-1 mr-2"></span>00<span class="money money-copper ml-1 mr-2"></span>'
            ],
            [
                'input'     => 1000,
                'expected'  => '10<span class="money money-silver ml-1 mr-2"></span>00<span class="money money-copper ml-1 mr-2"></span>'
            ],
            [
                'input'     => 10000,
                'expected'  => '1<span class="money money-gold ml-1 mr-2"></span>00<span class="money money-silver ml-1 mr-2"></span>00<span class="money money-copper ml-1 mr-2"></span>'
            ]
        ];

        foreach ($values as $value) {

            $result = $this->unitConverter->convertIntToMoneyAmount($value['input']);
            $this->assertTrue(  $result === $value['expected'],
                                sprintf('trying to convert %s should return %s, but returned %s.',
                                        strval($value['input']),
                                        strval($value['expected']),
                                        strval($result))
            );
        }
    }

    /**
     * Check if only integers are converted
     */
    public function testIntToMoneyDontConvertWrongValues() {

        $values = [
            NAN,
            1.2,
            null,
            'someMoney',
            'exposure',
            [0,1,2],
            false,
            true
        ];

        foreach ($values as $value) {

            $result = $this->unitConverter->convertIntToMoneyAmount($value);
            $this->assertTrue(  $result === 'The argument provided isn\'t an integer.',
                                sprintf('trying to convert %s should not be possible',
                                        gettype($value) === 'array' ? join(', ', $value) : strval($value))
            );
        }
    }

    /**
     * Check if negative values are correctly handled
     */
    public function testIntToMoneyNegativeInputReturnsPositiveOutput() {

        $values = [
            [
                'input'     => -0,
                'expected'  => '0<span class="money money-copper ml-1 mr-2"></span>'
            ],
            [
                'input'     => -1,
                'expected'  => '1<span class="money money-copper ml-1 mr-2"></span>'
            ],
            [
                'input'     => -10,
                'expected'  => '10<span class="money money-copper ml-1 mr-2"></span>'
            ],
            [
                'input'     => -100,
                'expected'  => '1<span class="money money-silver ml-1 mr-2"></span>00<span class="money money-copper ml-1 mr-2"></span>'
            ],
            [
                'input'     => -1000,
                'expected'  => '10<span class="money money-silver ml-1 mr-2"></span>00<span class="money money-copper ml-1 mr-2"></span>'
            ],
            [
                'input'     => -10000,
                'expected'  => '1<span class="money money-gold ml-1 mr-2"></span>00<span class="money money-silver ml-1 mr-2"></span>00<span class="money money-copper ml-1 mr-2"></span>'
            ]
        ];

        foreach ($values as $value) {

            $result = $this->unitConverter->convertIntToMoneyAmount($value['input']);
            $this->assertTrue(  $result === $value['expected'],
                                sprintf('trying to convert %s should return %s, but returned %s.',
                                        strval($value['input']),
                                        strval($value['expected']),
                                        strval($result))
            );
        }
    }

    /**
     * Check if the returned values are correct
     */
    public function testMoneyToIntReturnsRightValues() {

        $values = [
            [
                'input'     => '0<span class="money money-copper ml-1 mr-2"></span>',
                'expected'  => 0
            ],
            [
                'input'     => '0<span class="money money-gold ml-1 mr-2"></span>00<span class="money money-silver ml-1 mr-2"></span>00<span class="money money-copper ml-1 mr-2"></span>',
                'expected'  => 0
            ],
            [
                'input'     => '1<span class="money money-copper ml-1 mr-2"></span>',
                'expected'  => 1
            ],
            [
                'input'     => '0<span class="money money-gold ml-1 mr-2"></span>00<span class="money money-silver ml-1 mr-2"></span>1<span class="money money-copper ml-1 mr-2"></span>',
                'expected'  => 1
            ],
            [
                'input'     => '10<span class="money money-copper ml-1 mr-2"></span>',
                'expected'  => 10
            ],
            [
                'input'     => '0<span class="money money-gold ml-1 mr-2"></span>00<span class="money money-silver ml-1 mr-2"></span>10<span class="money money-copper ml-1 mr-2"></span>',
                'expected'  => 10
            ],
            [
                'input'     => '1<span class="money money-silver ml-1 mr-2"></span>',
                'expected'  => 100
            ],
            [
                'input'     => '0<span class="money money-gold ml-1 mr-2"></span>1<span class="money money-silver ml-1 mr-2"></span>00<span class="money money-copper ml-1 mr-2"></span>',
                'expected'  => 100
            ],
            [
                'input'     => '10<span class="money money-silver ml-1 mr-2"></span>',
                'expected'  => 1000
            ],
            [
                'input'     => '0<span class="money money-gold ml-1 mr-2"></span>10<span class="money money-silver ml-1 mr-2"></span>00<span class="money money-copper ml-1 mr-2"></span>',
                'expected'  => 1000
            ],
            [
                'input'     => '1<span class="money money-gold ml-1 mr-2"></span>',
                'expected'  => 10000
            ],
            [
                'input'     => '1<span class="money money-gold ml-1 mr-2"></span>00<span class="money money-silver ml-1 mr-2"></span>00<span class="money money-copper ml-1 mr-2"></span>',
                'expected'  => 10000
            ]
        ];

        foreach ($values as $value) {

            $result = $this->unitConverter->convertMoneyStringToInt($value['input']);
            $this->assertTrue(  $result === $value['expected'],
                                sprintf('trying to convert %s should return %s, but returned %s.',
                                        strval($value['input']),
                                        strval($value['expected']),
                                        strval($result))
            );
        }
    }

    /**
     * Check if only strings are converted
     */
    public function testMoneyToIntDontConvertWrongValues() {

        $values = [
            NAN,
            1.2,
            null,
            0,
            2854,
            [0,1,2],
            true,
            false
        ];

        foreach ($values as $value) {

            $result = $this->unitConverter->convertMoneyStringToInt($value);
            $this->assertTrue(  $result === 'The argument provided isn\'t a string.',
                                sprintf('trying to convert %s should not be possible',
                                        gettype($value) === 'array' ? join(', ', $value) : strval($value))
            );
        }
    }

    /**
     * Check if strings that don't follow the format return the expected output
     */
    public function testMoneyToIntIncorrectValuesReturnExpectedOutput() {

        $values = [
            [
                'input'     => 'test',
                'expected'  => 'The argument couldn\'t be converted to an integer.'
            ],
            [
                'input'     => '2<span class="money money-copper ml-1 mr-2"></span>1<span class="money money-copper ml-1 mr-2"></span>',
                'expected'  => 2
            ],
            [
                'input'     => '2<span class="money money-silver ml-1 mr-2"></span>1<span class="money money-silver ml-1 mr-2"></span>',
                'expected'  => 200
            ],
            [
                'input'     => '2<span class="money money-gold ml-1 mr-2"></span>1<span class="money money-gold ml-1 mr-2"></span>',
                'expected'  => 20000
            ],
            [
                'input'     => '2<span class="money money-gold ml-1 mr-2"></span>1<span class="money money-silver ml-1 mr-2"></span>35<span class="money money-copper ml-1 mr-2"></span>1<span class="money money-gold ml-1 mr-2"></span>',
                'expected'  => 20000
            ]
        ];

        foreach ($values as $value) {

            $result = $this->unitConverter->convertMoneyStringToInt($value['input']);
            $this->assertTrue(  $result === $value['expected'],
                                sprintf('trying to convert %s should return %s, but returned %s.',
                                        strval($value['input']),
                                        strval($value['expected']),
                                        strval($result))
            );
        }
    }
}
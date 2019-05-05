<?php
declare(strict_types=1);
/**
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @copyright     Copyright (c) Brian Nesbitt <brian@nesbot.com>
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Chronos\Test\DateTime;

use Cake\Chronos\ChronosInterface;
use TestCase;

class ComparisonTest extends TestCase
{
    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testGetSetWeekendDays($class)
    {
        $expected = [ChronosInterface::SATURDAY, ChronosInterface::SUNDAY];
        $this->assertEquals($expected, $class::getWeekendDays());

        $replace = [ChronosInterface::SUNDAY];
        $class::setWeekendDays($replace);
        $this->assertEquals($replace, $class::getWeekendDays());

        $class::setWeekendDays($expected);
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testEqualToTrue($class)
    {
        $this->assertTrue($class::createFromDate(2000, 1, 1)->eq($class::createFromDate(2000, 1, 1)));
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testEqualToFalse($class)
    {
        $this->assertFalse($class::createFromDate(2000, 1, 1)->eq($class::createFromDate(2000, 1, 2)));
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testEqualWithTimezoneTrue($class)
    {
        $this->assertTrue($class::create(2000, 1, 1, 12, 0, 0, 'America/Toronto')->eq($class::create(
            2000,
            1,
            1,
            9,
            0,
            0,
            'America/Vancouver'
        )));
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testEqualWithTimezoneFalse($class)
    {
        $this->assertFalse($class::createFromDate(2000, 1, 1, 'America/Toronto')->eq($class::createFromDate(
            2000,
            1,
            1,
            'America/Vancouver'
        )));
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testNotEqualToTrue($class)
    {
        $this->assertTrue($class::createFromDate(2000, 1, 1)->ne($class::createFromDate(2000, 1, 2)));
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testNotEqualToFalse($class)
    {
        $this->assertFalse($class::createFromDate(2000, 1, 1)->ne($class::createFromDate(2000, 1, 1)));
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testNotEqualWithTimezone($class)
    {
        $this->assertTrue($class::createFromDate(2000, 1, 1, 'America/Toronto')->ne($class::createFromDate(
            2000,
            1,
            1,
            'America/Vancouver'
        )));
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testGreaterThanTrue($class)
    {
        $this->assertTrue($class::createFromDate(2000, 1, 1)->gt($class::createFromDate(1999, 12, 31)));
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testGreaterThanFalse($class)
    {
        $this->assertFalse($class::createFromDate(2000, 1, 1)->gt($class::createFromDate(2000, 1, 2)));
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testGreaterThanWithTimezoneTrue($class)
    {
        $dt1 = $class::create(2000, 1, 1, 12, 0, 0, 'America/Toronto');
        $dt2 = $class::create(2000, 1, 1, 8, 59, 59, 'America/Vancouver');
        $this->assertTrue($dt1->gt($dt2));
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testGreaterThanWithTimezoneFalse($class)
    {
        $dt1 = $class::create(2000, 1, 1, 12, 0, 0, 'America/Toronto');
        $dt2 = $class::create(2000, 1, 1, 9, 0, 1, 'America/Vancouver');
        $this->assertFalse($dt1->gt($dt2));
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testGreaterThanOrEqualTrue($class)
    {
        $this->assertTrue($class::createFromDate(2000, 1, 1)->gte($class::createFromDate(1999, 12, 31)));
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testGreaterThanOrEqualTrueEqual($class)
    {
        $this->assertTrue($class::createFromDate(2000, 1, 1)->gte($class::createFromDate(2000, 1, 1)));
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testGreaterThanOrEqualFalse($class)
    {
        $this->assertFalse($class::createFromDate(2000, 1, 1)->gte($class::createFromDate(2000, 1, 2)));
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testLessThanTrue($class)
    {
        $this->assertTrue($class::createFromDate(2000, 1, 1)->lt($class::createFromDate(2000, 1, 2)));
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testLessThanFalse($class)
    {
        $this->assertFalse($class::createFromDate(2000, 1, 1)->lt($class::createFromDate(1999, 12, 31)));
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testLessThanOrEqualTrue($class)
    {
        $this->assertTrue($class::createFromDate(2000, 1, 1)->lte($class::createFromDate(2000, 1, 2)));
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testLessThanOrEqualTrueEqual($class)
    {
        $this->assertTrue($class::createFromDate(2000, 1, 1)->lte($class::createFromDate(2000, 1, 1)));
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testLessThanOrEqualFalse($class)
    {
        $this->assertFalse($class::createFromDate(2000, 1, 1)->lte($class::createFromDate(1999, 12, 31)));
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testBetweenEqualTrue($class)
    {
        $this->assertTrue($class::createFromDate(2000, 1, 15)->between(
            $class::createFromDate(2000, 1, 1),
            $class::createFromDate(2000, 1, 31),
            true
        ));
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testBetweenNotEqualTrue($class)
    {
        $this->assertTrue($class::createFromDate(2000, 1, 15)->between(
            $class::createFromDate(2000, 1, 1),
            $class::createFromDate(2000, 1, 31),
            false
        ));
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testBetweenEqualFalse($class)
    {
        $this->assertFalse($class::createFromDate(1999, 12, 31)->between(
            $class::createFromDate(2000, 1, 1),
            $class::createFromDate(2000, 1, 31),
            true
        ));
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testBetweenNotEqualFalse($class)
    {
        $this->assertFalse($class::createFromDate(2000, 1, 1)->between(
            $class::createFromDate(2000, 1, 1),
            $class::createFromDate(2000, 1, 31),
            false
        ));
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testBetweenEqualSwitchTrue($class)
    {
        $this->assertTrue($class::createFromDate(2000, 1, 15)->between(
            $class::createFromDate(2000, 1, 31),
            $class::createFromDate(2000, 1, 1),
            true
        ));
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testBetweenNotEqualSwitchTrue($class)
    {
        $this->assertTrue($class::createFromDate(2000, 1, 15)->between(
            $class::createFromDate(2000, 1, 31),
            $class::createFromDate(2000, 1, 1),
            false
        ));
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testBetweenEqualSwitchFalse($class)
    {
        $this->assertFalse($class::createFromDate(1999, 12, 31)->between(
            $class::createFromDate(2000, 1, 31),
            $class::createFromDate(2000, 1, 1),
            true
        ));
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testBetweenNotEqualSwitchFalse($class)
    {
        $this->assertFalse($class::createFromDate(2000, 1, 1)->between(
            $class::createFromDate(2000, 1, 31),
            $class::createFromDate(2000, 1, 1),
            false
        ));
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testMinIsFluid($class)
    {
        $dt = $class::now();
        $this->assertTrue($dt->min() instanceof $class);
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testMinWithNow($class)
    {
        $dt = $class::create(2012, 1, 1, 0, 0, 0)->min();
        $this->assertDateTime($dt, 2012, 1, 1, 0, 0, 0);
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testMinWithInstance($class)
    {
        $dt1 = $class::create(2013, 12, 31, 23, 59, 59);
        $dt2 = $class::create(2012, 1, 1, 0, 0, 0)->min($dt1);
        $this->assertDateTime($dt2, 2012, 1, 1, 0, 0, 0);
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testMaxIsFluid($class)
    {
        $dt = $class::now();
        $this->assertTrue($dt->max() instanceof $class);
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testMaxWithNow($class)
    {
        $dt = $class::create(2099, 12, 31, 23, 59, 59)->max();
        $this->assertDateTime($dt, 2099, 12, 31, 23, 59, 59);
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testMaxWithInstance($class)
    {
        $dt1 = $class::create(2012, 1, 1, 0, 0, 0);
        $dt2 = $class::create(2099, 12, 31, 23, 59, 59)->max($dt1);
        $this->assertDateTime($dt2, 2099, 12, 31, 23, 59, 59);
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testIsBirthday($class)
    {
        $dt = $class::now();
        $aBirthday = $dt->subYear();
        $this->assertTrue($aBirthday->isBirthday());
        $notABirthday = $dt->subDay();
        $this->assertFalse($notABirthday->isBirthday());
        $alsoNotABirthday = $dt->addDays(2);
        $this->assertFalse($alsoNotABirthday->isBirthday());

        $dt1 = $class::createFromDate(1987, 4, 23);
        $dt2 = $class::createFromDate(2014, 9, 26);
        $dt3 = $class::createFromDate(2014, 4, 23);
        $this->assertFalse($dt2->isBirthday($dt1));
        $this->assertTrue($dt3->isBirthday($dt1));
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testClosest($class)
    {
        $instance = $class::create(2015, 5, 28, 12, 0, 0);
        $dt1 = $class::create(2015, 5, 28, 11, 0, 0);
        $dt2 = $class::create(2015, 5, 28, 14, 0, 0);
        $closest = $instance->closest($dt1, $dt2);
        $this->assertEquals($dt1, $closest);
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testClosestWithEquals($class)
    {
        $instance = $class::create(2015, 5, 28, 12, 0, 0);
        $dt1 = $class::create(2015, 5, 28, 12, 0, 0);
        $dt2 = $class::create(2015, 5, 28, 14, 0, 0);
        $closest = $instance->closest($dt1, $dt2);
        $this->assertEquals($dt1, $closest);
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testFarthest($class)
    {
        $instance = $class::create(2015, 5, 28, 12, 0, 0);
        $dt1 = $class::create(2015, 5, 28, 11, 0, 0);
        $dt2 = $class::create(2015, 5, 28, 14, 0, 0);
        $Farthest = $instance->farthest($dt1, $dt2);
        $this->assertEquals($dt2, $Farthest);
    }

    /**
     * @dataProvider classNameProvider
     * @return void
     */
    public function testFarthestWithEquals($class)
    {
        $instance = $class::create(2015, 5, 28, 12, 0, 0);
        $dt1 = $class::create(2015, 5, 28, 12, 0, 0);
        $dt2 = $class::create(2015, 5, 28, 14, 0, 0);
        $Farthest = $instance->farthest($dt1, $dt2);
        $this->assertEquals($dt2, $Farthest);
    }
}

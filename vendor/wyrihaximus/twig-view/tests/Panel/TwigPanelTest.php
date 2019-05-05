<?php
declare(strict_types=1);
/**
 * This file is part of TwigView.
 *
 ** (c) 2015 Cees-Jan Kiewiet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WyriHaximus\CakePHP\Tests\TwigView\Panel;

use WyriHaximus\CakePHP\Tests\TwigView\TestCase;
use WyriHaximus\TwigView\Lib\TreeScanner;
use WyriHaximus\TwigView\Panel\TwigPanel;

class TwigPanelTest extends TestCase
{
    public function testData()
    {
        $this->assertSame([
            'templates' => TreeScanner::all(),
        ], (new TwigPanel())->data());
    }
}

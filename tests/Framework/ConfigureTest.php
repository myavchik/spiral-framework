<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Framework\Command;

use Spiral\Framework\ConsoleTest;

class ConfigureTest extends ConsoleTest
{
    public function testConfigure()
    {
        $output = $this->runCommandDebug('configure');

        $this->assertContains('Verifying runtime directory', $output);
        $this->assertContains('locale directory', $output);
        $this->assertContains('NativeEngine', $output);
    }
}
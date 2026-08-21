<?php

use CodeIgniter\Test\CIUnitTestCase;

final class MonopolyUiActionsTest extends CIUnitTestCase
{
    public function testEverySupportedManagementActionHasAUiControl(): void
    {
        $view = file_get_contents(APPPATH . 'Views' . DIRECTORY_SEPARATOR . 'games' . DIRECTORY_SEPARATOR . 'four_player_room.php');
        $script = file_get_contents(ROOTPATH . 'public' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'monopoly.js');

        foreach (['monopoly-build', 'monopoly-mortgage', 'monopoly-trade-offer', 'monopoly-trade-accept', 'monopoly-trade-reject'] as $id) {
            $this->assertStringContainsString('id="' . $id . '"', $view);
        }
        foreach (['build', 'mortgage', 'trade_offer', 'trade_response'] as $action) {
            $this->assertStringContainsString("act('{$action}'", $script);
        }
    }

    public function testBotChainPrioritizesPendingTradeRecipient(): void
    {
        $service = file_get_contents(APPPATH . 'Libraries' . DIRECTORY_SEPARATOR . 'FourPlayerGameService.php');

        $this->assertStringContainsString("!empty(\$state['trade'])", $service);
        $this->assertStringContainsString("(int)\$state['trade']['to']", $service);
    }
}

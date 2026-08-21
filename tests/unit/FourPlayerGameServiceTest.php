<?php

use App\Libraries\FourPlayerGameService;
use App\Libraries\MonopolyEngine;
use CodeIgniter\Test\CIUnitTestCase;

final class FourPlayerGameServiceTest extends CIUnitTestCase
{
    public function testBotTurnChainRunsThreeBotsUntilHumanTurn(): void
    {
        $players=[['seat_index'=>0,'player_type'=>'human'],['seat_index'=>1,'player_type'=>'bot'],['seat_index'=>2,'player_type'=>'bot'],['seat_index'=>3,'player_type'=>'bot']];$state=(new MonopolyEngine())->create(array_map(fn($p)=>['display_name'=>'P'.$p['seat_index'],'player_type'=>$p['player_type']],$players),[]);$state['turn']=1;
        $method=new ReflectionMethod(FourPlayerGameService::class,'runBotTurnChain');$arguments=[&$state,$players,new MonopolyEngine()];$method->invokeArgs(new FourPlayerGameService(),$arguments);
        $this->assertSame('playing',$state['phase']);
        $this->assertSame(0,$state['turn']);
    }

    public function testRematchWaitsForEveryHumanButIgnoresBots(): void
    {
        $method=new ReflectionMethod(FourPlayerGameService::class,'allHumanPlayersReady');$service=new FourPlayerGameService();$players=$this->players();
        $this->assertFalse($method->invoke($service,$players,[0=>true]));
        $this->assertTrue($method->invoke($service,$players,[0=>true,1=>true]));
    }

    public function testOkeyTeamModeIsExplicitAndIndividualIsDefault(): void
    {
        $method=new ReflectionMethod(FourPlayerGameService::class,'settingsForGame');$service=new FourPlayerGameService();
        $this->assertSame('teams',$method->invoke($service,'okey101',['okey_mode'=>'teams'])['mode']);
        $this->assertSame('individual',$method->invoke($service,'okey101',[])['mode']);
    }

    public function testOkeyProjectionNeverExposesDeckOrOpponentHands(): void
    {
        $state=(new \App\Libraries\Okey101Engine())->create([[],[],[],[]]);
        $ownHand=$state['hands'][2];$deckCount=count($state['deck']);
        $method=new ReflectionMethod(FourPlayerGameService::class,'projectOkeyStateForSeat');
        $projected=$method->invoke(new FourPlayerGameService(),$state,2);

        $this->assertArrayNotHasKey('deck',$projected);
        $this->assertArrayNotHasKey('hands',$projected);
        $this->assertSame($deckCount,$projected['deckCount']);
        $this->assertSame($ownHand,$projected['hand']);
        $this->assertSame([22,21,21,21],$projected['handCounts']);
    }

    public function testMonopolyProjectionNeverExposesCardDeckOrder(): void
    {
        $state = (new MonopolyEngine())->create([
            ['display_name' => 'Oyuncu 1', 'player_type' => 'human'],
            ['display_name' => 'Oyuncu 2', 'player_type' => 'human'],
            ['display_name' => 'Oyuncu 3', 'player_type' => 'bot'],
            ['display_name' => 'Oyuncu 4', 'player_type' => 'bot'],
        ], []);
        $method = new ReflectionMethod(FourPlayerGameService::class, 'projectMonopolyState');
        $projected = $method->invoke(new FourPlayerGameService(), $state);

        $this->assertArrayNotHasKey('chanceDeck', $projected);
        $this->assertArrayNotHasKey('chestDeck', $projected);
        $this->assertSame(MonopolyEngine::spaces(), $projected['spaces']);
    }

    private function players(): array
    {
        return [
            ['seat_index'=>0,'player_type'=>'human'],
            ['seat_index'=>1,'player_type'=>'human'],
            ['seat_index'=>2,'player_type'=>'bot'],
            ['seat_index'=>3,'player_type'=>'bot'],
        ];
    }
}

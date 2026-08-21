<?php

use App\Libraries\MonopolyEngine;
use CodeIgniter\Test\CIUnitTestCase;

final class MonopolyEngineTest extends CIUnitTestCase
{
    public function testCreatesBoardPlayersAndCardDecks(): void
    {
        $state=$this->state();
        $this->assertCount(4,$state['players']);
        $this->assertSame([1500,1500,1500,1500],array_column($state['players'],'money'));
        $this->assertCount(40,MonopolyEngine::spaces());
        $this->assertNotEmpty($state['chanceDeck']);
        $this->assertNotEmpty($state['chestDeck']);
    }

    public function testOwnedPropertyChargesRent(): void
    {
        $state=$this->state();$state['properties'][1]=1;$state['players'][1]['properties']=[1];$state['players'][0]['position']=1;
        $this->invoke('land',$state,0,7);
        $this->assertSame(1498,$state['players'][0]['money']);
        $this->assertSame(1502,$state['players'][1]['money']);
    }

    public function testGoToJailMovesPlayerAndSetsJailCounter(): void
    {
        $state=$this->state();$state['players'][0]['position']=30;
        $this->invoke('land',$state,0,7);
        $this->assertSame(10,$state['players'][0]['position']);
        $this->assertSame(1,$state['players'][0]['jail']);
    }

    public function testAuctionAwardsPropertyAfterOtherPlayersFold(): void
    {
        $engine=new MonopolyEngine();$state=$this->state();$state['auction']=['space'=>1,'bid'=>0,'leader'=>null,'active'=>[0,1,2,3],'next'=>0];
        $engine->act($state,0,'auction_bid',['amount'=>50]);
        $engine->act($state,1,'auction_bid',['amount'=>0]);$engine->act($state,2,'auction_bid',['amount'=>0]);$engine->act($state,3,'auction_bid',['amount'=>0]);
        $this->assertNull($state['auction']);
        $this->assertSame(0,$state['properties'][1]);
        $this->assertSame(1450,$state['players'][0]['money']);
    }

    public function testMortgagePaysHalfPurchasePrice(): void
    {
        $engine=new MonopolyEngine();$state=$this->state();$state['properties'][1]=0;$state['players'][0]['properties']=[1];
        $engine->act($state,0,'mortgage',['space'=>1]);
        $this->assertContains(1,$state['mortgaged']);
        $this->assertSame(1530,$state['players'][0]['money']);
    }

    public function testCompleteColorGroupCanBuildThroughHotelLevel(): void
    {
        $engine=new MonopolyEngine();$state=$this->state();$state['properties']=[1=>0,3=>0];$state['players'][0]['properties']=[1,3];
        for($level=1;$level<=5;$level++)$engine->act($state,0,'build',['space'=>1]);
        $this->assertSame(5,$state['houses'][1]);
        $this->assertSame(1250,$state['players'][0]['money']);
    }

    public function testHotelUsesPublishedHotelRent(): void
    {
        $state=$this->state();$state['properties'][1]=1;$state['players'][1]['properties']=[1];$state['houses'][1]=5;$state['players'][0]['position']=1;
        $this->invoke('land',$state,0,7);
        $this->assertSame(1476,$state['players'][0]['money']);
        $this->assertSame(1524,$state['players'][1]['money']);
    }

    public function testPublishedPropertyRentsMatchChargedRentAtEveryLevel(): void
    {
        $spaces=MonopolyEngine::spaces();$published=$spaces[1]['rents'];
        for($level=0;$level<=5;$level++){
            $state=$this->state();$state['properties'][1]=1;$state['players'][1]['properties']=[1];$state['houses'][1]=$level;
            $charged=$this->invoke('rent',$state,1,7);
            $this->assertSame($published[$level],$charged,"Seviye {$level} kirası UI ile eşleşmiyor.");
        }
    }

    public function testAcceptedTradeTransfersCash(): void
    {
        $engine=new MonopolyEngine();$state=$this->state();
        $engine->act($state,0,'trade_offer',['to'=>1,'cashGive'=>100,'cashWant'=>40]);
        $engine->act($state,1,'trade_response',['accept'=>true]);
        $this->assertSame(1440,$state['players'][0]['money']);
        $this->assertSame(1560,$state['players'][1]['money']);
        $this->assertNull($state['trade']);
    }

    public function testBankruptcyCompletesGameWhenOnePlayerRemains(): void
    {
        $engine=new MonopolyEngine();$state=$this->state();$state['players'][1]['bankrupt']=true;$state['players'][2]['bankrupt']=true;
        $engine->act($state,0,'bankrupt',[]);
        $this->assertSame('completed',$state['phase']);
        $this->assertSame(3,$state['winnerSeat']);
    }

    public function testChanceAndChestCardsAreStoredAndApplied(): void
    {
        $state=$this->state();$state['chanceDeck']=[3];$state['players'][0]['position']=7;
        $this->invoke('drawCard',$state,0,'chance');
        $this->assertSame(10,$state['players'][0]['position']);$this->assertSame(1,$state['players'][0]['jail']);$this->assertSame('chance',$state['lastCard']['type']);
        $state['chestDeck']=[1];$before=$state['players'][0]['money'];$this->invoke('drawCard',$state,0,'chest');
        $this->assertSame($before+200,$state['players'][0]['money']);
    }

    private function state(): array
    {
        $players=[];foreach(['A','B','C','D'] as $name)$players[]=['display_name'=>$name,'player_type'=>'human'];
        return (new MonopolyEngine())->create($players,['freeParkingPool'=>true]);
    }

    private function invoke(string $method,array &$state,mixed ...$arguments): mixed
    {
        $reflection=new ReflectionMethod(MonopolyEngine::class,$method);$parameters=[&$state,...$arguments];
        return $reflection->invokeArgs(new MonopolyEngine(),$parameters);
    }
}

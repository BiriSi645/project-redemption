<?php

use App\Libraries\Okey101Engine;
use CodeIgniter\Test\CIUnitTestCase;

final class Okey101EngineTest extends CIUnitTestCase
{
    public function testDealsCorrectTileCountsAndKeepsAllTiles(): void
    {
        $state=(new Okey101Engine())->create([[],[],[],[]]);
        $this->assertSame([22,21,21,21],array_map('count',$state['hands']));
        $this->assertCount(20,$state['deck']);$this->assertNotEmpty($state['indicator']);
    }

    public function testJokerCompletesRunAndFakeOkeyRepresentsIndicator(): void
    {
        $joker=['color'=>'red','number'=>6];
        $jokerRun=[$this->tile('red',5),$this->tile('red',6),$this->tile('red',7)];
        $fakeRun=[$this->tile('red',4),$this->fake(),$this->tile('red',6)];
        $this->assertSame(18,$this->meldValue($jokerRun,$joker));
        $this->assertSame(15,$this->meldValue($fakeRun,$joker));
    }

    public function testCanOpenMultipleMeldsTotallingExactly101(): void
    {
        $engine=new Okey101Engine();$state=$this->emptyState();
        $groups=[
            [$this->tile('red',10),$this->tile('red',11),$this->tile('red',12),$this->tile('red',13)],
            [$this->tile('blue',10),$this->tile('blue',11),$this->tile('blue',12),$this->tile('blue',13)],
            [$this->tile('red',3),$this->tile('blue',3),$this->tile('black',3)],
        ];
        $state['hands'][0]=array_merge(...$groups);
        $engine->act($state,0,'open',['groups'=>array_map(fn($group)=>array_column($group,'id'),$groups)]);
        $this->assertTrue($state['opened'][0]);$this->assertCount(3,$state['melds'][0]);$this->assertSame([],$state['hands'][0]);
    }

    public function testOpeningBelow101IsRejected(): void
    {
        $engine=new Okey101Engine();$state=$this->emptyState();$group=[$this->tile('blue',1),$this->tile('blue',2),$this->tile('blue',3)];$state['hands'][0]=$group;
        $this->expectException(RuntimeException::class);
        $engine->act($state,0,'open',['groups'=>[array_column($group,'id')]]);
    }

    public function testFivePairsCanBeOpened(): void
    {
        $engine=new Okey101Engine();$state=$this->emptyState();$tiles=[];
        foreach(range(1,5) as $number){$tiles[]=$this->tile('blue',$number,0);$tiles[]=$this->tile('blue',$number,1);}
        $state['hands'][0]=$tiles;$engine->act($state,0,'pairs',['tiles'=>array_column($tiles,'id')]);
        $this->assertTrue($state['pairOpened'][0]);$this->assertSame([],$state['hands'][0]);
    }

    public function testLeftDiscardMustBeUsedForOpeningOrReturned(): void
    {
        $engine=new Okey101Engine();$state=$this->emptyState();$state['turn']=1;$state['mustDraw']=true;$leftTile=$this->tile('yellow',9);$state['discards'][0]=[$leftTile];$state['deck']=[$this->tile('black',2)];
        $engine->act($state,1,'draw',['source'=>'left_discard']);
        $this->assertSame($leftTile['id'],$state['pendingDiscardDraw']['tile']['id']);
        try{$engine->act($state,1,'discard',['tile'=>$leftTile['id']]);$this->fail('Soldan alınan taş doğrudan atılmamalı.');}catch(RuntimeException){$this->addToAssertionCount(1);}
        $engine->act($state,1,'return_discard_draw',[]);
        $this->assertNull($state['pendingDiscardDraw']);$this->assertSame($leftTile['id'],$state['discards'][0][0]['id']);$this->assertSame('black-2-0',$state['hands'][1][0]['id']);
    }

    public function testDiscardingLastTileFinishesHand(): void
    {
        $engine=new Okey101Engine();$state=$this->emptyState();$tile=$this->tile('yellow',4);$state['hands'][0]=[$tile];$state['opened'][0]=true;
        $engine->act($state,0,'discard',['tile'=>$tile['id']]);
        $this->assertSame('completed',$state['phase']);$this->assertSame(0,$state['winnerSeat']);$this->assertSame('normal',$state['finishType']);
    }

    public function testFakeOkeyHasIndicatorValueInEndPenalty(): void
    {
        $state=$this->emptyState();$state['hands'][1]=[$this->fake()];
        $reflection=new ReflectionMethod(Okey101Engine::class,'finishRound');$arguments=[&$state,0,'normal'];$reflection->invokeArgs(new Okey101Engine(),$arguments);
        $this->assertSame(10,$state['scores'][1]);
    }

    private function emptyState(): array
    {
        return ['phase'=>'playing','turn'=>0,'mustDraw'=>false,'deck'=>[],'discard'=>[],'discards'=>[[],[],[],[]],'pendingDiscardDraw'=>null,'indicator'=>$this->tile('red',5),'joker'=>['color'=>'red','number'=>6],'hands'=>[[],[],[],[]],'opened'=>[false,false,false,false],'pairOpened'=>[false,false,false,false],'melds'=>[[],[],[],[]],'scores'=>[0,0,0,0],'winnerSeat'=>null,'finishType'=>null,'log'=>[]];
    }

    private function tile(string $color,int $number,int $copy=0): array{return ['id'=>$color.'-'.$number.'-'.$copy,'color'=>$color,'number'=>$number,'fake'=>false];}
    private function fake(): array{return ['id'=>'fake-0','color'=>null,'number'=>null,'fake'=>true];}
    private function meldValue(array $tiles,array $joker): int{$method=new ReflectionMethod(Okey101Engine::class,'meldValue');return $method->invoke(new Okey101Engine(),$tiles,$joker);}
}

<?php

use App\Libraries\Okey101Engine;
use CodeIgniter\Test\CIUnitTestCase;

final class Okey101EngineTest extends CIUnitTestCase
{
    public function testDealsCorrectTileCountsAndKeepsAllTiles(): void
    {
        $state=(new Okey101Engine())->create([[],[],[],[]]);
        $this->assertSame([22,21,21,21],array_map('count',$state['hands']));
        $this->assertCount(20,$state['deck']);
        $this->assertNotEmpty($state['indicator']);
    }

    public function testFirstPlayerCanDiscardAndNextPlayerMustDraw(): void
    {
        $engine=new Okey101Engine();$state=$engine->create([[],[],[],[]]);$id=$state['hands'][0][0]['id'];
        $engine->act($state,0,'discard',['tile'=>$id]);
        $this->assertSame(1,$state['turn']);$this->assertTrue($state['mustDraw']);$this->assertCount(1,$state['discard']);
    }
}

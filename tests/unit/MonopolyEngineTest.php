<?php
use App\Libraries\MonopolyEngine;
use CodeIgniter\Test\CIUnitTestCase;
final class MonopolyEngineTest extends CIUnitTestCase
{
 public function testCreatesFourSolventPlayers():void{$s=(new MonopolyEngine())->create([['display_name'=>'A','player_type'=>'human'],['display_name'=>'B','player_type'=>'bot'],['display_name'=>'C','player_type'=>'bot'],['display_name'=>'D','player_type'=>'bot']],['freeParkingPool'=>true]);$this->assertCount(4,$s['players']);$this->assertSame([1500,1500,1500,1500],array_column($s['players'],'money'));$this->assertTrue($s['freeParkingRule']);}
 public function testBoardHasFortySpaces():void{$this->assertCount(40,MonopolyEngine::spaces());}
}

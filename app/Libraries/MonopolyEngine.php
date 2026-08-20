<?php

namespace App\Libraries;

use RuntimeException;

final class MonopolyEngine
{
    private const SPACES = [
        ['Başlangıç','go'],['Akdeniz Caddesi','property',60,2,'brown'],['Toplum Fonu','chest'],['Baltık Caddesi','property',60,4,'brown'],['Gelir Vergisi','tax',200],['Reading Garı','railroad',200,25,'rail'],['Doğu Caddesi','property',100,6,'lightblue'],['Şans','chance'],['Vermont Caddesi','property',100,6,'lightblue'],['Connecticut Caddesi','property',120,8,'lightblue'],
        ['Hapishane','jail'],['St. Charles Meydanı','property',140,10,'pink'],['Elektrik Şirketi','utility',150,0,'utility'],['States Caddesi','property',140,10,'pink'],['Virginia Caddesi','property',160,12,'pink'],['Pennsylvania Garı','railroad',200,25,'rail'],['St. James Meydanı','property',180,14,'orange'],['Toplum Fonu','chest'],['Tennessee Caddesi','property',180,14,'orange'],['New York Caddesi','property',200,16,'orange'],
        ['Serbest Otopark','parking'],['Kentucky Caddesi','property',220,18,'red'],['Şans','chance'],['Indiana Caddesi','property',220,18,'red'],['Illinois Caddesi','property',240,20,'red'],['B&O Garı','railroad',200,25,'rail'],['Atlantik Caddesi','property',260,22,'yellow'],['Ventnor Caddesi','property',260,22,'yellow'],['Su İdaresi','utility',150,0,'utility'],['Marvin Gardens','property',280,24,'yellow'],
        ['Hapse Git','gotojail'],['Pasifik Caddesi','property',300,26,'green'],['Kuzey Carolina Caddesi','property',300,26,'green'],['Toplum Fonu','chest'],['Pennsylvania Caddesi','property',320,28,'green'],['Short Line','railroad',200,25,'rail'],['Şans','chance'],['Park Place','property',350,35,'darkblue'],['Lüks Vergisi','tax',100],['Boardwalk','property',400,50,'darkblue'],
    ];

    public function create(array $players,array $settings):array
    {
        if(count($players)!==4)throw new RuntimeException('Monopoly dört oyuncuyla başlar.');
        $p=[];foreach($players as $seat=>$player)$p[]=['seat'=>$seat,'name'=>$player['display_name'],'type'=>$player['player_type'],'money'=>1500,'position'=>0,'jail'=>0,'bankrupt'=>false,'properties'=>[]];
        return ['phase'=>'playing','turn'=>0,'players'=>$p,'properties'=>[],'houses'=>[],'mortgaged'=>[],'rolled'=>false,'dice'=>[0,0],'doubles'=>0,'pending'=>null,'auction'=>null,'trade'=>null,'freeParking'=>0,'freeParkingRule'=>!empty($settings['freeParkingPool']),'winnerSeat'=>null,'log'=>['Monopoly başladı.']];
    }

    public function act(array &$s,int $seat,string $action,array $in):void
    {
        if($s['phase']!=='playing'||$s['players'][$seat]['bankrupt'])throw new RuntimeException('Oyun aktif değil.');
        if($action==='trade_offer'){ $this->tradeOffer($s,$seat,$in); return; }
        if($action==='trade_response'){ $this->tradeResponse($s,$seat,!empty($in['accept'])); return; }
        if($action==='auction_bid'){ $this->auctionBid($s,$seat,(int)($in['amount']??0)); return; }
        if((int)$s['turn']!==$seat)throw new RuntimeException('Sıra sizde değil.');
        if($action==='roll'){ $this->roll($s,$seat); return; }
        if($action==='buy'){ $this->buy($s,$seat); return; }
        if($action==='pass'){ if($s['pending']===null)throw new RuntimeException('Bekleyen mülk yok.');$this->startAuction($s,(int)$s['pending']);$s['pending']=null;return; }
        if($action==='end'){ if(!$s['rolled']||$s['pending']!==null||$s['auction'])throw new RuntimeException('Önce turun işlemlerini tamamlayın.');$this->next($s);return; }
        if($action==='mortgage'){ $this->mortgage($s,$seat,(int)($in['space']??-1));return; }
        if($action==='build'){ $this->build($s,$seat,(int)($in['space']??-1));return; }
        if($action==='bankrupt'){ $this->bankrupt($s,$seat);return; }
        throw new RuntimeException('Geçersiz Monopoly hamlesi.');
    }

    public function botTurn(array &$s,int $seat):void
    {
        if($s['trade']&&$s['trade']['to']===$seat){$offer=$s['trade'];$gain=$offer['cashGive']-$offer['cashWant'];$this->tradeResponse($s,$seat,$gain>=-100);return;}
        if($s['auction']){$next=$s['auction']['next'];if($next!==$seat)return;$space=$s['auction']['space'];$price=self::SPACES[$space][2];$max=min($s['players'][$seat]['money']-100,(int)($price*1.25));$bid=$s['auction']['bid']+10;$this->auctionBid($s,$seat,$bid<=$max?$bid:0);return;}
        if((int)$s['turn']!==$seat)return;if(!$s['rolled']){$this->roll($s,$seat);return;}if($s['pending']!==null){$space=$s['pending'];$price=self::SPACES[$space][2];if($s['players'][$seat]['money']-$price>=200)$this->buy($s,$seat);else{$this->startAuction($s,$space);$s['pending']=null;}return;} $this->next($s);
    }

    public static function spaces():array{return array_map(fn($i,$x)=>['index'=>$i,'name'=>$x[0],'type'=>$x[1],'price'=>$x[2]??null,'group'=>$x[4]??null],array_keys(self::SPACES),self::SPACES);}
    private function roll(array &$s,int $seat):void
    {if($s['rolled'])throw new RuntimeException('Bu tur zar atıldı.');$a=random_int(1,6);$b=random_int(1,6);$s['dice']=[$a,$b];$p=&$s['players'][$seat];if($p['jail']>0){if($a===$b)$p['jail']=0;elseif(++$p['jail']>=3){$p['money']-=50;$p['jail']=0;}else{$s['rolled']=true;return;}}$old=$p['position'];$p['position']=($old+$a+$b)%40;if($p['position']<$old)$p['money']+=200;$s['rolled']=true;$this->land($s,$seat,$a+$b);}
    private function land(array &$s,int $seat,int $dice):void
    {$pos=$s['players'][$seat]['position'];$space=self::SPACES[$pos];$type=$space[1];if(in_array($type,['property','railroad','utility'],true)){if(!isset($s['properties'][$pos])){$s['pending']=$pos;return;}$owner=$s['properties'][$pos];if($owner!==$seat&&!in_array($pos,$s['mortgaged'],true)){$rent=$this->rent($s,$pos,$dice);$s['players'][$seat]['money']-=$rent;$s['players'][$owner]['money']+=$rent;}}elseif($type==='tax'){$s['players'][$seat]['money']-=$space[2];if($s['freeParkingRule'])$s['freeParking']+=$space[2];}elseif($type==='parking'&&$s['freeParkingRule']){$s['players'][$seat]['money']+=$s['freeParking'];$s['freeParking']=0;}elseif($type==='gotojail'){$s['players'][$seat]['position']=10;$s['players'][$seat]['jail']=1;}elseif(in_array($type,['chance','chest'],true)){$amount=random_int(-10,10)*10;$s['players'][$seat]['money']+=$amount;if($amount<0&&$s['freeParkingRule'])$s['freeParking']+=-$amount;}if($s['players'][$seat]['money']<0)$s['log'][]=$s['players'][$seat]['name'].' borcunu kapatmalı veya iflas etmeli.';}
    private function buy(array &$s,int $seat):void{$pos=$s['pending'];if($pos===null)throw new RuntimeException('Satın alınacak mülk yok.');$price=self::SPACES[$pos][2];if($s['players'][$seat]['money']<$price)throw new RuntimeException('Yeterli paranız yok.');$s['players'][$seat]['money']-=$price;$s['players'][$seat]['properties'][]=$pos;$s['properties'][$pos]=$seat;$s['pending']=null;}
    private function startAuction(array &$s,int $space):void{$active=$this->active($s);$s['auction']=['space'=>$space,'bid'=>0,'leader'=>null,'active'=>$active,'next'=>$active[0]];}
    private function auctionBid(array &$s,int $seat,int $amount):void{if(!$s['auction']||$s['auction']['next']!==$seat)throw new RuntimeException('Açık artırmada sıra sizde değil.');$a=&$s['auction'];if($amount>$a['bid']&&$amount<=$s['players'][$seat]['money']){$a['bid']=$amount;$a['leader']=$seat;}else{$a['active']=array_values(array_diff($a['active'],[$seat]));}$challengers=array_values(array_diff($a['active'],$a['leader']===null?[]:[$a['leader']]));if($a['leader']!==null&&$challengers===[]){$winner=$a['leader'];$s['players'][$winner]['money']-=$a['bid'];$s['players'][$winner]['properties'][]=$a['space'];$s['properties'][$a['space']]=$winner;$s['auction']=null;return;}if($a['leader']===null&&count($a['active'])<=1){$s['auction']=null;return;}$candidates=$challengers?:$a['active'];$after=array_values(array_filter($candidates,static fn(int $candidate):bool=>$candidate>$seat));$a['next']=$after[0]??$candidates[0];}
    private function rent(array $s,int $pos,int $dice):int{$space=self::SPACES[$pos];$owner=$s['properties'][$pos];if($space[1]==='railroad')return 25*(2**(count(array_filter($s['players'][$owner]['properties'],fn($p)=>self::SPACES[$p][1]==='railroad'))-1));if($space[1]==='utility')return $dice*(count(array_filter($s['players'][$owner]['properties'],fn($p)=>self::SPACES[$p][1]==='utility'))===2?10:4);return $space[3]*(1+(int)($s['houses'][$pos]??0)*2);}
    private function mortgage(array &$s,int $seat,int $pos):void{if(($s['properties'][$pos]??null)!==$seat||in_array($pos,$s['mortgaged'],true))throw new RuntimeException('Bu mülk ipotek edilemez.');$s['mortgaged'][]=$pos;$s['players'][$seat]['money']+=(int)(self::SPACES[$pos][2]/2);}
    private function build(array &$s,int $seat,int $pos):void{$space=self::SPACES[$pos]??null;if(!$space||$space[1]!=='property'||($s['properties'][$pos]??null)!==$seat)throw new RuntimeException('Bu mülke inşa edemezsiniz.');$group=$space[4];foreach(self::SPACES as $i=>$x)if(($x[4]??null)===$group&&($s['properties'][$i]??null)!==$seat)throw new RuntimeException('Renk grubunun tamamına sahip olmalısınız.');$count=(int)($s['houses'][$pos]??0);if($count>=5)throw new RuntimeException('Bu mülkte otel var.');$cost=max(50,(int)(ceil($space[2]/100)*50));if($s['players'][$seat]['money']<$cost)throw new RuntimeException('Yeterli paranız yok.');$s['players'][$seat]['money']-=$cost;$s['houses'][$pos]=$count+1;}
    private function tradeOffer(array &$s,int $seat,array $in):void{$to=(int)($in['to']??-1);if($to===$seat||!isset($s['players'][$to])||$s['trade'])throw new RuntimeException('Geçersiz takas.');$s['trade']=['from'=>$seat,'to'=>$to,'cashGive'=>max(0,(int)($in['cashGive']??0)),'cashWant'=>max(0,(int)($in['cashWant']??0))];}
    private function tradeResponse(array &$s,int $seat,bool $accept):void{if(!$s['trade']||$s['trade']['to']!==$seat)throw new RuntimeException('Bekleyen takas yok.');$t=$s['trade'];if($accept&&$s['players'][$t['from']]['money']>=$t['cashGive']&&$s['players'][$seat]['money']>=$t['cashWant']){$s['players'][$t['from']]['money']+=-$t['cashGive']+$t['cashWant'];$s['players'][$seat]['money']+=$t['cashGive']-$t['cashWant'];}$s['trade']=null;}
    private function bankrupt(array &$s,int $seat):void{$s['players'][$seat]['bankrupt']=true;foreach($s['players'][$seat]['properties'] as $p)unset($s['properties'][$p]);$s['players'][$seat]['properties']=[];$active=$this->active($s);if(count($active)===1){$s['phase']='completed';$s['winnerSeat']=$active[0];}else $this->next($s);}
    private function next(array &$s):void{$active=$this->active($s);$i=array_search($s['turn'],$active,true);$s['turn']=$active[(($i===false?-1:$i)+1)%count($active)];$s['rolled']=false;$s['dice']=[0,0];$s['pending']=null;}
    private function active(array $s):array{return array_values(array_map(fn($p)=>$p['seat'],array_filter($s['players'],fn($p)=>!$p['bankrupt'])));}
}

<?php

namespace App\Libraries;

use RuntimeException;

final class Okey101Engine
{
    private const COLORS = ['red', 'blue', 'black', 'yellow'];

    public function create(array $players): array
    {
        if (count($players) !== 4) throw new RuntimeException('101 Okey dört oyuncuyla başlar.');
        $tiles=[];
        foreach(self::COLORS as $color) for($number=1;$number<=13;$number++) for($copy=0;$copy<2;$copy++) $tiles[]=['id'=>$color[0].$number.'-'.$copy,'color'=>$color,'number'=>$number,'fake'=>false];
        $tiles[]=['id'=>'fake-0','color'=>null,'number'=>null,'fake'=>true]; $tiles[]=['id'=>'fake-1','color'=>null,'number'=>null,'fake'=>true]; shuffle($tiles);
        foreach($tiles as $index=>$candidate) if(!$candidate['fake']){$indicator=$candidate;array_splice($tiles,$index,1);break;}
        $joker=['color'=>$indicator['color'],'number'=>$indicator['number']===13?1:$indicator['number']+1];$hands=array_fill(0,4,[]);
        for($seat=0;$seat<4;$seat++)for($i=0;$i<($seat===0?22:21);$i++)$hands[$seat][]=array_pop($tiles);
        return ['phase'=>'playing','round'=>1,'turn'=>0,'mustDraw'=>false,'deck'=>array_values($tiles),'discard'=>[],'discards'=>[[],[],[],[]],
            'pendingDiscardDraw'=>null,'indicator'=>$indicator,'joker'=>$joker,'hands'=>$hands,'opened'=>[false,false,false,false],
            'pairOpened'=>[false,false,false,false],'melds'=>[[],[],[],[]],'scores'=>[0,0,0,0],'winnerSeat'=>null,'finishType'=>null,'log'=>['Oyun başladı. İlk oyuncu taş atmalı.']];
    }

    public function act(array &$s,int $seat,string $action,array $input):void
    {
        $s['discards']??=[[],[],[],[]];$s['pendingDiscardDraw']??=null;
        if(($s['phase']??'')!=='playing'||(int)$s['turn']!==$seat)throw new RuntimeException('Sıra sizde değil.');
        if($action==='draw'){
            if(!$s['mustDraw'])throw new RuntimeException('Önce taş atmalısınız.');$source=(string)($input['source']??'deck');
            if(in_array($source,['discard','left_discard'],true)){$from=($seat+3)%4;if(!$s['discards'][$from])throw new RuntimeException('Solunuzdaki oyuncunun attığı taş yok.');$tile=array_pop($s['discards'][$from]);$s['pendingDiscardDraw']=['seat'=>$seat,'from'=>$from,'tile'=>$tile];}
            else{if(!$s['deck']){$this->finishRound($s,null,'deck');return;}$tile=array_pop($s['deck']);$s['pendingDiscardDraw']=null;}
            $s['hands'][$seat][]=$tile;$s['mustDraw']=false;return;
        }
        if($action==='return_discard_draw'){
            $pending=$s['pendingDiscardDraw'];if(!$pending||(int)$pending['seat']!==$seat)throw new RuntimeException('Geri bırakılacak taş yok.');
            $tile=$this->removeOne($s['hands'][$seat],(string)$pending['tile']['id']);$s['discards'][(int)$pending['from']][]=$tile;
            if(!$s['deck']){$this->finishRound($s,null,'deck');return;}$s['hands'][$seat][]=array_pop($s['deck']);$s['pendingDiscardDraw']=null;return;
        }
        if($action==='open'){
            if($s['mustDraw'])throw new RuntimeException('Önce taş çekmelisiniz.');$groups=array_values(array_filter((array)($input['groups']??[]),'is_array'));
            if(!$groups)throw new RuntimeException('Istakada geçerli perleri boşluklarla ayırın.');$ids=[];$melds=[];$sum=0;
            foreach($groups as $groupIds){$group=$this->takePreview($s['hands'][$seat],$groupIds);$value=$this->meldValue($group,$s['joker']);if($value<0)throw new RuntimeException('Geçersiz per var. Perlerin arasına boşluk bırakın.');$sum+=$value;$ids=array_merge($ids,$groupIds);$melds[]=$group;}
            if(count($ids)!==count(array_unique($ids)))throw new RuntimeException('Aynı taş birden fazla perde kullanılamaz.');
            if(!$s['opened'][$seat]&&$sum<101)throw new RuntimeException('İlk açılış toplamı en az 101 olmalı.');
            $pendingId=$s['pendingDiscardDraw']['tile']['id']??null;if($pendingId!==null&&!in_array($pendingId,$ids,true))throw new RuntimeException('Soldan aldığınız taşı açtığınız perlerden birinde kullanmalısınız.');
            $this->removeIds($s['hands'][$seat],$ids);$s['melds'][$seat]=array_merge($s['melds'][$seat],$melds);$s['opened'][$seat]=true;$s['pendingDiscardDraw']=null;return;
        }
        if($action==='pairs'){
            if($s['opened'][$seat]||$s['pairOpened'][$seat])throw new RuntimeException('Daha önce açtınız.');if($s['pendingDiscardDraw'])throw new RuntimeException('Soldan alınan taşla yalnızca per açabilirsiniz.');
            $ids=(array)($input['tiles']??[]);$tiles=$this->takePreview($s['hands'][$seat],$ids);if(count($tiles)!==10||!$this->areFivePairs($tiles,$s['joker']))throw new RuntimeException('Çift açmak için beş geçerli çift seçmelisiniz.');
            $this->removeIds($s['hands'][$seat],$ids);$s['melds'][$seat][]=$tiles;$s['pairOpened'][$seat]=true;return;
        }
        if($action!=='discard')throw new RuntimeException('Geçersiz hamle.');if($s['mustDraw'])throw new RuntimeException('Önce taş çekmelisiniz.');
        if($s['pendingDiscardDraw'])throw new RuntimeException('Soldan aldığınız taşla bu tur el açın veya taşı geri bırakıp ortadan çekin.');
        $tile=$this->removeOne($s['hands'][$seat],(string)($input['tile']??''));$s['discard'][]=$tile;$s['discards'][$seat][]=$tile;
        if(!$s['hands'][$seat]){$this->finishRound($s,$seat,$s['opened'][$seat]||$s['pairOpened'][$seat]?'normal':'hand');return;}$s['turn']=($seat+1)%4;$s['mustDraw']=true;
    }

    public function botTurn(array &$s,int $seat):void
    {
        if($s['mustDraw'])$this->act($s,$seat,'draw',['source'=>'deck']);if(($s['phase']??'')!=='playing')return;$best=$this->bestOpening($s['hands'][$seat],$s['joker']);
        if(!$s['opened'][$seat]&&$best['value']>=101)$this->act($s,$seat,'open',['groups'=>$best['groups']]);$hand=$s['hands'][$seat];usort($hand,fn($a,$b)=>$this->tileUsefulness($a,$s['hands'][$seat],$s['joker'])<=>$this->tileUsefulness($b,$s['hands'][$seat],$s['joker']));$this->act($s,$seat,'discard',['tile'=>$hand[0]['id']]);
    }

    private function bestOpening(array $hand,array $joker):array
    {
        $groups=[];$used=[];$value=0;
        foreach(self::COLORS as $color){$by=[];foreach($hand as $t)if(!$this->wild($t,$joker)&&$t['color']===$color)$by[$t['number']][]=$t;ksort($by);$run=[];$last=0;foreach($by as $n=>$same){if($n!==$last+1&&count($run)>=3){$ids=array_column($run,'id');$groups[]=$ids;$used+=array_fill_keys($ids,true);$value+=array_sum(array_column($run,'number'));$run=[];}elseif($n!==$last+1)$run=[];$run[]=$same[0];$last=$n;}if(count($run)>=3){$ids=array_column($run,'id');$groups[]=$ids;$used+=array_fill_keys($ids,true);$value+=array_sum(array_column($run,'number'));}}
        foreach(range(1,13) as $n){$set=[];$colors=[];foreach($hand as $t)if(!isset($used[$t['id']])&&!$this->wild($t,$joker)&&$t['number']===$n&&!isset($colors[$t['color']])){$set[]=$t;$colors[$t['color']]=1;}if(count($set)>=3){$set=array_slice($set,0,4);$ids=array_column($set,'id');$groups[]=$ids;$used+=array_fill_keys($ids,true);$value+=count($set)*$n;}}
        return ['groups'=>$groups,'value'=>$value];
    }

    private function meldValue(array $tiles,array $joker):int
    {
        if(count($tiles)<3)return -1;$wild=array_values(array_filter($tiles,fn($t)=>$this->wild($t,$joker)));$normal=array_values(array_filter($tiles,fn($t)=>!$this->wild($t,$joker)));if(!$normal)return -1;
        $numbers=array_map('intval',array_column($normal,'number'));$colors=array_column($normal,'color');
        if(count(array_unique($numbers))===1&&count(array_unique($colors))===count($colors)&&count($tiles)<=4)return $numbers[0]*count($tiles);
        if(count(array_unique($colors))===1){sort($numbers);if(count(array_unique($numbers))!==count($numbers))return -1;$missing=0;for($i=1;$i<count($numbers);$i++)$missing+=max(0,$numbers[$i]-$numbers[$i-1]-1);$wildCount=count($wild);if($missing<=$wildCount&&($numbers[count($numbers)-1]-$numbers[0]+1+$wildCount)<=13){$sum=array_sum($numbers);for($i=1;$i<count($numbers);$i++)for($n=$numbers[$i-1]+1;$n<$numbers[$i];$n++)$sum+=$n;$remaining=$wildCount-$missing;$below=min($remaining,$numbers[0]-1);for($n=1;$n<=$below;$n++)$sum+=$numbers[0]-$n;$remaining-=$below;for($n=1;$n<=$remaining;$n++)$sum+=$numbers[count($numbers)-1]+$n;return $sum;}}
        return -1;
    }

    private function areFivePairs(array $tiles,array $joker):bool{$counts=[];$wild=0;foreach($tiles as $t){if($this->wild($t,$joker)){$wild++;continue;}$key=$t['color'].':'.$t['number'];$counts[$key]=($counts[$key]??0)+1;}$pairs=0;$singles=0;foreach($counts as $count){$pairs+=intdiv($count,2);$singles+=$count%2;}return $pairs+min($wild,$singles)+intdiv(max(0,$wild-$singles),2)>=5;}
    private function finishRound(array &$s,?int $winner,string $type):void{$s['phase']='completed';$s['winnerSeat']=$winner;$s['finishType']=$type;foreach($s['hands'] as $seat=>$hand){if($winner===$seat)continue;$penalty=0;foreach($hand as $t)$penalty+=$this->wild($t,$s['joker'])?101:(int)($t['number']??0);if(!$s['opened'][$seat]&&!$s['pairOpened'][$seat])$penalty*=2;$s['scores'][$seat]+=$penalty;}if($winner!==null&&$type==='hand')foreach($s['scores'] as $seat=>&$score)if($seat!==$winner)$score*=2;}
    private function wild(array $tile,array $joker):bool{return !$tile['fake']&&$tile['color']===$joker['color']&&(int)$tile['number']===(int)$joker['number'];}
    private function takePreview(array $hand,array $ids):array{$map=array_column($hand,null,'id');$out=[];foreach($ids as $id){if(!isset($map[$id]))throw new RuntimeException('Taş elinizde bulunamadı.');$out[]=$map[$id];}return $out;}
    private function removeIds(array &$hand,array $ids):void{foreach($ids as $id)$this->removeOne($hand,(string)$id);}
    private function removeOne(array &$hand,string $id):array{foreach($hand as $index=>$tile)if($tile['id']===$id){array_splice($hand,$index,1);return $tile;}throw new RuntimeException('Taş elinizde bulunamadı.');}
    private function tileUsefulness(array $tile,array $hand,array $joker):int{if($this->wild($tile,$joker))return 999;$score=0;foreach($hand as $other){if($other['id']===$tile['id'])continue;if($other['number']===$tile['number'])$score+=3;if($other['color']===$tile['color']&&abs($other['number']-$tile['number'])<=2)$score+=2;}return $score;}
}

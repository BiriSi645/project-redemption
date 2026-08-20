<?php

namespace App\Libraries;

use RuntimeException;

final class Okey101Engine
{
    private const COLORS = ['red', 'blue', 'black', 'yellow'];

    public function create(array $players): array
    {
        if (count($players) !== 4) throw new RuntimeException('101 Okey dört oyuncuyla başlar.');
        $tiles = [];
        foreach (self::COLORS as $color) for ($number = 1; $number <= 13; $number++) for ($copy = 0; $copy < 2; $copy++) {
            $tiles[] = ['id' => $color[0] . $number . '-' . $copy, 'color' => $color, 'number' => $number, 'fake' => false];
        }
        $tiles[] = ['id' => 'fake-0', 'color' => null, 'number' => null, 'fake' => true];
        $tiles[] = ['id' => 'fake-1', 'color' => null, 'number' => null, 'fake' => true];
        shuffle($tiles);
        $indicatorIndex = null;
        foreach ($tiles as $index => $tile) if (! $tile['fake']) { $indicatorIndex = $index; break; }
        $indicator = $tiles[$indicatorIndex]; array_splice($tiles, $indicatorIndex, 1);
        $joker = ['color' => $indicator['color'], 'number' => $indicator['number'] === 13 ? 1 : $indicator['number'] + 1];
        $hands = array_fill(0, 4, []);
        for ($seat = 0; $seat < 4; $seat++) for ($i = 0; $i < ($seat === 0 ? 22 : 21); $i++) $hands[$seat][] = array_pop($tiles);
        foreach ($hands as &$hand) $this->sort($hand, $joker); unset($hand);
        return ['phase' => 'playing', 'round' => 1, 'turn' => 0, 'mustDraw' => false, 'deck' => array_values($tiles), 'discard' => [],
            'indicator' => $indicator, 'joker' => $joker, 'hands' => $hands, 'opened' => [false,false,false,false],
            'pairOpened' => [false,false,false,false], 'melds' => [[],[],[],[]], 'scores' => [0,0,0,0],
            'winnerSeat' => null, 'finishType' => null, 'log' => ['Oyun başladı. İlk oyuncu taş atmalı.']];
    }

    public function act(array &$state, int $seat, string $action, array $input): void
    {
        if (($state['phase'] ?? '') !== 'playing' || (int) $state['turn'] !== $seat) throw new RuntimeException('Sıra sizde değil.');
        if ($action === 'draw') {
            if (! $state['mustDraw']) throw new RuntimeException('Önce taş atmalısınız.');
            $source = ($input['source'] ?? '') === 'discard' ? 'discard' : 'deck';
            if ($source === 'discard') { if (!$state['discard']) throw new RuntimeException('Yerde taş yok.'); $tile=array_pop($state['discard']); }
            else { if (!$state['deck']) { $this->finishRound($state, null, 'deck'); return; } $tile=array_pop($state['deck']); }
            $state['hands'][$seat][]=$tile; $this->sort($state['hands'][$seat], $state['joker']); $state['mustDraw']=false; return;
        }
        if ($action === 'open') {
            if ($state['mustDraw']) throw new RuntimeException('Önce taş çekmelisiniz.');
            $groups=$input['groups']??[]; if (!is_array($groups)||!$groups) throw new RuntimeException('Açılacak perleri seçin.');
            $tiles=[]; $melds=[]; $sum=0;
            foreach($groups as $ids){ $group=$this->takePreview($state['hands'][$seat],(array)$ids); $value=$this->meldValue($group,$state['joker']); if($value<0) throw new RuntimeException('Geçersiz per.'); $sum+=$value; $tiles=array_merge($tiles,(array)$ids); $melds[]=$group; }
            if(!$state['opened'][$seat] && $sum<101) throw new RuntimeException('İlk açılış toplamı en az 101 olmalı.');
            $this->removeIds($state['hands'][$seat],$tiles); $state['melds'][$seat]=array_merge($state['melds'][$seat],$melds); $state['opened'][$seat]=true; return;
        }
        if ($action === 'pairs') {
            if ($state['opened'][$seat] || $state['pairOpened'][$seat]) throw new RuntimeException('Daha önce açtınız.');
            $ids=(array)($input['tiles']??[]); $tiles=$this->takePreview($state['hands'][$seat],$ids);
            if(count($tiles)!==10 || !$this->areFivePairs($tiles,$state['joker'])) throw new RuntimeException('Çift açmak için beş geçerli çift seçmelisiniz.');
            $this->removeIds($state['hands'][$seat],$ids); $state['melds'][$seat][]=$tiles; $state['pairOpened'][$seat]=true; return;
        }
        if ($action !== 'discard') throw new RuntimeException('Geçersiz hamle.');
        if ($state['mustDraw']) throw new RuntimeException('Önce taş çekmelisiniz.');
        $id=(string)($input['tile']??''); $tile=$this->removeOne($state['hands'][$seat],$id); $state['discard'][]=$tile;
        if(!$state['hands'][$seat]) { $this->finishRound($state,$seat,$state['opened'][$seat]||$state['pairOpened'][$seat]?'normal':'hand'); return; }
        $state['turn']=($seat+1)%4; $state['mustDraw']=true;
    }

    public function botTurn(array &$state, int $seat): void
    {
        if($state['mustDraw']) $this->act($state,$seat,'draw',['source'=>'deck']);
        if(($state['phase']??'')!=='playing') return;
        $best=$this->bestOpening($state['hands'][$seat],$state['joker']);
        if(!$state['opened'][$seat] && $best['value']>=101) $this->act($state,$seat,'open',['groups'=>$best['groups']]);
        $hand=$state['hands'][$seat]; usort($hand,fn($a,$b)=>$this->tileUsefulness($a,$state['hands'][$seat],$state['joker'])<=>$this->tileUsefulness($b,$state['hands'][$seat],$state['joker']));
        $this->act($state,$seat,'discard',['tile'=>$hand[0]['id']]);
    }

    private function bestOpening(array $hand,array $joker): array
    {
        $groups=[];$used=[];$value=0;
        foreach(self::COLORS as $color){$by=[];foreach($hand as $t)if(!$this->wild($t,$joker)&&$t['color']===$color)$by[$t['number']][]=$t;ksort($by);$run=[];$last=0;foreach($by as $n=>$same){if($n!==$last+1&&count($run)>=3){$ids=array_column($run,'id');$groups[]=$ids;$used+=array_fill_keys($ids,true);$value+=array_sum(array_column($run,'number'));$run=[];}elseif($n!==$last+1)$run=[];$run[]=$same[0];$last=$n;}if(count($run)>=3){$ids=array_column($run,'id');$groups[]=$ids;$used+=array_fill_keys($ids,true);$value+=array_sum(array_column($run,'number'));}}
        foreach(range(1,13) as $n){$set=[];$colors=[];foreach($hand as $t)if(!isset($used[$t['id']])&&!$this->wild($t,$joker)&&$t['number']===$n&&!isset($colors[$t['color']])){$set[]=$t;$colors[$t['color']]=1;}if(count($set)>=3){$set=array_slice($set,0,4);$ids=array_column($set,'id');$groups[]=$ids;$used+=array_fill_keys($ids,true);$value+=count($set)*$n;}}
        return ['groups'=>$groups,'value'=>$value];
    }

    private function meldValue(array $tiles,array $joker): int
    {
        if(count($tiles)<3)return -1;$wild=array_filter($tiles,fn($t)=>$this->wild($t,$joker));$normal=array_values(array_filter($tiles,fn($t)=>!$this->wild($t,$joker)));
        $numbers=array_column($normal,'number');$colors=array_column($normal,'color');
        if(count(array_unique($numbers))===1&&count(array_unique($colors))===count($colors)&&count($tiles)<=4)return (int)$numbers[0]*count($tiles);
        if($normal&&count(array_unique($colors))===1){sort($numbers);if(count(array_unique($numbers))!==count($numbers))return -1;$missing=0;for($i=1;$i<count($numbers);$i++)$missing+=max(0,$numbers[$i]-$numbers[$i-1]-1);if($missing<=count($wild)){ $start=max(1,$numbers[0]-(count($wild)-$missing));$sum=array_sum($numbers);for($n=0;$n<count($wild);$n++)$sum+=min(13,$start+$n);return $sum;}}
        return -1;
    }

    private function areFivePairs(array $tiles,array $joker): bool { $counts=[];$wild=0;foreach($tiles as $t){if($this->wild($t,$joker)){$wild++;continue;}$k=$t['color'].':'.$t['number'];$counts[$k]=($counts[$k]??0)+1;} $pairs=0;$singles=0;foreach($counts as $c){$pairs+=intdiv($c,2);$singles+=$c%2;}return $pairs+min($wild,$singles)+intdiv(max(0,$wild-$singles),2)>=5; }
    private function finishRound(array &$s,?int $winner,string $type):void{$s['phase']='completed';$s['winnerSeat']=$winner;$s['finishType']=$type;foreach($s['hands'] as $seat=>$hand){if($winner===$seat)continue;$penalty=0;foreach($hand as $t)$penalty+=$this->wild($t,$s['joker'])?101:(int)($t['number']??0);if(!$s['opened'][$seat]&&!$s['pairOpened'][$seat])$penalty*=2;$s['scores'][$seat]+=$penalty;}if($winner!==null&&$type==='hand')foreach($s['scores'] as $seat=>&$score)if($seat!==$winner)$score*=2;}
    private function wild(array $t,array $j):bool{return !$t['fake']&&$t['color']===$j['color']&&(int)$t['number']===(int)$j['number'];}
    private function takePreview(array $hand,array $ids):array{$map=array_column($hand,null,'id');$out=[];foreach($ids as $id){if(!isset($map[$id]))throw new RuntimeException('Taş elinizde bulunamadı.');$out[]=$map[$id];}return $out;}
    private function removeIds(array &$hand,array $ids):void{foreach($ids as $id)$this->removeOne($hand,(string)$id);}
    private function removeOne(array &$hand,string $id):array{foreach($hand as $i=>$t)if($t['id']===$id){array_splice($hand,$i,1);return $t;}throw new RuntimeException('Taş elinizde bulunamadı.');}
    private function sort(array &$h,array $j):void{usort($h,fn($a,$b)=>[$a['color']??'z',$a['number']??99]<=>[$b['color']??'z',$b['number']??99]);}
    private function tileUsefulness(array $tile,array $hand,array $joker):int{if($this->wild($tile,$joker))return 999;$score=0;foreach($hand as $other){if($other['id']===$tile['id'])continue;if($other['number']===$tile['number'])$score+=3;if($other['color']===$tile['color']&&abs($other['number']-$tile['number'])<=2)$score+=2;}return $score;}
}

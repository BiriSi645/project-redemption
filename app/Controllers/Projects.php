<?php

namespace App\Controllers;

use App\Models\NotificationModel;
use App\Models\ProjectItemModel;
use App\Models\ProjectMemberModel;
use App\Models\ProjectModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use RuntimeException;
use Throwable;
use App\Libraries\ExperienceService;

class Projects extends BaseController
{
    public function index(): string
    {
        $userId=(int)session()->get('user_id'); $model=new ProjectModel();
        $projects=$model->select('projects.*, owner.username AS owner_username')
            ->select("(SELECT COUNT(*) FROM project_members pmc WHERE pmc.project_id=projects.id AND pmc.status='accepted') AS member_count",false)
            ->select('(SELECT COUNT(*) FROM project_items pic WHERE pic.project_id=projects.id) AS item_count',false)
            ->select("(SELECT COUNT(*) FROM project_items pid WHERE pid.project_id=projects.id AND pid.status='done') AS done_count",false)
            ->join('project_members pm','pm.project_id=projects.id')
            ->join('users owner','owner.id=projects.owner_id')->where('pm.user_id',$userId)->where('pm.status','accepted')
            ->orderBy('projects.updated_at','DESC')->paginate(12,'projects');
        $invitations=(new ProjectMemberModel())->select('project_members.*, projects.name, projects.color, users.username AS inviter_username')
            ->join('projects','projects.id=project_members.project_id')->join('users','users.id=project_members.invited_by','left')
            ->where('project_members.user_id',$userId)->where('project_members.status','pending')->orderBy('project_members.created_at','DESC')->findAll();
        return view('projects/index',['title'=>'Projeler','projects'=>$projects,'pager'=>$model->pager,'invitations'=>$invitations]);
    }

    public function store()
    {
        if(!$this->validate(['name'=>'required|min_length[3]|max_length[120]','description'=>'permit_empty|max_length[5000]','color'=>'required|regex_match[/^#[0-9a-fA-F]{6}$/]']))return redirect()->back()->withInput()->with('errors',$this->validator->getErrors());
        $db=db_connect();$db->transBegin();
        try{$userId=(int)session()->get('user_id');$id=(new ProjectModel())->insert(['owner_id'=>$userId,'name'=>trim((string)$this->request->getPost('name')),'description'=>trim((string)$this->request->getPost('description')),'color'=>(string)$this->request->getPost('color')],true);if(!$id)throw new RuntimeException('Proje oluşturulamadı.');(new ProjectMemberModel())->insert(['project_id'=>$id,'user_id'=>$userId,'invited_by'=>$userId,'role'=>'owner','status'=>'accepted','responded_at'=>date('Y-m-d H:i:s')]);if(!$db->transStatus())throw new RuntimeException('Proje üyeliği oluşturulamadı.');$db->transCommit();return redirect()->to(site_url('projects/'.$id))->with('success','Proje oluşturuldu.');}catch(Throwable $e){$db->transRollback();log_message('error','Proje oluşturulamadı: {message}',['message'=>$e->getMessage()]);return redirect()->back()->withInput()->with('error','Proje oluşturulamadı.');}
    }

    public function show(int $id): string
    {
        $membership=$this->acceptedMembership($id);$project=(new ProjectModel())->select('projects.*, users.username AS owner_username')->join('users','users.id=projects.owner_id')->find($id);if(!$project)throw PageNotFoundException::forPageNotFound('Proje bulunamadı.');
        $members=(new ProjectMemberModel())->select('project_members.*, users.username, users.bio')->join('users','users.id=project_members.user_id')->where('project_id',$id)->whereIn('status',['accepted','pending'])->orderBy('role','ASC')->orderBy('username','ASC')->findAll();
        $items=(new ProjectItemModel())->select('project_items.*, creator.username AS creator_username, assignee.username AS assignee_username')->join('users creator','creator.id=project_items.created_by','left')->join('users assignee','assignee.id=project_items.assigned_to','left')->where('project_id',$id)->orderBy("FIELD(project_items.status,'in_progress','todo','done')",'',false)->orderBy('due_date','ASC')->findAll();
        $accepted=array_values(array_filter($members,static fn(array $member): bool=>$member['status']==='accepted'));
        $pending=array_values(array_filter($members,static fn(array $member): bool=>$member['status']==='pending'));
        return view('projects/show',['title'=>$project['name'],'project'=>$project,'membership'=>$membership,'members'=>$members,'items'=>$items,'accepted'=>$accepted,'pending'=>$pending,'isOwner'=>$membership['role']==='owner']);
    }

    public function invite(int $id)
    {
        $project=$this->ownedProject($id);$username=trim((string)$this->request->getPost('username'));$target=(new UserModel())->where('username',$username)->where('is_active',1)->first();$current=(int)session()->get('user_id');
        if(!$target|| (int)$target['id']===$current)return redirect()->back()->with('error','Davet edilecek aktif kullanıcı bulunamadı.');
        $memberModel=new ProjectMemberModel();$member=$memberModel->where(['project_id'=>$id,'user_id'=>$target['id']])->first();if($member&&$member['status']==='accepted')return redirect()->back()->with('error','Bu kullanıcı zaten proje üyesi.');
        $data=['project_id'=>$id,'user_id'=>(int)$target['id'],'invited_by'=>$current,'role'=>'member','status'=>'pending','responded_at'=>null];
        if($member)$memberModel->update($member['id'],$data);else{$memberId=$memberModel->insert($data,true);$member=$memberModel->find($memberId);} $memberId=(int)($member['id']??$memberId??0);
        $key='project_invite:'.$id.':'.$target['id'];$notifications=new NotificationModel();$existing=$notifications->where('notification_key',$key)->first();if($existing)$notifications->delete($existing['id']);
        $notifications->insert(['user_id'=>(int)$target['id'],'actor_user_id'=>$current,'type'=>'project_invite','message'=>session()->get('username').' sizi “'.$project['name'].'” projesine davet etti.','target_path'=>'projects/invitations/'.$memberId,'notification_key'=>$key,'created_at'=>date('Y-m-d H:i:s')]);
        return redirect()->back()->with('success',$target['username'].' kullanıcısına proje daveti gönderildi.');
    }

    public function invitation(int $membershipId): string
    {
        $invitation=$this->invitationForUser($membershipId);return view('projects/invitation',['title'=>'Proje Daveti','invitation'=>$invitation]);
    }

    public function respond(int $membershipId)
    {
        $invitation=$this->invitationForUser($membershipId);if($invitation['status']!=='pending')return redirect()->to(site_url('projects'))->with('error','Bu davet daha önce yanıtlandı.');$decision=(string)$this->request->getPost('decision');if(!in_array($decision,['accept','reject'],true))return redirect()->back()->with('error','Geçersiz davet yanıtı.');
        $status=$decision==='accept'?'accepted':'rejected';(new ProjectMemberModel())->update($membershipId,['status'=>$status,'responded_at'=>date('Y-m-d H:i:s')]);
        (new NotificationModel())->where('notification_key','project_invite:'.$invitation['project_id'].':'.session()->get('user_id'))->set(['read_at'=>date('Y-m-d H:i:s')])->update();
        if(!empty($invitation['invited_by'])&&(int)$invitation['invited_by']!==(int)session()->get('user_id'))(new NotificationModel())->insert(['user_id'=>(int)$invitation['invited_by'],'actor_user_id'=>(int)session()->get('user_id'),'type'=>'project_response','message'=>session()->get('username').' “'.$invitation['name'].'” proje davetini '.($decision==='accept'?'kabul etti.':'reddetti.'),'target_path'=>$decision==='accept'?'projects/'.$invitation['project_id']:'projects','notification_key'=>'project_response:'.$membershipId.':'.$status,'created_at'=>date('Y-m-d H:i:s')]);
        return $decision==='accept'?redirect()->to(site_url('projects/'.$invitation['project_id']))->with('success','Proje davetini kabul ettiniz.'):redirect()->to(site_url('projects'))->with('success','Proje davetini reddettiniz.');
    }

    public function storeItem(int $id)
    {
        $membership=$this->acceptedMembership($id);if(!$this->validate(['title'=>'required|min_length[2]|max_length[160]','description'=>'permit_empty|max_length[3000]','start_date'=>'permit_empty|valid_date[Y-m-d]','due_date'=>'permit_empty|valid_date[Y-m-d]']))return redirect()->back()->withInput()->with('errors',$this->validator->getErrors());
        $startDate=$this->request->getPost('start_date')?:null;$dueDate=$this->request->getPost('due_date')?:null;if($startDate&&$dueDate&&$dueDate<$startDate)return redirect()->back()->withInput()->with('error','Bitiş tarihi başlangıç tarihinden önce olamaz.');
        $assigned=(int)$this->request->getPost('assigned_to');if($assigned>0&&$membership['role']!=='owner')return redirect()->back()->with('error','İş atamalarını yalnızca proje sahibi yapabilir.');if($assigned>0&&!$this->isAcceptedMember($id,$assigned))return redirect()->back()->with('error','Atanan kullanıcı proje üyesi değil.');
        (new ProjectItemModel())->insert(['project_id'=>$id,'created_by'=>(int)session()->get('user_id'),'assigned_to'=>$assigned?:null,'title'=>trim((string)$this->request->getPost('title')),'description'=>trim((string)$this->request->getPost('description')),'status'=>'todo','start_date'=>$startDate,'due_date'=>$dueDate]);
        db_connect()->table('projects')->where('id',$id)->update(['updated_at'=>date('Y-m-d H:i:s')]);return redirect()->back()->with('success','İş kalemi eklendi.');
    }

    public function itemStatus(int $projectId,int $itemId)
    {
        $membership=$this->acceptedMembership($projectId);$status=(string)$this->request->getPost('status');$item=(new ProjectItemModel())->where('project_id',$projectId)->find($itemId);if(!$item||!in_array($status,['todo','in_progress','done'],true))throw PageNotFoundException::forPageNotFound('İş kalemi bulunamadı.');$canInteract=$membership['role']==='owner'||(int)($item['assigned_to']??0)===(int)session()->get('user_id');if(!$canInteract)return redirect()->back()->with('error','Bu iş kaleminin durumunu yalnızca proje sahibi veya atanan kişi değiştirebilir.');(new ProjectItemModel())->update($itemId,['status'=>$status]);if($status==='done'&&$item['status']!=='done'&&!empty($item['assigned_to']))(new ExperienceService())->award((int)$item['assigned_to'],'project_item_completed','project_item:'.$itemId);db_connect()->table('projects')->where('id',$projectId)->update(['updated_at'=>date('Y-m-d H:i:s')]);return redirect()->back();
    }

    public function assignItem(int $projectId,int $itemId)
    {
        $this->ownedProject($projectId);$item=(new ProjectItemModel())->where('project_id',$projectId)->find($itemId);if(!$item)throw PageNotFoundException::forPageNotFound('İş kalemi bulunamadı.');$assigned=(int)$this->request->getPost('assigned_to');if($assigned>0&&!$this->isAcceptedMember($projectId,$assigned))return redirect()->back()->with('error','Atanan kullanıcı kabul edilmiş bir proje üyesi değil.');(new ProjectItemModel())->update($itemId,['assigned_to'=>$assigned?:null]);db_connect()->table('projects')->where('id',$projectId)->update(['updated_at'=>date('Y-m-d H:i:s')]);return redirect()->back()->with('success','İş ataması güncellendi.');
    }

    public function scheduleItem(int $projectId,int $itemId)
    {
        $this->ownedProject($projectId);$item=(new ProjectItemModel())->where('project_id',$projectId)->find($itemId);if(!$item)throw PageNotFoundException::forPageNotFound('İş kalemi bulunamadı.');if(!$this->validate(['start_date'=>'permit_empty|valid_date[Y-m-d]','due_date'=>'permit_empty|valid_date[Y-m-d]']))return redirect()->back()->with('errors',$this->validator->getErrors());$start=$this->request->getPost('start_date')?:null;$due=$this->request->getPost('due_date')?:null;if($start&&$due&&$due<$start)return redirect()->back()->with('error','Bitiş tarihi başlangıç tarihinden önce olamaz.');(new ProjectItemModel())->update($itemId,['start_date'=>$start,'due_date'=>$due]);db_connect()->table('projects')->where('id',$projectId)->update(['updated_at'=>date('Y-m-d H:i:s')]);return redirect()->back()->with('success','İş zaman planı güncellendi.');
    }

    private function acceptedMembership(int $projectId): array
    { $membership=(new ProjectMemberModel())->where(['project_id'=>$projectId,'user_id'=>(int)session()->get('user_id'),'status'=>'accepted'])->first();if(!$membership)throw PageNotFoundException::forPageNotFound('Projeye erişemezsiniz.');return $membership; }
    private function ownedProject(int $id): array
    { $project=(new ProjectModel())->where('owner_id',(int)session()->get('user_id'))->find($id);if(!$project)throw PageNotFoundException::forPageNotFound('Projeyi yönetemezsiniz.');return $project; }
    private function isAcceptedMember(int $projectId,int $userId): bool
    { return (new ProjectMemberModel())->where(['project_id'=>$projectId,'user_id'=>$userId,'status'=>'accepted'])->first()!==null; }
    private function invitationForUser(int $id): array
    { $row=(new ProjectMemberModel())->select('project_members.*, projects.name, projects.description, projects.color, users.username AS inviter_username')->join('projects','projects.id=project_members.project_id')->join('users','users.id=project_members.invited_by','left')->where('project_members.user_id',(int)session()->get('user_id'))->find($id);if(!$row)throw PageNotFoundException::forPageNotFound('Proje daveti bulunamadı.');return $row; }
}

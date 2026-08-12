<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<style>
    .habits-header{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:24px}.habits-header h1{margin:0 0 6px}.habits-header p{margin:0;color:#6b7280}.habit-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.habit-card{padding:20px;border:1px solid #e5e7eb;border-radius:13px;background:#fff}.habit-card.completed{border-color:#86efac;background:#f0fdf4}.habit-card.inactive{opacity:.68;background:#f9fafb}.habit-top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}.habit-title{margin:0;font-size:19px}.habit-frequency{padding:5px 9px;border-radius:999px;background:#e0e7ff;color:#3730a3;font-size:12px;font-weight:700;white-space:nowrap}.habit-description{color:#4b5563;white-space:pre-wrap}.habit-period{display:flex;justify-content:space-between;gap:12px;padding:12px;margin:15px 0 8px;border-radius:9px;background:#f3f4f6}.habit-status{font-weight:700}.completed .habit-status{color:#166534}.habit-progress-track{height:8px;margin-bottom:15px;border-radius:999px;background:#e5e7eb;overflow:hidden}.habit-progress-bar{height:100%;border-radius:inherit;background:#2563eb}.completed .habit-progress-bar{background:#16a34a}.habit-actions{display:flex;flex-wrap:wrap;gap:7px}.habit-actions form{margin:0}.habit-actions .button{padding:8px 11px}.habit-complete{width:100%;margin-bottom:10px;background:#16a34a}.completed .habit-complete{background:#64748b}.habit-goal-done{display:block;padding:10px;margin-bottom:10px;border-radius:8px;background:#dcfce7;color:#166534;text-align:center;font-weight:700}.empty-state{padding:44px 20px;border:1px dashed #d1d5db;border-radius:12px;text-align:center;color:#6b7280}@media(max-width:850px){.habit-list{grid-template-columns:1fr}}@media(max-width:620px){.habits-header{align-items:flex-start;flex-direction:column}}
</style>
<div class="habits-header"><div><h1>Alışkanlıklar</h1><p>Her alışkanlığı seçtiğiniz sıklığın mevcut dönemi içinde işaretleyin.</p></div><a class="button" href="<?= site_url('habits/create') ?>">Yeni Alışkanlık</a></div>
<?php if ($errors=session()->getFlashdata('errors')): ?><div class="alert error"><?php foreach($errors as $error): ?><div><?= esc($error) ?></div><?php endforeach; ?></div><?php endif; ?>
<?php if(empty($habits)): ?>
    <div class="empty-state"><p>Henüz takip edilen bir alışkanlık yok.</p><a class="button" href="<?= site_url('habits/create') ?>">İlk Alışkanlığı Ekle</a></div>
<?php else: ?>
    <div class="habit-list">
    <?php foreach($habits as $habit): ?>
        <article class="habit-card <?= $habit['completed']?'completed':'' ?> <?= (int)$habit['is_active']!==1?'inactive':'' ?>">
            <div class="habit-top"><h2 class="habit-title"><?= esc($habit['title']) ?></h2><span class="habit-frequency"><?= esc($habit['goal_label']) ?></span></div>
            <?php if($habit['description']): ?><p class="habit-description"><?= esc($habit['description']) ?></p><?php endif; ?>
            <div class="habit-period"><span><?= esc($habit['period_label']) ?></span><span class="habit-status"><?= (int)$habit['is_active']!==1?'Duraklatıldı':((int)$habit['completed_count'].' / '.(int)$habit['target_count'].' gün') ?></span></div>
            <div class="habit-progress-track"><div class="habit-progress-bar" style="width:<?= (int)$habit['progress_percent'] ?>%"></div></div>
            <?php if((int)$habit['is_active']===1): ?>
                <?php if($habit['completed'] && !$habit['completed_today']): ?><span class="habit-goal-done">✓ Bu dönemin hedefi tamamlandı</span>
                <?php else: ?><form method="post" action="<?= site_url('habits/'.$habit['id'].'/complete') ?>"><?= csrf_field() ?><button class="button habit-complete" type="submit"><?= $habit['completed_today']?'Bugünkü İşareti Geri Al':'Bugün Yaptım' ?></button></form><?php endif; ?>
            <?php endif; ?>
            <div class="habit-actions">
                <a class="button secondary" href="<?= site_url('habits/'.$habit['id'].'/edit') ?>">Düzenle</a>
                <form method="post" action="<?= site_url('habits/'.$habit['id'].'/toggle') ?>"><?= csrf_field() ?><button class="button secondary" type="submit"><?= (int)$habit['is_active']===1?'Duraklat':'Etkinleştir' ?></button></form>
                <form method="post" action="<?= site_url('habits/'.$habit['id'].'/delete') ?>" onsubmit="return confirm('Bu alışkanlık ve tüm işaretleme geçmişi silinsin mi?')"><?= csrf_field() ?><button class="button danger" type="submit">Sil</button></form>
                <span style="margin-left:auto;align-self:center;color:#6b7280;font-size:13px">Toplam <?= (int)$habit['total_completions'] ?> işaret</span>
            </div>
        </article>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
